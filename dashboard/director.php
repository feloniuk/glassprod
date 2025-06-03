<?php
// Файл: dashboard/director.php
// Дашборд директора

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director']);

// Ініціалізація моделей
$purchaseRequest = new PurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);
$material = new Material($pdo);
$userModel = new User($pdo);

// Отримання статистики
$pendingRequests = $purchaseRequest->count(['status' => 'pending']);
$activeOrders = $supplierOrder->count(['status' => ['sent', 'confirmed', 'in_progress']]);
$lowStockMaterials = $material->getLowStockMaterials();
$totalUsers = $userModel->count(['is_active' => 1]);

// Статистика заявок
$requestStats = $purchaseRequest->getStatistics();
$requestStatsFormatted = [];
foreach ($requestStats as $stat) {
    $requestStatsFormatted[$stat['status']] = $stat;
}

// Статистика замовлень
$orderStats = $supplierOrder->getStatistics();
$orderStatsFormatted = [];
foreach ($orderStats as $stat) {
    $orderStatsFormatted[$stat['status']] = $stat;
}

// Місячна статистика замовлень
$monthlyStats = $supplierOrder->getMonthlyStats();
$monthlyData = array_fill(1, 12, 0);
$monthlyAmount = array_fill(1, 12, 0);
foreach ($monthlyStats as $stat) {
    $monthlyData[$stat['month']] = $stat['orders_count'];
    $monthlyAmount[$stat['month']] = $stat['total_amount'];
}

// Останні заявки
$recentRequests = $purchaseRequest->findWhere([], 'request_date DESC', 5);

// Критичні матеріали
$criticalMaterials = array_slice($lowStockMaterials, 0, 5);

ob_start();
?>

<div class="row mb-4">
    <!-- Статистика -->
    <div class="col-md-3 mb-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $pendingRequests ?></h3>
                <p class="mb-0">Заявок очікує</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $activeOrders ?></h3>
                <p class="mb-0">Активних замовлень</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count($lowStockMaterials) ?></h3>
                <p class="mb-0">Критичних запасів</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalUsers ?></h3>
                <p class="mb-0">Активних користувачів</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Графік замовлень по місяцях -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Динаміка замовлень по місяцях (<?= date('Y') ?>)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Статистика заявок -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-pie-chart me-2"></i>
                    Статус заявок
                </h5>
            </div>
            <div class="card-body">
                <canvas id="requestsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Останні заявки -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Останні заявки
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/requests.php" class="btn btn-sm btn-outline-light">
                    Всі заявки
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentRequests)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Заявок поки немає</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Кількість</th>
                                    <th>Статус</th>
                                    <th>Пріоритет</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <?php
                                    $materialData = $material->findById($request['material_id']);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($materialData['name'] ?? 'Невідомий') ?></td>
                                        <td><?= $request['quantity'] ?> <?= htmlspecialchars($materialData['unit'] ?? '') ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($request['status']) ?>">
                                                <?= getStatusText($request['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= getPriorityBadge($request['priority']) ?>">
                                                <?= getPriorityText($request['priority']) ?>
                                            </span>
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
    
    <!-- Критичні запаси -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Критичні запаси
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-sm btn-outline-light">
                    Склад
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($criticalMaterials)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Всі запаси в нормі</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Поточний</th>
                                    <th>Мінімум</th>
                                    <th>Різниця</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criticalMaterials as $mat): ?>
                                    <tr class="<?= $mat['current_stock'] <= $mat['min_stock_level'] * 0.5 ? 'table-danger' : 'table-warning' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($mat['category_name']) ?></small>
                                        </td>
                                        <td><?= $mat['current_stock'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                        <td><?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                        <td class="text-danger">
                                            <strong><?= $mat['current_stock'] - $mat['min_stock_level'] ?></strong>
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
                        <a href="<?= BASE_URL ?>/dashboard/requests.php?status=pending" class="btn btn-warning w-100">
                            <i class="fas fa-clock me-2"></i>
                            Переглянути заявки
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/reports.php" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            Звіти
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-danger w-100">
                            <i class="fas fa-warehouse me-2"></i>
                            Склад
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/users.php" class="btn btn-secondary w-100">
                            <i class="fas fa-users me-2"></i>
                            Користувачі
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
// Графік місячної статистики
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
        datasets: [{
            label: 'Кількість замовлень',
            data: [" . implode(',', array_values($monthlyData)) . "],
            borderColor: 'rgb(79, 195, 247)',
            backgroundColor: 'rgba(79, 195, 247, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Кругова діаграма заявок
const requestsCtx = document.getElementById('requestsChart').getContext('2d');
const requestsData = {
    pending: " . ($requestStatsFormatted['pending']['count'] ?? 0) . ",
    approved: " . ($requestStatsFormatted['approved']['count'] ?? 0) . ",
    rejected: " . ($requestStatsFormatted['rejected']['count'] ?? 0) . ",
    ordered: " . ($requestStatsFormatted['ordered']['count'] ?? 0) . ",
    delivered: " . ($requestStatsFormatted['delivered']['count'] ?? 0) . "
};

new Chart(requestsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Очікує', 'Затверджено', 'Відхилено', 'Замовлено', 'Доставлено'],
        datasets: [{
            data: [requestsData.pending, requestsData.approved, requestsData.rejected, requestsData.ordered, requestsData.delivered],
            backgroundColor: [
                '#ffc107',
                '#28a745',
                '#dc3545',
                '#17a2b8',
                '#6f42c1'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            }
        }
    }
});
";

renderDashboardLayout('Головна панель', 'director', $content, '', $additionalJS);
?>