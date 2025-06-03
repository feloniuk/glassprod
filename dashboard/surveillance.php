<?php
// Файл: dashboard/surveillance.php
// Страница видеонаблюдения

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу - только директор
$user = checkRole(['director']);

$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_camera_settings') {
        $cameraId = $_POST['camera_id'] ?? '';
        $cameraName = trim($_POST['camera_name'] ?? '');
        $cameraUrl = trim($_POST['camera_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($cameraId) && !empty($cameraName)) {
            // В реальном проекте здесь бы было сохранение в базу данных
            $message = "Налаштування камери '{$cameraName}' збережено!";
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'start_recording') {
        $cameraId = $_POST['camera_id'] ?? '';
        if (!empty($cameraId)) {
            // В реальном проекте здесь бы был запуск записи
            $message = "Запис з камери #{$cameraId} розпочато!";
        }
    }
    
    elseif ($action === 'stop_recording') {
        $cameraId = $_POST['camera_id'] ?? '';
        if (!empty($cameraId)) {
            // В реальном проекте здесь бы была остановка записи
            $message = "Запис з камери #{$cameraId} зупинено!";
        }
    }
}

// Конфигурация камер (в реальном проекте из базы данных)
$cameras = [
    [
        'id' => 1,
        'name' => 'Вхід до заводу',
        'location' => 'Центральний вхід',
        'status' => 'online',
        'recording' => true,
        'resolution' => '1920x1080',
        'fps' => 30,
        'stream_url' => 'rtsp://demo:demo@stream.electronicmind.com/cgi-bin/faststream.jpg?stream=half&fps=15&rand=COUNTER',
        'type' => 'fixed'
    ],
    [
        'id' => 2,
        'name' => 'Виробнича зона 1',
        'location' => 'Цех скловаріння',
        'status' => 'online',
        'recording' => true,
        'resolution' => '1920x1080',
        'fps' => 25,
        'stream_url' => 'https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-mp4-file.mp4',
        'type' => 'ptz'
    ],
    [
        'id' => 3,
        'name' => 'Склад готової продукції',
        'location' => 'Головний склад',
        'status' => 'online',
        'recording' => false,
        'resolution' => '1280x720',
        'fps' => 15,
        'stream_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
        'type' => 'fixed'
    ],
    [
        'id' => 4,
        'name' => 'Паркувальна зона',
        'location' => 'Парковка співробітників',
        'status' => 'offline',
        'recording' => false,
        'resolution' => '1280x720',
        'fps' => 20,
        'stream_url' => '',
        'type' => 'fixed'
    ],
    [
        'id' => 5,
        'name' => 'Зона завантаження',
        'location' => 'Вантажний майданчик',
        'status' => 'online',
        'recording' => true,
        'resolution' => '1920x1080',
        'fps' => 30,
        'stream_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
        'type' => 'ptz'
    ],
    [
        'id' => 6,
        'name' => 'Офісна зона',
        'location' => 'Адміністративний корпус',
        'status' => 'online',
        'recording' => false,
        'resolution' => '1280x720',
        'fps' => 15,
        'stream_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_640x360_1mb.mp4',
        'type' => 'fixed'
    ]
];

// Статистика
$totalCameras = count($cameras);
$onlineCameras = count(array_filter($cameras, function($cam) { return $cam['status'] === 'online'; }));
$recordingCameras = count(array_filter($cameras, function($cam) { return $cam['recording']; }));

// Получение выбранного режима отображения
$viewMode = $_GET['mode'] ?? 'grid';
$selectedCamera = $_GET['camera'] ?? '';

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

<!-- Статистика и управление -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-video fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalCameras ?></h3>
                <p class="mb-0">Всього камер</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-circle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $onlineCameras ?></h3>
                <p class="mb-0">Онлайн</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <i class="fas fa-record-vinyl fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $recordingCameras ?></h3>
                <p class="mb-0">Записують</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalCameras - $onlineCameras ?></h3>
                <p class="mb-0">Офлайн</p>
            </div>
        </div>
    </div>
</div>

