<?php
// Файл: classes/MaterialCategory.php
class MaterialCategory extends BaseModel {
    protected $table = 'material_categories';
    
    public function getAllForSelect() {
        return $this->findAll('name ASC');
    }
}

// Файл: classes/OrderItem.php  
class OrderItem extends BaseModel {
    protected $table = 'order_items';
    
    public function getByOrderId($orderId) {
        $sql = "SELECT 
                    oi.*,
                    m.name as material_name,
                    m.unit as material_unit
                FROM order_items oi
                LEFT JOIN materials m ON oi.material_id = m.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function updateDeliveredQuantity($itemId, $deliveredQty) {
        return $this->update($itemId, ['delivered_quantity' => $deliveredQty]);
    }
}

// Файл: classes/StockMovement.php
class StockMovement extends BaseModel {
    protected $table = 'stock_movements';
    
    public function getAllWithDetails($limit = 100) {
        $sql = "SELECT 
                    sm.*,
                    m.name as material_name,
                    m.unit as material_unit,
                    u.full_name as performed_by_name
                FROM stock_movements sm
                LEFT JOIN materials m ON sm.material_id = m.id
                LEFT JOIN users u ON sm.performed_by = u.id
                ORDER BY sm.movement_date DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getByMaterial($materialId, $limit = 50) {
        $sql = "SELECT 
                    sm.*,
                    u.full_name as performed_by_name
                FROM stock_movements sm
                LEFT JOIN users u ON sm.performed_by = u.id
                WHERE sm.material_id = ?
                ORDER BY sm.movement_date DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$materialId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function recordIncoming($materialId, $quantity, $referenceType, $referenceId, $userId, $notes = '') {
        return $this->create([
            'material_id' => $materialId,
            'movement_type' => 'in',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $userId,
            'notes' => $notes
        ]);
    }
    
    public function recordOutgoing($materialId, $quantity, $referenceType, $referenceId, $userId, $notes = '') {
        return $this->create([
            'material_id' => $materialId,
            'movement_type' => 'out',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $userId,
            'notes' => $notes
        ]);
    }
}
?>