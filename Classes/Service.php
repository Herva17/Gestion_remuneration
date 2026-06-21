<?php

require_once __DIR__ . '/../Config/Database.php';

class Service {
    private $id;
    private $designation;
    private $description;
    private $db;

    public function __construct(
        $designation = null,
        $description = null,
        $id = null
    ) {
        $this->id = $id;
        $this->designation = $designation;
        $this->description = $description;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getDesignation() { return $this->designation; }
    public function getDescription() { return $this->description; }

    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setDesignation($designation) { $this->designation = $designation; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }

    public function insert() {
        if (empty($this->designation)) {
            return false;
        }

        $sql = "INSERT INTO service (designation, description) VALUES (:designation, :description)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':designation', $this->designation);
            $stmt->bindParam(':description', $this->description);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion du service : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE service SET designation = :designation, description = :description WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':designation', $this->designation);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du service : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM service WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du service : " . $e->getMessage());
            return false;
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM service WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['designation'],
                    $row['description'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du service : " . $e->getMessage());
            return null;
        }
    }

    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT * FROM service";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE designation LIKE :search OR description LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY designation";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $services = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $services[] = new self(
                    $row['designation'],
                    $row['description'],
                    $row['id']
                );
            }
            return $services;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des services : " . $e->getMessage());
            return [];
        }
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM service";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des services : " . $e->getMessage());
            return 0;
        }
    }

    // Relations
    public function getAgents() {
        require_once 'Affectation.php';
        return Affectation::getByService($this->id);
    }

    public function getAgentsCount() {
        require_once 'Affectation.php';
        return Affectation::countByService($this->id);
    }
}
?>