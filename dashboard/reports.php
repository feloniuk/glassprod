<?php
// Файл: dashboard/reports.php
// Звіти та аналітика

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager']);

// Ініціалізація моделей
$material = new Material($pdo);
$purchaseRequest = new PurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);
$stockMovement = new StockMovement($pdo);

// Отримання звітних даних
$selectedReport = $_GET['report'] ?? 'overview';

// Загальний огляд
$overviewData = [
    'total_materials' => $material->count(),
    'low_stock_materials' => count($material->getLowStockMaterials()),
    'pending_requests' => $purchaseRequest->count(['status' => 'pending']),
    'active_orders' => count($supplierOrder->findWhere(['status' => ['sent', 'confirmed', 'in_progress']])),
    'total_stock_value' => 0
];

// Розрахунок вартості запасів
$allMaterials = $material->getAllWithCategories();
foreach ($allMaterials as $mat) {
    $overviewData['total_stock_value'] += $mat['current_stock'] * $mat['price'];
}

// Звіт по матеріалах
$materialsReport = $material->getStockReport();

// Статистика заявок
$requestsStats = $purchaseRequest->getStatistics();
$priorityStats = $purchaseRequest->getPriorityStatistics();

// Статистика замовлень
$ordersStats = $supplierOrder->getStatistics();
$monthlyOrdersStats = $supplierOrder->getMonthlyStats();

// Останні рухи товарів
$recentMovements = $stockMovement->getAllWithDetails(20);

// Підготовка даних для графіків
$monthlyData = array_fill(1, 12, 0);
$monthlyAmount = array_fill(1, 12, 0);
foreach ($monthlyOrdersStats as $stat) {
    $monthlyData[$stat['month']] = $stat['orders_count'];
    $monthlyAmount[$stat['month']] = $stat['total_amount'];
}

ob_start();
?>

<!-- Навігація по звітах -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i>
            Звіти та аналітика
        </h5>
    </div>
    <div class="card-body">
        <div class="btn-group w-100" role="group">
            <a href="?report=overview" class="btn <?= $selectedReport === 'overview' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-tachometer-alt me-1"></i>Огляд
            </a>
            <a href="?report=materials" class="btn <?= $selectedReport === 'materials' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-boxes me-1"></i>Матеріали
            </a>
            <a href="?report=requests" class="btn <?= $selectedReport === 'requests' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-clipboard-list me-1"></i>Заявки
            </a>
            <a href="?report=orders" class="btn <?= $selectedReport === 'orders' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-shopping-cart me-1"></i>Замовлення
            </a>
            <a href="?report=movements" class="btn <?= $selectedReport === 'movements' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-exchange-alt me-1"></i>Рух товарів
            </a>
        </div>
    </div>
</div>

