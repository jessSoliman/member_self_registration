<?php
use SLiMS\DB;

defined('INDEX_AUTH') or die('Direct access is not allowed');

$db = DB::getInstance();

$schema_id = (int) $_POST['schema_id'];
$deactivate = isset($_POST['deactivate']) ? (int) $_POST['deactivate'] : 0;

if ($schema_id === 0) {
    exit;
}

if ($deactivate === 1) {
    // Just deactivate
    $stmt = $db->prepare('UPDATE self_registration_schemas SET status = 0 WHERE id = ?');
    $stmt->execute([$schema_id]);
} else {
    // Toggle current status
    $current = $db->prepare('SELECT status FROM self_registration_schemas WHERE id = ?');
    $current->execute([$schema_id]);
    $currentStatus = $current->fetchColumn();

    $newStatus = ($currentStatus == 1) ? 0 : 1;

    $update = $db->prepare('UPDATE self_registration_schemas SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $schema_id]);
}

exit;
