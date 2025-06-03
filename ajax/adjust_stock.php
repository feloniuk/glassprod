<?php
// Файл: ajax/adjust_stock.php
// AJAX коригування запасів складу

require_once '../config/config.php';

// Перевірка авторизації
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизований']);
    exit;
}

$user = getCurrentUser();
if (!$user || !in_array($user['role'], ['warehouse_keeper', 'director'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав']);
    exit;
}

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не дозволений']);
    exit;
}

// Отримання даних JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Невірні дані']);
    exit;
}

$materialId = $input['material_id'] ?? 0;
$adjustment = $input['adjustment'] ?? 0;
$notes = $input['notes'] ?? '';

// Валідація
if (!$materialId || $adjustment == 0) {
    echo json_encode(['success' => false, 'message' => 'Невірні параметри']);
    exit;
}

try {
    // Коригування запасу
    $material = new Material($pdo);
    $result = $material->adjustStock($materialId, $adjustment, $user['id'], $notes);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Запас успішно скориговано'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Не вдалося скоригувати запас'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Помилка: ' . $e->getMessage()
    ]);
}
?>