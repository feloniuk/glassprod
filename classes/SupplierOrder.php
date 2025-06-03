<?php
// Файл: classes/SupplierOrder.php
// Модель замовлень постачальникам

class SupplierOrder extends BaseModel {
    protected $table = 'supplier_orders';
    
    public function getAllWithDetails() {
        $sql = "SELECT 
                    so.*,
                    u1.company_name as supplier_name,
                    u1.full_name as supplier_contact,
                    u2.full_name as created_by_name
                FROM supplier_orders so
                LEFT JOIN users u1 ON so.supplier_id = u1.id
                LEFT JOIN users u2 ON so.created_by = u2.id
                ORDER BY so.order_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getBySupplier($supplierId) {
        $sql = "SELECT so.*, u.full_name as created_by_name
                FROM supplier_orders so
                LEFT JOIN users u ON so.created_by = u.id
                WHERE so.supplier_id = ?
                ORDER BY so.order_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll();
    }
    
    public function getWithItems($orderId) {
        $sql = "SELECT 
                    so.*,
                    u1.company_name as supplier_name,
                    u1.full_name as supplier_contact,
                    u1.phone as supplier_phone,
                    u1.email as supplier_email,
                    u2.full_name as created_by_name
                FROM supplier_orders so
                LEFT JOIN users u1 ON so.supplier_id = u1.id
                LEFT JOIN users u2 ON so.created_by = u2.id
                WHERE so.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Отримуємо позиції замовлення
            $itemsSql = "SELECT 
                            oi.*,
                            m.name as material_name,
                            m.unit as material_unit
                        FROM order_items oi
                        LEFT JOIN materials m ON oi.material_id = m.id
                        WHERE oi.order_id = ?";
            
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->execute([$orderId]);
            $order['items'] = $itemsStmt->fetchAll();
        }
        
        return $order;
    }
    
    public function createOrder($data, $items = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Генеруємо номер замовлення
            $data['order_number'] = $this->generateOrderNumber();
            
            // Створюємо замовлення
            $orderId = $this->create($data);
            
            // Додаємо позиції замовлення
            if (!empty($items)) {
                $orderItems = new OrderItem($this->pdo);
                $totalAmount = 0;
                
                foreach ($items as $item) {
                    $item['order_id'] = $orderId;
                    $item['total_price'] = $item['quantity'] * $item['unit_price'];
                    $totalAmount += $item['total_price'];
                    
                    $orderItems->create($item);
                }
                
                // Оновлюємо загальну суму замовлення
                $this->update($orderId, ['total_amount' => $totalAmount]);
            }
            
            $this->pdo->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function updateStatus($orderId, $status, $deliveryDate = null) {
        $updateData = ['status' => $status];
        
        if ($status === 'delivered' && $deliveryDate) {
            $updateData['actual_delivery'] = $deliveryDate;
        }
        
        return $this->update($orderId, $updateData);
    }
    
    public function getStatistics() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total_amount
                FROM supplier_orders 
                GROUP BY status";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMonthlyStats($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT 
                    MONTH(order_date) as month,
                    COUNT(*) as orders_count,
                    SUM(total_amount) as total_amount
                FROM supplier_orders 
                WHERE YEAR(order_date) = ?
                GROUP BY MONTH(order_date)
                ORDER BY month";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }
    
    private function generateOrderNumber() {
        $prefix = 'ORD-' . date('Y') . '-';
        
        $sql = "SELECT MAX(CAST(SUBSTRING(order_number, 10) AS UNSIGNED)) as last_num 
                FROM supplier_orders 
                WHERE order_number LIKE ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        
        $nextNum = ($result['last_num'] ?? 0) + 1;
        
        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}
?>