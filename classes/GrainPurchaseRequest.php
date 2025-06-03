<?php
// Файл: classes/GrainPurchaseRequest.php
// Модель заявок на закупку зернової сировини

class GrainPurchaseRequest extends BaseModel {
    protected $table = 'purchase_requests';
    
    public function getAllWithDetails() {
        $sql = "SELECT 
                    pr.*,
                    gm.name as material_name,
                    gm.unit as material_unit,
                    gm.price as material_price,
                    gm.alcohol_yield,
                    u1.full_name as requested_by_name,
                    u2.full_name as approved_by_name
                FROM purchase_requests pr
                LEFT JOIN grain_materials gm ON pr.material_id = gm.id
                LEFT JOIN users u1 ON pr.requested_by = u1.id
                LEFT JOIN users u2 ON pr.approved_by = u2.id
                ORDER BY pr.request_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByStatus($status) {
        $sql = "SELECT 
                    pr.*,
                    gm.name as material_name,
                    gm.unit as material_unit,
                    gm.price as material_price,
                    gm.alcohol_yield,
                    u1.full_name as requested_by_name
                FROM purchase_requests pr
                LEFT JOIN grain_materials gm ON pr.material_id = gm.id
                LEFT JOIN users u1 ON pr.requested_by = u1.id
                WHERE pr.status = ?
                ORDER BY pr.priority DESC, pr.request_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    public function getByUser($userId) {
        $sql = "SELECT 
                    pr.*,
                    gm.name as material_name,
                    gm.unit as material_unit,
                    gm.price as material_price,
                    gm.alcohol_yield,
                    u1.full_name as requested_by_name
                FROM purchase_requests pr
                LEFT JOIN grain_materials gm ON pr.material_id = gm.id
                LEFT JOIN users u1 ON pr.requested_by = u1.id
                WHERE pr.requested_by = ?
                ORDER BY pr.request_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function approve($requestId, $approvedBy) {
        return $this->update($requestId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_date' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function reject($requestId, $approvedBy, $comments = '') {
        return $this->update($requestId, [
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_date' => date('Y-m-d H:i:s'),
            'comments' => $comments
        ]);
    }
    
    public function markAsOrdered($requestId) {
        return $this->update($requestId, ['status' => 'ordered']);
    }
    
    public function getStatistics() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_cost) as total_amount
                FROM purchase_requests 
                GROUP BY status";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPriorityStatistics() {
        $sql = "SELECT 
                    priority,
                    COUNT(*) as count,
                    SUM(total_cost) as total_amount
                FROM purchase_requests 
                WHERE status IN ('pending', 'approved')
                GROUP BY priority";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function createRequest($data) {
        // Розраховуємо загальну вартість
        if (isset($data['material_id']) && isset($data['quantity'])) {
            $material = new GrainMaterial($this->pdo);
            $materialData = $material->findById($data['material_id']);
            if ($materialData) {
                $data['total_cost'] = $materialData['price'] * $data['quantity'];
            }
        }
        
        return $this->create($data);
    }
    
    public function getPurposeStatistics() {
        $sql = "SELECT 
                    purpose,
                    COUNT(*) as count,
                    SUM(total_cost) as total_amount,
                    SUM(quantity) as total_quantity
                FROM purchase_requests 
                WHERE status IN ('approved', 'ordered', 'delivered')
                GROUP BY purpose";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAlcoholPotentialFromRequests() {
        $sql = "SELECT 
                    SUM(pr.quantity * gm.alcohol_yield) as potential_alcohol_output,
                    COUNT(*) as total_requests,
                    SUM(pr.total_cost) as total_investment
                FROM purchase_requests pr
                LEFT JOIN grain_materials gm ON pr.material_id = gm.id
                WHERE pr.status IN ('approved', 'ordered')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>