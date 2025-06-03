<?php
// Файл: dashboard/supplier.php  
// Дашборд постачальника

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['supplier']);

// Ініціалізація моделей
$supplierOrder = new SupplierOrder($pdo);
$material = new Material($pdo);
$orderItems = new OrderItem($pdo);

// Статистика для постачальника
$myOrders = $supplierOrder->getBySupplier($user['id']);
$totalOrders = count($myOrders);
$activeOrders = $supplierOrder->count(['supplier_id' => $user['id'], 'status' => ['sent', 'confirmed', 'in_progress']]);
$completedOrders = $supplierOrder->count(['supplier_id' => $user['id'], 'status' => 'completed']);
$myMaterials = $material->findWhere(['supplier_id' => $user['id']]);

// Розрахунок загальної суми замовлень
$totalAmount = 0;
$monthlyAmount = 0;
foreach ($myOrders as $order) {
    $totalAmount += $order['total_amount'];
    if (date('Y-m', strtotime($order['order_date'])) === date('Y-m')) {
        $monthlyAmount += $order['total_amount'];
    }
}

// Останні замовлення
$recentOrders = array_slice($myOrders, 0, 5);

// Замовлення що потребують уваги
$pendingOrders = $supplierOrder->findWhere(['supplier_id' => $user['id'], 'status' => 'sent'], 'order_date DESC');
$overdueOrders = [];

// Перевіряємо прострочені замовлення
foreach ($myOrders as $order) {
    if ($order['status'] === 'confirmed' && $order['expected_delivery'] && 
        strtotime($order['expected_delivery']) < time()) {
        $overdueOrders[] = $order;
    }
}

// Статистика по статусах
$statusStats = [];
foreach ($myOrders as $order) {
    if (!isset($statusStats[$order['status']])) {
        $statusStats[$order['status']] = ['count' => 0, 'amount' => 0];
    }
    $statusStats[$order['status']]['count']++;
    $statusStats[$order['status']]['amount'] += $order['total_amount'];
}

// Місячна статистика
$monthlyStats = [];
foreach ($myOrders as $order) {
    $month = date('Y-m', strtotime($order['order_date']));
    if (!isset($monthlyStats[$month])) {
        $monthlyStats[$month] = ['count' => 0, 'amount' => 0];
    }
    $monthlyStats[$month]['count']++;
    $monthlyStats[$month]['amount'] += $order['total_amount'];
}

// Підготовка даних для графіків - останні 6 місяців
$monthlyData = [];
$monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    $monthlyLabels[] = $monthLabel;
    $monthlyData[] = $monthlyStats[$month]['amount'] ?? 0;
}

ob_start();
?>

