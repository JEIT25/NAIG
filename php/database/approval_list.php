<?php
/**
 * Approval list (AMORA-style).
 * Admin: only their own requests. Superadmin: all requests.
 */
header('Content-Type: application/json');
ob_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();
if (!isset($_SESSION['user'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role'] ?? '';

if (!in_array($role, ['admin', 'superadmin'])) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$status = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');

$where = "WHERE a.status = ?";
$params = [$status];
$types = 's';

// Context lets us distinguish between "my own requests" and "review queue"
$context = isset($_GET['context']) ? trim($_GET['context']) : 'review';

if ($role === 'admin') {
    if ($context === 'my') {
        // Admin viewing their own requests (admin_approvals.js)
        $where .= " AND a.requested_by = ?";
        $params[] = $currentUser['id'];
        $types .= 's';
    }
    // For the shared Requests page (context != 'my'), admins see all approvals;
    // approval_review.php still restricts what they are allowed to approve/reject.
}

if ($search !== '') {
    $searchParam = '%' . $search . '%';
    $where .= " AND (a.reason LIKE ? OR a.review_notes LIKE ? OR a.target_id LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

try {
    $countSql = "SELECT COUNT(*) AS total FROM approvals a $where";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $sql = "SELECT a.*,
                   u1.firstName AS requester_firstName, u1.lastName AS requester_lastName, u1.email AS requester_email,
                   u2.firstName AS reviewer_firstName, u2.lastName AS reviewer_lastName
            FROM approvals a
            LEFT JOIN users u1 ON a.requested_by = u1.id
            LEFT JOIN users u2 ON a.reviewed_by = u2.id
            $where
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $approvals = [];
    while ($row = $result->fetch_assoc()) {
        $approvals[] = $row;
    }
    $stmt->close();
    $conn->close();

    ob_clean();
    echo json_encode([
        'success' => true,
        'approvals' => $approvals,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ],
    ]);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
