<?php
// Файл: classes/Material.php
// Модель матеріалів

class Material extends BaseModel {
    protected $table = 'materials';
    
    public function getAllWithCategories() {
        $sql = "SELECT m.*, mc.name as category_name, u.company_name as supplier_name 
                FROM materials m 
                LEFT JOIN material_categories mc ON m.category_id = mc.id
                LEFT JOIN users u ON m.supplier_id = u.id
                ORDER BY m.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getWithDetails($id) {
        $sql = "SELECT m.*, mc.name as category_name, u.company_name as supplier_name, u.full_name as supplier_contact
                FROM materials m 
                LEFT JOIN material_categories mc ON m.category_id = mc.id
                LEFT JOIN users u ON m.supplier_id = u.id
                WHERE m.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getLowStockMaterials() {
        $sql = "SELECT m.*, mc.name as category_name 
                FROM materials m 
                LEFT JOIN material_categories mc ON m.category_id = mc.id
                WHERE m.current_stock <= m.min_stock_level
                ORDER BY (m.current_stock - m.min_stock_level) ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStock($materialId, $newStock) {
        return $this->update($materialId, ['current_stock' => $newStock]);
    }
    
    public function adjustStock($materialId, $adjustment, $userId, $notes = '') {
        // Отримуємо поточний залишок
        $material = $this->findById($materialId);
        if (!$material) {
            return false;
        }
        
        $newStock = $material['current_stock'] + $adjustment;
        if ($newStock < 0) {
            return false; // Не можемо мати від'ємний залишок
        }
        
        // Оновлюємо залишок
        $this->update($materialId, ['current_stock' => $newStock]);
        
        // Записуємо рух товару
        $stockMovement = new StockMovement($this->pdo);
        $stockMovement->create([
            'material_id' => $materialId,
            'movement_type' => $adjustment > 0 ? 'in' : 'out',
            'quantity' => abs($adjustment),
            'reference_type' => 'adjustment',
            'performed_by' => $userId,
            'notes' => $notes
        ]);
        
        return true;
    }
    
    public function getStockReport() {
        $sql = "SELECT 
                    m.id,
                    m.name,
                    mc.name as category_name,
                    m.unit,
                    m.current_stock,
                    m.min_stock_level,
                    (m.current_stock - m.min_stock_level) as stock_difference,
                    m.price,
                    (m.current_stock * m.price) as stock_value,
                    CASE 
                        WHEN m.current_stock <= m.min_stock_level * 0.5 THEN 'critical'
                        WHEN m.current_stock <= m.min_stock_level THEN 'low'
                        ELSE 'normal'
                    END as stock_status
                FROM materials m 
                LEFT JOIN material_categories mc ON m.category_id = mc.id
                ORDER BY stock_difference ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>