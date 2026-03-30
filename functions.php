<?php
function getDbConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_EMULATE_PREPARES => false // Защита от SQL инъекций
                ]
            );
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
        }
    }
    
    return $db;
}

function getAllLanguages($db = null) {
    if (!$db) $db = getDbConnection();
    
    static $languages = null;
    
    if ($languages === null) {
        try {
            $stmt = $db->query("SELECT id, name FROM programming_languages ORDER BY name");
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching languages: " . $e->getMessage());
            $languages = [];
        }
    }
    
    return $languages;
}

function getUserLanguages($db, $user_id) {
    try {
        $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching user languages: " . $e->getMessage());
        return [];
    }
}

function validateFullName($fullName) {
    $fullName = trim($fullName);
    
    if (empty($fullName)) {
        return 'ФИО обязательно для заполнения';
    }
    
    if (strlen($fullName) > 150) {
        return 'ФИО не должно превышать 150 символов';
    }
    
    if (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $fullName)) {
        return 'ФИО может содержать только буквы, пробелы и дефис';
    }
    
    return null;
}

function validateEmail($email) {
    $email = trim($email);
    
    if (empty($email)) {
        return 'Email обязателен для заполнения';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Введите корректный email';
    }
    
    return null;
}

function validatePhone($phone) {
    $phone = trim($phone);
    
    if (!empty($phone) && !preg_match('/^[\+\d\s\-\(\)]{10,20}$/', $phone)) {
        return 'Телефон может содержать только цифры, пробелы, дефисы, скобки и знак +';
    }
    
    return null;
}

function validateBirthDate($birth) {
    if (empty($birth)) {
        return null;
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
        return 'Неверный формат даты';
    }
    
    $date_parts = explode('-', $birth);
    if (!checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
        return 'Введите корректную дату рождения';
    }
    
    return null;
}

function validateGender($gender) {
    if (!in_array($gender, ['male', 'female'])) {
        return 'Выберите корректное значение пола';
    }
    
    return null;
}

function validateLanguages($selected_langs, $all_languages) {
    if (!is_array($selected_langs) || empty($selected_langs)) {
        return 'Выберите хотя бы один язык программирования';
    }
    
    $valid_ids = array_column($all_languages, 'id');
    foreach ($selected_langs as $lang_id) {
        if (!in_array((int)$lang_id, $valid_ids)) {
            return 'Выбран недопустимый язык';
        }
    }
    
    return null;
}

function requireAdminAuth() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || 
        !isset($_SERVER['PHP_AUTH_PW']) || 
        $_SERVER['PHP_AUTH_USER'] != ADMIN_USER || 
        !password_verify($_SERVER['PHP_AUTH_PW'], ADMIN_PASS_HASH)) {
        
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Требуется авторизация';
        exit();
    }
}

function generateCredentials() {
    $login = 'user_' . rand(1000, 9999);
    $password = bin2hex(random_bytes(4));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    return [
        'login' => $login,
        'password' => $password,
        'password_hash' => $password_hash
    ];
}

function saveUserToDB($db, $data, $credentials, $selected_langs) {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO applications 
            (full_name, email, phone, birth_date, gender, bio, contract_agreed, data_consent, login, password_hash)
            VALUES 
            (:full_name, :email, :phone, :birth_date, :gender, :bio, :contract, :consent, :login, :password_hash)
        ");
        
        $stmt->execute([
            ':full_name' => $data['fullName'],
            ':email' => $data['email'],
            ':phone' => $data['phone'] ?: null,
            ':birth_date' => $data['birth'] ?: null,
            ':gender' => $data['gender'],
            ':bio' => $data['bio'] ?: null,
            ':contract' => 1,
            ':consent' => 1,
            ':login' => $credentials['login'],
            ':password_hash' => $credentials['password_hash']
        ]);
        
        $application_id = $db->lastInsertId();
        
        $lang_stmt = $db->prepare("
            INSERT INTO application_languages (application_id, language_id)
            VALUES (:app_id, :lang_id)
        ");
        
        foreach ($selected_langs as $lang_id) {
            $lang_stmt->execute([
                ':app_id' => $application_id,
                ':lang_id' => $lang_id
            ]);
        }
        
        $db->commit();
        return $application_id;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error saving user: " . $e->getMessage());
        throw new Exception("Ошибка при сохранении данных");
    }
}

function updateUserInDB($db, $user_id, $data, $selected_langs) {
    try {
        $db->beginTransaction();
        
        $update = $db->prepare("
            UPDATE applications 
            SET full_name = ?, email = ?, phone = ?, birth_date = ?, 
                gender = ?, bio = ?
            WHERE id = ?
        ");
        $update->execute([
            $data['fullName'], 
            $data['email'], 
            $data['phone'] ?: null, 
            $data['birth'] ?: null,
            $data['gender'], 
            $data['bio'] ?: null, 
            $user_id
        ]);
        
        $del = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $del->execute([$user_id]);
        
        $insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($selected_langs as $lang_id) {
            $insert->execute([$user_id, $lang_id]);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating user: " . $e->getMessage());
        throw new Exception("Ошибка при обновлении данных");
    }
}

function getLanguageStats($db) {
    try {
        $stmt = $db->query("
            SELECT 
                pl.name,
                COUNT(al.application_id) as count
            FROM programming_languages pl
            LEFT JOIN application_languages al ON pl.id = al.language_id
            GROUP BY pl.id
            ORDER BY count DESC, pl.name
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting language stats: " . $e->getMessage());
        return [];
    }
}

function getAllUsersWithLanguages($db) {
    try {
        $stmt = $db->query("
            SELECT 
                a.*,
                GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
            FROM applications a
            LEFT JOIN application_languages al ON a.id = al.application_id
            LEFT JOIN programming_languages pl ON al.language_id = pl.id
            GROUP BY a.id
            ORDER BY a.id DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
        return [];
    }
}

function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
