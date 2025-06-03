<?php
// Файл: classes/GrainMaterial.php
// Модель зернової сировини

class GrainMaterial extends BaseModel {
    protected $table = 'grain_materials';
    
    public function getAllWithCategories() {
        $sql = "SELECT gm.*, gc.name as category_name, u.company_name as supplier_name 
                FROM grain_materials gm 
                LEFT JOIN grain_categories gc ON gm.category_id = gc.id
                LEFT JOIN users u ON gm.supplier_id = u.id
                ORDER BY gm.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getWithDetails($id) {
        $sql = "SELECT gm.*, gc.name as category_name, u.company_name as supplier_name, u.full_name as supplier_contact
                FROM grain_materials gm 
                LEFT JOIN grain_categories gc ON gm.category_id = gc.id
                LEFT JOIN users u ON gm.supplier_id = u.id
                WHERE gm.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getLowStockMaterials() {
        $sql = "SELECT gm.*, gc.name as category_name 
                FROM grain_materials gm 
                LEFT JOIN grain_categories gc ON gm.category_id = gc.id
                WHERE gm.current_stock <= gm.min_stock_level
                ORDER BY (gm.current_stock - gm.min_stock_level) ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStock($materialId, $newStock) {
        return $this->update($materialId, ['current_stock' => $newStock]);
    }
    
    public function adjustStock($materialId, $adjustment, $userId, $notes = '', $qualityGrade = null, $batchNumber = null) {
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
            'notes' => $notes,
            'quality_grade' => $qualityGrade,
            'batch_number' => $batchNumber
        ]);
        
        return true;
    }
    
    public function getStockReport() {
        $sql = "SELECT 
                    gm.id,
                    gm.name,
                    gc.name as category_name,
                    gm.unit,
                    gm.current_stock,
                    gm.min_stock_level,
                    (gm.current_stock - gm.min_stock_level) as stock_difference,
                    gm.price,
                    (gm.current_stock * gm.price) as stock_value,
                    gm.quality_grade,
                    gm.alcohol_yield,
                    gm.moisture_content,
                    gm.protein_content,
                    gm.starch_content,
                    CASE 
                        WHEN gm.current_stock <= gm.min_stock_level * 0.5 THEN 'critical'
                        WHEN gm.current_stock <= gm.min_stock_level THEN 'low'
                        ELSE 'normal'
                    END as stock_status
                FROM grain_materials gm 
                LEFT JOIN grain_categories gc ON gm.category_id = gc.id
                ORDER BY stock_difference ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getQualityStats() {
        $sql = "SELECT 
                    quality_grade,
                    COUNT(*) as count,
                    SUM(current_stock) as total_stock,
                    AVG(alcohol_yield) as avg_yield,
                    SUM(current_stock * price) as total_value
                FROM grain_materials
                WHERE current_stock > 0
                GROUP BY quality_grade";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByCategoryAndQuality($categoryId = null, $qualityGrade = null) {
        $sql = "SELECT gm.*, gc.name as category_name 
                FROM grain_materials gm 
                LEFT JOIN grain_categories gc ON gm.category_id = gc.id
                WHERE 1=1";
        $params = [];
        
        if ($categoryId) {
            $sql .= " AND gm.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($qualityGrade) {
            $sql .= " AND gm.quality_grade = ?";
            $params[] = $qualityGrade;
        }
        
        $sql .= " ORDER BY gm.quality_grade ASC, gm.alcohol_yield DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAlcoholProductionPotential() {
        $sql = "SELECT 
                    SUM(current_stock * alcohol_yield) as total_alcohol_potential,
                    SUM(CASE WHEN quality_grade = 'premium' THEN current_stock * alcohol_yield ELSE 0 END) as premium_potential,
                    SUM(CASE WHEN quality_grade = 'first' THEN current_stock * alcohol_yield ELSE 0 END) as first_potential,
                    SUM(CASE WHEN quality_grade = 'second' THEN current_stock * alcohol_yield ELSE 0 END) as second_potential,
                    SUM(CASE WHEN quality_grade = 'third' THEN current_stock * alcohol_yield ELSE 0 END) as third_potential
                FROM grain_materials 
                WHERE current_stock > 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>