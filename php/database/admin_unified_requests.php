<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

$admin_id = $_SESSION['user']['id'];

// Get pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// Base conditions for Admin
// Admins can see block/unblock requests they created AND registration approvals (since they manage consumers)
$blockWhere = "r.requester_id = ?";
$appWhere = "a.action_type = 'register_consumer'";

$blockParams = [$admin_id];
$blockTypes = 's';
$appParams = [];
$appTypes = '';

if ($status !== '') {
    $blockWhere .= " AND r.status = ?";
    $blockParams[] = $status;
    $blockTypes .= 's';

    $appWhere .= " AND a.status = ?";
    $appParams[] = $status;
    $appTypes .= 's';
}

if ($search !== '') {
    $searchToken = "%$search%";
    // Block search
    $blockWhere .= " AND (u2.firstName LIKE ? OR u2.lastName LIKE ? OR u2.username LIKE ? OR u2.email LIKE ? OR r.reason LIKE ?)";
    array_push($blockParams, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
    $blockTypes .= 'sssss';

    // Approval search
    $appWhere .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR a.reason LIKE ?)";
    array_push($appParams, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
    $appTypes .= 'sssss';
}

try {
    // We will fetch ALL matching records from BOTH tables, merge them in PHP, sort, and paginate.
    // Alternatively, we could UNION them in SQL, but the union schema is tricky.
    // Given typical admin loads, UNION in SQL is better for pagination.

    $sql = "
        SELECT
            r.id as request_id,
            r.request_type as type,
            r.reason,
            r.status,
            r.created_at,
            NULL as review_notes,
            u2.firstName as target_first,
            u2.lastName as target_last,
            u2.username as target_username,
            u2.id as target_id,
            'user_block_requests' as source_table
        FROM user_block_requests r
        JOIN users u2 ON r.target_id = u2.id
        WHERE $blockWhere

        UNION ALL

        SELECT
            a.id as request_id,
            a.action_type as type,
            a.reason,
            a.status,
            a.created_at,
            a.review_notes,
            u.firstName as target_first,
            u.lastName as target_last,
            u.username as target_username,
            u.id as target_id,
            'approvals' as source_table
        FROM approvals a
        JOIN users u ON a.target_id = u.id AND a.target_type = 'user'
        WHERE $appWhere

        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    // Combine parameters carefully for the UNION query
    $allParams = array_merge($blockParams, $appParams, [$limit, $offset]);
    $allTypes = $blockTypes . $appTypes . 'ii';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($allTypes, ...$allParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            // Normalize types
            if ($row['type'] === 'register_consumer') {
                $row['request_type'] = 'registration';
            }
            else {
                $row['request_type'] = $row['type'];
            }
            $requests[] = $row;
        }
        $stmt->close();

        // Get total count
        $countSql = "
            SELECT SUM(cnt) as total FROM (
                SELECT COUNT(*) as cnt FROM user_block_requests r JOIN users u2 ON r.target_id = u2.id WHERE $blockWhere
                UNION ALL
                SELECT COUNT(*) as cnt FROM approvals a JOIN users u ON a.target_id = u.id AND a.target_type = 'user' WHERE $appWhere
            ) as totals
        ";
        $countStmt = $conn->prepare($countSql);
        if ($countStmt) {
            // Total count needs the same params minus the limit/offset
            $countParams = array_merge($blockParams, $appParams);
            $countTypes = $blockTypes . $appTypes;
            if (strlen($countTypes) > 0) {
                $countStmt->bind_param($countTypes, ...$countParams);
            }
            $countStmt->execute();
            $totalResult = $countStmt->get_result()->fetch_assoc();
            $total = (int)$totalResult['total'];
            $countStmt->close();
        }
        else {
            $total = 0;
        }

        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_requests' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    else {
        throw new Exception($conn->error);
    }
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
