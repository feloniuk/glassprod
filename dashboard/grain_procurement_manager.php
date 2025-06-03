<?php
// Файл: dashboard/grain_procurement_manager.php
// Дашборд менеджера з закупівель зерна

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['procurement_manager']);

// Ініціалізація моделей
$grainPurchaseRequest = new GrainPurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);
$grainMaterial = new GrainMaterial($pdo);
$userModel = new User($pdo);
$qualityAssessment = new QualityAssessment($pdo);

// Статистика
$pendingRequests = $grainPurchaseRequest->count(['status' => 'pending']);
$approvedRequests = $grainPurchaseRequest->count(['status' => 'approved']);
$activeOrders = $supplierOrder->count(['status' => ['sent', 'confirmed', 'in_progress']]);
$totalSuppliers = $userModel->count(['role' => 'supplier', 'is_active' => 1]);

// Заявки що потребують уваги
$urgentRequests = $grainPurchaseRequest->findWhere(['priority' => 'urgent', 'status' => 'pending'], 'request_date ASC');
$pendingRequestsList = $grainPurchaseRequest->getByStatus('pending');

// Активні замовлення
$activeOrdersList = $supplierOrder->findWhere(['status' => ['sent', 'confirmed', 'in_progress']], 'order_date DESC');

// Статистика якості
$qualityStats = $grainMaterial->getQualityStats();

// Пріоритетна статистика
$priorityStats = $grainPurchaseRequest->getPriorityStatistics();
$priorityData = [];
foreach ($priorityStats as $stat) {
    $priorityData[$stat['priority']] = $stat['count'];
}

// Матеріали з низькими запасами
$lowStockMaterials = $grainMaterial->getLowStockMaterials();

