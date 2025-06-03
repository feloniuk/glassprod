<?php
// Файл: dashboard/stock_movements.php
// Рух товарів на складі

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager', 'warehouse_keeper']);

// Ініціалізація моделей
$stockMovement = new StockMovement($pdo);
$material = new Material($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_movement' && in_array($user['role'], ['warehouse_keeper', 'director'])) {
        $materialId = $_POST['material_id'] ?? 0;
        $movementType = $_POST['movement_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $referenceType = $_POST['reference_type'] ?? 'manual';
        $notes = trim($_POST['notes'] ?? '');
        
        if ($materialId && in_array($movementType, ['in', 'out']) && $quantity > 0) {
            // Перевіряємо чи достатньо товару для витрати
            if ($movementType === 'out') {
                $materialData = $material->findById($materialId);
                if (!$materialData || $materialData['current_stock'] < $quantity) {
                    $error = 'Недостатньо товару на складі для витрати';
                }
            }
            
            if (!$error) {
                try {
                    // Записуємо рух
                    if ($movementType === 'in') {
                        $stockMovement->recordIncoming($materialId, $quantity, $referenceType, null, $user['id'], $notes);
                    } else {
                        $stockMovement->recordOutgoing($materialId, $quantity, $referenceType, null, $user['id'], $notes);
                    }
                    
                    // Оновлюємо запас
                    $materialData = $material->findById($materialId);
                    $newStock = $materialData['current_stock'] + ($movementType === 'in' ? $quantity : -$quantity);
                    $material->updateStock($materialId, $newStock);
                    
                    $message = 'Рух товару успішно записано!';
                    
                } catch (Exception $e) {
                    $error = 'Помилка при записі руху: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
}

// Фільтри
$materialFilter = $_GET['material'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFromFilter = $_GET['date_from'] ?? '';
$dateToFilter = $_GET['date_to'] ?? '';

// Отримання рухів товарів
$movements = $stockMovement->getAllWithDetails(100);

// Застосування фільтрів
if ($materialFilter) {
    $movements = array_filter($movements, function($movement) use ($materialFilter) {
        return $movement['material_id'] == $materialFilter;
    });
}

if ($typeFilter) {
    $movements = array_filter($movements, function($movement) use ($typeFilter) {
        return $movement['movement_type'] === $typeFilter;
    });
}

if ($dateFromFilter) {
    $movements = array_filter($movements, function($movement) use ($dateFromFilter) {
        return date('Y-m-d', strtotime($movement['movement_date'])) >= $dateFromFilter;
    });
}

if ($dateToFilter) {
    $movements = array_filter($movements, function($movement) use ($dateToFilter) {
        return date('Y-m-d', strtotime($movement['movement_date'])) <= $dateToFilter;
    });
}

// Отримання матеріалів для фільтра та форми
$materials = $material->getAllWithCategories();

// Статистика
$totalIncoming = 0;
$totalOutgoing = 0;
foreach ($movements as $movement) {
    if ($movement['movement_type'] === 'in') {
        $totalIncoming += $movement['quantity'];
    } else {
        $totalOutgoing += $movement['quantity'];
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

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-arrow-down fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalIncoming ?></h3>
                <p class="mb-0">Надходжень</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <i class="fas fa-arrow-up fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalOutgoing ?></h3>
                <p class="mb-0">Витрат</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count($movements) ?></h3>
                <p class="mb-0">Операцій</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-balance-scale fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalIncoming - $totalOutgoing ?></h3>
                <p class="mb-0">Різниця</p>
            </div>
        </div>
    </div>
</div>

<!-- Фільтри та дії -->
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
                    <div class="col-md-3">
                        <label class="form-label">Матеріал</label>
                        <select name="material" class="form-select">
                            <option value="">Всі матеріали</option>
                            <?php foreach ($materials as $mat): ?>
                                <option value="<?= $mat['id'] ?>" <?= $materialFilter == $mat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Тип</label>
                        <select name="type" class="form-select">
                            <option value="">Всі типи</option>
                            <option value="in" <?= $typeFilter === 'in' ? 'selected' : '' ?>>Надходження</option>
                            <option value="out" <?= $typeFilter === 'out' ? 'selected' : '' ?>>Витрата</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Дата з</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFromFilter) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Дата по</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateToFilter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Фільтр
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/stock_movements.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (in_array($user['role'], ['warehouse_keeper', 'director'])): ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Дії
                </h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addMovementModal">
                    <i class="fas fa-plus me-2"></i>Додати рух товару
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Список рухів товарів -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-exchange-alt me-2"></i>
            Рух товарів на складі (<?= count($movements) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movements)): ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Рухів товарів не знайдено</h5>
                <p class="text-muted">Спробуйте змінити фільтри або додайте новий рух товару</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Матеріал</th>
                            <th>Тип руху</th>
                            <th>Кількість</th>
                            <th>Тип операції</th>
                            <th>Виконав</th>
                            <th>Примітки</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td>
                                    <?= formatDateTime($movement['movement_date']) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($movement['material_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($movement['material_unit']) ?></small>
                                </td>
                                <td>
                                    <?php if ($movement['movement_type'] === 'in'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-arrow-down me-1"></i>Надходження
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-arrow-up me-1"></i>Витрата
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= $movement['quantity'] ?></strong>
                                    <?= htmlspecialchars($movement['material_unit']) ?>
                                </td>
                                <td>
                                    <?php
                                    $referenceTypes = [
                                        'order' => 'Замовлення',
                                        'production' => 'Виробництво',
                                        'adjustment' => 'Коригування',
                                        'manual' => 'Ручне введення'
                                    ];
                                    ?>
                                    <span class="badge bg-secondary">
                                        <?= $referenceTypes[$movement['reference_type']] ?? $movement['reference_type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($movement['performed_by_name']) ?>
                                </td>
                                <td>
                                    <?php if ($movement['notes']): ?>
                                        <small><?= htmlspecialchars($movement['notes']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($user['role'], ['warehouse_keeper', 'director'])): ?>
<!-- Модальне вікно додавання руху товару -->
<div class="modal fade" id="addMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Додати рух товару
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_movement">
                    
                    <div class="mb-3">
                        <label class="form-label">Матеріал *</label>
                        <select name="material_id" class="form-select" required>
                            <option value="">Оберіть матеріал</option>
                            <?php foreach ($materials as $mat): ?>
                                <option value="<?= $mat['id'] ?>" data-stock="<?= $mat['current_stock'] ?>" data-unit="<?= htmlspecialchars($mat['unit']) ?>">
                                    <?= htmlspecialchars($mat['name']) ?> (<?= htmlspecialchars($mat['category_name']) ?>) - <?= $mat['current_stock'] ?> <?= htmlspecialchars($mat['unit']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Тип руху *</label>
                        <select name="movement_type" class="form-select" required onchange="updateMovementHelp()">
                            <option value="">Оберіть тип</option>
                            <option value="in">Надходження (+)</option>
                            <option value="out">Витрата (-)</option>
                        </select>
                        <small id="movementHelp" class="text-muted"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Кількість *</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" min="1" step="1" required>
                            <span class="input-group-text" id="quantityUnit">од.</span>
                        </div>
                        <div id="stockWarning" class="text-warning mt-1" style="display: none;">
                            <small><i class="fas fa-exclamation-triangle me-1"></i>Недостатньо товару на складі</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Тип операції</label>
                        <select name="reference_type" class="form-select">
                            <option value="manual">Ручне введення</option>
                            <option value="order">Замовлення</option>
                            <option value="production">Виробництво</option>
                            <option value="adjustment">Коригування</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Примітки</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Додаткова інформація про рух товару..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Додати рух
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

$additionalJS = "
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.querySelector('select[name=\"material_id\"]');
    const movementTypeSelect = document.querySelector('select[name=\"movement_type\"]');
    const quantityInput = document.querySelector('input[name=\"quantity\"]');
    const quantityUnit = document.getElementById('quantityUnit');
    const stockWarning = document.getElementById('stockWarning');
    
    // Оновлення одиниці виміру при виборі матеріалу
    if (materialSelect) {
        materialSelect.addEventListener('change', function() {
            const selectedOption = this.selectedOptions[0];
            if (selectedOption && selectedOption.dataset.unit) {
                quantityUnit.textContent = selectedOption.dataset.unit;
            } else {
                quantityUnit.textContent = 'од.';
            }
            checkStock();
        });
    }
    
    // Перевірка кількості при введенні
    if (quantityInput) {
        quantityInput.addEventListener('input', checkStock);
    }
    
    if (movementTypeSelect) {
        movementTypeSelect.addEventListener('change', checkStock);
    }
    
    function checkStock() {
        const materialOption = materialSelect.selectedOptions[0];
        const movementType = movementTypeSelect.value;
        const quantity = parseInt(quantityInput.value) || 0;
        
        if (materialOption && movementType === 'out' && quantity > 0) {
            const currentStock = parseInt(materialOption.dataset.stock) || 0;
            
            if (quantity > currentStock) {
                stockWarning.style.display = 'block';
                stockWarning.innerHTML = '<small><i class=\"fas fa-exclamation-triangle me-1\"></i>Недостатньо товару на складі (доступно: ' + currentStock + ')</small>';
            } else {
                stockWarning.style.display = 'none';
            }
        } else {
            stockWarning.style.display = 'none';
        }
    }
});

function updateMovementHelp() {
    const movementType = document.querySelector('select[name=\"movement_type\"]').value;
    const helpElement = document.getElementById('movementHelp');
    
    if (movementType === 'in') {
        helpElement.textContent = 'Збільшить кількість товару на складі';
        helpElement.className = 'text-success';
    } else if (movementType === 'out') {
        helpElement.textContent = 'Зменшить кількість товару на складі';
        helpElement.className = 'text-danger';
    } else {
        helpElement.textContent = '';
        helpElement.className = 'text-muted';
    }
}
";

renderDashboardLayout('Рух товарів', $user['role'], $content, '', $additionalJS);
?>