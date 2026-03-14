<?php
require_once 'init.php';
requireAdminAuth();

$user_id = $_GET['id'] ?? 0;
if ($user_id) {
    $del_langs = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $del_langs->execute([$user_id]);
    
    $del_user = $db->prepare("DELETE FROM applications WHERE id = ?");
    $del_user->execute([$user_id]);
}

header('Location: admin.php?deleted=1');
exit();
?>