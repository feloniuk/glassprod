<?php
// Файл: dashboard/analytics.php
// Страница аналитики с графиками данных

require_once '../config/config.php';
require_once '../templates/dashboard_layout.php';

// Перевірка прав доступу - только директор
$user = checkRole(['director']);

$message = '';
$error = '';

// Получение параметров фильтра
$timeFilter = $_GET['time_filter'] ?? '24h';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Построение SQL запроса в зависимости от фильтра
$whereClause = '1=1';
$params = [];

switch ($timeFilter) {
    case '1h':
        $whereClause = "DATE(STR_TO_DATE(Dates, '%d.%m.%Y')) = CURDATE() AND TIME(Times) >= DATE_SUB(CURTIME(), INTERVAL 1 HOUR)";
        break;
    case '24h':
        $whereClause = "DATE(STR_TO_DATE(Dates, '%d.%m.%Y')) = CURDATE()";
        break;
    case '7d':
        $whereClause = "STR_TO_DATE(Dates, '%d.%m.%Y') >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case '30d':
        $whereClause = "STR_TO_DATE(Dates, '%d.%m.%Y') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'custom':
        if ($dateFrom && $dateTo) {
            $whereClause = "STR_TO_DATE(Dates, '%d.%m.%Y') BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];
        }
        break;
    case 'all':
    default:
        $whereClause = '1=1';
        break;
}

// Получение данных из таблицы data
$sql = "SELECT ID, Name, Parameter, Dates, Times, 
               STR_TO_DATE(CONCAT(Dates, ' ', Times), '%d.%m.%Y %H:%i:%s') as datetime_combined
        FROM data 
        WHERE $whereClause 
        ORDER BY STR_TO_DATE(CONCAT(Dates, ' ', Times), '%d.%m.%Y %H:%i:%s') ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dataRecords = $stmt->fetchAll();

// Если нет данных с фильтром, получаем последние 100 записей
if (empty($dataRecords)) {
    $sql = "SELECT ID, Name, Parameter, Dates, Times, 
                   STR_TO_DATE(CONCAT(Dates, ' ', Times), '%d.%m.%Y %H:%i:%s') as datetime_combined
            FROM data 
            ORDER BY ID DESC 
            LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dataRecords = $stmt->fetchAll();
    $dataRecords = array_reverse($dataRecords); // Переворачиваем для правильного порядка на графике
}

// Подготовка данных для графиков
$chartLabels = [];
$chartData = [];
$hourlyData = [];
$dailyStats = [];

foreach ($dataRecords as $record) {
    // Для основного графика
    if ($record['datetime_combined']) {
        $datetime = new DateTime($record['datetime_combined']);
        $chartLabels[] = $datetime->format('d.m H:i');
        $chartData[] = (float)$record['Parameter'];
        
        // Для почасовой статистики
        $hour = $datetime->format('H');
        if (!isset($hourlyData[$hour])) {
            $hourlyData[$hour] = [];
        }
        $hourlyData[$hour][] = (float)$record['Parameter'];
        
        // Для дневной статистики
        $date = $datetime->format('Y-m-d');
        if (!isset($dailyStats[$date])) {
            $dailyStats[$date] = [];
        }
        $dailyStats[$date][] = (float)$record['Parameter'];
    }
}

// Вычисление статистики
$totalRecords = count($dataRecords);
$avgProductivity = $totalRecords > 0 ? array_sum($chartData) / $totalRecords : 0;
$maxProductivity = $totalRecords > 0 ? max($chartData) : 0;
$minProductivity = $totalRecords > 0 ? min($chartData) : 0;

// Подготовка данных для почасового графика
$hourlyLabels = [];
$hourlyAverages = [];
for ($i = 0; $i < 24; $i++) {
    $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
    $hourlyLabels[] = $hour . ':00';
    $hourlyAverages[] = isset($hourlyData[$hour]) ? array_sum($hourlyData[$hour]) / count($hourlyData[$hour]) : 0;
}

// Подготовка данных для дневного графика
$dailyLabels = [];
$dailyAverages = [];
$dailyMax = [];
$dailyMin = [];

foreach ($dailyStats as $date => $values) {
    $dailyLabels[] = date('d.m', strtotime($date));
    $dailyAverages[] = array_sum($values) / count($values);
    $dailyMax[] = max($values);
    $dailyMin[] = min($values);
}

