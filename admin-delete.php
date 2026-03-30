<?php
require_once 'init.php';
requireAdminAuth();

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header('Location: admin.php');
    exit();
}

try {
    $del_langs = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $del_langs->execute([$user_id]);

    $del_user = $db->prepare("DELETE FROM applications WHERE id = ?");
    $del_user->execute([$user_id]);
    
    header('Location: admin.php?deleted=1');
    exit();
} catch (PDOException $e) {
    error_log("Error deleting user: " . $e->getMessage());
    header('Location: admin.php?error=1');
    exit();
}
?>
