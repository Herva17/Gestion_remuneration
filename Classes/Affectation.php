<?php

require_once __DIR__ . '/../Config/Database.php';

class Affectation {
    private $id;
    private $id_agent;
    private $id_service;
    private $lieu_affectation;
    private $montant_remunerer;
    private $date_affectation;
    private $db;

    private $agent;
    private $service;

    public function __construct(
        $id_agent = null,
        $id_service = null,
        $lieu_affectation = null,
        $montant_remunerer = null,
        $date_affectation = null,
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->id_service = $id_service;
        $this->lieu_affectation = $lieu_affectation;
        $this->montant_remunerer = $montant_remunerer;
        $this->date_affectation = $date_affectation;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getIdService() { return $this->id_service; }
    public function getLieuAffectation() { return $this->lieu_affectation; }
    public function getMontantRemunerer() { return $this->montant_remunerer; }
    public function getDateAffectation() { return $this->date_affectation; }

    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setIdService($id_service) { $this->id_service = $id_service; return $this; }
    public function setLieuAffectation($lieu_affectation) { $this->lieu_affectation = $lieu_affectation; return $this; }
    public function setMontantRemunerer($montant_remunerer) { $this->montant_remunerer = $montant_remunerer; return $this; }
    public function setDateAffectation($date_affectation) { $this->date_affectation = $date_affectation; return $this; }

    public function insert() {
        if (empty($this->id_agent) || empty($this->id_service)) {
            return false;
        }

        $sql = "INSERT INTO affectation (id_agent, id_service, lieu_affectation, montant_remunerer, date_affectation) 
                VALUES (:id_agent, :id_service, :lieu_affectation, :montant_remunerer, :date_affectation)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_service', $this->id_service);
            $stmt->bindParam(':lieu_affectation', $this->lieu_affectation);
            $stmt->bindParam(':montant_remunerer', $this->montant_remunerer);
            $stmt->bindParam(':date_affectation', $this->date_affectation);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'affectation : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE affectation SET 
                id_agent = :id_agent, 
                id_service = :id_service, 
                lieu_affectation = :lieu_affectation,
                montant_remunerer = :montant_remunerer,
                date_affectation = :date_affectation 
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_service', $this->id_service);
            $stmt->bindParam(':lieu_affectation', $this->lieu_affectation);
            $stmt->bindParam(':montant_remunerer', $this->montant_remunerer);
            $stmt->bindParam(':date_affectation', $this->date_affectation);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'affectation : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM affectation WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'affectation : " . $e->getMessage());
            return false;
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM affectation WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['id_service'],
                    $row['lieu_affectation'],
                    $row['montant_remunerer'],
                    $row['date_affectation'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'affectation : " . $e->getMessage());
            return null;
        }
    }

    public static function getByAgent($id_agent) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM affectation WHERE id_agent = :id_agent ORDER BY date_affectation DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_agent', $id_agent);
            $stmt->execute();

            $affectations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $affectations[] = new self(
                    $row['id_agent'],
                    $row['id_service'],
                    $row['lieu_affectation'],
                    $row['montant_remunerer'],
                    $row['date_affectation'],
                    $row['id']
                );
            }
            return $affectations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des affectations : " . $e->getMessage());
            return [];
        }
    }

    public static function getByService($id_service) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM affectation WHERE id_service = :id_service";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_service', $id_service);
            $stmt->execute();

            $affectations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $affectations[] = new self(
                    $row['id_agent'],
                    $row['id_service'],
                    $row['lieu_affectation'],
                    $row['montant_remunerer'],
                    $row['date_affectation'],
                    $row['id']
                );
            }
            return $affectations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des affectations : " . $e->getMessage());
            return [];
        }
    }

    public static function countByService($id_service) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM affectation WHERE id_service = :id_service";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_service', $id_service);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des affectations : " . $e->getMessage());
            return 0;
        }
    }

    public static function getAll() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM affectation ORDER BY date_affectation DESC";

        try {
            $stmt = $db->query($sql);
            $affectations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $affectations[] = new self(
                    $row['id_agent'],
                    $row['id_service'],
                    $row['lieu_affectation'],
                    $row['montant_remunerer'],
                    $row['date_affectation'],
                    $row['id']
                );
            }
            return $affectations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des affectations : " . $e->getMessage());
            return [];
        }
    }

    // Relations
    public function getAgent() {
        if ($this->agent === null && $this->id_agent) {
            require_once 'Agent.php';
            $this->agent = Agent::getById($this->id_agent);
        }
        return $this->agent;
    }

    public function getService() {
        if ($this->service === null && $this->id_service) {
            require_once 'Service.php';
            $this->service = Service::getById($this->id_service);
        }
        return $this->service;
    }
}
?>