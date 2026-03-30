<?php
require_once 'init.php';
requireAdminAuth();

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header('Location: admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ошибка CSRF: недействительный токен');
    }
}

try {
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header('Location: admin.php');
    exit();
}

if (!$user) {
    header('Location: admin.php');
    exit();
}

$user_langs = getUserLanguages($db, $user_id);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            header('Location: admin.php?updated=1');
            exit();
        } catch (Exception $e) {
            error_log("Admin edit error: " . $e->getMessage());
            $errors['database'] = 'Ошибка при обновлении данных';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-link { display: inline-block; margin-bottom: 20px; color: #38a169; text-decoration: none; }
        .admin-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="form-card">
        <a href="admin.php" class="admin-link">← Вернуться в админ-панель</a>
        <h1 class="form-title"> Редактирование пользователя #<?= h($user_id) ?></h1>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fullName">ФИО *</label>
                    <input type="text" id="fullName" name="fullName" required
                           value="<?= h($_POST['fullName'] ?? $user['full_name']) ?>"
                           class="<?= isset($errors['fullName']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['fullName'])): ?>
                        <small class="error-hint">❌ <?= h($errors['fullName']) ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                           value="<?= h($_POST['email'] ?? $user['email']) ?>"
                           class="<?= isset($errors['email']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <small class="error-hint">❌ <?= h($errors['email']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= h($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="birth">Дата рождения</label>
                    <input type="date" id="birth" name="birth"
                           value="<?= h($_POST['birth'] ?? $user['birth_date'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Пол</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="gender" value="male" 
                               <?= (($_POST['gender'] ?? $user['gender'] ?? 'male') == 'male') ? 'checked' : '' ?>>
                        Мужской
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="female"
                               <?= (($_POST['gender'] ?? $user['gender'] ?? '') == 'female') ? 'checked' : '' ?>>
                        Женский
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="langs">Любимые языки программирования *</label>
                <select id="langs" name="langs[]" multiple size="4"
                        class="<?= isset($errors['langs']) ? 'error-field' : '' ?>">
                    <?php 
                    $selected = $_POST['langs'] ?? $user_langs;
                    foreach ($all_languages as $lang): 
                    ?>
                        <option value="<?= h($lang['id']) ?>" 
                            <?= in_array($lang['id'], $selected) ? 'selected' : '' ?>>
                            <?= h($lang['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint">Зажмите Ctrl/Cmd для выбора нескольких</small>
                <?php if (isset($errors['langs'])): ?>
                    <small class="error-hint">❌ <?= h($errors['langs']) ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="bio">Биография</label>
                <textarea id="bio" name="bio" rows="3"><?= h($_POST['bio'] ?? $user['bio'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>
