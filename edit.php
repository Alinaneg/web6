<?php
require_once 'init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ошибка CSRF: недействительный токен');
    }
}

$user_id = (int)$_SESSION['user_id'];
$errors = [];
$success_message = '';

try {
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!$user_data) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$user_langs = getUserLanguages($db, $user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Валидация входных данных
    $fullNameError = validateFullName($_POST['fullName'] ?? '');
    if ($fullNameError) $errors['fullName'] = $fullNameError;
    
    $emailError = validateEmail($_POST['email'] ?? '');
    if ($emailError) $errors['email'] = $emailError;
    
    $phoneError = validatePhone($_POST['phone'] ?? '');
    if ($phoneError) $errors['phone'] = $phoneError;
    
    $birthError = validateBirthDate($_POST['birth'] ?? '');
    if ($birthError) $errors['birth'] = $birthError;
    
    $genderError = validateGender($_POST['gender'] ?? '');
    if ($genderError) $errors['gender'] = $genderError;
    
    $langsError = validateLanguages($_POST['langs'] ?? [], $all_languages);
    if ($langsError) $errors['langs'] = $langsError;
    
    $form_data = [
        'fullName' => trim($_POST['fullName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'birth' => $_POST['birth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    if (empty($errors)) {
        try {
            updateUserInDB($db, $user_id, $form_data, $_POST['langs'] ?? []);
            $success_message = '✅ Данные успешно обновлены!';
            
            $user_data['full_name'] = $form_data['fullName'];
            $user_data['email'] = $form_data['email'];
            $user_data['phone'] = $form_data['phone'];
            $user_data['birth_date'] = $form_data['birth'];
            $user_data['gender'] = $form_data['gender'];
            $user_data['bio'] = $form_data['bio'];
            $user_langs = $_POST['langs'] ?? [];
            
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            $errors['database'] = 'Ошибка при обновлении данных. Пожалуйста, попробуйте позже.';
        }
    }
}

$edit_mode = true;
include('form.php');
?>