// Анализ трендов
$trend = 'стабільний';
if ($totalRecords > 1) {
    $firstHalf = array_slice($chartData, 0, floor($totalRecords / 2));
    $secondHalf = array_slice($chartData, floor($totalRecords / 2));
    
    if (!empty($firstHalf) && !empty($secondHalf)) {
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        if ($difference > 1) {
            $trend = 'зростаючий';
        } elseif ($difference < -1) {
            $trend = 'спадний';
        }
    }
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

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i>
            Фільтри часового періоду
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Період</label>
                <select name="time_filter" class="form-select" onchange="toggleCustomDates(this.value)">
                    <option value="all" <?= $timeFilter === 'all' ? 'selected' : '' ?>>Всі записи</option>
                    <option value="1h" <?= $timeFilter === '1h' ? 'selected' : '' ?>>Остання година</option>
                    <option value="24h" <?= $timeFilter === '24h' ? 'selected' : '' ?>>Останні 24 години</option>
                    <option value="7d" <?= $timeFilter === '7d' ? 'selected' : '' ?>>Останні 7 днів</option>
                    <option value="30d" <?= $timeFilter === '30d' ? 'selected' : '' ?>>Останні 30 днів</option>
                    <option value="custom" <?= $timeFilter === 'custom' ? 'selected' : '' ?>>Довільний період</option>
                </select>
            </div>
            <div class="col-md-3" id="dateFromField" style="<?= $timeFilter !== 'custom' ? 'display: none;' : '' ?>">
                <label class="form-label">Дата з</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-3" id="dateToField" style="<?= $timeFilter !== 'custom' ? 'display: none;' : '' ?>">
                <label class="form-label">Дата по</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-chart-line me-1"></i>Оновити графіки
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($avgProductivity, 2) ?></h3>
                <p class="mb-0">Середня продуктивність т/год</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-arrow-up fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($maxProductivity, 2) ?></h3>
                <p class="mb-0">Максимальна т/год</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-arrow-down fa-3x mb-3"></i>
                <h3 class="mb-1"><?= number_format($minProductivity, 2) ?></h3>
                <p class="mb-0">Мінімальна т/год</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-database fa-3x mb-3"></i>
                <h3 class="mb-1"><?= $totalRecords ?></h3>
                <p class="mb-0">Записів в періоді</p>
            </div>
        </div>
    </div>
</div>

<!-- Основной график продуктивности -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-area me-2"></i>
                    Продуктивність ділянки ПТЛ в часі
                </h5>
                <div class="badge bg-info">
                    Тренд: <?= $trend ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($dataRecords)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Немає даних для відображення</h5>
                        <p class="text-muted">Перевірте наявність даних в таблиці або змініть фільтри</p>
                    </div>
                <?php else: ?>
                    <canvas id="productivityChart" height="300"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($hourlyData)): ?>
<!-- Почасовая статистика -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Середня продуктивність по годинах
                </h5>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Аналіз ефективності
                </h5>
            </div>
            <div class="card-body">
                <?php
                $activeHours = array_filter($hourlyAverages);
                if (!empty($activeHours)) {
                    $peakHour = array_keys($hourlyAverages, max($activeHours))[0];
                    $lowHour = array_keys($hourlyAverages, min($activeHours))[0];
                ?>
                <div class="mb-3">
                    <h6 class="text-success">Найпродуктивніша година:</h6>
                    <p class="mb-1"><?= $hourlyLabels[$peakHour] ?></p>
                    <small class="text-muted"><?= number_format($hourlyAverages[$peakHour], 2) ?> т/год</small>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-warning">Найменш продуктивна година:</h6>
                    <p class="mb-1"><?= $hourlyLabels[$lowHour] ?></p>
                    <small class="text-muted"><?= number_format($hourlyAverages[$lowHour], 2) ?> т/год</small>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-info">Коефіцієнт варіації:</h6>
                    <?php
                    $mean = array_sum($activeHours) / count($activeHours);
                    $variance = 0;
                    foreach ($activeHours as $value) {
                        $variance += pow($value - $mean, 2);
                    }
                    $stdDev = sqrt($variance / count($activeHours));
                    $coefficient = $mean > 0 ? ($stdDev / $mean) * 100 : 0;
                    ?>
                    <p class="mb-1"><?= number_format($coefficient, 1) ?>%</p>
                    <small class="text-muted">Стабільність роботи</small>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($dailyStats) && count($dailyStats) > 1): ?>
