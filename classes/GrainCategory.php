<?php
// Файл: classes/GrainCategory.php
class GrainCategory extends BaseModel {
    protected $table = 'grain_categories';
    
    public function getAllForSelect() {
        return $this->findAll('name ASC');
    }
    
    public function getWithMaterialsCount() {
        $sql = "SELECT 
                    gc.*,
                    COUNT(gm.id) as materials_count,
                    SUM(gm.current_stock) as total_stock,
                    SUM(gm.current_stock * gm.price) as total_value
                FROM grain_categories gc
                LEFT JOIN grain_materials gm ON gc.id = gm.category_id
                GROUP BY gc.id
                ORDER BY gc.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>