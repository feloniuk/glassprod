<?php
// Файл: classes/QualityAssessment.php
// Модель оцінки якості зернової сировини

class QualityAssessment extends BaseModel {
    protected $table = 'quality_assessments';
    
    public function getAllWithDetails() {
        $sql = "SELECT 
                    qa.*,
                    gm.name as material_name,
                    u1.company_name as supplier_name,
                    u1.full_name as supplier_contact,
                    u2.full_name as assessed_by_name
                FROM quality_assessments qa
                LEFT JOIN grain_materials gm ON qa.material_id = gm.id
                LEFT JOIN users u1 ON qa.supplier_id = u1.id
                LEFT JOIN users u2 ON qa.assessed_by = u2.id
                ORDER BY qa.assessment_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByStatus($isApproved = null) {
        $sql = "SELECT 
                    qa.*,
                    gm.name as material_name,
                    u1.company_name as supplier_name,
                    u2.full_name as assessed_by_name
                FROM quality_assessments qa
                LEFT JOIN grain_materials gm ON qa.material_id = gm.id
                LEFT JOIN users u1 ON qa.supplier_id = u1.id
                LEFT JOIN users u2 ON qa.assessed_by = u2.id";
        
        $params = [];
        if ($isApproved !== null) {
            $sql .= " WHERE qa.is_approved = ?";
            $params[] = $isApproved;
        }
        
        $sql .= " ORDER BY qa.assessment_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function createAssessment($data) {
        // Автоматично розраховуємо загальну оцінку на основі параметрів
        $score = $this->calculateQualityScore($data);
        $data['overall_score'] = $score;
        
        // Визначаємо клас якості на основі оцінки
        $data['quality_grade'] = $this->determineQualityGrade($score, $data);
        
        return $this->create($data);
    }
    
    public function approveAssessment($assessmentId, $userId) {
        return $this->update($assessmentId, [
            'is_approved' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function rejectAssessment($assessmentId, $notes) {
        return $this->update($assessmentId, [
            'quality_grade' => 'rejected',
            'is_approved' => 0,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getSupplierStats($supplierId = null) {
        $sql = "SELECT 
                    u.company_name as supplier_name,
                    COUNT(*) as total_assessments,
                    AVG(qa.overall_score) as avg_score,
                    SUM(CASE WHEN qa.quality_grade = 'premium' THEN 1 ELSE 0 END) as premium_count,
                    SUM(CASE WHEN qa.quality_grade = 'first' THEN 1 ELSE 0 END) as first_count,
                    SUM(CASE WHEN qa.quality_grade = 'second' THEN 1 ELSE 0 END) as second_count,
                    SUM(CASE WHEN qa.quality_grade = 'third' THEN 1 ELSE 0 END) as third_count,
                    SUM(CASE WHEN qa.quality_grade = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN qa.is_approved = 1 THEN 1 ELSE 0 END) as approved_count
                FROM quality_assessments qa
                LEFT JOIN users u ON qa.supplier_id = u.id";
        
        $params = [];
        if ($supplierId) {
            $sql .= " WHERE qa.supplier_id = ?";
            $params[] = $supplierId;
        }
        
        $sql .= " GROUP BY qa.supplier_id, u.company_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getQualityTrends($days = 30) {
        $sql = "SELECT 
                    DATE(assessment_date) as date,
                    AVG(overall_score) as avg_score,
                    COUNT(*) as assessments_count,
                    AVG(alcohol_yield) as avg_yield,
                    AVG(moisture_content) as avg_moisture
                FROM quality_assessments 
                WHERE assessment_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND is_approved = 1
                GROUP BY DATE(assessment_date)
                ORDER BY date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    public function canAcceptDelivery($materialId, $supplierId, $batchNumber = null) {
        // Перевіряємо чи є затверджена оцінка якості для цього матеріалу та постачальника
        $sql = "SELECT * FROM quality_assessments 
                WHERE material_id = ? AND supplier_id = ? 
                AND is_approved = 1 
                AND quality_grade != 'rejected'";
        
        $params = [$materialId, $supplierId];
        
        if ($batchNumber) {
            $sql .= " AND batch_number = ?";
            $params[] = $batchNumber;
        }
        
        $sql .= " ORDER BY assessment_date DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $assessment = $stmt->fetch();
        
        return $assessment !== false;
    }
    
    private function calculateQualityScore($data) {
        $score = 100; // Початкова оцінка
        
        // Штрафи за високу вологість
        if ($data['moisture_content'] > 14) {
            $score -= ($data['moisture_content'] - 14) * 2;
        }
        
        // Штрафи за домішки
        if ($data['impurities'] > 2) {
            $score -= ($data['impurities'] - 2) * 5;
        }
        
        // Бонус за високий вміст крохмалю
        if ($data['starch_content'] > 70) {
            $score += ($data['starch_content'] - 70) * 0.5;
        }
        
        // Бонус за високий вихід спирту
        if ($data['alcohol_yield'] > 400) {
            $score += ($data['alcohol_yield'] - 400) * 0.1;
        }
        
        return max(0, min(100, round($score)));
    }
    
    private function determineQualityGrade($score, $data) {
        if ($score >= 90 && $data['moisture_content'] <= 12 && $data['impurities'] <= 1) {
            return 'premium';
        } elseif ($score >= 80 && $data['moisture_content'] <= 14 && $data['impurities'] <= 2) {
            return 'first';
        } elseif ($score >= 70 && $data['moisture_content'] <= 16 && $data['impurities'] <= 3) {
            return 'second';
        } elseif ($score >= 60) {
            return 'third';
        } else {
            return 'rejected';
        }
    }
    
    public function getLatestByMaterial($materialId, $supplierId) {
        $sql = "SELECT * FROM quality_assessments 
                WHERE material_id = ? AND supplier_id = ?
                ORDER BY assessment_date DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$materialId, $supplierId]);
        return $stmt->fetch();
    }
}
?>