<!-- Панель управления -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-cog me-2"></i>
            Панель управління
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-success" onclick="startAllRecording()">
                <i class="fas fa-play me-1"></i>Запустити всі записи
            </button>
            <button class="btn btn-warning" onclick="stopAllRecording()">
                <i class="fas fa-stop me-1"></i>Зупинити всі записи
            </button>
            <button class="btn btn-info" onclick="refreshAllCameras()">
                <i class="fas fa-sync-alt me-1"></i>Оновити
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Режим відображення</label>
                <select class="form-select" onchange="changeViewMode(this.value)">
                    <option value="grid" <?= $viewMode === 'grid' ? 'selected' : '' ?>>Сітка (2x3)</option>
                    <option value="large" <?= $viewMode === 'large' ? 'selected' : '' ?>>Велика сітка (1x2)</option>
                    <option value="single" <?= $viewMode === 'single' ? 'selected' : '' ?>>Одна камера</option>
                    <option value="quad" <?= $viewMode === 'quad' ? 'selected' : '' ?>>Квадрат (2x2)</option>
                </select>
            </div>
            
            <?php if ($viewMode === 'single'): ?>
            <div class="col-md-3">
                <label class="form-label">Вибрати камеру</label>
                <select class="form-select" onchange="selectCamera(this.value)">
                    <option value="">Оберіть камеру</option>
                    <?php foreach ($cameras as $camera): ?>
                        <option value="<?= $camera['id'] ?>" <?= $selectedCamera == $camera['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($camera['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Якість відео</label>
                <select class="form-select" onchange="changeQuality(this.value)">
                    <option value="high">Висока (1080p)</option>
                    <option value="medium" selected>Середня (720p)</option>
                    <option value="low">Низька (480p)</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Автооновлення</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                    <label class="form-check-label" for="autoRefresh">
                        Кожні 30 сек
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Камеры в зависимости от режима отображения -->
<?php if ($viewMode === 'single' && $selectedCamera): ?>
    <!-- Режим одной камеры -->
    <?php
    $camera = array_filter($cameras, function($cam) use ($selectedCamera) {
        return $cam['id'] == $selectedCamera;
    });
    $camera = reset($camera);
    ?>
    
    <?php if ($camera): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-video me-2"></i>
                    <?= htmlspecialchars($camera['name']) ?>
                    <span class="badge bg-<?= $camera['status'] === 'online' ? 'success' : 'danger' ?> ms-2">
                        <?= $camera['status'] === 'online' ? 'Онлайн' : 'Офлайн' ?>
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <?php if ($camera['type'] === 'ptz'): ?>
                        <button class="btn btn-outline-primary" onclick="showPTZControls(<?= $camera['id'] ?>)">
                            <i class="fas fa-arrows-alt me-1"></i>PTZ
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($camera['recording']): ?>
                        <button class="btn btn-outline-danger" onclick="stopRecording(<?= $camera['id'] ?>)">
                            <i class="fas fa-stop me-1"></i>Зупинити запис
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline-success" onclick="startRecording(<?= $camera['id'] ?>)">
                            <i class="fas fa-record-vinyl me-1"></i>Почати запис
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-info" onclick="takeSnapshot(<?= $camera['id'] ?>)">
                        <i class="fas fa-camera me-1"></i>Знімок
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="camera-container" style="height: 600px;">
                    <?php if ($camera['status'] === 'online'): ?>
                        <div class="video-placeholder d-flex align-items-center justify-content-center bg-dark text-white" 
                             style="width: 100%; height: 100%; position: relative;">
                            <div class="text-center">
                                <i class="fas fa-video fa-5x mb-3 text-primary"></i>
                                <h4><?= htmlspecialchars($camera['name']) ?></h4>
                                <p><?= htmlspecialchars($camera['location']) ?></p>
                                <p><?= $camera['resolution'] ?> @ <?= $camera['fps'] ?> FPS</p>
                                
                                <!-- Симуляция видеопотока -->
                                <div class="recording-indicator" style="position: absolute; top: 10px; left: 10px;">
                                    <?php if ($camera['recording']): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-circle recording-pulse me-1"></i>REC
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="timestamp" style="position: absolute; bottom: 10px; right: 10px;">
                                    <span class="badge bg-dark" id="timestamp_<?= $camera['id'] ?>">
                                        <?= date('d.m.Y H:i:s') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="offline-placeholder d-flex align-items-center justify-content-center bg-secondary text-white" 
                             style="width: 100%; height: 100%;">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle fa-5x mb-3 text-warning"></i>
                                <h4>Камера офлайн</h4>
                                <p><?= htmlspecialchars($camera['name']) ?></p>
                                <button class="btn btn-warning" onclick="reconnectCamera(<?= $camera['id'] ?>)">
                                    <i class="fas fa-plug me-1"></i>Переподключити
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Сеточные режимы отображения -->
    <div class="row">
        <?php
        $colClass = 'col-md-6';
        $displayCameras = $cameras;
        $height = '300px';
        
        switch ($viewMode) {
            case 'large':
                $colClass = 'col-md-6';
                $displayCameras = array_slice($cameras, 0, 2);
                $height = '400px';
                break;
            case 'quad':
                $colClass = 'col-md-6';
                $displayCameras = array_slice($cameras, 0, 4);
                $height = '350px';
                break;
            case 'grid':
            default:
                $colClass = 'col-md-4';
                $height = '250px';
                break;
        }
        ?>
        
        <?php foreach ($displayCameras as $camera): ?>
            <div class="<?= $colClass ?> mb-4">
                <div class="card camera-card" data-camera-id="<?= $camera['id'] ?>">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($camera['name']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($camera['location']) ?></small>
                        </div>
                        <div class="d-flex gap-1">
                            <span class="badge bg-<?= $camera['status'] === 'online' ? 'success' : 'danger' ?>">
                                <?= $camera['status'] === 'online' ? 'ON' : 'OFF' ?>
                            </span>
                            
                            <?php if ($camera['recording']): ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-circle recording-pulse me-1"></i>REC
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="camera-container" style="height: <?= $height ?>;">
                            <?php if ($camera['status'] === 'online'): ?>
                                <div class="video-placeholder d-flex align-items-center justify-content-center bg-dark text-white" 
                                     style="width: 100%; height: 100%; position: relative; cursor: pointer;"
                                     onclick="openFullscreen(<?= $camera['id'] ?>)">
                                    <div class="text-center">
                                        <i class="fas fa-video fa-3x mb-2 text-primary"></i>
                                        <p class="mb-0"><?= $camera['resolution'] ?></p>
                                        <small><?= $camera['fps'] ?> FPS</small>
                                        
                                        <!-- Timestamp -->
                                        <div class="timestamp" style="position: absolute; bottom: 5px; right: 5px;">
                                            <span class="badge bg-dark" style="font-size: 10px;" id="timestamp_<?= $camera['id'] ?>">
                                                <?= date('H:i:s') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="offline-placeholder d-flex align-items-center justify-content-center bg-secondary text-white" 
                                     style="width: 100%; height: 100%;">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
                                        <p class="mb-0">Офлайн</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group btn-group-sm">
                                <?php if ($camera['recording']): ?>
                                    <button class="btn btn-outline-danger" onclick="stopRecording(<?= $camera['id'] ?>)" title="Зупинити запис">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-success" onclick="startRecording(<?= $camera['id'] ?>)" title="Почати запис">
                                        <i class="fas fa-record-vinyl"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-info" onclick="takeSnapshot(<?= $camera['id'] ?>)" title="Зробити знімок">
                                    <i class="fas fa-camera"></i>
                                </button>
                                
                                <?php if ($camera['type'] === 'ptz'): ?>
                                    <button class="btn btn-outline-primary" onclick="showPTZControls(<?= $camera['id'] ?>)" title="PTZ управління">
                                        <i class="fas fa-arrows-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn btn-outline-secondary btn-sm" onclick="showCameraSettings(<?= $camera['id'] ?>)" title="Налаштування">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Быстрые действия -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Швидкі дії та записи
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary w-100" onclick="showRecordingsArchive()">
                            <i class="fas fa-folder me-2"></i>
                            Архів записів
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-success w-100" onclick="exportVideo()">
                            <i class="fas fa-download me-2"></i>
                            Експорт відео
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-warning w-100" onclick="showAlerts()">
                            <i class="fas fa-bell me-2"></i>
                            Сповіщення
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-info w-100" onclick="systemDiagnostics()">
                            <i class="fas fa-stethoscope me-2"></i>
                            Діагностика
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальные окна -->

<!-- PTZ Controls Modal -->
<div class="modal fade" id="ptzModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-arrows-alt me-2"></i>PTZ Управління
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h6 id="ptzCameraName">Камера</h6>
                </div>
                
                <!-- Pan/Tilt Controls -->
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <div class="ptz-controls">
                            <div class="mb-2">
                                <button class="btn btn-outline-primary" onclick="ptzMove('up')">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                            </div>
                            <div class="mb-2">
                                <button class="btn btn-outline-primary me-2" onclick="ptzMove('left')">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="btn btn-primary" onclick="ptzMove('center')">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                                <button class="btn btn-outline-primary ms-2" onclick="ptzMove('right')">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary" onclick="ptzMove('down')">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Zoom Controls -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Зум</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary" onclick="ptzZoom('out')">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <input type="range" class="form-range" min="1" max="10" value="5" id="zoomSlider">
                            <button class="btn btn-outline-secondary" onclick="ptzZoom('in')">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Preset Positions -->
                <div class="row">
                    <div class="col-12">
                        <label class="form-label">Збережені позиції</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-info" onclick="gotoPreset(1)">Позиція 1</button>
                            <button class="btn btn-sm btn-outline-info" onclick="gotoPreset(2)">Позиція 2</button>
                            <button class="btn btn-sm btn-outline-info" onclick="gotoPreset(3)">Позиція 3</button>
                            <button class="btn btn-sm btn-success" onclick="savePreset()">
                                <i class="fas fa-save me-1"></i>Зберегти
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Camera Settings Modal -->
<div class="modal fade" id="cameraSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cog me-2"></i>Налаштування камери
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_camera_settings">
                    <input type="hidden" name="camera_id" id="settingsCameraId">
                    
                    <div class="mb-3">
                        <label class="form-label">Назва камери</label>
                        <input type="text" name="camera_name" id="settingsCameraName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL потоку</label>
                        <input type="url" name="camera_url" id="settingsCameraUrl" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Розташування</label>
                        <input type="text" name="camera_location" id="settingsCameraLocation" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="settingsIsActive">
                            <label class="form-check-label" for="settingsIsActive">
                                Активна камера
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="motion_detection" id="settingsMotionDetection">
                            <label class="form-check-label" for="settingsMotionDetection">
                                Детекція руху
                            </label>
                        </div>
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

<?php
$content = ob_get_clean();

$additionalCSS = "
.camera-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.camera-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.recording-pulse {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.video-placeholder {
    border: 2px dashed #6c757d;
    transition: border-color 0.3s ease;
}

.video-placeholder:hover {
    border-color: #0d6efd;
}

.ptz-controls button {
    width: 50px;
    height: 50px;
}

.camera-container {
    overflow: hidden;
    border-radius: 8px;
}

.offline-placeholder {
    opacity: 0.7;
}
";

$additionalJS = "
let currentCameraId = null;
let autoRefreshInterval = null;

// Управление камерами
function changeViewMode(mode) {
    const url = new URL(window.location);
    url.searchParams.set('mode', mode);
    window.location.href = url.toString();
}

function selectCamera(cameraId) {
    const url = new URL(window.location);
    url.searchParams.set('camera', cameraId);
    window.location.href = url.toString();
}

function changeQuality(quality) {
    showAlert('Якість відео змінено на: ' + quality, 'info');
}

// Запись
function startRecording(cameraId) {
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=start_recording&camera_id=' + cameraId
    })
    .then(() => {
        showAlert('Запис розпочато для камери #' + cameraId, 'success');
        setTimeout(() => window.location.reload(), 1000);
    })
    .catch(() => showAlert('Помилка при запуску запису', 'danger'));
}

function stopRecording(cameraId) {
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=stop_recording&camera_id=' + cameraId
    })
    .then(() => {
        showAlert('Запис зупинено для камери #' + cameraId, 'warning');
        setTimeout(() => window.location.reload(), 1000);
    })
    .catch(() => showAlert('Помилка при зупинці запису', 'danger'));
}

