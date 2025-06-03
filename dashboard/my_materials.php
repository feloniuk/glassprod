<?php
// Файл: dashboard/my_materials.php
// Матеріали постачальника

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['supplier']);

// Ініціалізація моделей
$material = new Material($pdo);
$materialCategory = new MaterialCategory($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_price') {
        $materialId = $_POST['material_id'] ?? 0;
        $newPrice = (float)($_POST['price'] ?? 0);
        
        // Перевіряємо що це матеріал цього постачальника
        $materialData = $material->findById($materialId);
        if ($materialData && $materialData['supplier_id'] == $user['id'] && $newPrice > 0) {
            if ($material->update($materialId, ['price' => $newPrice])) {
                $message = 'Ціну успішно оновлено!';
            } else {
                $error = 'Помилка при оновленні ціни';
            }
        } else {
            $error = 'Недостатньо прав або невірна ціна';
        }
    }
}

// Фільтри
$categoryFilter = $_GET['category'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Отримання матеріалів постачальника
$materials = $material->findWhere(['supplier_id' => $user['id']], 'name ASC');

// Додаємо інформацію про категорії
foreach ($materials as &$mat) {
    if ($mat['category_id']) {
        $category = $materialCategory->findById($mat['category_id']);
        $mat['category_name'] = $category['name'] ?? '';
    }
}

// Застосування фільтрів
if ($categoryFilter) {
    $materials = array_filter($materials, function($mat) use ($categoryFilter) {
        return $mat['category_id'] == $categoryFilter;
    });
}

if ($searchFilter) {
    $materials = array_filter($materials, function($mat) use ($searchFilter) {
        return stripos($mat['name'], $searchFilter) !== false;
    });
}

// Отримання категорій для фільтра
$categories = $materialCategory->getAllForSelect();

// Статистика
$totalMaterials = count($materials);
$avgPrice = $totalMaterials > 0 ? array_sum(array_column($materials, 'price')) / $totalMaterials : 0;

// Категорії з кількістю матеріалів
$categoryStats = [];
foreach ($materials as $mat) {
    $catId = $mat['category_id'] ?? 0;
    $catName = $mat['category_name'] ?? 'Без категорії';
    
    if (!isset($categoryStats[$catId])) {
        $categoryStats[$catId] = [
            'name' => $catName,
            'count' => 0,
            'total_value' => 0
        ];
    }
    
    $categoryStats[$catId]['count']++;
    $categoryStats[$catId]['total_value'] += $mat['current_stock'] * $mat['price'];
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
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalMaterials ?></h3>
                <p class="mb-0">Ваших матеріалів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-layer-group fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count($categoryStats) ?></h3>
                <p class="mb-0">Категорій</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-hryvnia fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($avgPrice, 0) ?></h3>
                <p class="mb-0">Середня ціна</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <h3 class="mb-1"><?= array_sum(array_column($materials, 'current_stock')) ?></h3>
                <p class="mb-0">Загальні запаси</p>
            </div>
        </div>
    </div>
</div>

<!-- Фільтри -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i>
            Фільтри та пошук
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Пошук</label>
                <input type="text" name="search" class="form-control" 
                       value="<?= htmlspecialchars($searchFilter) ?>" placeholder="Назва матеріалу...">
            </div>
            <div class="col-md-4">
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
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Фільтр
                    </button>
                    <a href="<?= BASE_URL ?>/dashboard/my_materials.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Список матеріалів -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    Ваші матеріали (<?= count($materials) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($materials)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Матеріалів не знайдено</h5>
                        <p class="text-muted">У вас поки немає матеріалів у системі або вони не відповідають фільтрам</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Категорія</th>
                                    <th>Од. виміру</th>
                                    <th>Запас на заводі</th>
                                    <th>Мінімум</th>
                                    <th>Ваша ціна</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $mat): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($mat['name']) ?></strong>
                                            <?php if ($mat['description']): ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($mat['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($mat['category_name'] ?? 'Без категорії') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($mat['unit']) ?></td>
                                        <td>
                                            <strong><?= $mat['current_stock'] ?></strong>
                                            <?php if ($mat['current_stock'] <= $mat['min_stock_level']): ?>
                                                <span class="badge bg-warning text-dark ms-1">Низько</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $mat['min_stock_level'] ?></td>
                                        <td class="text-success fw-bold">
                                            <?= formatMoney($mat['price']) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="updatePrice(<?= $mat['id'] ?>, '<?= htmlspecialchars($mat['name']) ?>', <?= $mat['price'] ?>)">
                                                <i class="fas fa-edit"></i> Ціна
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
    
    <!-- Статистика по категоріях -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Статистика по категоріях
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categoryStats)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає даних</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($categoryStats as $stat): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($stat['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $stat['count'] ?> позицій</small>
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold">
                                        <?= formatMoney($stat['total_value']) ?>
                                    </div>
                                    <small class="text-muted">загальна вартість</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Інформація про компанію -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    Інформація про компанію
                </h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-industry fa-4x text-primary mb-3"></i>
                <h5><?= htmlspecialchars($user['company_name']) ?></h5>
                <p class="text-muted"><?= htmlspecialchars($user['full_name']) ?></p>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Email</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Телефон</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['phone']) ?></p>
                    </div>
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

<!-- Модальне вікно оновлення ціни -->
<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Оновити ціну
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_price">
                    <input type="hidden" name="material_id" id="priceMaterialId">
                    
                    <div class="alert alert-info">
                        <strong>Матеріал:</strong> <span id="priceMaterialName"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Поточна ціна</label>
                        <input type="text" id="currentPrice" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Нова ціна *</label>
                        <div class="input-group">
                            <input type="number" name="price" id="newPrice" class="form-control" 
                                   step="0.01" min="0.01" required>
                            <span class="input-group-text">грн</span>
                        </div>
                        <small class="text-muted">Введіть нову ціну за одиницю товару</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Оновити ціну
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
function updatePrice(materialId, materialName, currentPrice) {
    document.getElementById('priceMaterialId').value = materialId;
    document.getElementById('priceMaterialName').textContent = materialName;
    document.getElementById('currentPrice').value = currentPrice.toFixed(2) + ' грн';
    document.getElementById('newPrice').value = currentPrice.toFixed(2);
    
    new bootstrap.Modal(document.getElementById('priceModal')).show();
}
";

renderDashboardLayout('Мої матеріали', $user['role'], $content, '', $additionalJS);
?>