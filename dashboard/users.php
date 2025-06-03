<?php
// Файл: dashboard/users.php
// Управління користувачами (тільки для директора)

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу - тільки директор
$user = checkRole(['director']);

// Ініціалізація моделей
$userModel = new User($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_user') {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role' => $_POST['role'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? '')
        ];
        
        // Валідація
        if (empty($data['username']) || empty($data['password']) || empty($data['email']) || 
            empty($data['full_name']) || empty($data['role'])) {
            $error = 'Заповніть всі обов\'язкові поля';
        } elseif ($userModel->isUsernameExists($data['username'])) {
            $error = 'Користувач з таким логіном вже існує';
        } elseif (!in_array($data['role'], ['director', 'procurement_manager', 'warehouse_keeper', 'supplier'])) {
            $error = 'Невірна роль користувача';
        } elseif ($data['role'] === 'supplier' && empty($data['company_name'])) {
            $error = 'Для постачальника обов\'язково вказати назву компанії';
        } else {
            // Якщо не постачальник - очищуємо назву компанії
            if ($data['role'] !== 'supplier') {
                $data['company_name'] = '';
            }
            
            if ($userModel->createUser($data)) {
                $message = 'Користувача успішно створено!';
            } else {
                $error = 'Помилка при створенні користувача';
            }
        }
    }
    
    elseif ($action === 'update_user') {
        $userId = $_POST['user_id'] ?? 0;
        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($userId && !empty($data['email']) && !empty($data['full_name'])) {
            if ($userModel->update($userId, $data)) {
                $message = 'Користувача успішно оновлено!';
            } else {
                $error = 'Помилка при оновленні користувача';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'reset_password') {
        $userId = $_POST['user_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        if ($userId && !empty($newPassword) && strlen($newPassword) >= 6) {
            if ($userModel->updatePassword($userId, $newPassword)) {
                $message = 'Пароль успішно змінено!';
            } else {
                $error = 'Помилка при зміні пароля';
            }
        } else {
            $error = 'Пароль повинен містити принаймні 6 символів';
        }
    }
    
    elseif ($action === 'toggle_status') {
        $userId = $_POST['user_id'] ?? 0;
        $isActive = $_POST['is_active'] ?? 0;
        
        if ($userId) {
            if ($userModel->update($userId, ['is_active' => $isActive])) {
                $message = $isActive ? 'Користувача активовано!' : 'Користувача деактивовано!';
            } else {
                $error = 'Помилка при зміні статусу користувача';
            }
        }
    }
}

// Фільтри
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Отримання користувачів
$users = $userModel->findAll('full_name ASC');

// Застосування фільтрів
if ($roleFilter) {
    $users = array_filter($users, function($u) use ($roleFilter) {
        return $u['role'] === $roleFilter;
    });
}

if ($statusFilter !== '') {
    $users = array_filter($users, function($u) use ($statusFilter) {
        return $u['is_active'] == $statusFilter;
    });
}

// Статистика
$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($u) { return $u['is_active']; }));
$roleStats = [];
foreach ($users as $u) {
    $roleStats[$u['role']] = ($roleStats[$u['role']] ?? 0) + 1;
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
                <i class="fas fa-users fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalUsers ?></h3>
                <p class="mb-0">Всього користувачів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $activeUsers ?></h3>
                <p class="mb-0">Активних</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalUsers - $activeUsers ?></h3>
                <p class="mb-0">Неактивних</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-truck fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $roleStats['supplier'] ?? 0 ?></h3>
                <p class="mb-0">Постачальників</p>
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
                    Фільтри
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Роль</label>
                        <select name="role" class="form-select">
                            <option value="">Всі ролі</option>
                            <option value="director" <?= $roleFilter === 'director' ? 'selected' : '' ?>>Директор</option>
                            <option value="procurement_manager" <?= $roleFilter === 'procurement_manager' ? 'selected' : '' ?>>Менеджер з закупівель</option>
                            <option value="warehouse_keeper" <?= $roleFilter === 'warehouse_keeper' ? 'selected' : '' ?>>Начальник складу</option>
                            <option value="supplier" <?= $roleFilter === 'supplier' ? 'selected' : '' ?>>Постачальник</option>
                        </select>
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
                                <i class="fas fa-search me-1"></i>Фільтр
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/users.php" class="btn btn-outline-secondary">
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
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-2"></i>Новий користувач
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Список користувачів -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Користувачі системи (<?= count($users) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Користувачів не знайдено</h5>
                <p class="text-muted">Спробуйте змінити фільтри або створіть нового користувача</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Користувач</th>
                            <th>Роль</th>
                            <th>Контакти</th>
                            <th>Компанія</th>
                            <th>Статус</th>
                            <th>Дата створення</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="<?= !$u['is_active'] ? 'table-secondary' : '' ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?= getRoleText($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($u['email']) ?><br>
                                        <?php if ($u['phone']): ?>
                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($u['phone']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($u['company_name']): ?>
                                        <strong><?= htmlspecialchars($u['company_name']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge bg-success">Активний</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Неактивний</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= formatDate($u['created_at']) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="resetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <?php if ($u['id'] != $user['id']): // Не можна деактивувати себе ?>
                                            <button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                    onclick="toggleStatus(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>', <?= $u['is_active'] ? 'false' : 'true' ?>)">
                                                <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Модальне вікно створення користувача -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Новий користувач
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Повне ім'я *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Логін *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Роль *</label>
                            <select name="role" class="form-select" required onchange="toggleCompanyField(this.value)">
                                <option value="">Оберіть роль</option>
                                <option value="director">Директор</option>
                                <option value="procurement_manager">Менеджер з закупівель</option>
                                <option value="warehouse_keeper">Начальник складу</option>
                                <option value="supplier">Постачальник</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="companyField" style="display: none;">
                        <label class="form-label">Назва компанії *</label>
                        <input type="text" name="company_name" class="form-control">
                        <small class="text-muted">Обов'язково для постачальників</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus me-1"></i>Створити користувача
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно редагування користувача -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Редагувати користувача
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Повне ім'я *</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" id="editPhone" class="form-control">
                        </div>
                        <div class="col-md-6" id="editCompanyField">
                            <label class="form-label">Назва компанії</label>
                            <input type="text" name="company_name" id="editCompanyName" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                            <label class="form-check-label" for="editIsActive">
                                Активний користувач
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

<!-- Модальне вікно зміни пароля -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>Змінити пароль
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="passwordUserId">
                    
                    <div class="alert alert-info">
                        <strong>Користувач:</strong> <span id="passwordUserName"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Новий пароль *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Мінімум 6 символів</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Змінити пароль
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
function toggleCompanyField(role) {
    const companyField = document.getElementById('companyField');
    const companyInput = document.querySelector('input[name=\"company_name\"]');
    
    if (role === 'supplier') {
        companyField.style.display = 'block';
        companyInput.required = true;
    } else {
        companyField.style.display = 'none';
        companyInput.required = false;
        companyInput.value = '';
    }
}

function editUser(userData) {
    document.getElementById('editUserId').value = userData.id;
    document.getElementById('editFullName').value = userData.full_name;
    document.getElementById('editEmail').value = userData.email;
    document.getElementById('editPhone').value = userData.phone || '';
    document.getElementById('editCompanyName').value = userData.company_name || '';
    document.getElementById('editIsActive').checked = userData.is_active == 1;
    
    // Показуємо поле компанії тільки для постачальників
    const companyField = document.getElementById('editCompanyField');
    if (userData.role === 'supplier') {
        companyField.style.display = 'block';
    } else {
        companyField.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function resetPassword(userId, userName) {
    document.getElementById('passwordUserId').value = userId;
    document.getElementById('passwordUserName').textContent = userName;
    
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}

function toggleStatus(userId, userName, newStatus) {
    const action = newStatus === 'true' ? 'активувати' : 'деактивувати';
    const confirmMessage = 'Ви впевнені, що хочете ' + action + ' користувача \"' + userName + '\"?';
    
    if (confirm(confirmMessage)) {
        // Створюємо форму для відправки
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'toggle_status';
        
        const userIdInput = document.createElement('input');
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        const statusInput = document.createElement('input');
        statusInput.name = 'is_active';
        statusInput.value = newStatus === 'true' ? '1' : '0';
        
        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
";

renderDashboardLayout('Управління користувачами', $user['role'], $content, '', $additionalJS);
?>