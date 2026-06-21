<?php

require_once __DIR__ . '/../Config/Database.php';

class Remuneration {
    private $id;
    private $id_agent;
    private $montant;
    private $date_remun;
    private $mois;
    private $annee;
    private $db;

    private $agent;

    public function __construct(
        $id_agent = null,
        $montant = null,
        $date_remun = null,
        $mois = null,
        $annee = null,
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->montant = $montant;
        $this->date_remun = $date_remun;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getMontant() { return $this->montant; }
    public function getDateRemun() { return $this->date_remun; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }

    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setMontant($montant) { $this->montant = $montant; return $this; }
    public function setDateRemun($date_remun) { $this->date_remun = $date_remun; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }

    public function insert() {
        if (empty($this->id_agent) || empty($this->montant)) {
            return false;
        }

        $sql = "INSERT INTO remuneration (id_agent, montant, date_remun, mois, annee) 
                VALUES (:id_agent, :montant, :date_remun, :mois, :annee)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_remun', $this->date_remun);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE remuneration SET 
                id_agent = :id_agent, 
                montant = :montant, 
                date_remun = :date_remun, 
                mois = :mois, 
                annee = :annee 
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_remun', $this->date_remun);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM remuneration WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['montant'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la rémunération : " . $e->getMessage());
            return null;
        }
    }

    public static function getByAgent($id_agent, $mois = null, $annee = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($mois !== null) {
            $sql .= " AND mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $sql .= " AND annee = :annee";
            $params[':annee'] = $annee;
        }

        $sql .= " ORDER BY date_remun DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $remunerations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remunerations[] = new self(
                    $row['id_agent'],
                    $row['montant'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return $remunerations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rémunérations : " . $e->getMessage());
            return [];
        }
    }

    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT r.*, a.nom_complet 
                FROM remuneration r 
                JOIN agent a ON r.id_agent = a.id_agent";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE a.nom_complet LIKE :search OR r.mois LIKE :search OR r.annee LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY r.date_remun DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $remunerations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remunerations[] = new self(
                    $row['id_agent'],
                    $row['montant'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return $remunerations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rémunérations : " . $e->getMessage());
            return [];
        }
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM remuneration";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des rémunérations : " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalByMonth($mois, $annee) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM remuneration WHERE mois = :mois AND annee = :annee";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':mois', $mois);
            $stmt->bindParam(':annee', $annee);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
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
}
?>