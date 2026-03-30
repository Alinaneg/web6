<?php
require_once 'init.php';

if (isset($_SESSION['user_id'])) {
    header('Location: edit.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ошибка CSRF: недействительный токен');
    }
    
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM applications WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_name'] = $user['full_name'];
                
                header('Location: edit.php');
                exit();
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Ошибка при входе. Пожалуйста, попробуйте позже.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в личный кабинет</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container { max-width: 400px; margin: 0 auto; }
        .register-link { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #e2e8f0; }
        .register-link a { color: #38a169; font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="form-card login-container">
        <h1 class="form-title">🔐 Вход в личный кабинет</h1>
        
        <?php if ($error): ?>
            <div class="message error" style="display: block; margin-bottom: 20px;">
                ❌ <?= h($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required 
                       value="<?= h($_POST['login'] ?? '') ?>"
                       placeholder="Введите логин">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Введите пароль">
            </div>
            
            <button type="submit" class="btn-submit">Войти</button>
        </form>
        
        <div class="register-link">
            <p>Ещё нет логина и пароля? <a href="index.php">Заполните анкету</a></p>
        </div>
    </div>
</body>
</html>
