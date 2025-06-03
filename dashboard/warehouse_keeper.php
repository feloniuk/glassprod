<?php
// Файл: dashboard/warehouse_keeper.php
// Дашборд Начальник складу

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['warehouse_keeper']);

// Ініціалізація моделей
$material = new Material($pdo);
$stockMovement = new StockMovement($pdo);
$purchaseRequest = new PurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);

// Статистика
$totalMaterials = $material->count();
$lowStockMaterials = $material->getLowStockMaterials();
$lowStockCount = count($lowStockMaterials);
$recentMovements = $stockMovement->getAllWithDetails(10);
$pendingDeliveries = $supplierOrder->count(['status' => ['confirmed', 'in_progress']]);

// Останні заявки Начальник складу
$myRequests = $purchaseRequest->getByUser($user['id']);
$myRequestsCount = count($myRequests);

// Статистика по категоріях
$categoryStats = $pdo->query("
    SELECT 
        mc.name as category_name,
        COUNT(m.id) as materials_count,
        SUM(m.current_stock * m.price) as total_value,
        SUM(CASE WHEN m.current_stock <= m.min_stock_level THEN 1 ELSE 0 END) as low_stock_count
    FROM materials m
    LEFT JOIN material_categories mc ON m.category_id = mc.id
    GROUP BY mc.id, mc.name
    ORDER BY total_value DESC
")->fetchAll();

// Сумарна вартість складу
$totalStockValue = array_sum(array_column($categoryStats, 'total_value'));

// Останні руху товарів
$recentIncoming = $stockMovement->findWhere(['movement_type' => 'in'], 'movement_date DESC', 5);
$recentOutgoing = $stockMovement->findWhere(['movement_type' => 'out'], 'movement_date DESC', 5);

ob_start();
?>

<div class="row mb-4">
    <!-- Статистика -->
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalMaterials ?></h3>
                <p class="mb-0">Позицій матеріалів</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $lowStockCount ?></h3>
                <p class="mb-0">Критичних запасів</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-truck fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $pendingDeliveries ?></h3>
                <p class="mb-0">Очікується поставок</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-hryvnia fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($totalStockValue/1000, 0) ?>к</h3>
                <p class="mb-0">Вартість запасів</p>
            </div>
        </div>
    </div>
</div>

<?php if ($lowStockCount > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Увага! Критично низькі запаси
            </h5>
            <p class="mb-3">На складі є <?= $lowStockCount ?> позицій з критично низькими запасами:</p>
            <div class="row">
                <?php foreach (array_slice($lowStockMaterials, 0, 3) as $mat): ?>
                    <div class="col-md-4 mb-2">
                        <div class="card border-warning">
                            <div class="card-body p-3">
                                <h6 class="card-title text-warning mb-1">
                                    <?= htmlspecialchars($mat['name']) ?>
                                </h6>
                                <p class="card-text mb-2">
                                    Залишок: <?= $mat['current_stock'] ?> <?= htmlspecialchars($mat['unit']) ?><br>
                                    Мінімум: <?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?>
                                </p>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="createRequest(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>')">
                                    Створити заявку
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Статистика по категоріях -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Статистика по категоріях
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Категорія</th>
                                <th>Позицій</th>
                                <th>Вартість</th>
                                <th>Критичних</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryStats as $stat): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($stat['category_name'] ?? 'Без категорії') ?></strong>
                                    </td>
                                    <td><?= $stat['materials_count'] ?></td>
                                    <td class="text-success fw-bold">
                                        <?= formatMoney($stat['total_value']) ?>
                                    </td>
                                    <td>
                                        <?php if ($stat['low_stock_count'] > 0): ?>
                                            <span class="badge bg-danger"><?= $stat['low_stock_count'] ?></span>
                                        <?php else: ?>
                                            <span class="text-success">✓</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Останні рухи товарів -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Останні рухи товарів
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/stock_movements.php" class="btn btn-sm btn-outline-light">
                    Всі рухи
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentMovements)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає рухів товарів</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Тип</th>
                                    <th>Кількість</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentMovements, 0, 5) as $movement): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($movement['material_name']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($movement['movement_type'] === 'in'): ?>
                                                <span class="badge bg-success">Надходження</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Витрата</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $movement['quantity'] ?> <?= htmlspecialchars($movement['material_unit']) ?>
                                        </td>
                                        <td>
                                            <small><?= formatDateTime($movement['movement_date']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Критичні запаси -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Критичні запаси (топ-10)
                </h5>
                <div>
                    <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-sm btn-outline-light me-2">
                        Весь склад
                    </a>
                    <button class="btn btn-sm btn-success" onclick="showBulkRequestModal()">
                        <i class="fas fa-plus me-1"></i>Заявка на всі
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockMaterials)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Всі запаси в нормі! Відмінна робота!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Категорія</th>
                                    <th>Поточний запас</th>
                                    <th>Мінімум</th>
                                    <th>Різниця</th>
                                    <th>Ціна</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($lowStockMaterials, 0, 10) as $mat): ?>
                                    <tr class="<?= $mat['current_stock'] <= $mat['min_stock_level'] * 0.5 ? 'table-danger' : 'table-warning' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($mat['category_name'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= $mat['current_stock'] ?></span>
                                            <?= htmlspecialchars($mat['unit']) ?>
                                        </td>
                                        <td><?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                        <td class="text-danger fw-bold">
                                            <?= $mat['current_stock'] - $mat['min_stock_level'] ?>
                                        </td>
                                        <td class="text-success">
                                            <?= formatMoney($mat['price']) ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="createRequest(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" 
                                                        onclick="adjustStock(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
    </div>
</div>

<!-- Швидкі дії -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Швидкі дії
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-primary w-100">
                            <i class="fas fa-warehouse me-2"></i>
                            Склад
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/stock_movements.php?action=add" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>
                            Додати рух
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/requests.php?action=create" class="btn btn-warning w-100">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Нова заявка
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/materials.php" class="btn btn-info w-100">
                            <i class="fas fa-boxes me-2"></i>
                            Матеріали
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
// Функція створення заявки
function createRequest(materialId, materialName) {
    const quantity = prompt('Введіть кількість для замовлення ' + materialName + ':');
    if (quantity && !isNaN(quantity) && quantity > 0) {
        window.location.href = '" . BASE_URL . "/dashboard/requests.php?action=create&material_id=' + materialId + '&quantity=' + quantity;
    }
}

// Функція коригування запасів
function adjustStock(materialId, materialName) {
    const adjustment = prompt('Введіть коригування запасу для ' + materialName + ' (+ для збільшення, - для зменшення):');
    if (adjustment && !isNaN(adjustment) && adjustment != 0) {
        const notes = prompt('Причина коригування:') || '';
        
        // Відправка AJAX запиту
        fetch('" . BASE_URL . "/ajax/adjust_stock.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                material_id: materialId,
                adjustment: adjustment,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Запас успішно скориговано', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('Помилка: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Помилка при коригуванні запасу', 'danger');
        });
    }
}

// Функція масового створення заявок
function showBulkRequestModal() {
    if (confirm('Створити заявки на всі матеріали з критично низькими запасами?')) {
        window.location.href = '" . BASE_URL . "/dashboard/requests.php?action=bulk_create';
    }
}
";

renderDashboardLayout('Панель Начальник складу', 'warehouse_keeper', $content, '', $additionalJS);
?>