<?php
// Файл: dashboard/data_input.php
// Страница для ввода данных мониторинга (симуляция поступления данных от оборудования)

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу - только директор и начальник склада
$user = checkRole(['director', 'warehouse_keeper']);

$message = '';
$error = '';

// Обработка добавления новой записи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_record') {
        $name = trim($_POST['name'] ?? '');
        $parameter = $_POST['parameter'] ?? '';
        $dates = $_POST['dates'] ?? '';
        $times = $_POST['times'] ?? '';
        
        if (!empty($name) && !empty($parameter) && !empty($dates) && !empty($times)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO data (Name, Parameter, Dates, Times) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$name, $parameter, $dates, $times]);
                
                if ($result) {
                    $message = 'Запис успішно додано!';
                } else {
                    $error = 'Помилка при додаванні запису';
                }
            } catch (Exception $e) {
                $error = 'Помилка: ' . $e->getMessage();
            }
        } else {
            $error = 'Заповніть всі поля';
        }
    }
    
    elseif ($action === 'generate_data') {
        $hours = (int)($_POST['hours'] ?? 1);
        $baseValue = (float)($_POST['base_value'] ?? 60.0);
        $variation = (float)($_POST['variation'] ?? 2.0);
        
        if ($hours > 0 && $hours <= 24) {
            try {
                $stmt = $pdo->prepare("INSERT INTO data (Name, Parameter, Dates, Times) VALUES (?, ?, ?, ?)");
                $currentTime = time();
                $addedRecords = 0;
                
                for ($i = 0; $i < $hours * 12; $i++) { // Каждые 5 минут
                    $timestamp = $currentTime - ($i * 300); // 300 секунд = 5 минут
                    $dateFormatted = date('d.m.Y', $timestamp);
                    $timeFormatted = date('H:i:s', $timestamp);
                    
                    // Генерируем значение с небольшой вариацией
                    $variation_factor = (rand(-100, 100) / 100) * $variation;
                    $value = $baseValue + $variation_factor;
                    
                    $result = $stmt->execute([
                        'Продуктивність ділянки ПТЛ, т/год',
                        round($value, 2),
                        $dateFormatted,
                        $timeFormatted
                    ]);
                    
                    if ($result) {
                        $addedRecords++;
                    }
                }
                
                if ($addedRecords > 0) {
                    $message = "Згенеровано $addedRecords записів за $hours годин(и)";
                } else {
                    $error = 'Не вдалося згенерувати записи';
                }
            } catch (Exception $e) {
                $error = 'Помилка при генерації даних: ' . $e->getMessage();
            }
        } else {
            $error = 'Невірне значення кількості годин (1-24)';
        }
    }
    
    elseif ($action === 'delete_old') {
        $days = (int)($_POST['days'] ?? 30);
        
        if ($days > 0) {
            try {
                $cutoffDate = date('d.m.Y', strtotime("-$days days"));
                $stmt = $pdo->prepare("DELETE FROM data WHERE STR_TO_DATE(Dates, '%d.%m.%Y') < STR_TO_DATE(?, '%d.%m.%Y')");
                $result = $stmt->execute([$cutoffDate]);
                
                $deletedRows = $stmt->rowCount();
                if ($deletedRows > 0) {
                    $message = "Видалено $deletedRows старих записів (старіше $days днів)";
                } else {
                    $message = 'Старих записів для видалення не знайдено';
                }
            } catch (Exception $e) {
                $error = 'Помилка при видаленні старих записів: ' . $e->getMessage();
            }
        } else {
            $error = 'Невірне значення кількості днів';
        }
    }
}

// Получение статистики
$totalRecords = $pdo->query("SELECT COUNT(*) FROM data")->fetchColumn();
$lastRecord = $pdo->query("SELECT * FROM data ORDER BY ID DESC LIMIT 1")->fetch();
$oldestRecord = $pdo->query("SELECT * FROM data ORDER BY ID ASC LIMIT 1")->fetch();

