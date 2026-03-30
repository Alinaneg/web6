<?php
require_once 'init.php';

$errors = [];
$form_data = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ошибка CSRF: недействительный токен');
    }
}

if (isset($_COOKIE['saved_data']) && empty($_POST)) {
    $form_data = json_decode($_COOKIE['saved_data'], true);
    if (!is_array($form_data)) {
        $form_data = [];
    }
}

if (isset($_COOKIE['form_errors'])) {
    $errors = json_decode($_COOKIE['form_errors'], true);
    setcookie('form_errors', '', time() - 3600, '/', '', false, true);
}

if (isset($_COOKIE['form_data'])) {
    $form_data = json_decode($_COOKIE['form_data'], true);
    setcookie('form_data', '', time() - 3600, '/', '', false, true);
}

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
    
    if (!isset($_POST['contract'])) {
        $errors['contract'] = 'Необходимо подтвердить ознакомление с контрактом';
    }
    
    if (!isset($_POST['consent'])) {
        $errors['consent'] = 'Необходимо согласие на обработку персональных данных';
    }

    $form_data = [
        'fullName' => trim($_POST['fullName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'birth' => $_POST['birth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    if (!empty($errors)) {
        setcookie('form_errors', json_encode($errors), 0, '/', '', false, true);
        setcookie('form_data', json_encode($form_data), 0, '/', '', false, true);
        header('Location: index.php');
        exit();
    }
    
    try {
        $credentials = generateCredentials();
        saveUserToDB($db, $form_data, $credentials, $_POST['langs'] ?? []);
        
        setcookie('saved_data', json_encode($form_data), time() + COOKIE_EXPIRE, '/', '', false, true);
        
        header('Location: index.php?save=1&login=' . urlencode($credentials['login']) . 
               '&password=' . urlencode($credentials['password']));
        exit();
        
    } catch (Exception $e) {
        error_log("Form submission error: " . $e->getMessage());
        $errors['database'] = 'Ошибка при сохранении данных. Пожалуйста, попробуйте позже.';
        setcookie('form_errors', json_encode(['database' => 'Ошибка базы данных']), 0, '/', '', false, true);
        header('Location: index.php');
        exit();
    }
}

if (isset($_GET['save']) && $_GET['save'] == 1) {
    if (isset($_GET['login']) && isset($_GET['password'])) {
        $login = h($_GET['login']);
        $password = h($_GET['password']);
        $success_message = " Данные успешно сохранены!
                           <div class='login-info'>
                               <strong> Логин:</strong> " . h($login) . "<br>
                               <strong> Пароль:</strong> " . h($password) . "<br>
                               <small>Сохраните эти данные для входа в личный кабинет</small>
                           </div>";
    } else {
        $success_message = 'Данные успешно сохранены!';
    }
}

$edit_mode = false;
include('form.php');
?>
