<?php
// Файл: dashboard/quality_assessment.php
// Оцінка якості зернової сировини

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу - тільки менеджер з закупівель та директор
$user = checkRole(['director', 'procurement_manager']);

// Ініціалізація моделей
$qualityAssessment = new QualityAssessment($pdo);
$grainMaterial = new GrainMaterial($pdo);
$userModel = new User($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_assessment') {
        $data = [
            'material_id' => $_POST['material_id'] ?? 0,
            'supplier_id' => $_POST['supplier_id'] ?? 0,
            'assessed_by' => $user['id'],
            'moisture_content' => (float)($_POST['moisture_content'] ?? 0),
            'protein_content' => (float)($_POST['protein_content'] ?? 0),
            'starch_content' => (float)($_POST['starch_content'] ?? 0),
            'impurities' => (float)($_POST['impurities'] ?? 0),
            'alcohol_yield' => (float)($_POST['alcohol_yield'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'batch_number' => trim($_POST['batch_number'] ?? ''),
            'test_results' => json_encode([
                'lab_test_date' => $_POST['lab_test_date'] ?? '',
                'test_method' => $_POST['test_method'] ?? '',
                'equipment_used' => $_POST['equipment_used'] ?? '',
                'additional_tests' => $_POST['additional_tests'] ?? ''
            ])
        ];
        
        if ($data['material_id'] && $data['supplier_id']) {
            $assessmentId = $qualityAssessment->createAssessment($data);
            if ($assessmentId) {
                $message = 'Оцінку якості успішно створено!';
            } else {
                $error = 'Помилка при створенні оцінки якості';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'approve_assessment') {
        $assessmentId = $_POST['assessment_id'] ?? 0;
        if ($assessmentId && $qualityAssessment->approveAssessment($assessmentId, $user['id'])) {
            $message = 'Оцінку якості затверджено!';
        } else {
            $error = 'Помилка при затвердженні оцінки';
        }
    }
    
    elseif ($action === 'reject_assessment') {
        $assessmentId = $_POST['assessment_id'] ?? 0;
        $rejectNotes = $_POST['reject_notes'] ?? '';
        if ($assessmentId && $qualityAssessment->rejectAssessment($assessmentId, $rejectNotes)) {
            $message = 'Оцінку якості відхилено!';
        } else {
            $error = 'Помилка при відхиленні оцінки';
        }
    }
}

// Фільтри
$statusFilter = $_GET['status'] ?? '';
$supplierFilter = $_GET['supplier'] ?? '';
$materialFilter = $_GET['material'] ?? '';
$gradeFilter = $_GET['grade'] ?? '';

// Отримання оцінок якості
if ($statusFilter !== '') {
    $assessments = $qualityAssessment->getByStatus($statusFilter === 'approved' ? 1 : 0);
} else {
    $assessments = $qualityAssessment->getAllWithDetails();
}

// Застосування додаткових фільтрів
if ($supplierFilter) {
    $assessments = array_filter($assessments, function($assessment) use ($supplierFilter) {
        return $assessment['supplier_id'] == $supplierFilter;
    });
}

if ($materialFilter) {
    $assessments = array_filter($assessments, function($assessment) use ($materialFilter) {
        return $assessment['material_id'] == $materialFilter;
    });
}

if ($gradeFilter) {
    $assessments = array_filter($assessments, function($assessment) use ($gradeFilter) {
        return $assessment['quality_grade'] === $gradeFilter;
    });
}

// Отримання даних для форми
$materials = $grainMaterial->getAllWithCategories();
$suppliers = $userModel->getSuppliers();

// Статистика
$supplierStats = $qualityAssessment->getSupplierStats();
$qualityTrends = $qualityAssessment->getQualityTrends(30);

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
                <i class="fas fa-microscope fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count($assessments) ?></h3>
                <p class="mb-0">Оцінок якості</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($assessments, function($a) { return $a['is_approved']; })) ?></h3>
                <p class="mb-0">Затверджено</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($assessments, function($a) { return !$a['is_approved'] && $a['quality_grade'] !== 'rejected'; })) ?></h3>
                <p class="mb-0">Очікує затвердження</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-3x mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($assessments, function($a) { return $a['quality_grade'] === 'rejected'; })) ?></h3>
                <p class="mb-0">Відхилено</p>
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
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select name="status" class="form-select">
                            <option value="">Всі статуси</option>
                            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Затверджено</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Очікує</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Клас якості</label>
                        <select name="grade" class="form-select">
                            <option value="">Всі класи</option>
                            <option value="premium" <?= $gradeFilter === 'premium' ? 'selected' : '' ?>>Преміум</option>
                            <option value="first" <?= $gradeFilter === 'first' ? 'selected' : '' ?>>Перший</option>
                            <option value="second" <?= $gradeFilter === 'second' ? 'selected' : '' ?>>Другий</option>
                            <option value="third" <?= $gradeFilter === 'third' ? 'selected' : '' ?>>Третій</option>
                            <option value="rejected" <?= $gradeFilter === 'rejected' ? 'selected' : '' ?>>Відхилено</option>
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
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Фільтр
                            </button>
                            <a href="<?= BASE_URL ?>/dashboard/quality_assessment.php" class="btn btn-outline-secondary">
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
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                    <i class="fas fa-plus me-2"></i>Нова оцінка якості
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Список оцінок якості -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list-alt me-2"></i>
            Оцінки якості (<?= count($assessments) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($assessments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-microscope fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Оцінок якості не знайдено</h5>
                <p class="text-muted">Створіть нову оцінку або змініть фільтри</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Дата оцінки</th>
                            <th>Сировина</th>
                            <th>Постачальник</th>
                            <th>Партія</th>
                            <th>Клас якості</th>
                            <th>Оцінка</th>
                            <th>Вихід спирту</th>
                            <th>Статус</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessments as $assessment): ?>
                            <tr class="<?= $assessment['quality_grade'] === 'rejected' ? 'table-danger' : ($assessment['is_approved'] ? 'table-success' : 'table-warning') ?>">
                                <td>
                                    <?= formatDateTime($assessment['assessment_date']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($assessment['