// Статистика по дням
$dailyStats = $pdo->query("
    SELECT 
        Dates,
        COUNT(*) as records_count,
        AVG(Parameter) as avg_parameter,
        MIN(Parameter) as min_parameter,
        MAX(Parameter) as max_parameter
    FROM data 
    GROUP BY Dates 
    ORDER BY STR_TO_DATE(Dates, '%d.%m.%Y') DESC 
    LIMIT 7
")->fetchAll();

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
                <i class="fas fa-database fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($totalRecords) ?></h3>
                <p class="mb-0">Всього записів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $lastRecord ? date('H:i', strtotime($lastRecord['Times'])) : '—' ?></h3>
                <p class="mb-0">Останній запис</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-calendar fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $lastRecord ? htmlspecialchars($lastRecord['Dates']) : '—' ?></h3>
                <p class="mb-0">Остання дата</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-tachometer-alt fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $lastRecord ? number_format($lastRecord['Parameter'], 2) : '—' ?></h3>
                <p class="mb-0">Останнє значення</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ручное добавление записи -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Додати новий запис
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_record">
                    
                    <div class="mb-3">
                        <label class="form-label">Назва параметра</label>
                        <select name="name" class="form-select" required>
                            <option value="Продуктивність ділянки ПТЛ, т/год">Продуктивність ділянки ПТЛ, т/год</option>
                            <option value="Температура печі, °C">Температура печі, °C</option>
                            <option value="Витрата газу, м³/год">Витрата газу, м³/год</option>
                            <option value="Тиск в системі, атм">Тиск в системі, атм</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Значення параметра</label>
                        <input type="number" name="parameter" class="form-control" step="0.01" required 
                               placeholder="Введіть значення">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Дата</label>
                            <input type="text" name="dates" class="form-control" 
                                   value="<?= date('d.m.Y') ?>" placeholder="дд.мм.рррр" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Час</label>
                            <input type="text" name="times" class="form-control" 
                                   value="<?= date('H:i:s') ?>" placeholder="гг:хх:сс" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-plus me-1"></i>Додати запис
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Генерация тестовых данных -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Генерація тестових даних
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="generate_data">
                    
                    <div class="mb-3">
                        <label class="form-label">Кількість годин для генерації</label>
                        <input type="number" name="hours" class="form-control" min="1" max="24" value="1" required>
                        <small class="text-muted">Запис кожні 5 хвилин</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Базове значення</label>
                        <input type="number" name="base_value" class="form-control" step="0.1" value="60.0" required>
                        <small class="text-muted">Середнє значення навколо якого генерувати</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Варіація (±)</label>
                        <input type="number" name="variation" class="form-control" step="0.1" value="2.0" required>
                        <small class="text-muted">Максимальне відхилення від базового значення</small>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-magic me-1"></i>Згенерувати дані
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Статистика по дням -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Статистика по днях (останні 7 днів)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($dailyStats)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Немає даних для відображення</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Кількість записів</th>
                                    <th>Середнє значення</th>
                                    <th>Мінімум</th>
                                    <th>Максимум</th>
                                    <th>Діапазон</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dailyStats as $stat): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($stat['Dates']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $stat['records_count'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= number_format($stat['avg_parameter'], 2) ?></strong>
                                            <small class="text-muted">т/год</small>
                                        </td>
                                        <td class="text-warning">
                                            <?= number_format($stat['min_parameter'], 2) ?>
                                        </td>
                                        <td class="text-success">
                                            <?= number_format($stat['max_parameter'], 2) ?>
                                        </td>
                                        <td>
                                            <span class="text-info">
                                                <?= number_format($stat['max_parameter'] - $stat['min_parameter'], 2) ?>
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
</div>

<!-- Утилиты -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-trash me-2"></i>
                    Очищення старих даних
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirm('Ви впевнені, що хочете видалити старі записи?')">
                    <input type="hidden" name="action" value="delete_old">
                    
                    <div class="mb-3">
                        <label class="form-label">Видалити записи старіше (днів)</label>
                        <input type="number" name="days" class="form-control" min="1" value="30" required>
                        <small class="text-muted">Записи старіше вказаної кількості днів будуть видалені</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Увага!</strong> Ця операція незворотна. Видалені дані не можна буде відновити.
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-trash me-1"></i>Видалити старі записи
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Інформація про дані
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted">Перший запис</h6>
                        <p class="mb-1">
                            <?= $oldestRecord ? htmlspecialchars($oldestRecord['Dates']) : 'Немає даних' ?>
                        </p>
                        <small class="text-muted">
                            <?= $oldestRecord ? htmlspecialchars($oldestRecord['Times']) : '' ?>
                        </small>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Останній запис</h6>
                        <p class="mb-1">
                            <?= $lastRecord ? htmlspecialchars($lastRecord['Dates']) : 'Немає даних' ?>
                        </p>
                        <small class="text-muted">
                            <?= $lastRecord ? htmlspecialchars($lastRecord['Times']) : '' ?>
                        </small>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/dashboard/analytics.php" class="btn btn-primary">
                        <i class="fas fa-chart-line me-2"></i>Переглянути аналітику
                    </a>
                    <button class="btn btn-outline-info" onclick="refreshStats()">
                        <i class="fas fa-sync-alt me-2"></i>Оновити статистику
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Последние записи -->
<?php if ($totalRecords > 0): ?>
    <?php
    $recentRecords = $pdo->query("SELECT * FROM data ORDER BY ID DESC LIMIT 10")->fetchAll();
    ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history me-2"></i>
                Останні 10 записів
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Значення</th>
                            <th>Дата</th>
                            <th>Час</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRecords as $record): ?>
                            <tr>
                                <td><?= $record['ID'] ?></td>
                                <td><?= htmlspecialchars($record['Name']) ?></td>
                                <td>
                                    <strong><?= number_format($record['Parameter'], 2) ?></strong>
                                    <small class="text-muted">т/год</small>
                                </td>
                                <td><?= htmlspecialchars($record['Dates']) ?></td>
                                <td><?= htmlspecialchars($record['Times']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

$additionalJS = "
function refreshStats() {
    window.location.reload();
}

// Автообновление времени в форме каждую секунду
setInterval(function() {
    const now = new Date();
    const timeInput = document.querySelector('input[name=\"times\"]');
    if (timeInput && timeInput === document.activeElement) {
        // Не обновляем если пользователь редактирует поле
        return;
    }
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    if (timeInput) {
        timeInput.value = hours + ':' + minutes + ':' + seconds;
    }
}, 1000);

// Автообновление даты
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name=\"dates\"]');
    if (dateInput) {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        dateInput.value = day + '.' + month + '.' + year;
    }
});
";

renderDashboardLayout('Ввід даних моніторингу', $user['role'], $content, '', $additionalJS);
?>