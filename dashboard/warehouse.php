<?php
// Файл: dashboard/warehouse.php
// Управління складом

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager', 'warehouse_keeper']);

// Ініціалізація моделей
$material = new Material($pdo);
$stockMovement = new StockMovement($pdo);

$message = '';
$error = '';

// Обробка коригування запасів
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'adjust_stock' && in_array($user['role'], ['warehouse_keeper', 'director'])) {
        $materialId = $_POST['material_id'] ?? 0;
        $adjustment = $_POST['adjustment'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        
        if ($materialId && $adjustment != 0) {
            if ($material->adjustStock($materialId, $adjustment, $user['id'], $notes)) {
                $message = 'Запас успішно скориговано!';
            } else {
                $error = 'Помилка при коригуванні запасу';
            }
        }
    }
}

// Фільтри
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Отримання матеріалів
$materials = $material->getAllWithCategories();

// Застосування фільтрів
if ($categoryFilter) {
    $materials = array_filter($materials, function($mat) use ($categoryFilter) {
        return $mat['category_id'] == $categoryFilter;
    });
}

if ($statusFilter) {
    $materials = array_filter($materials, function($mat) use ($statusFilter) {
        if ($statusFilter === 'low') {
            return $mat['current_stock'] <= $mat['min_stock_level'];
        } elseif ($statusFilter === 'critical') {
            return $mat['current_stock'] <= $mat['min_stock_level'] * 0.5;
        } elseif ($statusFilter === 'normal') {
            return $mat['current_stock'] > $mat['min_stock_level'];
        }
        return true;
    });
}

if ($searchFilter) {
    $materials = array_filter($materials, function($mat) use ($searchFilter) {
        return stripos($mat['name'], $searchFilter) !== false;
    });
}

// Отримання категорій для фільтра
$categories = $pdo->query("SELECT * FROM material_categories ORDER BY name")->fetchAll();

// Статистика
$totalMaterials = count($materials);
$lowStockCount = count(array_filter($materials, function($mat) {
    return $mat['current_stock'] <= $mat['min_stock_level'];
}));
$totalValue = array_sum(array_map(function($mat) {
    return $mat['current_stock'] * $mat['price'];
}, $materials));

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
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalMaterials ?></h3>
                <p class="mb-0">Позицій матеріалів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card <?= $lowStockCount > 0 ? 'danger' : 'success' ?>">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $lowStockCount ?></h3>
                <p class="mb-0">Критичних запасів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-hryvnia fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($totalValue/1000, 0) ?>к</h3>
                <p class="mb-0">Загальна вартість</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($totalValue / max($totalMaterials, 1), 0) ?></h3>
                <p class="mb-0">Сер. вартість позиції</p>
            </div>
        </div>
    </div>
</div>

<!-- Фільтри та пошук -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i>
            Фільтри та пошук
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Пошук</label>
                <input type="text" name="search" class="form-control" 
                       value="<?= htmlspecialchars($searchFilter) ?>" placeholder="Назва матеріалу...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Категорія</label>
                <select name="category" class="form-select">
                    <option value="">Всі категорії</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Статус запасів</label>
                <select name="status" class="form-select">
                    <option value="">Всі статуси</option>
                    <option value="normal" <?= $statusFilter === 'normal' ? 'selected' : '' ?>>Нормальний</option>
                    <option value="low" <?= $statusFilter === 'low' ? 'selected' : '' ?>>Низький</option>
                    <option value="critical" <?= $statusFilter === 'critical' ? 'selected' : '' ?>>Критичний</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Фільтр
                    </button>
                    <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Список матеріалів -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-warehouse me-2"></i>
            Склад матеріалів (<?= count($materials) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($materials)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Матеріалів не знайдено</h5>
                <p class="text-muted">Спробуйте змінити фільтри пошуку</p>
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
                            <th>Статус</th>
                            <th>Ціна за од.</th>
                            <th>Вартість запасу</th>
                            <?php if (in_array($user['role'], ['warehouse_keeper', 'director'])): ?>
                                <th>Дії</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $mat): ?>
                            <?php
                            $stockValue = $mat['current_stock'] * $mat['price'];
                            $statusClass = '';
                            $statusText = 'Нормально';
                            
                            if ($mat['current_stock'] <= $mat['min_stock_level'] * 0.5) {
                                $statusClass = 'table-danger';
                                $statusText = 'Критично';
                            } elseif ($mat['current_stock'] <= $mat['min_stock_level']) {
                                $statusClass = 'table-warning';
                                $statusText = 'Низько';
                            }
                            ?>
                            <tr class="<?= $statusClass ?>">
                                <td>
                                    <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($mat['description'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($mat['category_name'] ?? 'Без категорії') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="fs-5"><?= $mat['current_stock'] ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($mat['unit']) ?></small>
                                </td>
                                <td>
                                    <?= $mat['min_stock_level'] ?> <?= htmlspecialchars($mat['unit']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ? ($statusText === 'Критично' ? 'bg-danger' : 'bg-warning') : 'bg-success' ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="text-success">
                                    <?= formatMoney($mat['price']) ?>
                                </td>
                                <td class="text-success fw-bold">
                                    <?= formatMoney($stockValue) ?>
                                </td>
                                <?php if (in_array($user['role'], ['warehouse_keeper', 'director'])): ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="showAdjustModal(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>', <?= $mat['current_stock'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($user['role'], ['warehouse_keeper', 'director'])): ?>
<!-- Модальне вікно коригування запасів -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Коригування запасу
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="adjust_stock">
                    <input type="hidden" name="material_id" id="adjustMaterialId">
                    
                    <div class="alert alert-info">
                        <strong>Матеріал:</strong> <span id="adjustMaterialName"></span><br>
                        <strong>Поточний запас:</strong> <span id="adjustCurrentStock"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Коригування *</label>
                        <input type="number" name="adjustment" class="form-control" 
                               placeholder="+ для збільшення, - для зменшення" required>
                        <small class="text-muted">Введіть позитивне число для збільшення або негативне для зменшення</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Причина коригування *</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Опишіть причину коригування запасу..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Зберегти
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
function showAdjustModal(materialId, materialName, currentStock) {
    document.getElementById('adjustMaterialId').value = materialId;
    document.getElementById('adjustMaterialName').textContent = materialName;
    document.getElementById('adjustCurrentStock').textContent = currentStock;
    
    new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
}
";

renderDashboardLayout('Склад', $user['role'], $content, '', $additionalJS);
?>