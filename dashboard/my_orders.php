<?php
// Файл: dashboard/my_orders.php
// Замовлення постачальника

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['supplier']);

// Ініціалізація моделей
$supplierOrder = new SupplierOrder($pdo);
$orderItems = new OrderItem($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $orderId = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $deliveryDate = $_POST['delivery_date'] ?? null;
        
        // Перевіряємо що це замовлення цього постачальника
        $order = $supplierOrder->findById($orderId);
        if ($order && $order['supplier_id'] == $user['id']) {
            if ($supplierOrder->updateStatus($orderId, $status, $deliveryDate)) {
                $message = 'Статус замовлення оновлено!';
            } else {
                $error = 'Помилка при оновленні статусу';
            }
        } else {
            $error = 'Недостатньо прав для цієї дії';
        }
    }
    
    elseif ($action === 'update_delivery') {
        $orderId = $_POST['order_id'] ?? 0;
        $itemUpdates = $_POST['items'] ?? [];
        
        // Перевіряємо що це замовлення цього постачальника
        $order = $supplierOrder->findById($orderId);
        if ($order && $order['supplier_id'] == $user['id']) {
            $success = true;
            foreach ($itemUpdates as $itemId => $deliveredQty) {
                if (!$orderItems->updateDeliveredQuantity($itemId, $deliveredQty)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $message = 'Інформацію про поставку оновлено!';
            } else {
                $error = 'Помилка при оновленні поставки';
            }
        } else {
            $error = 'Недостатньо прав для цієї дії';
        }
    }
}

// Фільтри
$statusFilter = $_GET['status'] ?? '';

// Отримання замовлень постачальника
$orders = $supplierOrder->getBySupplier($user['id']);

// Застосування фільтрів
if ($statusFilter) {
    $orders = array_filter($orders, function($order) use ($statusFilter) {
        return $order['status'] === $statusFilter;
    });
}

