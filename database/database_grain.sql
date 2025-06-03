-- Файл: database/database_grain.sql
-- База данных для проекта GlassProd - Автоматизація процесу управління запасами зернової сировини на спиртозаводі

CREATE DATABASE IF NOT EXISTS glassprod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE glassprod;

-- Таблица пользователей системы
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('director', 'procurement_manager', 'warehouse_keeper', 'supplier') NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица категорий зерновой сырья
CREATE TABLE grain_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    quality_requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица зерновой сырья
CREATE TABLE grain_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    unit VARCHAR(20) NOT NULL,
    min_stock_level INT DEFAULT 0,
    current_stock INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    supplier_id INT,
    description TEXT,
    quality_grade ENUM('premium', 'first', 'second', 'third') DEFAULT 'first',
    alcohol_yield DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Вихід спирту л/т',
    moisture_content DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Вологість %',
    protein_content DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Білок %',
    starch_content DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Крохмаль %',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES grain_categories(id),
    FOREIGN KEY (supplier_id) REFERENCES users(id)
);

-- Таблица заявок на закупку зерна
CREATE TABLE purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    quantity INT NOT NULL,
    requested_by INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'ordered', 'delivered') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    needed_date DATE,
    approved_by INT NULL,
    approved_date TIMESTAMP NULL,
    comments TEXT,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    purpose ENUM('production', 'reserve', 'quality_improvement') DEFAULT 'production',
    FOREIGN KEY (material_id) REFERENCES grain_materials(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Таблица заказов поставщикам зерна
CREATE TABLE supplier_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('draft', 'sent', 'confirmed', 'in_progress', 'delivered', 'completed', 'cancelled') DEFAULT 'draft',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery DATE,
    actual_delivery DATE NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (supplier_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Таблица позиций заказов
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    delivered_quantity INT DEFAULT 0,
    quality_requirements TEXT,
    FOREIGN KEY (order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES grain_materials(id)
);

-- Таблица оценки качества зерна
CREATE TABLE quality_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    supplier_id INT NOT NULL,
    assessed_by INT NOT NULL,
    assessment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    moisture_content DECIMAL(5,2) NOT NULL COMMENT 'Вологість %',
    protein_content DECIMAL(5,2) NOT NULL COMMENT 'Білок %',
    starch_content DECIMAL(5,2) NOT NULL COMMENT 'Крохмаль %',
    impurities DECIMAL(5,2) NOT NULL COMMENT 'Домішки %',
    alcohol_yield DECIMAL(5,2) NOT NULL COMMENT 'Вихід спирту л/т',
    quality_grade ENUM('premium', 'first', 'second', 'third', 'rejected') NOT NULL,
    overall_score INT NOT NULL COMMENT 'Загальна оцінка 1-100',
    notes TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    batch_number VARCHAR(50),
    test_results JSON COMMENT 'Детальні результати лабораторних тестів',
    FOREIGN KEY (material_id) REFERENCES grain_materials(id),
    FOREIGN KEY (supplier_id) REFERENCES users(id),
    FOREIGN KEY (assessed_by) REFERENCES users(id)
);

-- Таблица движения зерна на складе
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('order', 'production', 'adjustment', 'manual', 'quality_rejection') NOT NULL,
    reference_id INT NULL,
    performed_by INT NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    quality_grade ENUM('premium', 'first', 'second', 'third') NULL,
    batch_number VARCHAR(50),
    FOREIGN KEY (material_id) REFERENCES grain_materials(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- Таблица производственных процессов
CREATE TABLE production_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    planned_alcohol_output DECIMAL(10,2) DEFAULT 0.00,
    actual_alcohol_output DECIMAL(10,2) DEFAULT 0.00,
    efficiency_rate DECIMAL(5,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Таблица использования зерна в производстве
CREATE TABLE production_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity_used INT NOT NULL,
    quality_grade ENUM('premium', 'first', 'second', 'third') NOT NULL,
    alcohol_contribution DECIMAL(10,2) DEFAULT 0.00,
    usage_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES production_batches(id),
    FOREIGN KEY (material_id) REFERENCES grain_materials(id)
);

-- Заполнение тестовыми данными

-- Пользователи (пароли: password)
INSERT INTO users (username, password, email, full_name, role, phone, company_name) VALUES
('director', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'director@spiritplant.ua', 'Петренко Олександр Іванович', 'director', '+380501234567', NULL),
('procurement', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'procurement@spiritplant.ua', 'Коваленко Марія Петрівна', 'procurement_manager', '+380501234568', NULL),
('warehouse', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'warehouse@spiritplant.ua', 'Сидоренко Василь Миколайович', 'warehouse_keeper', '+380501234569', NULL),
('supplier1', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'supplier1@grain.ua', 'Іваненко Андрій Сергійович', 'supplier', '+380501234570', 'АгроЗерно ТОВ'),
('supplier2', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'supplier2@grain.ua', 'Мельник Ольга Василівна', 'supplier', '+380501234571', 'УкрГрейн Експорт');

-- Категории зерновой сырья
INSERT INTO grain_categories (name, description, quality_requirements) VALUES
('Зернові культури', 'Основна сировина для виробництва спирту', 'Вологість не більше 14%, домішки не більше 2%'),
('Технічні культури', 'Додаткова сировина для підвищення виходу спирту', 'Вміст крохмалю не менше 65%'),
('Цукровмісна сировина', 'Сировина з високим вмістом цукрів', 'Цукристість не менше 16%'),
('Вторинна сировина', 'Відходи переробки для додаткового виробництва', 'Придатність для ферментації');

-- Зерновая сырье
INSERT INTO grain_materials (name, category_id, unit, min_stock_level, current_stock, price, supplier_id, description, quality_grade, alcohol_yield, moisture_content, protein_content, starch_content) VALUES
('Пшениця озима', 1, 'т', 100, 280, 6500.00, 4, 'Пшениця озима класу А для виробництва спирту', 'first', 420.5, 12.5, 14.2, 68.5),
('Кукурудза', 1, 'т', 150, 320, 5800.00, 4, 'Кукурудза кормова для спиртового виробництва', 'first', 380.2, 13.1, 9.8, 72.3),
('Жито озиме', 1, 'т', 80, 145, 5200.00, 5, 'Жито озиме продовольче', 'second', 350.8, 13.8, 12.1, 64.2),
('Тритикале', 1, 'т', 60, 95, 5600.00, 5, 'Тритикале гібрид пшениці та жита', 'first', 365.4, 12.9, 13.5, 66.8),
('Ячмінь пивоварний', 1, 'т', 50, 120, 7200.00, 4, 'Ячмінь пивоварний високої якості', 'premium', 340.6, 11.8, 12.4, 58.9),
('Картопля технічна', 2, 'т', 200, 450, 2800.00, 5, 'Картопля технічна для крохмального виробництва', 'second', 280.3, 78.5, 2.1, 18.7),
('Цукровий буряк', 3, 'т', 100, 180, 1200.00, 4, 'Цукровий буряк для виробництва спирту', 'first', 95.2, 75.2, 1.8, 1.2),
('Меляса', 3, 'т', 50, 85, 4500.00, 5, 'Меляса - відходи цукрового виробництва', 'first', 245.8, 20.5, 9.2, 0.8);

-- Заявки на закупку
INSERT INTO purchase_requests (material_id, quantity, requested_by, status, priority, needed_date, comments, total_cost, purpose) VALUES
(1, 50, 3, 'pending', 'high', '2025-06-15', 'Терміново потрібно для планового виробництва', 325000.00, 'production'),
(2, 80, 3, 'approved', 'medium', '2025-06-20', 'Поповнення основних запасів кукурудзи', 464000.00, 'production'),
(6, 100, 3, 'pending', 'low', '2025-06-25', 'Сезонна закупівля технічної картоплі', 280000.00, 'reserve'),
(4, 30, 3, 'approved', 'urgent', '2025-06-10', 'Критично низький рівень запасів тритикале', 168000.00, 'production');

-- Заказы поставщикам
INSERT INTO supplier_orders (supplier_id, order_number, status, expected_delivery, total_amount, created_by, notes) VALUES
(4, 'ORD-2025-001', 'confirmed', '2025-06-12', 789000.00, 2, 'Терміновий заказ зернових на поточний місяць'),
(5, 'ORD-2025-002', 'sent', '2025-06-18', 448000.00, 2, 'Планова закупівля технічних культур');

-- Позиции заказов
INSERT INTO order_items (order_id, material_id, quantity, unit_price, total_price, delivered_quantity, quality_requirements) VALUES
(1, 1, 50, 6500.00, 325000.00, 0, 'Вологість не більше 14%, домішки не більше 2%'),
(1, 2, 80, 5800.00, 464000.00, 0, 'Крохмаль не менше 70%, вологість до 14%'),
(2, 4, 30, 5600.00, 168000.00, 0, 'Стандартні вимоги для тритикале'),
(2, 6, 100, 2800.00, 280000.00, 0, 'Крохмаль не менше 18%, гниль відсутня');

-- Оценки качества
INSERT INTO quality_assessments (material_id, supplier_id, assessed_by, moisture_content, protein_content, starch_content, impurities, alcohol_yield, quality_grade, overall_score, notes, is_approved, batch_number) VALUES
(1, 4, 2, 12.5, 14.2, 68.5, 1.8, 420.5, 'first', 87, 'Відмінна якість пшениці, відповідає всім стандартам', TRUE, 'PWH-2025-001'),
(2, 4, 2, 13.1, 9.8, 72.3, 1.5, 380.2, 'first', 85, 'Хорошсловеа якість кукурудзи, високий вміст крохмалю', TRUE, 'CRN-2025-001'),
(3, 5, 2, 13.8, 12.1, 64.2, 2.1, 350.8, 'second', 78, 'Задовільна якість жита, трохи підвищена вологість', TRUE, 'RYE-2025-001'),
(6, 5, 2, 78.5, 2.1, 18.7, 0.5, 280.3, 'second', 75, 'Картопля прийнятної якості для технічних цілей', TRUE, 'POT-2025-001');

-- Движения зерна на складе
INSERT INTO stock_movements (material_id, movement_type, quantity, reference_type, reference_id, performed_by, notes, quality_grade, batch_number) VALUES
(1, 'in', 200, 'order', 1, 3, 'Поступлення пшениці від постачальника АгроЗерно', 'first', 'PWH-2025-001'),
(2, 'in', 250, 'order', 1, 3, 'Поступлення кукурудзи від постачальника АгроЗерно', 'first', 'CRN-2025-001'),
(1, 'out', 120, 'production', 1, 3, 'Витрата пшениці на виробництво партії SPR-001', 'first', 'PWH-2025-001'),
(2, 'out', 180, 'production', 1, 3, 'Витрата кукурудзи на виробництво партії SPR-001', 'first', 'CRN-2025-001'),
(3, 'in', 145, 'manual', NULL, 3, 'Початкові залишки жита', 'second', 'RYE-2025-001');

-- Производственные партии
INSERT INTO production_batches (batch_number, status, start_date, end_date, planned_alcohol_output, actual_alcohol_output, efficiency_rate, created_by, notes) VALUES
('SPR-2025-001', 'completed', '2025-05-15 08:00:00', '2025-05-18 16:00:00', 42500.00, 41200.00, 96.94, 1, 'Партія спирту з пшениці та кукурудзи'),
('SPR-2025-002', 'in_progress', '2025-06-01 08:00:00', NULL, 38000.00, 0.00, 0.00, 1, 'Поточна партія виробництва'),
('SPR-2025-003', 'planned', NULL, NULL, 45000.00, 0.00, 0.00, 1, 'Планова партія на наступний тиждень');

-- Использование материалов в производстве
INSERT INTO production_materials (batch_id, material_id, quantity_used, quality_grade, alcohol_contribution, usage_date) VALUES
(1, 1, 120, 'first', 25000.00, '2025-05-15 10:00:00'),
(1, 2, 180, 'first', 16200.00, '2025-05-15 14:00:00'),
(2, 1, 90, 'first', 18800.00, '2025-06-01 09:00:00'),
(2, 4, 60, 'first', 12200.00, '2025-06-01 11:00:00');

-- Добавляем индексы для оптимизации
CREATE INDEX idx_quality_assessments_material ON quality_assessments(material_id);
CREATE INDEX idx_quality_assessments_approved ON quality_assessments(is_approved);
CREATE INDEX idx_stock_movements_material ON stock_movements(material_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(movement_date);
CREATE INDEX idx_production_materials_batch ON production_materials(batch_id);

-- Таблица для данных SCADA (производственные данные)
CREATE TABLE production_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Parameter DECIMAL(10,2) NOT NULL,
    Dates VARCHAR(17) NOT NULL DEFAULT '0000-00-00',
    Times VARCHAR(17) NOT NULL DEFAULT '00:00:00',
    Unit VARCHAR(20) DEFAULT '',
    Equipment_ID VARCHAR(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Данные SCADA для спиртозавода
INSERT INTO production_data (Name, Parameter, Dates, Times, Unit, Equipment_ID) VALUES
('Продуктивність варильного відділення', 58.5, '03.06.2025', '14:30:15', 'т/год', 'BREW_001'),
('Температура бродіння № 1', 28.5, '03.06.2025', '14:30:20', '°C', 'FERM_001'),
('Температура бродіння № 2', 29.1, '03.06.2025', '14:30:25', '°C', 'FERM_002'),
('Рівень спирту в колоні', 96.2, '03.06.2025', '14:30:30', '%', 'DIST_001'),
('Витрата пари', 245.8, '03.06.2025', '14:30:35', 'кг/год', 'STEAM_001'),
('Температура ректифікаційної колони', 78.2, '03.06.2025', '14:30:40', '°C', 'RECT_001'),
('Продуктивність варильного відділення', 59.2, '03.06.2025', '14:35:15', 'т/год', 'BREW_001'),
('Температура бродіння № 1', 28.8, '03.06.2025', '14:35:20', '°C', 'FERM_001'),
('Температура бродіння № 2', 28.9, '03.06.2025', '14:35:25', '°C', 'FERM_002'),
('Рівень спирту в колоні', 96.4, '03.06.2025', '14:35:30', '%', 'DIST_001');

-- Переименовываем исходную таблицу data для совместимости
ALTER TABLE production_data RENAME TO data;