<?php if ($selectedReport === 'overview'): ?>
    <!-- Загальний огляд -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-3x mb-3"></i>
                    <h3 class="mb-1"><?= $overviewData['total_materials'] ?></h3>
                    <p class="mb-0">Матеріалів</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card <?= $overviewData['low_stock_materials'] > 0 ? 'danger' : 'success' ?>">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h3 class="mb-1"><?= $overviewData['low_stock_materials'] ?></h3>
                    <p class="mb-0">Критичних запасів</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h3 class="mb-1"><?= $overviewData['pending_requests'] ?></h3>
                    <p class="mb-0">Заявок очікує</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="fas fa-hryvnia fa-3x mb-3"></i>
                    <h3 class="mb-1"><?= number_format($overviewData['total_stock_value']/1000, 0) ?>к</h3>
                    <p class="mb-0">Вартість запасів</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Динаміка замовлень -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Динаміка замовлень по місяцях (<?= date('Y') ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Топ критичних матеріалів -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Критичні запаси
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $lowStockMaterials = $material->getLowStockMaterials(); ?>
                    <?php if (empty($lowStockMaterials)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Всі запаси в нормі</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($lowStockMaterials, 0, 5) as $mat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($mat['category_name']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-danger fw-bold">
                                            <?= $mat['current_stock'] - $mat['min_stock_level'] ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($mat['unit']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($selectedReport === 'materials'): ?>
    <!-- Звіт по матеріалах -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-boxes me-2"></i>
                Детальний звіт по матеріалах
            </h5>
            <button class="btn btn-success" onclick="exportReport('materials')">
                <i class="fas fa-download me-1"></i>Експорт
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Матеріал</th>
                            <th>Категорія</th>
                            <th>Поточний запас</th>
                            <th>Мінімум</th>
                            <th>Статус</th>
                            <th>Ціна</th>
                            <th>Вартість запасу</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materialsReport as $mat): ?>
                            <tr class="<?= $mat['stock_status'] === 'critical' ? 'table-danger' : ($mat['stock_status'] === 'low' ? 'table-warning' : '') ?>">
                                <td>
                                    <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($mat['category_name'] ?? 'Без категорії') ?></td>
                                <td><?= $mat['current_stock'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                <td><?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                <td>
                                    <?php if ($mat['stock_status'] === 'critical'): ?>
                                        <span class="badge bg-danger">Критично</span>
                                    <?php elseif ($mat['stock_status'] === 'low'): ?>
                                        <span class="badge bg-warning text-dark">Низько</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Нормально</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatMoney($mat['price']) ?></td>
                                <td class="text-success fw-bold"><?= formatMoney($mat['stock_value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <td colspan="6"><strong>Загальна вартість запасів:</strong></td>
                            <td class="text-success fw-bold">
                                <strong><?= formatMoney(array_sum(array_column($materialsReport, 'stock_value'))) ?></strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($selectedReport === 'requests'): ?>
    <!-- Звіт по заявках -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Статус заявок
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="requestsStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Пріоритети заявок
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="requestsPriorityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Статистика заявок
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Статус</th>
                            <th>Кількість</th>
                            <th>Загальна сума</th>
                            <th>Середня сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requestsStats as $stat): ?>
                            <tr>
                                <td>
                                    <span style="color:black;" class="badge <?= getStatusBadge($stat['status']) ?>">
                                        <?= getStatusText($stat['status']) ?>
                                    </span>
                                </td>
                                <td><?= $stat['count'] ?></td>
                                <td class="text-success"><?= formatMoney($stat['total_amount']) ?></td>
                                <td class="text-info"><?= formatMoney($stat['total_amount'] / max($stat['count'], 1)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($selectedReport === 'orders'): ?>
    <!-- Звіт по замовленнях -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Динаміка замовлень та сум по місяцях
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-doughnut me-2"></i>
                        Статуси замовлень
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Детальна статистика замовлень
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Статус</th>
                            <th>Кількість</th>
                            <th>Загальна сума</th>
                            <th>Середня сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordersStats as $stat): ?>
                            <tr>
                                <td>
                                    <span style="color:black;" class="badge <?= getStatusBadge($stat['status']) ?>">
                                        <?= getStatusText($stat['status']) ?>
                                    </span>
                                </td>
                                <td><?= $stat['count'] ?></td>
                                <td class="text-success"><?= formatMoney($stat['total_amount']) ?></td>
                                <td class="text-info"><?= formatMoney($stat['total_amount'] / max($stat['count'], 1)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <td><strong>Загалом:</strong></td>
                            <td><strong><?= array_sum(array_column($ordersStats, 'count')) ?></strong></td>
                            <td class="text-success"><strong><?= formatMoney(array_sum(array_column($ordersStats, 'total_amount'))) ?></strong></td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($selectedReport === 'movements'): ?>
    <!-- Звіт по рухах товарів -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-exchange-alt me-2"></i>
                Останні рухи товарів (20 записів)
            </h5>
            <a href="<?= BASE_URL ?>/dashboard/stock_movements.php" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt me-1"></i>Детальний перегляд
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Матеріал</th>
                            <th>Тип</th>
                            <th>Кількість</th>
                            <th>Операція</th>
                            <th>Виконав</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $movement): ?>
                            <tr>
                                <td><?= formatDateTime($movement['movement_date']) ?></td>
                                <td><?= htmlspecialchars($movement['material_name']) ?></td>
                                <td>
                                    <?php if ($movement['movement_type'] === 'in'): ?>
                                        <span class="badge bg-success">Надходження</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Витрата</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $movement['quantity'] ?> <?= htmlspecialchars($movement['material_unit']) ?></td>
                                <td>
                                    <?php
                                    $types = [
                                        'order' => 'Замовлення',
                                        'production' => 'Виробництво', 
                                        'adjustment' => 'Коригування',
                                        'manual' => 'Ручне'
                                    ];
                                    ?>
                                    <span class="badge bg-secondary">
                                        <?= $types[$movement['reference_type']] ?? $movement['reference_type'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($movement['performed_by_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Підготовка даних для графіків
$requestsStatsData = [];
$priorityStatsData = [];
$ordersStatsData = [];

foreach ($requestsStats as $stat) {
    $requestsStatsData[$stat['status']] = $stat['count'];
}

foreach ($priorityStats as $stat) {
    $priorityStatsData[$stat['priority']] = $stat['count'];
}

foreach ($ordersStats as $stat) {
    $ordersStatsData[$stat['status']] = $stat['count'];
}

$additionalJS = "
// Загальний графік по місяцях
if (document.getElementById('monthlyChart')) {
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
            datasets: [{
                label: 'Кількість замовлень',
                data: [" . implode(',', array_values($monthlyData)) . "],
                borderColor: '#4fc3f7',
                backgroundColor: 'rgba(79, 195, 247, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

// Графік статусів заявок
if (document.getElementById('requestsStatusChart')) {
    const requestsCtx = document.getElementById('requestsStatusChart').getContext('2d');
    new Chart(requestsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Очікує', 'Затверджено', 'Відхилено', 'Замовлено', 'Доставлено'],
            datasets: [{
                data: [
                    " . ($requestsStatsData['pending'] ?? 0) . ",
                    " . ($requestsStatsData['approved'] ?? 0) . ",
                    " . ($requestsStatsData['rejected'] ?? 0) . ",
                    " . ($requestsStatsData['ordered'] ?? 0) . ",
                    " . ($requestsStatsData['delivered'] ?? 0) . "
                ],
                backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#17a2b8', '#6f42c1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Графік пріоритетів заявок
if (document.getElementById('requestsPriorityChart')) {
    const priorityCtx = document.getElementById('requestsPriorityChart').getContext('2d');
    new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Низький', 'Середній', 'Високий', 'Терміновий'],
            datasets: [{
                data: [
                    " . ($priorityStatsData['low'] ?? 0) . ",
                    " . ($priorityStatsData['medium'] ?? 0) . ",
                    " . ($priorityStatsData['high'] ?? 0) . ",
                    " . ($priorityStatsData['urgent'] ?? 0) . "
                ],
                backgroundColor: ['#6c757d', '#0dcaf0', '#fd7e14', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Графік замовлень
if (document.getElementById('ordersChart')) {
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
            datasets: [{
                label: 'Кількість замовлень',
                data: [" . implode(',', array_values($monthlyData)) . "],
                backgroundColor: 'rgba(79, 195, 247, 0.8)',
                yAxisID: 'y'
            }, {
                label: 'Сума (тис. грн)',
                data: [" . implode(',', array_map(function($v) { return round($v/1000, 1); }, array_values($monthlyAmount))) . "],
                type: 'line',
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                yAxisID: 'y1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Графік статусів замовлень
if (document.getElementById('ordersStatusChart')) {
    const ordersStatusCtx = document.getElementById('ordersStatusChart').getContext('2d');
    new Chart(ordersStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Чернетка', 'Відправлено', 'Підтверджено', 'В процесі', 'Доставлено', 'Завершено'],
            datasets: [{
                data: [
                    " . ($ordersStatsData['draft'] ?? 0) . ",
                    " . ($ordersStatsData['sent'] ?? 0) . ",
                    " . ($ordersStatsData['confirmed'] ?? 0) . ",
                    " . ($ordersStatsData['in_progress'] ?? 0) . ",
                    " . ($ordersStatsData['delivered'] ?? 0) . ",
                    " . ($ordersStatsData['completed'] ?? 0) . "
                ],
                backgroundColor: ['#6c757d', '#17a2b8', '#28a745', '#ffc107', '#6f42c1', '#20c997'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function exportReport(reportType) {
    // Простий експорт даних (можна розширити)
    alert('Функція експорту буде реалізована в наступних версіях');
}
";

renderDashboardLayout('Звіти та аналітика', $user['role'], $content, '', $additionalJS);
?>