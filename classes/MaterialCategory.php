<?php
// Файл: classes/MaterialCategory.php
class MaterialCategory extends BaseModel {
    protected $table = 'material_categories';
    
    public function getAllForSelect() {
        return $this->findAll('name ASC');
    }
}
?>