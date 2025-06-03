<?php
// Файл: dashboard/orders.php
// Управління замовленнями постачальникам

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager']);

// Ініціалізація моделей
$supplierOrder = new SupplierOrder($pdo);
$orderItems = new OrderItem($pdo);
$userModel = new User($pdo);
$material = new Material($pdo);
$purchaseRequest = new PurchaseRequest($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_order') {
        $supplierId = $_POST['supplier_id'] ?? 0;
        $expectedDelivery = $_POST['expected_delivery'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $materials = $_POST['materials'] ?? [];
        
        if ($supplierId && !empty($materials)) {
            try {
                $orderData = [
                    'supplier_id' => $supplierId,
                    'expected_delivery' => $expectedDelivery,
                    'notes' => $notes,
                    'created_by' => $user['id']
                ];
                
                $items = [];
                foreach ($materials as $matId => $data) {
                    if ($data['quantity'] > 0) {
                        $items[] = [
                            'material_id' => $matId,
                            'quantity' => $data['quantity'],
                            'unit_price' => $data['price']
                        ];
                    }
                }
                
                $orderId = $supplierOrder->createOrder($orderData, $items);
                $message = 'Замовлення успішно створено!';
                
                // Оновлюємо статус заявок на "замовлено"
                if (!empty($_POST['request_ids'])) {
                    $requestIds = explode(',', $_POST['request_ids']);
                    foreach ($requestIds as $requestId) {
                        $purchaseRequest->markAsOrdered($requestId);
                    }
                }
                
            } catch (Exception $e) {
                $error = 'Помилка при створенні замовлення: ' . $e->getMessage();
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'update_status') {
        $orderId = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $deliveryDate = $_POST['delivery_date'] ?? null;
        
        if ($orderId && $status) {
            if ($supplierOrder->updateStatus($orderId, $status, $deliveryDate)) {
                $message = 'Статус замовлення оновлено!';
            } else {
                $error = 'Помилка при оновленні статусу';
            }
        }
    }
}

// Фільтри
$statusFilter = $_GET['status'] ?? '';
$supplierFilter = $_GET['supplier'] ?? '';

// Отримання замовлень
$orders = $supplierOrder->getAllWithDetails();

// Застосування фільтрів
if ($statusFilter) {
    $orders = array_filter($orders, function($order) use ($statusFilter) {
        return $order['status'] === $statusFilter;
    });
}

if ($supplierFilter) {
    $orders = array_filter($orders, function($order) use ($supplierFilter) {
        return $order['supplier_id'] == $supplierFilter;
    });
}

// Отримання постачальників
$suppliers = $userModel->getSuppliers();

// Отримання затверджених заявок для створення замовлення
$approvedRequests = $purchaseRequest->getByStatus('approved');

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

<!-- Фільтри -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
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
                            <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Чернетка</option>
                            <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Відправлено</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Підтверджено</option>
                            <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>В процесі</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Доставлено</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Завершено</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Постачальник</label>
                        <select name="supplier" class="form-select">
                            <option value="">Всі постачальники</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>" <?= $supplierFilter == $supplier['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Фільтр
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Дії
                </h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                    <i class="fas fa-plus me-2"></i>Нове замовлення
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Список замовлень -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Замовлення постачальникам (<?= count($orders) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Замовлень не знайдено</h5>
                <p class="text-muted">Створіть нове замовлення або змініть фільтри</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Постачальник</th>
                            <th>Статус</th>
                            <th>Дата замовлення</th>
                            <th>Очікувана поставка</th>
                            <th>Сума</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($order['supplier_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($order['supplier_contact']) ?></small>
                                </td>
                                <td>
                                    <span style="color:black;" class="badge <?= getStatusBadge($order['status']) ?>">
                                        <?= getStatusText($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($order['order_date']) ?></td>
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
                                        <button class="btn btn-sm btn-info" 
                                                onclick="showOrderDetails(<?= $order['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="showStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                                <i class="fas fa-edit"></i>
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

<!-- Модальне вікно створення замовлення -->
<div class="modal fade" id="createOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Нове замовлення
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_order">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Постачальник *</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Оберіть постачальника</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>">
                                        <?= htmlspecialchars($supplier['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Очікувана дата поставки</label>
                            <input type="date" name="expected_delivery" class="form-control" 
                                   value="<?= date('Y-m-d', strtotime('+2 weeks')) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Примітки</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Додаткові примітки до замовлення..."></textarea>
                    </div>
                    
                    <?php if (!empty($approvedRequests)): ?>
                        <div class="mb-3">
                            <label class="form-label">Затверджені заявки</label>
                            <div class="alert alert-info">
                                <small>Ви можете створити замовлення на основі затверджених заявок</small>
                            </div>
                            
                            <div id="materialsFromRequests">
                                <?php 
                                $groupedRequests = [];
                                foreach ($approvedRequests as $request) {
                                    $groupedRequests[$request['material_id']][] = $request;
                                }
                                ?>
                                
                                <?php foreach ($groupedRequests as $materialId => $requests): ?>
                                    <?php $firstRequest = $requests[0]; ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               id="material_<?= $materialId ?>" name="request_materials[<?= $materialId ?>]" 
                                               value="1" onchange="toggleMaterialRow(<?= $materialId ?>)">
                                        <label class="form-check-label" for="material_<?= $materialId ?>">
                                            <strong><?= htmlspecialchars($firstRequest['material_name']) ?></strong>
                                            (<?= array_sum(array_column($requests, 'quantity')) ?> <?= htmlspecialchars($firstRequest['material_unit']) ?>)
                                            <input type="hidden" id="requests_<?= $materialId ?>" 
                                                   value="<?= implode(',', array_column($requests, 'id')) ?>">
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Матеріали для замовлення</label>
                        <div id="materialsList">
                            <!-- Динамічно додаються матеріали -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMaterialRow()">
                            <i class="fas fa-plus me-1"></i>Додати матеріал
                        </button>
                    </div>
                    
                    <div class="alert alert-success" id="totalAmount" style="display: none;">
                        <strong>Загальна сума:</strong> <span id="totalAmountValue">0 грн</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Створити замовлення
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                    
                    <div class="mb-3">
                        <label class="form-label">Новий статус *</label>
                        <select name="status" id="statusSelect" class="form-select" required>
                            <option value="sent">Відправлено</option>
                            <option value="confirmed">Підтверджено</option>
                            <option value="in_progress">В процесі</option>
                            <option value="delivered">Доставлено</option>
                            <option value="completed">Завершено</option>
                            <option value="cancelled">Скасовано</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="deliveryDateField" style="display: none;">
                        <label class="form-label">Дата поставки</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Оновити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$materials_json = json_encode($material->getAllWithCategories());

$additionalJS = "
const materials = $materials_json;
let materialRowCounter = 0;

function showStatusModal(orderId, currentStatus) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusSelect').value = currentStatus;
    
    // Показуємо поле дати для статусів доставки
    const deliveryField = document.getElementById('deliveryDateField');
    const statusSelect = document.getElementById('statusSelect');
    
    function toggleDeliveryField() {
        if (statusSelect.value === 'delivered' || statusSelect.value === 'completed') {
            deliveryField.style.display = 'block';
        } else {
            deliveryField.style.display = 'none';
        }
    }
    
    statusSelect.addEventListener('change', toggleDeliveryField);
    toggleDeliveryField();
    
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function addMaterialRow(materialId = '', quantity = '', price = '') {
    materialRowCounter++;
    const materialsList = document.getElementById('materialsList');
    
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.id = 'materialRow_' + materialRowCounter;
    
    let materialOptions = '<option value=\"\">Оберіть матеріал</option>';
    materials.forEach(material => {
        materialOptions += '<option value=\"' + material.id + '\" data-price=\"' + material.price + '\" data-unit=\"' + material.unit + '\">' + 
                          material.name + ' (' + material.category_name + ')</option>';
    });
    
    row.innerHTML = `
        <div class=\"col-md-5\">
            <select name=\"materials[\${materialId || 'new_' + materialRowCounter}][material_id]\" class=\"form-select material-select\" required>
                \${materialOptions}
            </select>
        </div>
        <div class=\"col-md-3\">
            <input type=\"number\" name=\"materials[\${materialId || 'new_' + materialRowCounter}][quantity]\" 
                   class=\"form-control quantity-input\" placeholder=\"Кількість\" min=\"1\" value=\"\${quantity}\" required>
        </div>
        <div class=\"col-md-3\">
            <input type=\"number\" name=\"materials[\${materialId || 'new_' + materialRowCounter}][price]\" 
                   class=\"form-control price-input\" placeholder=\"Ціна\" step=\"0.01\" min=\"0\" value=\"\${price}\" required>
        </div>
        <div class=\"col-md-1\">
            <button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"removeMaterialRow(\${materialRowCounter})\">
                <i class=\"fas fa-times\"></i>
            </button>
        </div>
    `;
    
    materialsList.appendChild(row);
    
    // Додаємо обробники подій
    const materialSelect = row.querySelector('.material-select');
    const priceInput = row.querySelector('.price-input');
    
    materialSelect.addEventListener('change', function() {
        const selectedOption = this.selectedOptions[0];
        if (selectedOption && selectedOption.dataset.price) {
            priceInput.value = selectedOption.dataset.price;
        }
        calculateTotal();
    });
    
    row.querySelectorAll('.quantity-input, .price-input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    if (materialId) {
        materialSelect.value = materialId;
    }
    
    calculateTotal();
}

function removeMaterialRow(counter) {
    const row = document.getElementById('materialRow_' + counter);
    if (row) {
        row.remove();
        calculateTotal();
    }
}

function toggleMaterialRow(materialId) {
    const checkbox = document.getElementById('material_' + materialId);
    const requestIds = document.getElementById('requests_' + materialId).value;
    
    if (checkbox.checked) {
        // Знаходимо матеріал в списку
        const material = materials.find(m => m.id == materialId);
        if (material) {
            addMaterialRow(materialId, '', material.price);
            
            // Додаємо приховане поле з ID заявок
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'request_ids';
            hiddenField.value = requestIds;
            hiddenField.id = 'hidden_requests_' + materialId;
            document.getElementById('materialsList').appendChild(hiddenField);
        }
    } else {
        // Видаляємо рядок матеріалу
        const row = document.getElementById('materialRow_' + materialId);
        if (row) {
            row.remove();
        }
        
        // Видаляємо приховане поле
        const hiddenField = document.getElementById('hidden_requests_' + materialId);
        if (hiddenField) {
            hiddenField.remove();
        }
        
        calculateTotal();
    }
}

function calculateTotal() {
    let total = 0;
    const materialRows = document.querySelectorAll('#materialsList .row');
    
    materialRows.forEach(row => {
        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        
        if (quantityInput && priceInput) {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            total += quantity * price;
        }
    });
    
    const totalAmountDiv = document.getElementById('totalAmount');
    const totalAmountValue = document.getElementById('totalAmountValue');
    
    if (total > 0) {
        totalAmountValue.textContent = total.toLocaleString('uk-UA') + ' грн';
        totalAmountDiv.style.display = 'block';
    } else {
        totalAmountDiv.style.display = 'none';
    }
}

function showOrderDetails(orderId) {
    // Тут можна реалізувати показ деталей замовлення
    window.location.href = '" . BASE_URL . "/dashboard/orders.php?view=' + orderId;
}

// Ініціалізація
document.addEventListener('DOMContentLoaded', function() {
    // Додаємо один початковий рядок матеріалу
    // addMaterialRow();
});
";

renderDashboardLayout('Замовлення постачальникам', $user['role'], $content, '', $additionalJS);
?>