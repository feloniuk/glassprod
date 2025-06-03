<?php
// Файл: dashboard/requests.php
// Управління заявками на закупку

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу
$user = checkRole(['director', 'procurement_manager', 'warehouse_keeper']);

// Ініціалізація моделей
$purchaseRequest = new PurchaseRequest($pdo);
$material = new Material($pdo);
$userModel = new User($pdo);

$message = '';
$error = '';

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Створення нової заявки
        $materialId = $_POST['material_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $priority = $_POST['priority'] ?? 'medium';
        $neededDate = $_POST['needed_date'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        if ($materialId && $quantity > 0) {
            $requestData = [
                'material_id' => $materialId,
                'quantity' => $quantity,
                'requested_by' => $user['id'],
                'priority' => $priority,
                'needed_date' => $neededDate,
                'comments' => $comments
            ];
            
            $requestId = $purchaseRequest->createRequest($requestData);
            if ($requestId) {
                $message = 'Заявку успішно створено!';
            } else {
                $error = 'Помилка при створенні заявки';
            }
        } else {
            $error = 'Заповніть всі обов\'язкові поля';
        }
    }
    
    elseif ($action === 'approve' && in_array($user['role'], ['director', 'procurement_manager'])) {
        $requestId = $_POST['request_id'] ?? 0;
        if ($requestId && $purchaseRequest->approve($requestId, $user['id'])) {
            $message = 'Заявку затверджено!';
        } else {
            $error = 'Помилка при затвердженні заявки';
        }
    }
    
    elseif ($action === 'reject' && in_array($user['role'], ['director', 'procurement_manager'])) {
        $requestId = $_POST['request_id'] ?? 0;
        $comments = $_POST['reject_comments'] ?? '';
        if ($requestId && $purchaseRequest->reject($requestId, $user['id'], $comments)) {
            $message = 'Заявку відхилено!';
        } else {
            $error = 'Помилка при відхиленні заявки';
        }
    }
    
    elseif ($action === 'bulk_create' && $user['role'] === 'warehouse_keeper') {
        // Масове створення заявок для критичних запасів
        $lowStockMaterials = $material->getLowStockMaterials();
        $created = 0;
        
        foreach ($lowStockMaterials as $mat) {
            $recommendedQty = max($mat['min_stock_level'] * 2 - $mat['current_stock'], $mat['min_stock_level']);
            
            $requestData = [
                'material_id' => $mat['id'],
                'quantity' => $recommendedQty,
                'requested_by' => $user['id'],
                'priority' => $mat['current_stock'] <= $mat['min_stock_level'] * 0.5 ? 'urgent' : 'high',
                'needed_date' => date('Y-m-d', strtotime('+1 week')),
                'comments' => 'Автоматично створена заявка через критично низькі запаси'
            ];
            
            if ($purchaseRequest->createRequest($requestData)) {
                $created++;
            }
        }
        
        if ($created > 0) {
            $message = "Створено $created заявок для критичних запасів";
        } else {
            $error = 'Не вдалося створити заявки';
        }
    }
}

// Фільтри
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$materialFilter = $_GET['material'] ?? '';

// Отримання заявок з фільтрами
if ($user['role'] === 'warehouse_keeper') {
    // Начальник складу бачить тільки свої заявки
    $requests = $purchaseRequest->getByUser($user['id']);
} else {
    // Директор і менеджер бачать всі заявки
    $requests = $purchaseRequest->getAllWithDetails();
}

// Застосування фільтрів
if ($statusFilter) {
    $requests = array_filter($requests, function($req) use ($statusFilter) {
        return $req['status'] === $statusFilter;
    });
}

if ($priorityFilter) {
    $requests = array_filter($requests, function($req) use ($priorityFilter) {
        return $req['priority'] === $priorityFilter;
    });
}

// Отримання списку матеріалів для форми
$materials = $material->getAllWithCategories();

// GET параметри для попереднього заповнення форми
$preSelectedMaterial = $_GET['material_id'] ?? '';
$preSelectedQuantity = $_GET['quantity'] ?? '';

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

