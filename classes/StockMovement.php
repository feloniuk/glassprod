<?php
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