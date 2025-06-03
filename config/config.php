<?php
// Файл: config/config.php
// Конфігураційний файл GlassProd

// Конфігурація бази даних
define('DB_HOST', 'localhost');
define('DB_NAME', 'glassprod');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Основні налаштування додатка
define('APP_NAME', 'GlassProd');
define('BASE_URL', 'http://glassprod.loc');
define('ROOT_PATH', dirname(__DIR__));

// Налаштування сесії
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Змініть на 1 для HTTPS
session_start();

// Часовий пояс
date_default_timezone_set('Europe/Kiev');

// Налаштування відображення помилок (для розробки)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Автозавантаження класів
spl_autoload_register(function ($className) {
    $classFile = ROOT_PATH . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Підключення до бази даних
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Помилка підключення до бази даних: " . $e->getMessage());
}

// Функції помічники
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function checkRole($allowedRoles) {
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], $allowedRoles)) {
        redirect('/login.php?error=access_denied');
    }
    return $user;
}

function formatMoney($amount) {
    return number_format($amount, 2, '.', ' ') . ' грн';
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime)); 
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success', 
        'rejected' => 'badge-danger',
        'ordered' => 'badge-info',
        'delivered' => 'badge-primary',
        'draft' => 'badge-secondary',
        'sent' => 'badge-info',
        'confirmed' => 'badge-success',
        'in_progress' => 'badge-warning',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : 'badge-secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Очікує',
        'approved' => 'Затверджено',
        'rejected' => 'Відхилено', 
        'ordered' => 'Замовлено',
        'delivered' => 'Доставлено',
        'draft' => 'Чернетка',
        'sent' => 'Відправлено',
        'confirmed' => 'Підтверджено',
        'in_progress' => 'В процесі',
        'completed' => 'Завершено',
        'cancelled' => 'Скасовано'
    ];
    
    return isset($texts[$status]) ? $texts[$status] : $status;
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => 'badge-light',
        'medium' => 'badge-primary',
        'high' => 'badge-warning',
        'urgent' => 'badge-danger'
    ];
    
    return isset($badges[$priority]) ? $badges[$priority] : 'badge-secondary';
}

function getPriorityText($priority) {
    $texts = [
        'low' => 'Низький',
        'medium' => 'Середній',
        'high' => 'Високий',
        'urgent' => 'Терміновий'
    ];
    
    return isset($texts[$priority]) ? $texts[$priority] : $priority;
}

function getRoleText($role) {
    $texts = [
        'director' => 'Директор',
        'procurement_manager' => 'Менеджер з закупівель',
        'warehouse_keeper' => 'Начальник складу',
        'supplier' => 'Постачальник'
    ];
    
    return isset($texts[$role]) ? $texts[$role] : $role;
}
?>