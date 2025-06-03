<?php
// Файл: index.php
// Головна сторінка проекту

require_once 'config/config.php';

// Якщо користувач авторизований - перенаправляємо на відповідний дашборд
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        redirect('/dashboard/' . $user['role'] . '.php');
    }
}

// Інакше перенаправляємо на сторінку входу
redirect('/login.php');
?>