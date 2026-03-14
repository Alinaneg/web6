<?php
require_once 'init.php';
requireAdminAuth();

$user_id = $_GET['id'] ?? 0;
if (!$user_id) {
    header('Location: admin.php');
    exit();
}

$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $errors['database'] = 'Ошибка при обновлении';
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
        <h1 class="form-title">✏️ Редактирование пользователя #<?= $user_id ?></h1>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="fullName">ФИО *</label>
                    <input type="text" id="fullName" name="fullName" required
                           value="<?= htmlspecialchars($_POST['fullName'] ?? $user['full_name']) ?>"
                           class="<?= isset($errors['fullName']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['fullName'])): ?>
                        <small class="error-hint">❌ <?= $errors['fullName'] ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                           class="<?= isset($errors['email']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <small class="error-hint">❌ <?= $errors['email'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="birth">Дата рождения</label>
                    <input type="date" id="birth" name="birth"
                           value="<?= htmlspecialchars($_POST['birth'] ?? $user['birth_date'] ?? '') ?>">
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
                        <option value="<?= $lang['id'] ?>" 
                            <?= in_array($lang['id'], $selected) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lang['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint">Зажмите Ctrl/Cmd для выбора нескольких</small>
            </div>
            
            <div class="form-group">
                <label for="bio">Биография</label>
                <textarea id="bio" name="bio" rows="3"><?= htmlspecialchars($_POST['bio'] ?? $user['bio'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>