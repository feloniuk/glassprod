<?php
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
?>