<!-- Дневная статистика -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>
                    Динаміка продуктивності по днях
                </h5>
            </div>
            <div class="card-body">
                <canvas id="dailyChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Детальная таблица данных -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-table me-2"></i>
            Детальні дані (останні 50 записів)
        </h5>
        <button class="btn btn-outline-light btn-sm" onclick="exportData()">
            <i class="fas fa-download me-1"></i>Експорт CSV
        </button>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dataRecords)): ?>
            <div class="text-center py-5">
                <i class="fas fa-table fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Немає даних для відображення</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва параметра</th>
                            <th>Значення</th>
                            <th>Дата</th>
                            <th>Час</th>
                            <th>Відхилення від середнього</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice(array_reverse($dataRecords), 0, 50) as $record): ?>
                            <?php
                            $deviation = (float)$record['Parameter'] - $avgProductivity;
                            $deviationClass = '';
                            if ($deviation > 2) {
                                $deviationClass = 'table-success';
                            } elseif ($deviation < -2) {
                                $deviationClass = 'table-warning';
                            }
                            ?>
                            <tr class="<?= $deviationClass ?>">
                                <td><?= $record['ID'] ?></td>
                                <td><?= htmlspecialchars($record['Name']) ?></td>
                                <td>
                                    <strong><?= number_format((float)$record['Parameter'], 2) ?></strong>
                                    <small class="text-muted">т/год</small>
                                </td>
                                <td><?= htmlspecialchars($record['Dates']) ?></td>
                                <td><?= htmlspecialchars($record['Times']) ?></td>
                                <td>
                                    <span class="<?= $deviation > 0 ? 'text-success' : ($deviation < 0 ? 'text-danger' : 'text-muted') ?>">
                                        <?= ($deviation > 0 ? '+' : '') . number_format($deviation, 2) ?>
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

<?php
$content = ob_get_clean();

$additionalJS = "
function toggleCustomDates(value) {
    const dateFromField = document.getElementById('dateFromField');
    const dateToField = document.getElementById('dateToField');
    
    if (value === 'custom') {
        dateFromField.style.display = 'block';
        dateToField.style.display = 'block';
    } else {
        dateFromField.style.display = 'none';
        dateToField.style.display = 'none';
    }
}

function exportData() {
    const data = " . json_encode($dataRecords) . ";
    
    let csv = 'ID,Назва,Параметр,Дата,Час\\n';
    data.forEach(row => {
        csv += `\${row.ID},\"\${row.Name}\",\${row.Parameter},\${row.Dates},\${row.Times}\\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `productivity_data_\${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Основной график продуктивности
" . (!empty($dataRecords) ? "
const productivityCtx = document.getElementById('productivityChart');
if (productivityCtx) {
    new Chart(productivityCtx, {
        type: 'line',
        data: {
            labels: " . json_encode($chartLabels) . ",
            datasets: [{
                label: 'Продуктивність (т/год)',
                data: " . json_encode($chartData) . ",
                borderColor: '#4fc3f7',
                backgroundColor: 'rgba(79, 195, 247, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: '#4fc3f7',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#4fc3f7',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Продуктивність: ' + context.parsed.y.toFixed(2) + ' т/год';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Час',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        maxTicksLimit: 20,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Продуктивність (т/год)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });
}
" : "") . "

// Почасовой график
" . (!empty($hourlyData) ? "
const hourlyCtx = document.getElementById('hourlyChart');
if (hourlyCtx) {
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: " . json_encode($hourlyLabels) . ",
            datasets: [{
                label: 'Середня продуктивність (т/год)',
                data: " . json_encode($hourlyAverages) . ",
                backgroundColor: 'rgba(76, 175, 80, 0.8)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            return 'Середня: ' + context.parsed.y.toFixed(2) + ' т/год';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Година дня',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Продуктивність (т/год)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            }
        }
    });
}
" : "") . "

// Дневной график
" . (!empty($dailyStats) && count($dailyStats) > 1 ? "
const dailyCtx = document.getElementById('dailyChart');
if (dailyCtx) {
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: " . json_encode($dailyLabels) . ",
            datasets: [{
                label: 'Середня',
                data: " . json_encode($dailyAverages) . ",
                borderColor: '#4fc3f7',
                backgroundColor: 'rgba(79, 195, 247, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4
            }, {
                label: 'Максимум',
                data: " . json_encode($dailyMax) . ",
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4
            }, {
                label: 'Мінімум',
                data: " . json_encode($dailyMin) . ",
                borderColor: '#ff9800',
                backgroundColor: 'rgba(255, 152, 0, 0.1)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' т/год';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Дата',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Продуктивність (т/год)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            }
        }
    });
}
" : "") . "

// Автообновление каждые 30 секунд (по желанию)
// setInterval(function() {
//     window.location.reload();
// }, 30000);
";

renderDashboardLayout('SCADA - Моніторинг продуктивності', 'director', $content, '', $additionalJS);
?>