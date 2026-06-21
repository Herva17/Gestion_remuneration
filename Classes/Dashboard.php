<?php

require_once __DIR__ . '/../Config/Database.php';

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getTotalAgents() {
        $sql = "SELECT COUNT(*) as total FROM agent";
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalServices() {
        $sql = "SELECT COUNT(*) as total FROM service";
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRemunerations($mois = null, $annee = null) {
        $sql = "SELECT SUM(montant) as total FROM remuneration";
        $params = [];
        $conditions = [];

        if ($mois !== null) {
            $conditions[] = "mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $conditions[] = "annee = :annee";
            $params[':annee'] = $annee;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRetenues($mois = null, $annee = null) {
        $sql = "SELECT SUM(montant) as total FROM retenue";
        $params = [];
        $conditions = [];

        if ($mois !== null) {
            $conditions[] = "mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $conditions[] = "annee = :annee";
            $params[':annee'] = $annee;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalAvantages($id_annee = null) {
        $sql = "SELECT SUM(montant) as total FROM avantages";
        $params = [];

        if ($id_annee !== null) {
            $sql .= " WHERE id_annee = :id_annee";
            $params[':id_annee'] = $id_annee;
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getAgentsParService() {
        $sql = "SELECT s.designation, COUNT(a.id_agent) as nombre 
                FROM service s 
                LEFT JOIN affectation af ON s.id = af.id_service 
                LEFT JOIN agent a ON af.id_agent = a.id_agent 
                GROUP BY s.id, s.designation 
                ORDER BY nombre DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return [];
        }
    }

    public function getTopAgents($limit = 5) {
        $sql = "SELECT a.id_agent, a.nom_complet, 
                COALESCE(SUM(r.montant), 0) as total_remuneration 
                FROM agent a 
                LEFT JOIN remuneration r ON a.id_agent = r.id_agent 
                GROUP BY a.id_agent, a.nom_complet 
                ORDER BY total_remuneration DESC 
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return [];
        }
    }

    public function getAgentsAvecRetenues($mois = null, $annee = null) {
        $sql = "SELECT COUNT(DISTINCT id_agent) as total FROM retenue";
        $params = [];
        $conditions = [];

        if ($mois !== null) {
            $conditions[] = "mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $conditions[] = "annee = :annee";
            $params[':annee'] = $annee;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }

    public function getSalaireMoyen($mois = null, $annee = null) {
        $sql = "SELECT AVG(montant) as moyenne FROM remuneration";
        $params = [];
        $conditions = [];

        if ($mois !== null) {
            $conditions[] = "mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $conditions[] = "annee = :annee";
            $params[':annee'] = $annee;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['moyenne'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur : " . $e->getMessage());
            return 0;
        }
    }
}
?>