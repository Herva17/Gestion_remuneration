<?php

require_once __DIR__ . '/../Config/Database.php';

class AnneeScolaire {
    private $id;
    private $designation_ann;
    private $db;

    public function __construct(
        $designation_ann = null,
        $id = null
    ) {
        $this->id = $id;
        $this->designation_ann = $designation_ann;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getDesignationAnn() { return $this->designation_ann; }

    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setDesignationAnn($designation_ann) { $this->designation_ann = $designation_ann; return $this; }

    public function insert() {
        if (empty($this->designation_ann)) {
            return false;
        }

        $sql = "INSERT INTO annee_scolaire (designation_ann) VALUES (:designation_ann)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':designation_ann', $this->designation_ann);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'année scolaire : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE annee_scolaire SET designation_ann = :designation_ann WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':designation_ann', $this->designation_ann);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'année scolaire : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM annee_scolaire WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'année scolaire : " . $e->getMessage());
            return false;
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM annee_scolaire WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['designation_ann'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'année scolaire : " . $e->getMessage());
            return null;
        }
    }

    public static function getAll() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM annee_scolaire ORDER BY designation_ann DESC";

        try {
            $stmt = $db->query($sql);
            $annees = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $annees[] = new self(
                    $row['designation_ann'],
                    $row['id']
                );
            }
            return $annees;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des années scolaires : " . $e->getMessage());
            return [];
        }
    }

    public static function getCurrent() {
        $annees = self::getAll();
        if (!empty($annees)) {
            return $annees[0]; // Retourne la dernière année ajoutée
        }
        return null;
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM annee_scolaire";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des années scolaires : " . $e->getMessage());
            return 0;
        }
    }

    // Relations
    public function getAvantages() {
        require_once 'Avantage.php';
        return Avantage::getByAnnee($this->id);
    }
}
?>