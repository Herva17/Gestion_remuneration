<?php
require_once __DIR__ . '/../Config/Database.php';

class Agent {
    private $id_agent;
    private $nom_complet;
    private $adresse;
    private $date_naissance;
    private $telephone;
    private $profil;
    private $lieu_naissance;
    private $fonction;
    private $db;

    public function __construct(
        $nom_complet = null,
        $adresse = null,
        $date_naissance = null,
        $telephone = null,
        $profil = null,
        $lieu_naissance = null,
        $fonction = null,
        $id_agent = null
    ) {
        $this->id_agent = $id_agent;
        $this->nom_complet = $nom_complet;
        $this->adresse = $adresse;
        $this->date_naissance = $date_naissance;
        $this->telephone = $telephone;
        $this->profil = $profil;
        $this->lieu_naissance = $lieu_naissance;
        $this->fonction = $fonction;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getIdAgent() { return $this->id_agent; }
    public function getNomComplet() { return $this->nom_complet; }
    public function getAdresse() { return $this->adresse; }
    public function getDateNaissance() { return $this->date_naissance; }
    public function getTelephone() { return $this->telephone; }
    public function getProfil() { return $this->profil; }
    public function getLieuNaissance() { return $this->lieu_naissance; }
    public function getFonction() { return $this->fonction; }

    // Setters
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setNomComplet($nom_complet) { $this->nom_complet = $nom_complet; return $this; }
    public function setAdresse($adresse) { $this->adresse = $adresse; return $this; }
    public function setDateNaissance($date_naissance) { $this->date_naissance = $date_naissance; return $this; }
    public function setTelephone($telephone) { $this->telephone = $telephone; return $this; }
    public function setProfil($profil) { $this->profil = $profil; return $this; }
    public function setLieuNaissance($lieu_naissance) { $this->lieu_naissance = $lieu_naissance; return $this; }
    public function setFonction($fonction) { $this->fonction = $fonction; return $this; }

    // CRUD Operations
    public function insert() {
        if (empty($this->nom_complet)) {
            return false;
        }

        $sql = "INSERT INTO agent (nom_complet, adresse, date_naissance, telephone, profil, lieu_naissance, fonction) 
                VALUES (:nom_complet, :adresse, :date_naissance, :telephone, :profil, :lieu_naissance, :fonction)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nom_complet', $this->nom_complet);
            $stmt->bindParam(':adresse', $this->adresse);
            $stmt->bindParam(':date_naissance', $this->date_naissance);
            $stmt->bindParam(':telephone', $this->telephone);
            $stmt->bindParam(':profil', $this->profil);
            $stmt->bindParam(':lieu_naissance', $this->lieu_naissance);
            $stmt->bindParam(':fonction', $this->fonction);

            if ($stmt->execute()) {
                $this->id_agent = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'agent : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id_agent)) {
            return false;
        }

        $sql = "UPDATE agent SET 
                nom_complet = :nom_complet, 
                adresse = :adresse, 
                date_naissance = :date_naissance, 
                telephone = :telephone, 
                profil = :profil, 
                lieu_naissance = :lieu_naissance, 
                fonction = :fonction 
                WHERE id_agent = :id_agent";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nom_complet', $this->nom_complet);
            $stmt->bindParam(':adresse', $this->adresse);
            $stmt->bindParam(':date_naissance', $this->date_naissance);
            $stmt->bindParam(':telephone', $this->telephone);
            $stmt->bindParam(':profil', $this->profil);
            $stmt->bindParam(':lieu_naissance', $this->lieu_naissance);
            $stmt->bindParam(':fonction', $this->fonction);
            $stmt->bindParam(':id_agent', $this->id_agent);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'agent : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id_agent)) {
            return false;
        }

        $sql = "DELETE FROM agent WHERE id_agent = :id_agent";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'agent : " . $e->getMessage());
            return false;
        }
    }

    // Méthodes statiques
    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM agent WHERE id_agent = :id_agent";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_agent', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['nom_complet'],
                    $row['adresse'],
                    $row['date_naissance'],
                    $row['telephone'],
                    $row['profil'],
                    $row['lieu_naissance'],
                    $row['fonction'],
                    $row['id_agent']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'agent : " . $e->getMessage());
            return null;
        }
    }

    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT * FROM agent";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE nom_complet LIKE :search OR fonction LIKE :search OR profil LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY nom_complet";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $agents = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $agents[] = new self(
                    $row['nom_complet'],
                    $row['adresse'],
                    $row['date_naissance'],
                    $row['telephone'],
                    $row['profil'],
                    $row['lieu_naissance'],
                    $row['fonction'],
                    $row['id_agent']
                );
            }
            return $agents;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des agents : " . $e->getMessage());
            return [];
        }
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM agent";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des agents : " . $e->getMessage());
            return 0;
        }
    }

    // Relations - avec vérification des fichiers
    public function getAffectations() {
        $file = __DIR__ . '/Affectation.php';
        if (file_exists($file)) {
            require_once $file;
            return Affectation::getByAgent($this->id_agent);
        }
        return [];
    }

    public function getRemunerations() {
        $file = __DIR__ . '/Remuneration.php';
        if (file_exists($file)) {
            require_once $file;
            return Remuneration::getByAgent($this->id_agent);
        }
        return [];
    }

    public function getRetenues() {
        $file = __DIR__ . '/Retenue.php';
        if (file_exists($file)) {
            require_once $file;
            return Retenue::getByAgent($this->id_agent);
        }
        return [];
    }

    public function getAvantages() {
        $file = __DIR__ . '/Avantage.php';
        // Essayer avec un nom de fichier alternatif si le premier n'existe pas
        if (!file_exists($file)) {
            $file = __DIR__ . '/avantages.php';
        }
        if (file_exists($file)) {
            require_once $file;
            // Vérifier si la classe existe avec le bon nom
            if (class_exists('Avantage')) {
                return Avantage::getByAgent($this->id_agent);
            } elseif (class_exists('Avantages')) {
                return Avantages::getByAgent($this->id_agent);
            }
        }
        return [];
    }

    public function getTotalRemuneration($mois = null, $annee = null) {
        $remunerations = $this->getRemunerations();
        $total = 0;
        foreach ($remunerations as $remuneration) {
            if (($mois === null || $remuneration->getMois() === $mois) && 
                ($annee === null || $remuneration->getAnnee() === $annee)) {
                $total += $remuneration->getMontant();
            }
        }
        return $total;
    }

    public function getTotalRetenues($mois = null, $annee = null) {
        $retenues = $this->getRetenues();
        $total = 0;
        foreach ($retenues as $retenue) {
            if (($mois === null || $retenue->getMois() === $mois) && 
                ($annee === null || $retenue->getAnnee() === $annee)) {
                $total += $retenue->getMontant();
            }
        }
        return $total;
    }

    public function getSalaireNet($mois, $annee) {
        $totalRemuneration = $this->getTotalRemuneration($mois, $annee);
        $totalRetenues = $this->getTotalRetenues($mois, $annee);
        return $totalRemuneration - $totalRetenues;
    }

    // Vérifier si l'agent a des relations
    public function hasRelations() {
        $hasRelations = false;
        $relations = [];

        // Vérifier les rémunérations
        $remunerations = $this->getRemunerations();
        if (!empty($remunerations)) {
            $hasRelations = true;
            $relations[] = count($remunerations) . ' rémunération(s)';
        }

        // Vérifier les retenues
        $retenues = $this->getRetenues();
        if (!empty($retenues)) {
            $hasRelations = true;
            $relations[] = count($retenues) . ' retenue(s)';
        }

        // Vérifier les avantages
        $avantages = $this->getAvantages();
        if (!empty($avantages)) {
            $hasRelations = true;
            $relations[] = count($avantages) . ' avantage(s)';
        }

        // Vérifier les affectations
        $affectations = $this->getAffectations();
        if (!empty($affectations)) {
            $hasRelations = true;
            $relations[] = count($affectations) . ' affectation(s)';
        }

        return ['has' => $hasRelations, 'relations' => $relations];
    }
}
?>