<div class="row mb-4">
    <!-- Фільтри -->
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
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Очікує</option>
                            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Затверджено</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Відхилено</option>
                            <option value="ordered" <?= $statusFilter === 'ordered' ? 'selected' : '' ?>>Замовлено</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Доставлено</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Пріоритет</label>
                        <select name="priority" class="form-select">
                            <option value="">Всі пріоритети</option>
                            <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : '' ?>>Терміновий</option>
                            <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>Високий</option>
                            <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Середній</option>
                            <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Низький</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="fas fa-search me-1"></i>Фільтрувати
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="<?= BASE_URL ?>/dashboard/requests.php" class="btn btn-outline-secondary d-block">
                            <i class="fas fa-times me-1"></i>Скинути
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Швидкі дії -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Дії
                </h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i class="fas fa-plus me-2"></i>Нова заявка
                </button>
                
                <?php if ($user['role'] === 'warehouse_keeper'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="bulk_create">
                        <button type="submit" class="btn btn-warning w-100" 
                                onclick="return confirm('Створити заявки на всі критичні матеріали?')">
                            <i class="fas fa-magic me-2"></i>Заявки на всі критичні
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Список заявок -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Заявки на закупку (<?= count($requests) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Заявок не знайдено</h5>
                <p class="text-muted">Спробуйте змінити фільтри або створіть нову заявку</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Матеріал</th>
                            <th>Кількість</th>
                            <th>Заявник</th>
                            <th>Пріоритет</th>
                            <th>Статус</th>
                            <th>Потрібно до</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><strong>#<?= $request['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($request['material_name']) ?></strong>
                                    <br>
                                    <small class="text-success"><?= formatMoney($request['total_cost']) ?></small>
                                </td>
                                <td>
                                    <?= $request['quantity'] ?> <?= htmlspecialchars($request['material_unit']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($request['requested_by_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= formatDateTime($request['request_date']) ?></small>
                                </td>
                                <td>
                                    <span style="color:black;" class="badge <?= getPriorityBadge($request['priority']) ?>">
                                        <?= getPriorityText($request['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color:black;" class="badge <?= getStatusBadge($request['status']) ?>">
                                        <?= getStatusText($request['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['needed_date']): ?>
                                        <?= formatDate($request['needed_date']) ?>
                                        <?php
                                        $daysLeft = floor((strtotime($request['needed_date']) - time()) / 86400);
                                        if ($daysLeft < 0): ?>
                                            <br><small class="text-danger">Прострочено на <?= abs($daysLeft) ?> днів</small>
                                        <?php elseif ($daysLeft <= 3): ?>
                                            <br><small class="text-warning">Залишилось <?= $daysLeft ?> днів</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="showRequestDetails(<?= htmlspecialchars(json_encode($request)) ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($request['status'] === 'pending' && in_array($user['role'], ['director', 'procurement_manager'])): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Затвердити заявку?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="showRejectModal(<?= $request['id'] ?>)">
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

<!-- Модальне вікно створення заявки -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Нова заявка
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Матеріал *</label>
                        <select name="material_id" class="form-select" required>
                            <option value="">Оберіть матеріал</option>
                            <?php foreach ($materials as $mat): ?>
                                <option value="<?= $mat['id'] ?>" 
                                        data-unit="<?= htmlspecialchars($mat['unit']) ?>"
                                        data-price="<?= $mat['price'] ?>"
                                        <?= $preSelectedMaterial == $mat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mat['name']) ?> (<?= htmlspecialchars($mat['category_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Кількість *</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" 
                                   value="<?= htmlspecialchars($preSelectedQuantity) ?>" 
                                   min="1" step="1" required>
                            <span class="input-group-text" id="unitDisplay">од.</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Пріоритет</label>
                        <select name="priority" class="form-select">
                            <option value="low">Низький</option>
                            <option value="medium" selected>Середній</option>
                            <option value="high">Високий</option>
                            <option value="urgent">Терміновий</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Потрібно до</label>
                        <input type="date" name="needed_date" class="form-control" 
                               value="<?= date('Y-m-d', strtotime('+1 week')) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Коментарі</label>
                        <textarea name="comments" class="form-control" rows="3" 
                                  placeholder="Додаткові коментарі до заявки..."></textarea>
                    </div>
                    
                    <div id="costEstimate" class="alert alert-info" style="display: none;">
                        <strong>Орієнтовна вартість:</strong> <span id="estimatedCost">0 грн</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Створити заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальне вікно відхилення заявки -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-times me-2"></i>Відхилити заявку
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    
                    <div class="mb-3">
                        <label class="form-label">Причина відхилення *</label>
                        <textarea name="reject_comments" class="form-control" rows="4" 
                                  placeholder="Вкажіть причину відхилення заявки..." required></textarea>
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

<!-- Модальне вікно деталей заявки -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Деталі заявки
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetailsBody">
                <!-- Контент буде завантажено через JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJS = "
// Розрахунок вартості при зміні кількості або матеріалу
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.querySelector('select[name=\"material_id\"]');
    const quantityInput = document.querySelector('input[name=\"quantity\"]');
    const unitDisplay = document.getElementById('unitDisplay');
    const costEstimate = document.getElementById('costEstimate');
    const estimatedCost = document.getElementById('estimatedCost');
    
    function updateCost() {
        const selectedOption = materialSelect.selectedOptions[0];
        if (selectedOption && quantityInput.value) {
            const price = parseFloat(selectedOption.dataset.price || 0);
            const quantity = parseFloat(quantityInput.value || 0);
            const unit = selectedOption.dataset.unit || 'од.';
            
            unitDisplay.textContent = unit;
            
            if (price > 0 && quantity > 0) {
                const total = price * quantity;
                estimatedCost.textContent = total.toLocaleString('uk-UA') + ' грн';
                costEstimate.style.display = 'block';
            } else {
                costEstimate.style.display = 'none';
            }
        } else {
            costEstimate.style.display = 'none';
            unitDisplay.textContent = 'од.';
        }
    }
    
    materialSelect.addEventListener('change', updateCost);
    quantityInput.addEventListener('input', updateCost);
    
    // Первинне оновлення
    updateCost();
});

// Показати модальне вікно відхилення
function showRejectModal(requestId) {
    document.getElementById('rejectRequestId').value = requestId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

// Показати деталі заявки
function showRequestDetails(request) {
    const modal = document.getElementById('detailsModal');
    const body = document.getElementById('requestDetailsBody');
    
    const neededDate = request.needed_date ? new Date(request.needed_date).toLocaleDateString('uk-UA') : 'Не вказано';
    const approvedBy = request.approved_by_name || 'Не затверджено';
    
    body.innerHTML = `
        <div class='row'>
            <div class='col-md-6'>
                <h6>Основна інформація</h6>
                <table class='table table-sm'>
                    <tr><th>Номер заявки:</th><td>#\${request.id}</td></tr>
                    <tr><th>Матеріал:</th><td>\${request.material_name}</td></tr>
                    <tr><th>Кількість:</th><td>\${request.quantity} \${request.material_unit}</td></tr>
                    <tr><th>Вартість:</th><td class='text-success fw-bold'>\${parseFloat(request.total_cost).toLocaleString('uk-UA')} грн</td></tr>
                    <tr><th>Пріоритет:</th><td><span class='badge'>\${request.priority}</span></td></tr>
                </table>
            </div>
            <div class='col-md-6'>
                <h6>Статус та дати</h6>
                <table class='table table-sm'>
                    <tr><th>Статус:</th><td><span class='badge'>\${request.status}</span></td></tr>
                    <tr><th>Заявник:</th><td>\${request.requested_by_name}</td></tr>
                    <tr><th>Дата заявки:</th><td>\${new Date(request.request_date).toLocaleDateString('uk-UA')}</td></tr>
                    <tr><th>Потрібно до:</th><td>\${neededDate}</td></tr>
                    <tr><th>Затвердив:</th><td>\${approvedBy}</td></tr>
                </table>
            </div>
        </div>
        \${request.comments ? `<div class='mt-3'><h6>Коментарі</h6><p class='bg-light p-3 rounded'>\${request.comments}</p></div>` : ''}
    `;
    
    new bootstrap.Modal(modal).show();
}
";

renderDashboardLayout('Заявки на закупку', $user['role'], $content, '', $additionalJS);
?>