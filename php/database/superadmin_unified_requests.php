<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

// Get pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$endDate = trim($_GET['endDate'] ?? '');

$blockWhere = "1=1";
$appWhere = "a.target_type = 'user'";

$blockParams = [];
$blockTypes = '';
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
    $blockWhere .= " AND (u1.firstName LIKE ? OR u1.lastName LIKE ? OR u2.firstName LIKE ? OR u2.lastName LIKE ? OR u2.username LIKE ? OR r.reason LIKE ?)";
    array_push($blockParams, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
    $blockTypes .= 'ssssss';

    $appWhere .= " AND (u1.firstName LIKE ? OR u1.lastName LIKE ? OR u.firstName LIKE ? OR u.lastName LIKE ? OR u.username LIKE ? OR a.reason LIKE ?)";
    array_push($appParams, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
    $appTypes .= 'ssssss';
}

// Date Range Filtering
if ($startDate !== '') {
    $blockWhere .= " AND DATE(r.created_at) >= ?";
    $blockParams[] = $startDate;
    $blockTypes .= 's';

    $appWhere .= " AND DATE(a.created_at) >= ?";
    $appParams[] = $startDate;
    $appTypes .= 's';
}
if ($endDate !== '') {
    $blockWhere .= " AND DATE(r.created_at) <= ?";
    $blockParams[] = $endDate;
    $blockTypes .= 's';

    $appWhere .= " AND DATE(a.created_at) <= ?";
    $appParams[] = $endDate;
    $appTypes .= 's';
}

try {
    $sql = "
        SELECT
            r.id as request_id,
            r.request_type as type,
            r.reason,
            r.status,
            r.created_at,
            NULL as review_notes,
            u1.firstName as requester_first,
            u1.lastName as requester_last,
            u2.firstName as target_first,
            u2.lastName as target_last,
            u2.username as target_username,
            u2.id as target_id,
            'user_block_requests' as source_table
        FROM user_block_requests r
        JOIN users u1 ON r.requester_id = u1.id
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
            u1.firstName as requester_first,
            u1.lastName as requester_last,
            u.firstName as target_first,
            u.lastName as target_last,
            u.username as target_username,
            u.id as target_id,
            'approvals' as source_table
        FROM approvals a
        JOIN users u1 ON a.requested_by = u1.id
        JOIN users u ON a.target_id = u.id
        WHERE $appWhere

        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    $allParams = array_merge($blockParams, $appParams, [$limit, $offset]);
    $allTypes = $blockTypes . $appTypes . 'ii';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (strlen($allTypes) > 0) {
            $stmt->bind_param($allTypes, ...$allParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['type'] === 'register_consumer') {
                $row['request_type'] = 'registration';
            }
            elseif ($row['type'] === 'delete_user') {
                $row['request_type'] = 'deletion';
            }
            else {
                $row['request_type'] = $row['type'];
            }
            $requests[] = $row;
        }
        $stmt->close();

        $countSql = "
            SELECT SUM(cnt) as total FROM (
                SELECT COUNT(*) as cnt FROM user_block_requests r JOIN users u1 ON r.requester_id = u1.id JOIN users u2 ON r.target_id = u2.id WHERE $blockWhere
                UNION ALL
                SELECT COUNT(*) as cnt FROM approvals a JOIN users u1 ON a.requested_by = u1.id JOIN users u ON a.target_id = u.id AND a.target_type = 'user' WHERE $appWhere
            ) as totals
        ";
        $countStmt = $conn->prepare($countSql);
        if ($countStmt) {
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