<div class="row mb-4">
    <!-- Статистика -->
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalOrders ?></h3>
                <p class="mb-0">Всього замовлень</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $activeOrders ?></h3>
                <p class="mb-0">Активних замовлень</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-check fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $completedOrders ?></h3>
                <p class="mb-0">Виконано</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-hryvnia fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($monthlyAmount/1000, 0) ?>к</h3>
                <p class="mb-0">Цього місяця</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($pendingOrders)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h5 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>
                Нові замовлення для підтвердження!
            </h5>
            <p class="mb-3">У вас є <?= count($pendingOrders) ?> нових замовлень, що потребують підтвердження:</p>
            <div class="row">
                <?php foreach (array_slice($pendingOrders, 0, 3) as $order): ?>
                    <div class="col-md-4 mb-2">
                        <div class="card border-info">
                            <div class="card-body p-3">
                                <h6 class="card-title text-info mb-1">
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </h6>
                                <p class="card-text mb-2">
                                    Сума: <?= formatMoney($order['total_amount']) ?><br>
                                    Дата: <?= formatDate($order['order_date']) ?>
                                </p>
                                <a href="<?= BASE_URL ?>/dashboard/my_orders.php?id=<?= $order['id'] ?>" 
                                   class="btn btn-sm btn-info">Переглянути</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($overdueOrders)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Прострочені поставки!
            </h5>
            <p class="mb-3">У вас є <?= count($overdueOrders) ?> прострочених замовлень:</p>
            <div class="row">
                <?php foreach (array_slice($overdueOrders, 0, 3) as $order): ?>
                    <div class="col-md-4 mb-2">
                        <div class="card border-danger">
                            <div class="card-body p-3">
                                <h6 class="card-title text-danger mb-1">
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </h6>
                                <p class="card-text mb-2">
                                    Очікувалося: <?= formatDate($order['expected_delivery']) ?><br>
                                    Прострочено на: <?= floor((time() - strtotime($order['expected_delivery'])) / 86400) ?> днів
                                </p>
                                <a href="<?= BASE_URL ?>/dashboard/my_orders.php?id=<?= $order['id'] ?>" 
                                   class="btn btn-sm btn-danger">Оновити статус</a>
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
    <!-- Динаміка замовлень -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Динаміка замовлень (останні 6 місяців)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="ordersChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Статистика по статусах -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-pie-chart me-2"></i>
                    Статуси замовлень
                </h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Останні замовлення -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list-alt me-2"></i>
                    Останні замовлення
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/my_orders.php" class="btn btn-sm btn-outline-light">
                    Всі замовлення
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Замовлень поки немає</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                    <th>Сума</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                        </td>
                                        <td><?= formatDate($order['order_date']) ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($order['status']) ?>">
                                                <?= getStatusText($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-success fw-bold">
                                            <?= formatMoney($order['total_amount']) ?>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/dashboard/my_orders.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">Деталі</a>
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
    
    <!-- Інформація про компанію -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    Інформація про компанію
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-building fa-4x text-primary mb-3"></i>
                    <h5><?= htmlspecialchars($user['company_name']) ?></h5>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Контактна особа</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['full_name']) ?></p>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Телефон</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['phone']) ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Email</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Матеріалів</h6>
                        <p class="mb-0"><?= count($myMaterials) ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <h6 class="text-muted">Загальна сума замовлень</h6>
                    <h4 class="text-success"><?= formatMoney($totalAmount) ?></h4>
                </div>
                
                <div class="mt-3">
                    <a href="<?= BASE_URL ?>/dashboard/profile.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-edit me-2"></i>Редагувати профіль
                    </a>
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
                        <a href="<?= BASE_URL ?>/dashboard/my_orders.php?status=sent" class="btn btn-warning w-100">
                            <i class="fas fa-clock me-2"></i>
                            Нові замовлення
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/my_orders.php?status=confirmed" class="btn btn-info w-100">
                            <i class="fas fa-truck me-2"></i>
                            До поставки
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/my_materials.php" class="btn btn-success w-100">
                            <i class="fas fa-boxes me-2"></i>
                            Мої матеріали
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/profile.php" class="btn btn-secondary w-100">
                            <i class="fas fa-user me-2"></i>
                            Профіль
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
// Графік динаміки замовлень
const ordersCtx = document.getElementById('ordersChart').getContext('2d');
new Chart(ordersCtx, {
    type: 'line',
    data: {
        labels: " . json_encode($monthlyLabels) . ",
        datasets: [{
            label: 'Сума замовлень (грн)',
            data: " . json_encode($monthlyData) . ",
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
                    callback: function(value) {
                        return value.toLocaleString() + ' грн';
                    }
                }
            }
        }
    }
});

// Кругова діаграма статусів
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = " . json_encode(array_column($statusStats, 'count')) . ";
const statusLabels = " . json_encode(array_map('getStatusText', array_keys($statusStats))) . ";

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: [
                '#6c757d',
                '#17a2b8', 
                '#28a745',
                '#ffc107',
                '#6f42c1',
                '#dc3545'
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
                    padding: 10,
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});
";

renderDashboardLayout('Панель постачальника', 'supplier', $content, '', $additionalJS);
?>