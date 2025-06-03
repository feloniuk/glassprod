<?php
// Файл: dashboard/profile.php
// Профіль користувача

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager', 'warehouse_keeper', 'supplier']);

// Ініціалізація моделей
$userModel = new User($pdo);

$message = '';
$error = '';

// Обробка оновлення профілю
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'company_name' => trim($_POST['company_name'] ?? '')
        ];
        
        if (!empty($data['full_name']) && !empty($data['email'])) {
            if ($userModel->update($user['id'], $data)) {
                $message = 'Профіль успішно оновлено!';
                // Оновлюємо дані користувача в сесії
                $_SESSION['user_name'] = $data['full_name'];
            } else {
                $error = 'Помилка при оновленні профілю';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Заповніть всі поля';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Нові паролі не співпадають';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Новий пароль повинен містити принаймні 6 символів';
        } else {
            // Перевіряємо поточний пароль
            if (password_verify($currentPassword, $user['password'])) {
                if ($userModel->updatePassword($user['id'], $newPassword)) {
                    $message = 'Пароль успішно змінено!';
                } else {
                    $error = 'Помилка при зміні пароля';
                }
            } else {
                $error = 'Поточний пароль невірний';
            }
        }
    }
}

// Оновлення даних користувача після можливих змін
$user = getCurrentUser();

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

<div class="row">
    <!-- Інформація профілю -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Інформація профілю
                </h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                <h4><?= htmlspecialchars($user['full_name']) ?></h4>
                <p class="text-muted"><?= getRoleText($user['role']) ?></p>
                
                <?php if ($user['company_name']): ?>
                    <div class="alert alert-info">
                        <strong>Компанія:</strong><br>
                        <?= htmlspecialchars($user['company_name']) ?>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Логін</h6>
                        <p class="mb-0"><?= htmlspecialchars($user['username']) ?></p>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Створено</h6>
                        <p class="mb-0"><?= formatDate($user['created_at']) ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Статус</h6>
                        <span class="badge bg-success">Активний</span>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Останнє оновлення</h6>
                        <p class="mb-0"><?= formatDate($user['updated_at']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Статистика активності -->
        <?php if ($user['role'] !== 'supplier'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Ваша активність
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Статистика для різних ролей
                    if ($user['role'] === 'warehouse_keeper') {
                        $myRequests = $pdo->prepare("SELECT COUNT(*) FROM purchase_requests WHERE requested_by = ?");
                        $myRequests->execute([$user['id']]);
                        $requestsCount = $myRequests->fetchColumn();
                        
                        $myMovements = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE performed_by = ?");
                        $myMovements->execute([$user['id']]);
                        $movementsCount = $myMovements->fetchColumn();
                        ?>
                        <div class="text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $requestsCount ?></h4>
                                    <small class="text-muted">Заявок створено</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= $movementsCount ?></h4>
                                    <small class="text-muted">Рухів товарів</small>
                                </div>
                            </div>
                        </div>
                    <?php } elseif ($user['role'] === 'procurement_manager') {
                        $myOrders = $pdo->prepare("SELECT COUNT(*) FROM supplier_orders WHERE created_by = ?");
                        $myOrders->execute([$user['id']]);
                        $ordersCount = $myOrders->fetchColumn();
                        
                        $myApprovals = $pdo->prepare("SELECT COUNT(*) FROM purchase_requests WHERE approved_by = ?");
                        $myApprovals->execute([$user['id']]);
                        $approvalsCount = $myApprovals->fetchColumn();
                        ?>
                        <div class="text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $ordersCount ?></h4>
                                    <small class="text-muted">Замовлень створено</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= $approvalsCount ?></h4>
                                    <small class="text-muted">Заявок затверджено</small>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="text-center">
                            <p class="text-muted">Статистика доступна для всіх розділів системи</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Редагування профілю -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Редагування профілю
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Повне ім'я *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        <?php if ($user['role'] === 'supplier'): ?>
                            <div class="col-md-6">
                                <label class="form-label">Назва компанії</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['company_name']) ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Роль</label>
                        <input type="text" class="form-control" value="<?= getRoleText($user['role']) ?>" readonly>
                        <small class="text-muted">Роль користувача може змінити тільки директор</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Зберегти зміни
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Зміна пароля -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2"></i>
                    Зміна пароля
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Поточний пароль *</label>
                        <input type="password" name="current_password" class="form-control" required>
                        <small class="text-muted">Введіть ваш поточний пароль для підтвердження</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Новий пароль *</label>
                            <input type="password" name="new_password" class="form-control" 
                                   minlength="6" required>
                            <small class="text-muted">Мінімум 6 символів</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Підтвердження нового пароля *</label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   minlength="6" required>
                            <small class="text-muted">Повторіть новий пароль</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Увага!</strong> Після зміни пароля вам потрібно буде увійти в систему заново.
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Змінити пароль
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Швидкі посилання -->
<div class="row mt-4">
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
                    <?php if ($user['role'] === 'director'): ?>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/director.php" class="btn btn-primary w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>Головна панель
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/reports.php" class="btn btn-info w-100">
                                <i class="fas fa-chart-bar me-2"></i>Звіти
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/users.php" class="btn btn-secondary w-100">
                                <i class="fas fa-users me-2"></i>Користувачі
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-warning w-100">
                                <i class="fas fa-warehouse me-2"></i>Склад
                            </a>
                        </div>
                    <?php elseif ($user['role'] === 'procurement_manager'): ?>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/procurement_manager.php" class="btn btn-primary w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>Головна панель
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/requests.php" class="btn btn-warning w-100">
                                <i class="fas fa-clipboard-list me-2"></i>Заявки
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/orders.php" class="btn btn-success w-100">
                                <i class="fas fa-shopping-cart me-2"></i>Замовлення
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/materials.php" class="btn btn-info w-100">
                                <i class="fas fa-boxes me-2"></i>Матеріали
                            </a>
                        </div>
                    <?php elseif ($user['role'] === 'warehouse_keeper'): ?>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/warehouse_keeper.php" class="btn btn-primary w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>Головна панель
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/warehouse.php" class="btn btn-warning w-100">
                                <i class="fas fa-warehouse me-2"></i>Склад
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/stock_movements.php" class="btn btn-info w-100">
                                <i class="fas fa-exchange-alt me-2"></i>Рух товарів
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/requests.php" class="btn btn-success w-100">
                                <i class="fas fa-clipboard-list me-2"></i>Заявки
                            </a>
                        </div>
                    <?php else: // supplier ?>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/supplier.php" class="btn btn-primary w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>Головна панель
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/my_orders.php" class="btn btn-warning w-100">
                                <i class="fas fa-list-alt me-2"></i>Мої замовлення
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/dashboard/my_materials.php" class="btn btn-success w-100">
                                <i class="fas fa-box me-2"></i>Мої матеріали
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger w-100">
                                <i class="fas fa-sign-out-alt me-2"></i>Вихід
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

renderDashboardLayout('Профіль користувача', $user['role'], $content);
?>