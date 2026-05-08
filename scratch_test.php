<?php
require_once 'c:\xampp\htdocs\NAIG\php\database\db_connect.php';

$admin_id = '0001-0004'; 
$status = '';
$search = '';

$blockWhere = "r.requester_id = ?";
$appWhere = "a.action_type = 'register_consumer'";
$blockParams = [$admin_id];
$blockTypes = 's';
$appParams = [];
$appTypes = '';

$total = 0;

echo "Checking Block Requests Count...\n";
$countBlockSql = "SELECT COUNT(*) as cnt FROM user_block_requests r JOIN users u2 ON r.target_id = u2.id WHERE $blockWhere";
$cStmt1 = $conn->prepare($countBlockSql);
if ($cStmt1) {
    if (strlen($blockTypes) > 0) $cStmt1->bind_param($blockTypes, ...$blockParams);
    $cStmt1->execute();
    $res1 = $cStmt1->get_result()->fetch_assoc();
    echo "Block Count: " . ($res1['cnt'] ?? '0') . "\n";
    $total += (int)($res1['cnt'] ?? 0);
    $cStmt1->close();
}

echo "Checking Approval Requests Count...\n";
$countAppSql = "SELECT COUNT(*) as cnt FROM approvals a JOIN users u ON a.target_id = u.id AND a.target_type = 'user' WHERE $appWhere";
$cStmt2 = $conn->prepare($countAppSql);
if ($cStmt2) {
    if (strlen($appTypes) > 0) $cStmt2->bind_param($appTypes, ...$appParams);
    $cStmt2->execute();
    $res2 = $cStmt2->get_result()->fetch_assoc();
    echo "App Count: " . ($res2['cnt'] ?? '0') . "\n";
    $total += (int)($res2['cnt'] ?? 0);
    $cStmt2->close();
}

echo "Total: $total\n";
