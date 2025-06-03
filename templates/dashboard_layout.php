<?php
// Файл: templates/dashboard_layout.php
// Базовий шаблон для дашбордів

function renderDashboardLayout($pageTitle, $userRole, $content, $additionalCSS = '', $additionalJS = '') {
    $user = getCurrentUser();
    
    $navigation = [
        'director' => [
            'Головна' => '/dashboard/director.php',
            'Заявки' => '/dashboard/requests.php',
            'Замовлення' => '/dashboard/orders.php', 
            'Склад' => '/dashboard/warehouse.php',
            'SCADA' => '/dashboard/analytics.php',
            'Відеоспостереження' => '/dashboard/surveillance.php',
            'Звіти' => '/dashboard/reports.php',
            'Користувачі' => '/dashboard/users.php'
        ],
        'procurement_manager' => [
            'Головна' => '/dashboard/procurement_manager.php',
            'Заявки' => '/dashboard/requests.php',
            'Замовлення' => '/dashboard/orders.php',
            'Постачальники' => '/dashboard/suppliers.php',
            'Матеріали' => '/dashboard/materials.php'
        ],
        'warehouse_keeper' => [
            'Головна' => '/dashboard/warehouse_keeper.php',
            'Склад' => '/dashboard/warehouse.php',
            'Заявки' => '/dashboard/requests.php',
            'Рух товарів' => '/dashboard/stock_movements.php',
            'Матеріали' => '/dashboard/materials.php'
        ],
        'supplier' => [
            'Головна' => '/dashboard/supplier.php',
            'Мої замовлення' => '/dashboard/my_orders.php',
            'Матеріали' => '/dashboard/my_materials.php',
            'Профіль' => '/dashboard/profile.php'
        ]
    ];
    
    $currentNav = $navigation[$userRole] ?? [];
    $roleTitle = getRoleText($userRole);
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle} - {$roleTitle} - GlassProd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #4fc3f7;
            --secondary-color: #29b6f6;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --dark-color: #263238;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--dark-color) 0%, #37474f 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            font-weight: 600;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4caf50 0%, #45a047 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--dark-color);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                width: 250px;
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        {$additionalCSS}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar position-fixed top-0 start-0" style="width: 250px; z-index: 100;">
        <div class="p-3">
            <h4 class="text-white mb-4">
                <i class="fas fa-industry me-2"></i>GlassProd
            </h4>
            
            <div class="user-info text-white">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user-circle fa-2x me-3"></i>
                    <div>
                        <div class="fw-bold">{$user['full_name']}</div>
                        <small class="text-light">{$roleTitle}</small>
                    </div>
                </div>
            </div>
            
            <ul class="nav nav-pills flex-column">
HTML;

    foreach ($currentNav as $title => $url) {
        $isActive = (basename($_SERVER['PHP_SELF']) === basename($url)) ? 'active' : '';
        $icon = [
            'Головна' => 'fas fa-home',
            'Заявки' => 'fas fa-clipboard-list',
            'Замовлення' => 'fas fa-shopping-cart',
            'Склад' => 'fas fa-warehouse',
            'Звіти' => 'fas fa-chart-bar',
            'SCADA' => 'fas fa-chart-bar',
            'Відеоспостереження' => 'fas fa-chart-bar',
            'Користувачі' => 'fas fa-users',
            'Постачальники' => 'fas fa-truck',
            'Матеріали' => 'fas fa-boxes',
            'Рух товарів' => 'fas fa-exchange-alt',
            'Мої замовлення' => 'fas fa-list-alt',
            'Мої матеріали' => 'fas fa-box',
            'Профіль' => 'fas fa-user'
        ][$title] ?? 'fas fa-circle';
        
        echo "<li class='nav-item'>";
        echo "<a class='nav-link {$isActive}' href='" . BASE_URL . $url . "'>";
        echo "<i class='{$icon} me-2'></i>{$title}";
        echo "</a>";
        echo "</li>";
    }

    echo <<<HTML
            </ul>
        </div>
        
        <div class="position-absolute bottom-0 start-0 w-100 p-3">
            <a href="/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2"></i>Вихід
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Mobile Toggle Button -->
        <button class="btn btn-primary d-md-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{$pageTitle}</h1>
            <div class="d-flex align-items-center">
                <span class="text-muted me-3">
                    <i class="fas fa-calendar me-1"></i>
HTML;
    echo date('d.m.Y H:i');
    echo <<<HTML
                </span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        {$user['full_name']}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/dashboard/profile.php">
                            <i class="fas fa-user me-2"></i>Профіль
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Вихід
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        {$content}
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
    <script>
        // Загальні JavaScript функції
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-\${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                \${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Автоматичне закриття алертів
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        {$additionalJS}
    </script>
</body>
</html>
HTML;
}
?>