function startAllRecording() {
    if (confirm('Запустити запис на всіх камерах?')) {
        showAlert('Запис запущено на всіх доступних камерах', 'success');
        setTimeout(() => window.location.reload(), 1500);
    }
}

function stopAllRecording() {
    if (confirm('Зупинити запис на всіх камерах?')) {
        showAlert('Запис зупинено на всіх камерах', 'warning');
        setTimeout(() => window.location.reload(), 1500);
    }
}

// PTZ управление
function showPTZControls(cameraId) {
    currentCameraId = cameraId;
    const camera = getCameraById(cameraId);
    if (camera) {
        document.getElementById('ptzCameraName').textContent = camera.name;
        new bootstrap.Modal(document.getElementById('ptzModal')).show();
    }
}

function ptzMove(direction) {
    const directions = {
        'up': 'Вгору',
        'down': 'Вниз', 
        'left': 'Ліворуч',
        'right': 'Праворуч',
        'center': 'Центр'
    };
    
    showAlert('PTZ рух: ' + directions[direction], 'info');
}

function ptzZoom(direction) {
    const text = direction === 'in' ? 'Приближення' : 'Віддалення';
    showAlert('Зум: ' + text, 'info');
}

function gotoPreset(preset) {
    showAlert('Перехід до збереженої позиції ' + preset, 'info');
}

