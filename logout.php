<?php
// Файл: logout.php
// Вихід з системи

require_once 'config/config.php';

// Очищуємо всі дані сесії
session_unset();
session_destroy();

// Перенаправляємо на сторінку входу
redirect('/login.php');
?>