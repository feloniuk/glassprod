<?php
// Файл: dashboard/suppliers.php
// Управління постачальниками

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager']);

// Ініціалізація моделей
$userModel = new User($pdo);
$material = new Material($pdo);
$supplierOrder = new SupplierOrder($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_supplier') {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role' => 'supplier',
            'phone' => trim($_POST['phone'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? '')
        ];
        
        if (empty($data['username']) || empty($data['password']) || empty($data['email']) || 
            empty($data['full_name']) || empty($data['company_name'])) {
            $error = 'Заповніть всі обов\'язкові поля';
        } elseif ($userModel->isUsernameExists($data['username'])) {
            $error = 'Користувач з таким логіном вже існує';
        } else {
            if ($userModel->createUser($data)) {
                $message = 'Постачальника успішно додано!';
            } else {
                $error = 'Помилка при додаванні постачальника';
            }
        }
    }
    
    elseif ($action === 'update_supplier') {
        $supplierId = $_POST['supplier_id'] ?? 0;
        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($supplierId && !empty($data['email']) && !empty($data['full_name']) && !empty($data['company_name'])) {
            if ($userModel->update($supplierId, $data)) {
                $message = 'Інформацію про постачальника оновлено!';
            } else {
                $error = 'Помилка при оновленні даних постачальника';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'toggle_status') {
        $supplierId = $_POST['supplier_id'] ?? 0;
        $isActive = $_POST['is_active'] ?? 0;
        
        if ($supplierId) {
            if ($userModel->update($supplierId, ['is_active' => $isActive])) {
                $message = $isActive ? 'Постачальника активовано!' : 'Постачальника деактивовано!';
            } else {
                $error = 'Помилка при зміні статусу постачальника';
            }
        }
    }
}

// Фільтри
$statusFilter = $_GET['status'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Отримання постачальників
$suppliers = $userModel->getSuppliers();

// Застосування фільтрів
if ($statusFilter !== '') {
    $suppliers = array_filter($suppliers, function($supplier) use ($statusFilter) {
        return $supplier['is_active'] == $statusFilter;
    });
}

if ($searchFilter) {
    $suppliers = array_filter($suppliers, function($supplier) use ($searchFilter) {
        return stripos($supplier['company_name'], $searchFilter) !== false || 
               stripos($supplier['full_name'], $searchFilter) !== false;
    });
}

// Статистика по постачальниках
$totalSuppliers = count($suppliers);
$activeSuppliers = count(array_filter($suppliers, function($s) { return $s['is_active']; }));

// Додаткова статистика для кожного постачальника
foreach ($suppliers as &$supplier) {
    // Кількість матеріалів
    $supplier['materials_count'] = $material->count(['supplier_id' => $supplier['id']]);
    
    // Кількість замовлень
    $supplier['orders_count'] = $supplierOrder->count(['supplier_id' => $supplier['id']]);
    
    // Загальна сума замовлень
    $ordersSums = $pdo->prepare("SELECT SUM(total_amount) FROM supplier_orders WHERE supplier_id = ?");
    $ordersSums->execute([$supplier['id']]);
    $supplier['total_orders_amount'] = $ordersSums->fetchColumn() ?: 0;
    
    // Останнє замовлення
    $lastOrder = $pdo->prepare("SELECT order_date FROM supplier_orders WHERE supplier_id = ? ORDER BY order_date DESC LIMIT 1");
    $lastOrder->execute([$supplier['id']]);
    $supplier['last_order_date'] = $lastOrder->fetchColumn();
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
                <i class="fas fa-truck fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalSuppliers ?></h3>
                <p class="mb-0">Всього постачальників</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $activeSuppliers ?></h3>
                <p class="mb-0">Активних</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-pause-circle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalSuppliers - $activeSuppliers ?></h3>
                <p class="mb-0">Неактивних</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x mb-3"></i>
                <h3 class="mb-1"><?= array_sum(array_column($suppliers, 'materials_count')) ?></h3>
                <p class="mb-0">Позицій матеріалів</p>
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
                               value="<?= htmlspecialchars($searchFilter) ?>" 
                               placeholder="Назва компанії або ім'я...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Статус</label>
                        <select name="status" class="form-select">
                            <option value="">Всі статуси</option>
                            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Активні</option>
                            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Неактивні</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Пошук
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/suppliers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Дії
                </h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus me-2"></i>Додати постачальника
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Список постачальників -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-truck me-2"></i>
            Постачальники (<?= count($suppliers) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($suppliers)): ?>
            <div class="text-center py-5">
                <i class="fas fa-truck fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Постачальників не знайдено</h5>
                <p class="text-muted">Додайте першого постачальника або змініть фільтри пошуку</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Компанія</th>
                            <th>Контактна особа</th>
                            <th>Контакти</th>
                            <th>Матеріали</th>
                            <th>Замовлення</th>
                            <th>Статус</th>
                            <th>Останнє замовлення</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="<?= !$supplier['is_active'] ? 'table-secondary' : '' ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-building fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($supplier['company_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">ID: <?= $supplier['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($supplier['full_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">@<?= htmlspecialchars($supplier['username']) ?></small>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($supplier['email']) ?><br>
                                        <?php if ($supplier['phone']): ?>
                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($supplier['phone']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $supplier['materials_count'] ?></span>
                                    <br>
                                    <small class="text-muted">позицій</small>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-primary"><?= $supplier['orders_count'] ?></span>
                                        <small class="text-muted">шт.</small>
                                    </div>
                                    <?php if ($supplier['total_orders_amount'] > 0): ?>
                                        <small class="text-success">
                                            <?= formatMoney($supplier['total_orders_amount']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['is_active']): ?>
                                        <span class="badge bg-success">Активний</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Неактивний</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['last_order_date']): ?>
                                        <?= formatDate($supplier['last_order_date']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Немає замовлень</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editSupplier(<?= htmlspecialchars(json_encode($supplier)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewSupplierDetails(<?= $supplier['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm <?= $supplier['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                onclick="toggleSupplierStatus(<?= $supplier['id'] ?>, '<?= htmlspecialchars($supplier['company_name']) ?>', <?= $supplier['is_active'] ? 'false' : 'true' ?>)">
                                            <i class="fas fa-<?= $supplier['is_active'] ? 'ban' : 'check' ?>"></i>
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

<!-- Модальне вікно додавання постачальника -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Додати постачальника
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_supplier">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Назва компанії *</label>
                            <input type="text" name="company_name" class="form-control" required 
                                   placeholder="ТОВ 'Назва компанії'">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Контактна особа *</label>
                            <input type="text" name="full_name" class="form-control" required 
                                   placeholder="Прізвище Ім'я По батькові">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required 
                                   placeholder="contact@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control" 
                                   placeholder="+380XXXXXXXXX">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Логін для входу *</label>
                            <input type="text" name="username" class="form-control" required 
                                   placeholder="username">
                            <small class="text-muted">Унікальний логін для входу в систему</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required 
                                   minlength="6" placeholder="Мінімум 6 символів">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Увага:</strong> Постачальник зможе увійти в систему, переглядати та обробляти замовлення, 
                        а також управляти своїми матеріалами та цінами.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Додати постачальника
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування постачальника -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Редагувати постачальника
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_supplier">
                    <input type="hidden" name="supplier_id" id="editSupplierId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Назва компанії *</label>
                            <input type="text" name="company_name" id="editCompanyName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Контактна особа *</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" id="editPhone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                            <label class="form-check-label" for="editIsActive">
                                Активний постачальник
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Зберегти зміни
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно деталей постачальника -->
<div class="modal fade" id="supplierDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Детальна інформація про постачальника
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="supplierDetailsContent">
                <!-- Контент буде завантажений через JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
function editSupplier(supplierData) {
    document.getElementById('editSupplierId').value = supplierData.id;
    document.getElementById('editCompanyName').value = supplierData.company_name;
    document.getElementById('editFullName').value = supplierData.full_name;
    document.getElementById('editEmail').value = supplierData.email;
    document.getElementById('editPhone').value = supplierData.phone || '';
    document.getElementById('editIsActive').checked = supplierData.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
}

function toggleSupplierStatus(supplierId, companyName, newStatus) {
    const action = newStatus === 'true' ? 'активувати' : 'деактивувати';
    const confirmMessage = 'Ви впевнені, що хочете ' + action + ' постачальника \"' + companyName + '\"?';
    
    if (confirm(confirmMessage)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'toggle_status';
        
        const supplierIdInput = document.createElement('input');
        supplierIdInput.name = 'supplier_id';
        supplierIdInput.value = supplierId;
        
        const statusInput = document.createElement('input');
        statusInput.name = 'is_active';
        statusInput.value = newStatus === 'true' ? '1' : '0';
        
        form.appendChild(actionInput);
        form.appendChild(supplierIdInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function viewSupplierDetails(supplierId) {
    // Знаходимо постачальника в списку
    const suppliers = " . json_encode($suppliers) . ";
    const supplier = suppliers.find(s => s.id == supplierId);
    
    if (!supplier) {
        alert('Постачальника не знайдено');
        return;
    }
    
    const content = document.getElementById('supplierDetailsContent');
    content.innerHTML = `
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h6 class='mb-0'><i class='fas fa-building me-2'></i>Основна інформація</h6>
                    </div>
                    <div class='card-body'>
                        <table class='table table-sm'>
                            <tr><th>Компанія:</th><td>\${supplier.company_name}</td></tr>
                            <tr><th>Контактна особа:</th><td>\${supplier.full_name}</td></tr>
                            <tr><th>Email:</th><td>\${supplier.email}</td></tr>
                            <tr><th>Телефон:</th><td>\${supplier.phone || 'Не вказано'}</td></tr>
                            <tr><th>Логін:</th><td>\${supplier.username}</td></tr>
                            <tr><th>Статус:</th><td><span class='badge bg-\${supplier.is_active ? 'success' : 'secondary'}'>\${supplier.is_active ? 'Активний' : 'Неактивний'}</span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h6 class='mb-0'><i class='fas fa-chart-bar me-2'></i>Статистика</h6>
                    </div>
                    <div class='card-body'>
                        <div class='row text-center'>
                            <div class='col-6'>
                                <h4 class='text-primary'>\${supplier.materials_count}</h4>
                                <small class='text-muted'>Матеріалів</small>
                            </div>
                            <div class='col-6'>
                                <h4 class='text-success'>\${supplier.orders_count}</h4>
                                <small class='text-muted'>Замовлень</small>
                            </div>
                        </div>
                        <hr>
                        <div class='text-center'>
                            <h5 class='text-success'>\${supplier.total_orders_amount ? parseFloat(supplier.total_orders_amount).toLocaleString('uk-UA') + ' грн' : '0 грн'}</h5>
                            <small class='text-muted'>Загальна сума замовлень</small>
                        </div>
                        <hr>
                        <div class='text-center'>
                            <p class='mb-0'><strong>Останнє замовлення:</strong></p>
                            <p class='text-muted'>\${supplier.last_order_date ? new Date(supplier.last_order_date).toLocaleDateString('uk-UA') : 'Немає замовлень'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class='row mt-3'>
            <div class='col-12'>
                <div class='d-flex gap-2 justify-content-center'>
                    <a href='" . BASE_URL . "/dashboard/orders.php?supplier=\${supplier.id}' class='btn btn-primary'>
                        <i class='fas fa-shopping-cart me-1'></i>Замовлення
                    </a>
                    <a href='" . BASE_URL . "/dashboard/materials.php?supplier=\${supplier.id}' class='btn btn-info'>
                        <i class='fas fa-boxes me-1'></i>Матеріали
                    </a>
                    <button class='btn btn-warning' onclick='editSupplier(\${JSON.stringify(supplier)})'>
                        <i class='fas fa-edit me-1'></i>Редагувати
                    </button>
                </div>
            </div>
        </div>
    `;
    
    new bootstrap.Modal(document.getElementById('supplierDetailsModal')).show();
}
";

renderDashboardLayout('Управління постачальниками', $user['role'], $content, '', $additionalJS);
?>