// Останні оцінки якості
$recentAssessments = $qualityAssessment->getAllWithDetails();
$recentAssessments = array_slice($recentAssessments, 0, 5);

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
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-check fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $approvedRequests ?></h3>
                <p class="mb-0">Затверджено</p>
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
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-truck fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalSuppliers ?></h3>
                <p class="mb-0">Постачальників</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($urgentRequests)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Терміновi заявки!
            </h5>
            <p class="mb-3">У вас є <?= count($urgentRequests) ?> терміновi заявкi, що потребують негайної уваги:</p>
            <div class="row">
                <?php foreach (array_slice($urgentRequests, 0, 3) as $urgent): ?>
                    <?php $materialData = $grainMaterial->findById($urgent['material_id']); ?>
                    <div class="col-md-4 mb-2">
                        <div class="card border-danger">
                            <div class="card-body p-3">
                                <h6 class="card-title text-danger mb-1">
                                    <?= htmlspecialchars($materialData['name'] ?? 'Невідомий') ?>
                                </h6>
                                <p class="card-text mb-2">
                                    Кількість: <?= $urgent['quantity'] ?> <?= htmlspecialchars($materialData['unit'] ?? '') ?><br>
                                    Потрібно до: <?= formatDate($urgent['needed_date']) ?>
                                </p>
                                <a href="<?= BASE_URL ?>/dashboard/grain_requests.php?id=<?= $urgent['id'] ?>" 
                                   class="btn btn-sm btn-danger">Переглянути</a>
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
    <!-- Заявки на розгляд -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Заявки на розгляд
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/grain_requests.php" class="btn btn-sm btn-outline-light">
                    Всі заявки
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingRequestsList)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає заявок на розгляд</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Кількість</th>
                                    <th>Пріоритет</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($pendingRequestsList, 0, 5) as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($request['material_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                від <?= htmlspecialchars($request['requested_by_name']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= $request['quantity'] ?> <?= htmlspecialchars($request['material_unit']) ?>
                                            <br>
                                            <small class="text-success"><?= formatMoney($request['total_cost']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= getPriorityBadge($request['priority']) ?>">
                                                <?= getPriorityText($request['priority']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/dashboard/grain_requests.php?id=<?= $request['id'] ?>" 
                                               class="btn btn-sm btn-primary">Розглянути</a>
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
    
    <!-- Останні оцінки якості -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-microscope me-2"></i>
                    Останні оцінки якості
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/quality_assessment.php" class="btn btn-sm btn-outline-light">
                    Усі оцінки
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentAssessments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-microscope fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає оцінок якості</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Постачальник</th>
                                    <th>Якість</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAssessments as $assessment): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($assessment['material_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($assessment['supplier_name']) ?></td>
                                        <td>
                                            <?php
                                            $gradeColors = [
                                                'premium' => 'bg-success',
                                                'first' => 'bg-primary',
                                                'second' => 'bg-warning',
                                                'third' => 'bg-secondary',
                                                'rejected' => 'bg-danger'
                                            ];
                                            $gradeTexts = [
                                                'premium' => 'Преміум',
                                                'first' => 'Перший',
                                                'second' => 'Другий',
                                                'third' => 'Третій',
                                                'rejected' => 'Відхилено'
                                            ];
                                            ?>
                                            <span class="badge <?= $gradeColors[$assessment['quality_grade']] ?? 'bg-secondary' ?>">
                                                <?= $gradeTexts[$assessment['quality_grade']] ?? ucfirst($assessment['quality_grade']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDate($assessment['assessment_date']) ?></td>
                                        <td>
                                            <?php if ($assessment['is_approved']): ?>
                                                <span class="badge bg-success">Затверджено</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Очікує</span>
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
    </div>
</div>

<div class="row">
    <!-- Статистика якості зерна -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Статистика якості зерна
                </h5>
            </div>
            <div class="card-body">
                <canvas id="qualityChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Пріоритети заявок -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Пріоритети заявок
                </h5>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Потенціал виробництва спирту -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-wine-bottle me-2"></i>
                    Потенціал виробництва спирту
                </h5>
            </div>
            <div class="card-body">
                <?php $alcoholPotential = $grainMaterial->getAlcoholProductionPotential(); ?>
                <div class="text-center">
                    <h2 class="text-primary"><?= number_format($alcoholPotential['total_alcohol_potential'] ?? 0, 0) ?></h2>
                    <p class="text-muted">Загальний потенціал (літрів)</p>
                </div>
                <div class="progress mb-4" style="height: 20px;">
                    <?php
                    $total = $alcoholPotential['total_alcohol_potential'] ?? 0;
                    $premium = $alcoholPotential['premium_potential'] ?? 0;
                    $first = $alcoholPotential['first_potential'] ?? 0;
                    $second = $alcoholPotential['second_potential'] ?? 0;
                    $third = $alcoholPotential['third_potential'] ?? 0;
                    
                    $premiumPercent = $total > 0 ? ($premium / $total * 100) : 0;
                    $firstPercent = $total > 0 ? ($first / $total * 100) : 0;
                    $secondPercent = $total > 0 ? ($second / $total * 100) : 0;
                    $thirdPercent = $total > 0 ? ($third / $total * 100) : 0;
                    ?>
                    <div class="progress-bar bg-success" style="width: <?= $premiumPercent ?>%" title="Преміум: <?= number_format($premium, 0) ?> л"></div>
                    <div class="progress-bar bg-primary" style="width: <?= $firstPercent ?>%" title="Перший: <?= number_format($first, 0) ?> л"></div>
                    <div class="progress-bar bg-warning" style="width: <?= $secondPercent ?>%" title="Другий: <?= number_format($second, 0) ?> л"></div>
                    <div class="progress-bar bg-secondary" style="width: <?= $thirdPercent ?>%" title="Третій: <?= number_format($third, 0) ?> л"></div>
                </div>
                <div class="row text-center small">
                    <div class="col-3">
                        <span class="badge bg-success">Преміум</span><br>
                        <?= number_format($premium, 0) ?> л
                    </div>
                    <div class="col-3">
                        <span class="badge bg-primary">Перший</span><br>
                        <?= number_format($first, 0) ?> л
                    </div>
                    <div class="col-3">
                        <span class="badge bg-warning">Другий</span><br>
                        <?= number_format($second, 0) ?> л
                    </div>
                    <div class="col-3">
                        <span class="badge bg-secondary">Третій</span><br>
                        <?= number_format($third, 0) ?> л
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Критичні запаси -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Критичні запаси зерна
        </h5>
        <a href="<?= BASE_URL ?>/dashboard/grain_warehouse.php" class="btn btn-sm btn-outline-light">
            Весь склад
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($lowStockMaterials)): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p class="text-muted">Всі запаси в нормі</p>
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
                            <th>Вихід спирту</th>
                            <th>Якість</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($lowStockMaterials, 0, 5) as $mat): ?>
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
                                    <strong><?= $mat['alcohol_yield'] ?></strong> л/т
                                </td>
                                <td>
                                    <span class="badge bg-<?= $mat['quality_grade'] === 'premium' ? 'success' : ($mat['quality_grade'] === 'first' ? 'primary' : 'warning') ?>">
                                        <?= ucfirst($mat['quality_grade']) ?>
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
                        <a href="<?= BASE_URL ?>/dashboard/grain_requests.php?status=pending" class="btn btn-warning w-100">
                            <i class="fas fa-clock me-2"></i>
                            Розглянути заявки
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/orders.php?action=create" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>
                            Нове замовлення
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/quality_assessment.php" class="btn btn-info w-100">
                            <i class="fas fa-microscope me-2"></i>
                            Оцінка якості
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/suppliers.php" class="btn btn-primary w-100">
                            <i class="fas fa-truck me-2"></i>
                            Постачальники
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Формування даних для діаграм
$qualityData = [];
foreach ($qualityStats as $stat) {
    $qualityData[$stat['quality_grade']] = $stat['count'];
}

$additionalJS = "
// Діаграма якості зерна
const qualityCtx = document.getElementById('qualityChart').getContext('2d');
const qualityData = {
    premium: " . ($qualityData['premium'] ?? 0) . ",
    first: " . ($qualityData['first'] ?? 0) . ",
    second: " . ($qualityData['second'] ?? 0) . ",
    third: " . ($qualityData['third'] ?? 0) . "
};

new Chart(qualityCtx, {
    type: 'doughnut',
    data: {
        labels: ['Преміум', 'Перший', 'Другий', 'Третій'],
        datasets: [{
            data: [qualityData.premium, qualityData.first, qualityData.second, qualityData.third],
            backgroundColor: [
                '#28a745',
                '#007bff',
                '#ffc107',
                '#6c757d'
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

// Діаграма пріоритетів заявок
const priorityCtx = document.getElementById('priorityChart').getContext('2d');
const priorityData = {
    urgent: " . ($priorityData['urgent'] ?? 0) . ",
    high: " . ($priorityData['high'] ?? 0) . ",
    medium: " . ($priorityData['medium'] ?? 0) . ",
    low: " . ($priorityData['low'] ?? 0) . "
};

new Chart(priorityCtx, {
    type: 'doughnut',
    data: {
        labels: ['Терміновий', 'Високий', 'Середній', 'Низький'],
        datasets: [{
            data: [priorityData.urgent, priorityData.high, priorityData.medium, priorityData.low],
            backgroundColor: [
                '#dc3545',
                '#fd7e14',
                '#0dcaf0',
                '#6c757d'
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

renderDashboardLayout('Панель менеджера закупівель зерна', 'procurement_manager', $content, '', $additionalJS);
?>