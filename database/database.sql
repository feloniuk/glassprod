-- Файл: database/database.sql
-- База данных для проекта GlassProd

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

-- Таблица категорий материалов
CREATE TABLE material_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица материалов
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    unit VARCHAR(20) NOT NULL,
    min_stock_level INT DEFAULT 0,
    current_stock INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    supplier_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES material_categories(id),
    FOREIGN KEY (supplier_id) REFERENCES users(id)
);

-- Таблица заявок на закупку
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
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Таблица заказов поставщикам
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
    FOREIGN KEY (order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- Таблица движения товаров на складе
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('order', 'production', 'adjustment', 'manual') NOT NULL,
    reference_id INT NULL,
    performed_by INT NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- Заполнение тестовыми данными

-- Пользователи (пароли: 123456)
INSERT INTO users (username, password, email, full_name, role, phone, company_name) VALUES
('director', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'director@glassprod.ua', 'Петренко Олександр Іванович', 'director', '+380501234567', NULL),
('procurement', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'procurement@glassprod.ua', 'Коваленко Марія Петрівна', 'procurement_manager', '+380501234568', NULL),
('warehouse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warehouse@glassprod.ua', 'Сидоренко Василь Миколайович', 'warehouse_keeper', '+380501234569', NULL),
('supplier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supplier1@supplier.ua', 'Іваненко Андрій Сергійович', 'supplier', '+380501234570', 'СклоМатеріали ТОВ'),
('supplier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supplier2@supplier.ua', 'Мельник Ольга Василівна', 'supplier', '+380501234571', 'ПромСкло Україна');

-- Категории материалов
INSERT INTO material_categories (name, description) VALUES
('Основні матеріали', 'Сировина для виробництва скла'),
('Хімічні реагенти', 'Хімічні речовини для обробки'),
('Паливо та енергія', 'Паливо та енергоносії'),
('Допоміжні матеріали', 'Допоміжні матеріали для виробництва');

-- Материалы
INSERT INTO materials (name, category_id, unit, min_stock_level, current_stock, price, supplier_id, description) VALUES
('Пісок кварцовий', 1, 'т', 50, 120, 1500.00, 4, 'Високоякісний кварцовий пісок для виробництва скла'),
('Сода кальцинована', 1, 'т', 20, 45, 8500.00, 4, 'Карбонат натрію технічний'),
('Вапняк', 1, 'т', 30, 75, 450.00, 4, 'Карбонат кальцію для скловиробництва'),
('Доломіт', 1, 'т', 15, 32, 680.00, 5, 'Доломітова мука для скломаси'),
('Борна кислота', 2, 'кг', 500, 1200, 45.00, 5, 'Для зниження температури плавлення'),
('Оксид цинку', 2, 'кг', 200, 850, 125.00, 5, 'Для підвищення хімічної стійкості скла'),
('Природний газ', 3, 'м³', 10000, 25000, 8.50, 4, 'Паливо для печей'),
('Електроенергія', 3, 'кВт·год', 50000, 120000, 2.85, 4, 'Електропостачання виробництва');

-- Заявки на закупку
INSERT INTO purchase_requests (material_id, quantity, requested_by, status, priority, needed_date, comments, total_cost) VALUES
(1, 25, 3, 'pending', 'high', '2025-06-15', 'Терміново потрібно для нового замовлення', 37500.00),
(2, 10, 3, 'approved', 'medium', '2025-06-20', 'Планова закупівля', 85000.00),
(5, 300, 3, 'pending', 'low', '2025-06-25', 'Поповнення запасів', 13500.00),
(7, 5000, 3, 'approved', 'urgent', '2025-06-10', 'Критично низький рівень запасів', 42500.00);

-- Заказы поставщикам
INSERT INTO supplier_orders (supplier_id, order_number, status, expected_delivery, total_amount, created_by, notes) VALUES
(4, 'ORD-2025-001', 'confirmed', '2025-06-12', 127500.00, 2, 'Терміновий заказ основних матеріалів'),
(5, 'ORD-2025-002', 'sent', '2025-06-18', 58500.00, 2, 'Планова закупівля хімічних реагентів');

-- Позиции заказов
INSERT INTO order_items (order_id, material_id, quantity, unit_price, total_price, delivered_quantity) VALUES
(1, 1, 25, 1500.00, 37500.00, 0),
(1, 2, 10, 8500.00, 85000.00, 0),
(1, 7, 1000, 8.50, 8500.00, 0),
(2, 4, 50, 680.00, 34000.00, 0),
(2, 5, 200, 45.00, 9000.00, 0),
(2, 6, 125, 125.00, 15625.00, 0);

-- Движения товаров на складе
INSERT INTO stock_movements (material_id, movement_type, quantity, reference_type, reference_id, performed_by, notes) VALUES
(1, 'in', 100, 'order', 1, 3, 'Поступлення від постачальника'),
(2, 'in', 50, 'order', 1, 3, 'Поступлення від постачальника'),
(3, 'out', 25, 'production', NULL, 3, 'Витрата на виробництво'),
(1, 'out', 30, 'production', NULL, 3, 'Витрата на виробництво партії №125'),
(7, 'in', 15000, 'manual', NULL, 3, 'Початкові залишки');