// Перегляд конкретного замовлення
$viewOrder = null;
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $viewOrder = $supplierOrder->getWithItems($orderId);
    
    // Перевіряємо що це замовлення цього постачальника
    if (!$viewOrder || $viewOrder['supplier_id'] != $user['id']) {
        $viewOrder = null;
        $error = 'Замовлення не знайдено';
    }
}

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($viewOrder): ?>
    <!-- Деталі замовлення -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                Замовлення <?= htmlspecialchars($viewOrder['order_number']) ?>
            </h5>
            <a href="<?= BASE_URL ?>/dashboard/my_orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад до списку
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Інформація про замовлення</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Номер:</th>
                            <td><?= htmlspecialchars($viewOrder['order_number']) ?></td>
                        </tr>
                        <tr>
                            <th>Дата замовлення:</th>
                            <td><?= formatDate($viewOrder['order_date']) ?></td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span style="color:black;" class="badge <?= getStatusBadge($viewOrder['status']) ?>">
                                    <?= getStatusText($viewOrder['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Очікувана поставка:</th>
                            <td>
                                <?= $viewOrder['expected_delivery'] ? formatDate($viewOrder['expected_delivery']) : 'Не вказано' ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Загальна сума:</th>
                            <td class="text-success fw-bold"><?= formatMoney($viewOrder['total_amount']) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Контактна інформація</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Створив замовлення:</th>
                            <td><?= htmlspecialchars($viewOrder['created_by_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Телефон:</th>
                            <td><?= htmlspecialchars($viewOrder['supplier_phone'] ?? 'Не вказано') ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?= htmlspecialchars($viewOrder['supplier_email'] ?? 'Не вказано') ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($viewOrder['notes']): ?>
                        <h6 class="mt-3">Примітки</h6>
                        <p class="bg-light p-3 rounded"><?= htmlspecialchars($viewOrder['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Позиції замовлення -->
            <h6>Позиції замовлення</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Матеріал</th>
                            <th>Кількість</th>
                            <th>Ціна за од.</th>
                            <th>Сума</th>
                            <th>Доставлено</th>
                            <?php if (in_array($viewOrder['status'], ['confirmed', 'in_progress'])): ?>
                                <th>Дії</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewOrder['items'] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['material_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($item['material_unit']) ?></small>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= formatMoney($item['unit_price']) ?></td>
                                <td class="text-success"><?= formatMoney($item['total_price']) ?></td>
                                <td>
                                    <span class="<?= $item['delivered_quantity'] >= $item['quantity'] ? 'text-success' : 'text-warning' ?>">
                                        <?= $item['delivered_quantity'] ?> / <?= $item['quantity'] ?>
                                    </span>
                                </td>
                                <?php if (in_array($viewOrder['status'], ['confirmed', 'in_progress'])): ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="updateDelivery(<?= $item['id'] ?>, <?= $item['delivered_quantity'] ?>, <?= $item['quantity'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Дії з замовленням -->
            <?php if ($viewOrder['status'] !== 'completed' && $viewOrder['status'] !== 'cancelled'): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Оновити статус замовлення</h6>
                        <div class="d-flex gap-2">
                            <?php if ($viewOrder['status'] === 'sent'): ?>
                                <button class="btn btn-success" onclick="updateStatus(<?= $viewOrder['id'] ?>, 'confirmed')">
                                    <i class="fas fa-check me-1"></i>Підтвердити замовлення
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($viewOrder['status'], ['confirmed', 'in_progress'])): ?>
                                <button class="btn btn-info" onclick="updateStatus(<?= $viewOrder['id'] ?>, 'in_progress')">
                                    <i class="fas fa-cog me-1"></i>В процесі виконання
                                </button>
                                <button class="btn btn-primary" onclick="updateStatus(<?= $viewOrder['id'] ?>, 'delivered')">
                                    <i class="fas fa-truck me-1"></i>Доставлено
                                </button>
                                <button class="btn btn-success" onclick="updateStatus(<?= $viewOrder['id'] ?>, 'completed')">
                                    <i class="fas fa-check-circle me-1"></i>Завершити замовлення
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-danger" onclick="updateStatus(<?= $viewOrder['id'] ?>, 'cancelled')">
                                <i class="fas fa-times me-1"></i>Скасувати
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Фільтри -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Фільтри
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-select">
                        <option value="">Всі статуси</option>
                        <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Відправлено</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Підтверджено</option>
                        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>В процесі</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Доставлено</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Завершено</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Скасовано</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Фільтр
                        </button>
                        <a href="<?= BASE_URL ?>/dashboard/my_orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Список замовлень -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list-alt me-2"></i>
                Мої замовлення (<?= count($orders) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Замовлень не знайдено</h5>
                    <p class="text-muted">Поки що немає замовлень від заводу</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Номер</th>
                                <th>Дата замовлення</th>
                                <th>Статус</th>
                                <th>Очікувана поставка</th>
                                <th>Сума</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                        <br>
                                        <small class="text-muted">від <?= htmlspecialchars($order['created_by_name']) ?></small>
                                    </td>
                                    <td><?= formatDate($order['order_date']) ?></td>
                                    <td>
                                        <span style="color:black;" class="badge <?= getStatusBadge($order['status']) ?>">
                                            <?= getStatusText($order['status']) ?>
                                        </span>
                                        
                                        <?php if ($order['status'] === 'sent'): ?>
                                            <br><small class="text-warning">Потребує підтвердження</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($order['expected_delivery']): ?>
                                            <?= formatDate($order['expected_delivery']) ?>
                                            <?php
                                            $daysLeft = floor((strtotime($order['expected_delivery']) - time()) / 86400);
                                            if ($daysLeft < 0 && $order['status'] !== 'completed'): ?>
                                                <br><small class="text-danger">Прострочено на <?= abs($daysLeft) ?> днів</small>
                                            <?php elseif ($daysLeft <= 3 && $daysLeft >= 0): ?>
                                                <br><small class="text-warning">Залишилось <?= $daysLeft ?> днів</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-success fw-bold">
                                        <?= formatMoney($order['total_amount']) ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>/dashboard/my_orders.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Деталі
                                            </a>
                                            
                                            <?php if ($order['status'] === 'sent'): ?>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="updateStatus(<?= $order['id'] ?>, 'confirmed')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Модальне вікно оновлення статусу -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Оновити статус
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    <input type="hidden" name="status" id="statusValue">
                    
                    <p id="statusMessage"></p>
                    
                    <div class="mb-3" id="deliveryDateField" style="display: none;">
                        <label class="form-label">Дата поставки</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary" id="statusSubmitBtn">
                        <i class="fas fa-save me-1"></i>Підтвердити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
function updateStatus(orderId, status) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusValue').value = status;
    
    const messages = {
        'confirmed': 'Підтвердити отримання замовлення?',
        'in_progress': 'Відмітити замовлення як \"В процесі виконання\"?',
        'delivered': 'Відмітити замовлення як доставлене?',
        'completed': 'Завершити замовлення?',
        'cancelled': 'Скасувати замовлення?'
    };
    
    document.getElementById('statusMessage').textContent = messages[status] || 'Оновити статус замовлення?';
    
    // Показуємо поле дати для статусів доставки
    const deliveryField = document.getElementById('deliveryDateField');
    if (status === 'delivered' || status === 'completed') {
        deliveryField.style.display = 'block';
    } else {
        deliveryField.style.display = 'none';
    }
    
    // Змінюємо кольір кнопки в залежності від статусу
    const submitBtn = document.getElementById('statusSubmitBtn');
    submitBtn.className = 'btn me-1';
    
    switch(status) {
        case 'confirmed':
            submitBtn.className += ' btn-success';
            break;
        case 'cancelled':
            submitBtn.className += ' btn-danger';
            break;
        default:
            submitBtn.className += ' btn-primary';
    }
    
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function updateDelivery(itemId, currentDelivered, totalQuantity) {
    const newQuantity = prompt('Введіть кількість доставленого товару (максимум ' + totalQuantity + '):', currentDelivered);
    
    if (newQuantity !== null && !isNaN(newQuantity) && newQuantity >= 0 && newQuantity <= totalQuantity) {
        // Створюємо форму для відправки
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'update_delivery';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.name = 'order_id';
        orderIdInput.value = " . ($viewOrder ? $viewOrder['id'] : 0) . ";
        
        const itemInput = document.createElement('input');
        itemInput.name = 'items[' + itemId + ']';
        itemInput.value = newQuantity;
        
        form.appendChild(actionInput);
        form.appendChild(orderIdInput);
        form.appendChild(itemInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
";

renderDashboardLayout('Мої замовлення', $user['role'], $content, '', $additionalJS);
?>