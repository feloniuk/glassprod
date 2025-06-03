<?php
// Файл: dashboard/procurement_manager.php
// Дашборд менеджера з закупівель

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['procurement_manager']);

// Ініціалізація моделей
$purchaseRequest = new PurchaseRequest($pdo);
$supplierOrder = new SupplierOrder($pdo);
$material = new Material($pdo);
$userModel = new User($pdo);

// Статистика
$pendingRequests = $purchaseRequest->count(['status' => 'pending']);
$approvedRequests = $purchaseRequest->count(['status' => 'approved']);
$activeOrders = $supplierOrder->count(['status' => ['sent', 'confirmed', 'in_progress']]);
$totalSuppliers = $userModel->count(['role' => 'supplier', 'is_active' => 1]);

// Заявки що потребують уваги
$urgentRequests = $purchaseRequest->findWhere(['priority' => 'urgent', 'status' => 'pending'], 'request_date ASC');
$pendingRequestsList = $purchaseRequest->getByStatus('pending');

// Активні замовлення
$activeOrdersList = $supplierOrder->findWhere(['status' => ['sent', 'confirmed', 'in_progress']], 'order_date DESC');

// Пріоритетна статистика
$priorityStats = $purchaseRequest->getPriorityStatistics();
$priorityData = [];
foreach ($priorityStats as $stat) {
    $priorityData[$stat['priority']] = $stat['count'];
}

// Матеріали з низькими запасами
$lowStockMaterials = $material->getLowStockMaterials();

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
                    <?php $materialData = $material->findById($urgent['material_id']); ?>
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
                                <a href="<?= BASE_URL ?>/dashboard/requests.php?id=<?= $urgent['id'] ?>" 
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
                <a href="<?= BASE_URL ?>/dashboard/requests.php" class="btn btn-sm btn-outline-light">
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
                                    <th>Матеріал</th>
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
                                            <a href="<?= BASE_URL ?>/dashboard/requests.php?id=<?= $request['id'] ?>" 
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
    
    <!-- Активні замовлення -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Активні замовлення
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/orders.php" class="btn btn-sm btn-outline-light">
                    Всі замовлення
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeOrdersList)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає активних замовлень</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Постачальник</th>
                                    <th>Статус</th>
                                    <th>Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($activeOrdersList, 0, 5) as $order): ?>
                                    <?php $supplier = $userModel->findById($order['supplier_id']); ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= formatDate($order['order_date']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($supplier['company_name'] ?? 'Невідомий') ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($order['status']) ?>">
                                                <?= getStatusText($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-success fw-bold">
                                            <?= formatMoney($order['total_amount']) ?>
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
    
    <!-- Критичні запаси -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Критичні запаси
                </h5>
                <a href="<?= BASE_URL ?>/dashboard/materials.php" class="btn btn-sm btn-outline-light">
                    Всі матеріали
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
                                    <th>Матеріал</th>
                                    <th>Поточний запас</th>
                                    <th>Мінімум</th>
                                    <th>Різниця</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($lowStockMaterials, 0, 5) as $mat): ?>
                                    <tr class="<?= $mat['current_stock'] <= $mat['min_stock_level'] * 0.5 ? 'table-danger' : 'table-warning' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($mat['category_name']) ?></small>
                                        </td>
                                        <td><?= $mat['current_stock'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                        <td><?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?></td>
                                        <td class="text-danger fw-bold">
                                            <?= $mat['current_stock'] - $mat['min_stock_level'] ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="createRequest(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>')">
                                                Створити заявку
                                            </button>
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
                        <a href="<?= BASE_URL ?>/dashboard/suppliers.php" class="btn btn-info w-100">
                            <i class="fas fa-truck me-2"></i>
                            Постачальники
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= BASE_URL ?>/dashboard/materials.php" class="btn btn-secondary w-100">
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
// Діаграма пріоритетів
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

// Функція створення заявки
function createRequest(materialId, materialName) {
    const quantity = prompt('Введіть кількість для замовлення ' + materialName + ':');
    if (quantity && !isNaN(quantity) && quantity > 0) {
        window.location.href = '" . BASE_URL . "/dashboard/requests.php?action=create&material_id=' + materialId + '&quantity=' + quantity;
    }
}
";

renderDashboardLayout('Панель менеджера з закупівель', 'procurement_manager', $content, '', $additionalJS);
?>