<?php
// Файл: dashboard/materials.php
// Управління матеріалами

// Включаємо відображення помилок для діагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager', 'warehouse_keeper']);

// Ініціалізація моделей
$material = new Material($pdo);
$materialCategory = new MaterialCategory($pdo);
$userModel = new User($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_material' && in_array($user['role'], ['director', 'procurement_manager'])) {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => $_POST['category_id'] ?? null,
            'unit' => trim($_POST['unit'] ?? ''),
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 0),
            'current_stock' => (int)($_POST['current_stock'] ?? 0),
            'price' => (float)($_POST['price'] ?? 0),
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'description' => trim($_POST['description'] ?? '')
        ];
        
        if (!empty($data['name']) && !empty($data['unit']) && $data['price'] > 0) {
            if ($material->create($data)) {
                $message = 'Матеріал успішно створено!';
            } else {
                $error = 'Помилка при створенні матеріалу';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'update_material' && in_array($user['role'], ['director', 'procurement_manager'])) {
        $materialId = $_POST['material_id'] ?? 0;
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => $_POST['category_id'] ?? null,
            'unit' => trim($_POST['unit'] ?? ''),
            'min_stock_level' => (int)($_POST['min_stock_level'] ?? 0),
            'price' => (float)($_POST['price'] ?? 0),
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'description' => trim($_POST['description'] ?? '')
        ];
        
        if ($materialId && !empty($data['name']) && !empty($data['unit']) && $data['price'] > 0) {
            if ($material->update($materialId, $data)) {
                $message = 'Матеріал успішно оновлено!';
            } else {
                $error = 'Помилка при оновленні матеріалу';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'create_category' && in_array($user['role'], ['director', 'procurement_manager'])) {
        $categoryName = trim($_POST['category_name'] ?? '');
        $categoryDescription = trim($_POST['category_description'] ?? '');
        
        if (!empty($categoryName)) {
            $categoryData = [
                'name' => $categoryName,
                'description' => $categoryDescription
            ];
            
            if ($materialCategory->create($categoryData)) {
                $message = 'Категорію успішно створено!';
            } else {
                $error = 'Помилка при створенні категорії';
            }
        } else {
            $error = 'Введіть назву категорії';
        }
    }
}

// Фільтри
$categoryFilter = $_GET['category'] ?? '';
$supplierFilter = $_GET['supplier'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Отримання матеріалів
$materials = $material->getAllWithCategories();

// Застосування фільтрів
if ($categoryFilter) {
    $materials = array_filter($materials, function($mat) use ($categoryFilter) {
        return $mat['category_id'] == $categoryFilter;
    });
}

if ($supplierFilter) {
    $materials = array_filter($materials, function($mat) use ($supplierFilter) {
        return $mat['supplier_id'] == $supplierFilter;
    });
}

if ($searchFilter) {
    $materials = array_filter($materials, function($mat) use ($searchFilter) {
        return stripos($mat['name'], $searchFilter) !== false;
    });
}

// Отримання категорій та постачальників
$categories = $materialCategory->getAllForSelect();
$suppliers = $userModel->getSuppliers();

// Статистика
$totalMaterials = count($materials);
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
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalMaterials ?></h3>
                <p class="mb-0">Позицій матеріалів</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-layer-group fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count($categories) ?></h3>
                <p class="mb-0">Категорій</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-hryvnia fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($totalValue/1000, 0) ?>к</h3>
                <p class="mb-0">Загальна вартість</p>
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
                        <label class="form-label">Постачальник</label>
                        <select name="supplier" class="form-select">
                            <option value="">Всі постачальники</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>" <?= $supplierFilter == $supplier['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/materials.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (in_array($user['role'], ['director', 'procurement_manager'])): ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Дії
                </h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createMaterialModal">
                    <i class="fas fa-plus me-2"></i>Новий матеріал
                </button>
                <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="fas fa-layer-group me-2"></i>Нова категорія
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Список матеріалів -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-boxes me-2"></i>
            Каталог матеріалів (<?= count($materials) ?>)
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
                            <th>Од. виміру</th>
                            <th>Поточний запас</th>
                            <th>Мінімум</th>
                            <th>Ціна</th>
                            <th>Постачальник</th>
                            <?php if (in_array($user['role'], ['director', 'procurement_manager'])): ?>
                                <th>Дії</th>
                            <?php endif; ?>
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
                                        <span class="badge bg-warning ms-1">Низько</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $mat['min_stock_level'] ?></td>
                                <td class="text-success"><?= formatMoney($mat['price']) ?></td>
                                <td>
                                    <?php if ($mat['supplier_name']): ?>
                                        <small><?= htmlspecialchars($mat['supplier_name']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Не вказано</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (in_array($user['role'], ['director', 'procurement_manager'])): ?>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editMaterial(<?= htmlspecialchars(json_encode($mat)) ?>)">
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

<?php if (in_array($user['role'], ['director', 'procurement_manager'])): ?>
<!-- Модальне вікно створення матеріалу -->
<div class="modal fade" id="createMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Новий матеріал
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_material">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Назва матеріалу *</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Введіть назву матеріалу">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Категорія</label>
                            <select name="category_id" class="form-select">
                                <option value="">Оберіть категорію</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Одиниця виміру *</label>
                            <input type="text" name="unit" class="form-control" required 
                                   placeholder="кг, м, шт, л...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Початковий запас</label>
                            <input type="number" name="current_stock" class="form-control" 
                                   min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Мінімальний запас</label>
                            <input type="number" name="min_stock_level" class="form-control" 
                                   min="0" value="10">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ціна за одиницю *</label>
                            <input type="number" name="price" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Постачальник</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">Оберіть постачальника</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Опис</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Додатковий опис матеріалу..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Створити матеріал
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування матеріалу -->
<div class="modal fade" id="editMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Редагувати матеріал
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_material">
                    <input type="hidden" name="material_id" id="editMaterialId">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Назва матеріалу *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Категорія</label>
                            <select name="category_id" id="editCategoryId" class="form-select">
                                <option value="">Оберіть категорію</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Одиниця виміру *</label>
                            <input type="text" name="unit" id="editUnit" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Мінімальний запас</label>
                            <input type="number" name="min_stock_level" id="editMinStock" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ціна за одиницю *</label>
                            <input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Постачальник</label>
                            <select name="supplier_id" id="editSupplierId" class="form-select">
                                <option value="">Оберіть постачальника</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Опис</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Оновити матеріал
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно створення категорії -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-layer-group me-2"></i>Нова категорія
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_category">
                    
                    <div class="mb-3">
                        <label class="form-label">Назва категорії *</label>
                        <input type="text" name="category_name" class="form-control" required 
                               placeholder="Введіть назву категорії">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Опис</label>
                        <textarea name="category_description" class="form-control" rows="3" 
                                  placeholder="Опис категорії..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-plus me-1"></i>Створити категорію
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
function editMaterial(material) {
    document.getElementById('editMaterialId').value = material.id;
    document.getElementById('editName').value = material.name;
    document.getElementById('editCategoryId').value = material.category_id || '';
    document.getElementById('editUnit').value = material.unit;
    document.getElementById('editMinStock').value = material.min_stock_level;
    document.getElementById('editPrice').value = material.price;
    document.getElementById('editSupplierId').value = material.supplier_id || '';
    document.getElementById('editDescription').value = material.description || '';
    
    new bootstrap.Modal(document.getElementById('editMaterialModal')).show();
}
";