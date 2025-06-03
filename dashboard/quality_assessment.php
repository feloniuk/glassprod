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
                                    <small class="text-muted"><?= htmlspecialchars($assessment['assessed_by_name']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($assessment['material_name']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($assessment['supplier_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($assessment['supplier_contact']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($assessment['batch_number'] ?? 'Не вказано') ?>
                                </td>
                                <td>
                                    <?php
                                    $gradeColors = [
                                        'premium' => 'bg-success',
                                        'first' => 'bg-primary',
                                        'second' => 'bg-warning',
                                        'third' => 'bg-secondary',
                                        'rejected' => 'bg-danger'
                                    ];
                                    $gradeTexts = [
                                        'premium' => 'Преміум',
                                        'first' => 'Перший',
                                        'second' => 'Другий', 
                                        'third' => 'Третій',
                                        'rejected' => 'Відхилено'
                                    ];
                                    ?>
                                    <span class="badge <?= $gradeColors[$assessment['quality_grade']] ?? 'bg-secondary' ?>">
                                        <?= $gradeTexts[$assessment['quality_grade']] ?? $assessment['quality_grade'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= $assessment['overall_score'] ?>/100</strong>
                                    <br>
                                    <small class="text-muted">
                                        Вологість: <?= $assessment['moisture_content'] ?>%<br>
                                        Крохмаль: <?= $assessment['starch_content'] ?>%
                                    </small>
                                </td>
                                <td>
                                    <strong><?= $assessment['alcohol_yield'] ?></strong> л/т
                                </td>
                                <td>
                                    <?php if ($assessment['is_approved']): ?>
                                        <span class="badge bg-success">Затверджено</span>
                                    <?php elseif ($assessment['quality_grade'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Відхилено</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Очікує</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="showAssessmentDetails(<?= htmlspecialchars(json_encode($assessment)) ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if (!$assessment['is_approved'] && $assessment['quality_grade'] !== 'rejected'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="approveAssessment(<?= $assessment['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="showRejectModal(<?= $assessment['id'] ?>)">
                                                <i class="fas fa-times"></i>
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

<!-- Модальне вікно створення оцінки якості -->
<div class="modal fade" id="createAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Нова оцінка якості зернової сировини
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_assessment">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Зернова сировина *</label>
                            <select name="material_id" class="form-select" required>
                                <option value="">Оберіть сировину</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>">
                                        <?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['category_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Постачальник *</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Оберіть постачальника</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>">
                                        <?= htmlspecialchars($supplier['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Номер партії</label>
                            <input type="text" name="batch_number" class="form-control" 
                                   placeholder="Введіть номер партії">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата лабораторного тесту</label>
                            <input type="date" name="lab_test_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <h6>Показники якості:</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Вологість (%) *</label>
                            <input type="number" name="moisture_content" class="form-control" 
                                   step="0.1" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Білок (%) *</label>
                            <input type="number" name="protein_content" class="form-control" 
                                   step="0.1" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Крохмаль (%) *</label>
                            <input type="number" name="starch_content" class="form-control" 
                                   step="0.1" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Домішки (%) *</label>
                            <input type="number" name="impurities" class="form-control" 
                                   step="0.1" min="0" max="100" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Вихід спирту (л/т) *</label>
                            <input type="number" name="alcohol_yield" class="form-control" 
                                   step="0.1" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Метод тестування</label>
                            <select name="test_method" class="form-select">
                                <option value="">Оберіть метод</option>
                                <option value="ДСТУ 4117">ДСТУ 4117</option>
                                <option value="ISO 712">ISO 712</option>
                                <option value="Лабораторний аналіз">Лабораторний аналіз</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Примітки</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Додаткові примітки щодо якості сировини..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Додаткові тести</label>
                        <textarea name="additional_tests" class="form-control" rows="2" 
                                  placeholder="Опис додаткових тестів та їх результатів..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Створити оцінку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно відхилення оцінки -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-times me-2"></i>Відхилити оцінку якості
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_assessment">
                    <input type="hidden" name="assessment_id" id="rejectAssessmentId">
                    
                    <div class="mb-3">
                        <label class="form-label">Причина відхилення *</label>
                        <textarea name="reject_notes" class="form-control" rows="4" 
                                  placeholder="Опишіть причину відхилення оцінки якості..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Відхилити
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно деталей оцінки -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Деталі оцінки якості
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="assessmentDetailsBody">
                <!-- Контент буде завантажено через JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
function approveAssessment(assessmentId) {
    if (confirm('Затвердити оцінку якості?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'approve_assessment';
        
        const assessmentIdInput = document.createElement('input');
        assessmentIdInput.name = 'assessment_id';
        assessmentIdInput.value = assessmentId;
        
        form.appendChild(actionInput);
        form.appendChild(assessmentIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(assessmentId) {
    document.getElementById('rejectAssessmentId').value = assessmentId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function showAssessmentDetails(assessment) {
    const modal = document.getElementById('detailsModal');
    const body = document.getElementById('assessmentDetailsBody');
    
    const testResults = assessment.test_results ? JSON.parse(assessment.test_results) : {};
    
    body.innerHTML = `
        <div class='row'>
            <div class='col-md-6'>
                <h6>Основна інформація</h6>
                <table class='table table-sm'>
                    <tr><th>Сировина:</th><td>\${assessment.material_name}</td></tr>
                    <tr><th>Постачальник:</th><td>\${assessment.supplier_name}</td></tr>
                    <tr><th>Партія:</th><td>\${assessment.batch_number || 'Не вказано'}</td></tr>
                    <tr><th>Дата оцінки:</th><td>\${new Date(assessment.assessment_date).toLocaleDateString('uk-UA')}</td></tr>
                    <tr><th>Оцінював:</th><td>\${assessment.assessed_by_name}</td></tr>
                </table>
            </div>
            <div class='col-md-6'>
                <h6>Показники якості</h6>
                <table class='table table-sm'>
                    <tr><th>Вологість:</th><td>\${assessment.moisture_content}%</td></tr>
                    <tr><th>Білок:</th><td>\${assessment.protein_content}%</td></tr>
                    <tr><th>Крохмаль:</th><td>\${assessment.starch_content}%</td></tr>
                    <tr><th>Домішки:</th><td>\${assessment.impurities}%</td></tr>
                    <tr><th>Вихід спирту:</th><td>\${assessment.alcohol_yield} л/т</td></tr>
                    <tr><th>Загальна оцінка:</th><td><strong>\${assessment.overall_score}/100</strong></td></tr>
                </table>
            </div>
        </div>
        \${assessment.notes ? `<div class='mt-3'><h6>Примітки</h6><p class='bg-light p-3 rounded'>\${assessment.notes}</p></div>` : ''}
        \${testResults.additional_tests ? `<div class='mt-3'><h6>Додаткові тести</h6><p class='bg-light p-3 rounded'>\${testResults.additional_tests}</p></div>` : ''}
    `;
    
    new bootstrap.Modal(modal).show();
}
";

renderDashboardLayout('Оцінка якості зернової сировини', $user['role'], $content, '', $additionalJS);
?>