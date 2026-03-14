<?php
require_once 'init.php';
requireAdminAuth();

$users = getAllUsersWithLanguages($db);
$stats = getLanguageStats($db);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin: 30px 0; }
        .stat-card { background: linear-gradient(135deg, #ad9bb1 0%, #ad9bb1 100%); color: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 2rem; margin: 0; }
        .stat-card p { margin: 10px 0 0; opacity: 0.9; }
        .users-table { width: 100%; border-collapse: collapse; margin-top: 30px; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .users-table th { background: linear-gradient(135deg, #ad9bb1 0%, #ad9bb1 100%); color: white; padding: 15px; text-align: left; }
        .users-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
        .users-table tr:hover { background: #f7fafc; }
        .action-btn { padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; margin: 0 3px; display: inline-block; }
        .edit-btn { background: #4299e1; color: white; }
        .delete-btn { background: #f56565; color: white; }
        .logout-btn { background: #f56565; color: white; padding: 10px 20px; border-radius: 50px; text-decoration: none; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h1 class="form-title" style="margin-bottom: 0;">👑 Админ-панель</h1>
            <a href="#" onclick="window.location.reload()" class="logout-btn">Обновить</a>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="message success" style="display: block;">✅ Пользователь удалён</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="message success" style="display: block;">✅ Данные обновлены</div>
        <?php endif; ?>
        
        <h2>📊 Статистика по языкам</h2>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?= $stat['count'] ?></h3>
                    <p><?= htmlspecialchars($stat['name']) ?></p>
                </div>
            <?php endforeach; ?>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);">
                <h3><?= count($users) ?></h3>
                <p>Всего пользователей</p>
            </div>
        </div>
        
        <h2>👥 Пользователи</h2>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Логин</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['birth_date'] ?? '-') ?></td>
                    <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                    <td><?= htmlspecialchars($user['languages'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['login'] ?? '-') ?></td>
                    <td>
                        <a href="admin-edit.php?id=<?= $user['id'] ?>" class="action-btn edit-btn">✏️ Ред.</a>
                        <a href="admin-delete.php?id=<?= $user['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Удалить пользователя?')">🗑️ Уд.</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>