function savePreset() {
    showAlert('Поточна позиція збережена', 'success');
}

// Настройки камеры
function showCameraSettings(cameraId) {
    const camera = getCameraById(cameraId);
    if (camera) {
        document.getElementById('settingsCameraId').value = camera.id;
        document.getElementById('settingsCameraName').value = camera.name;
        document.getElementById('settingsCameraLocation').value = camera.location;
        document.getElementById('settingsIsActive').checked = camera.status === 'online';
        
        new bootstrap.Modal(document.getElementById('cameraSettingsModal')).show();
    }
}

// Дополнительные функции
function takeSnapshot(cameraId) {
    showAlert('Знімок збережено для камери #' + cameraId, 'success');
}

function reconnectCamera(cameraId) {
    showAlert('Спроба переподключення камери #' + cameraId, 'info');
    setTimeout(() => {
        showAlert('Камера #' + cameraId + ' підключена', 'success');
        window.location.reload();
    }, 2000);
}

function openFullscreen(cameraId) {
    const url = new URL(window.location);
    url.searchParams.set('mode', 'single');
    url.searchParams.set('camera', cameraId);
    window.location.href = url.toString();
}

function refreshAllCameras() {
    showAlert('Оновлення всіх камер...', 'info');
    setTimeout(() => window.location.reload(), 1000);
}

