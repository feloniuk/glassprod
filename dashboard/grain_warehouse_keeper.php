<?php
// Файл: dashboard/grain_warehouse_keeper.php
// Дашборд начальника складу зернової сировини

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['warehouse_keeper']);

// Ініціалізація моделей
$grainMaterial = new GrainMaterial($pdo);
$stockMovement = new StockMovement($pdo);
$grainPurchaseRequest = new GrainPurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);
$qualityAssessment = new QualityAssessment($pdo);

// Статистика
$totalMaterials = $grainMaterial->count();
$lowStockMaterials = $grainMaterial->getLowStockMaterials();
$lowStockCount = count($lowStockMaterials);
$recentMovements = $stockMovement->getAllWithDetails(10);
$pendingDeliveries = $supplierOrder->count(['status' => ['confirmed', 'in_progress']]);

// Останні заявки начальника складу
$myRequests = $grainPurchaseRequest->getByUser($user['id']);
$myRequestsCount = count($myRequests);

// Статистика по категоріях
$categoryStats = $pdo->query("
    SELECT 
        gc.name as category_name,
        COUNT(gm.id) as materials_count,
        SUM(gm.current_stock * gm.price) as total_value,
        SUM(CASE WHEN gm.current_stock <= gm.min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
        AVG(gm.alcohol_yield) as avg_alcohol_yield
    FROM grain_materials gm
    LEFT JOIN grain_categories gc ON gm.category_id = gc.id
    GROUP BY gc.id, gc.name
    ORDER BY total_value DESC
")->fetchAll();

// Сумарна вартість складу та потенціал виробництва спирту
$totalStockValue = array_sum(array_column($categoryStats, 'total_value'));
$alcoholProductionPotential = $grainMaterial->getAlcoholProductionPotential();

// Статистика якості
$qualityStats = $grainMaterial->getQualityStats();

ob_start();
?>

<div class="row mb-4">
    <!-- Статистика -->
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-seedling fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalMaterials ?></h3>
                <p class="mb-0">Видів зернової сировини</p>
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
                <i class="fas fa-wine-bottle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($alcoholProductionPotential['total_alcohol_potential'] ?? 0, 0) ?></h3>
                <p class="mb-0">Потенціал спирту (л)</p>
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
                Увага! Критично низькі запаси зернової сировини
            </h5>
            <p class="mb-3">На складі є <?= $lowStockCount ?> видів сировини з критично низькими запасами:</p>
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
                                    Мінімум: <?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?><br>
                                    Клас: <?= ucfirst($mat['quality_grade']) ?><br>
                                    Вихід спирту: <?= $mat['alcohol_yield'] ?> л/т
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
    <!-- Статистика по категоріях зерна -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Статистика по категоріях зерна
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Категорія</th>
                                <th>Видів</th>
                                <th>Вартість</th>
                                <th>Сер. вихід спирту</th>
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
                                        <span class="badge bg-info">
                                            <?= number_format($stat['avg_alcohol_yield'] ?? 0, 1) ?> л/т
                                        </span>
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
    
    <!-- Статистика якості зерна -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>
                    Статистика якості зерна
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($qualityStats)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає даних про якість</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Клас якості</th>
                                    <th>Кількість</th>
                                    <th>Запас</th>
                                    <th>Вихід спирту</th>
                                    <th>Вартість</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($qualityStats as $stat): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $gradeColors = [
                                                'premium' => 'bg-success',
                                                'first' => 'bg-primary',
                                                'second' => 'bg-warning',
                                                'third' => 'bg-secondary'
                                            ];
                                            $gradeTexts = [
                                                'premium' => 'Преміум',
                                                'first' => 'Перший',
                                                'second' => 'Другий',
                                                'third' => 'Третій'
                                            ];
                                            ?>
                                            <span class="badge <?= $gradeColors[$stat['quality_grade']] ?? 'bg-secondary' ?>">
                                                <?= $gradeTexts[$stat['quality_grade']] ?? ucfirst($stat['quality_grade']) ?>
                                            </span>
                                        </td>
                                        <td><?= $stat['count'] ?> видів</td>
                                        <td><?= number_format($stat['total_stock'], 0) ?> т</td>
                                        <td><?= number_format($stat['avg_yield'] ?? 0, 1) ?> л/т</td>
                                        <td class="text-success"><?= formatMoney($stat['total_value']) ?></td>
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
    <!-- Критичні запаси зернової сировини -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Критичні запаси зернової сировини (топ-10)
                </h5>
                <div>
                    <a href="<?= BASE_URL ?>/dashboard/grain_warehouse.php" class="btn btn-sm btn-outline-light me-2">
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
                        <p class="text-muted">Всі запаси зернової сировини в нормі! Відмінна робота!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Зернова сировина</th>
                                    <th>Категорія</th>
                                    <th>Поточний запас</th>
                                    <th>Мінімум</th>
                                    <th>Різниця</th>
                                    <th>Клас якості</th>
                                    <th>Вихід спирту</th>
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
                                        <td>
                                            <span class="badge bg-<?= $mat['quality_grade'] === 'premium' ? 'success' : ($mat['quality_grade'] === 'first' ? 'primary' : 'warning') ?>">
                                                <?= ucfirst($mat['quality_grade']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= $mat['alcohol_yield'] ?></strong> л/т
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
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="checkQuality(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>')">
                                                    <i class="fas fa-microscope"></i>
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

<!-- Потенціал виробництва спирту -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-wine-bottle me-2"></i>
                    Потенціал виробництва спирту з наявних запасів
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h3 class="text-primary"><?= number_format($alcoholProductionPotential['total_alcohol_potential'] ?? 0, 0) ?> л</h3>
                        <p class="text-muted">Загальний потенціал</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-success"><?= number_format($alcoholProductionPotential['premium_potential'] ?? 0, 0) ?> л</h3>
                        <p class="text-muted">З сировини преміум</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-info"><?= number_format($alcoholProductionPotential['first_potential'] ?? 0, 0) ?> л</h3>
                        <p class="text-muted">З сировини І класу</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-warning"><?= number_format($alcoholProductionPotential['second_potential'] ?? 0, 0) ?> л</h3>
                        <p class="text-muted">З сировини ІІ класу</p>
                    </div>
                </div>
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
                        <a href="<?= BASE_URL ?>/dashboard/grain_warehouse.php" class="btn btn-primary w-100">
                            <i class="fas fa-warehouse me-2"></i>
                            Склад зерна
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/stock_movements.php?action=add" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>
                            Рух зерна
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/grain_requests.php?action=create" class="btn btn-warning w-100">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Заявка на зерно
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/quality_assessment.php" class="btn btn-info w-100">
                            <i class="fas fa-microscope me-2"></i>
                            Якість зерна
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
// Функція створення заявки на зернову сировину
function createRequest(materialId, materialName) {
    const quantity = prompt('Введіть кількість для замовлення ' + materialName + ' (тонн):');
    if (quantity && !isNaN(quantity) && quantity > 0) {
        window.location.href = '" . BASE_URL . "/dashboard/grain_requests.php?action=create&material_id=' + materialId + '&quantity=' + quantity;
    }
}

// Функція коригування запасів зерна
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
                showAlert('Запас зернової сировини успішно скориговано', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('Помилка: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Помилка при коригуванні запасу зерна', 'danger');
        });
    }
}

// Функція перевірки якості зерна
function checkQuality(materialId, materialName) {
    if (confirm('Перейти до перевірки якості сировини \"' + materialName + '\"?')) {
        window.location.href = '" . BASE_URL . "/dashboard/quality_assessment.php?material=' + materialId;
    }
}

// Функція масового створення заявок
function showBulkRequestModal() {
    if (confirm('Створити заявки на всю зернову сировину з критично низькими запасами?')) {
        window.location.href = '" . BASE_URL . "/dashboard/grain_requests.php?action=bulk_create';
    }
}
";

renderDashboardLayout('Панель начальника складу зернової сировини', 'warehouse_keeper', $content, '', $additionalJS);
?>