// Быстрые действия
function showRecordingsArchive() {
    showAlert('Функція архіву записів буде доступна в наступних версіях', 'info');
}

function exportVideo() {
    showAlert('Функція експорту відео буде доступна в наступних версіях', 'info');
}

function showAlerts() {
    showAlert('Система сповіщень: Всі камери працюють нормально', 'success');
}

function systemDiagnostics() {
    showAlert('Діагностика системи: Всі компоненти функціонують коректно', 'success');
}

// Вспомогательные функции
function getCameraById(cameraId) {
    const cameras = " . json_encode($cameras) . ";
    return cameras.find(camera => camera.id == cameraId);
}

// Автообновление времени
function updateTimestamps() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('uk-UA');
    
    document.querySelectorAll('[id^=\"timestamp_\"]').forEach(element => {
        element.textContent = timeString;
    });
}

// Автообновление
function setupAutoRefresh() {
    const checkbox = document.getElementById('autoRefresh');
    
    function startAutoRefresh() {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        autoRefreshInterval = setInterval(() => {
            updateTimestamps();
            // В реальном проекте здесь бы было обновление видеопотоков
        }, 30000);
    }
    
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }
    
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            startAutoRefresh();
            showAlert('Автооновлення увімкнено', 'info');
        } else {
            stopAutoRefresh();
            showAlert('Автооновлення вимкнено', 'info');
        }
    });
    
    // Запуск по умолчанию
    if (checkbox.checked) {
        startAutoRefresh();
    }
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    setupAutoRefresh();
    updateTimestamps();
    
    // Обновление времени каждую секунду
    setInterval(updateTimestamps, 1000);
});
";

renderDashboardLayout('Відеонаблюдення', 'director', $content, $additionalCSS, $additionalJS);
?>