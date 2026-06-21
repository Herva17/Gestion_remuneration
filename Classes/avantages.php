<?php
require_once __DIR__ . '/../Config/Database.php';

class Avantage {
    private $id;
    private $id_agent;
    private $libelle;
    private $description;
    private $type_avantage;
    private $est_recurrent;
    private $date_debut;
    private $date_fin;
    private $id_annee;
    private $montant;
    private $date_avantage;
    private $mois;
    private $annee;
    private $statut;
    private $created_at;
    private $updated_at;
    private $db;
    private $agent;
    private $annee_scolaire;

    // Constructeur avec tous les champs
    public function __construct(
        $id_agent = null,
        $libelle = null,
        $description = null,
        $type_avantage = 'autre',
        $est_recurrent = true,
        $date_debut = null,
        $date_fin = null,
        $id_annee = null,
        $montant = null,
        $date_avantage = null,
        $mois = null,
        $annee = null,
        $statut = 'actif',
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->libelle = $libelle;
        $this->description = $description;
        $this->type_avantage = $type_avantage;
        $this->est_recurrent = (bool)$est_recurrent;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->id_annee = $id_annee;
        $this->montant = $montant;
        $this->date_avantage = $date_avantage;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->statut = $statut;
        $this->db = Database::getInstance();
    }

    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getLibelle() { return $this->libelle; }
    public function getDescription() { return $this->description; }
    public function getTypeAvantage() { return $this->type_avantage; }
    public function getEstRecurrent() { return $this->est_recurrent; }
    public function getDateDebut() { return $this->date_debut; }
    public function getDateFin() { return $this->date_fin; }
    public function getIdAnnee() { return $this->id_annee; }
    public function getMontant() { return $this->montant; }
    public function getDateAvantage() { return $this->date_avantage; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }
    public function getStatut() { return $this->statut; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // ========== SETTERS ==========
    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setLibelle($libelle) { $this->libelle = $libelle; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setTypeAvantage($type_avantage) { $this->type_avantage = $type_avantage; return $this; }
    public function setEstRecurrent($est_recurrent) { $this->est_recurrent = (bool)$est_recurrent; return $this; }
    public function setDateDebut($date_debut) { $this->date_debut = $date_debut; return $this; }
    public function setDateFin($date_fin) { $this->date_fin = $date_fin; return $this; }
    public function setIdAnnee($id_annee) { $this->id_annee = $id_annee; return $this; }
    public function setMontant($montant) { $this->montant = $montant; return $this; }
    public function setDateAvantage($date_avantage) { $this->date_avantage = $date_avantage; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }

    // ========== MÉTHODES CRUD ==========

    // Insérer un avantage
    public function insert() {
        if (empty($this->id_agent) || empty($this->montant) || empty($this->libelle)) {
            error_log("Données manquantes pour l'insertion de l'avantage");
            return false;
        }

        $sql = "INSERT INTO avantages (
                    id_agent, libelle, description, type_avantage, 
                    est_recurrent, date_debut, date_fin, id_annee, 
                    montant, date_avantage, mois, annee, statut
                ) VALUES (
                    :id_agent, :libelle, :description, :type_avantage,
                    :est_recurrent, :date_debut, :date_fin, :id_annee,
                    :montant, :date_avantage, :mois, :annee, :statut
                )";

        try {
            $stmt = $this->db->prepare($sql);
            
            $date_debut = !empty($this->date_debut) ? $this->date_debut : null;
            $date_fin = !empty($this->date_fin) ? $this->date_fin : null;
            $date_avantage = !empty($this->date_avantage) ? $this->date_avantage : date('Y-m-d');
            
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_avantage', $this->type_avantage);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent, PDO::PARAM_BOOL);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':id_annee', $this->id_annee);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_avantage', $date_avantage);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':statut', $this->statut);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            
            error_log("Erreur lors de l'insertion de l'avantage: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'avantage : " . $e->getMessage());
            return false;
        }
    }

    // Mettre à jour un avantage
    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE avantages SET 
                    id_agent = :id_agent,
                    libelle = :libelle,
                    description = :description,
                    type_avantage = :type_avantage,
                    est_recurrent = :est_recurrent,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    id_annee = :id_annee,
                    montant = :montant,
                    date_avantage = :date_avantage,
                    mois = :mois,
                    annee = :annee,
                    statut = :statut
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            
            $date_debut = !empty($this->date_debut) ? $this->date_debut : null;
            $date_fin = !empty($this->date_fin) ? $this->date_fin : null;
            $date_avantage = !empty($this->date_avantage) ? $this->date_avantage : date('Y-m-d');
            
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_avantage', $this->type_avantage);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent, PDO::PARAM_BOOL);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':id_annee', $this->id_annee);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_avantage', $date_avantage);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'avantage : " . $e->getMessage());
            return false;
        }
    }

    // Supprimer un avantage
    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM avantages WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'avantage : " . $e->getMessage());
            return false;
        }
    }

    // ========== MÉTHODES STATIQUES ==========

    // Récupérer un avantage par son ID
    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return self::createFromRow($row);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'avantage : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer les avantages d'un agent
    public static function getByAgent($id_agent, $id_annee = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($id_annee !== null) {
            $sql .= " AND id_annee = :id_annee";
            $params[':id_annee'] = $id_annee;
        }

        $sql .= " AND statut = 'actif' ORDER BY date_avantage DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantages[] = self::createFromRow($row);
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les avantages par année scolaire
    public static function getByAnnee($id_annee) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages WHERE id_annee = :id_annee AND statut = 'actif' ORDER BY date_avantage DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_annee', $id_annee);
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantages[] = self::createFromRow($row);
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer tous les avantages
    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT a.*, ag.nom_complet, an.designation_ann 
                FROM avantages a 
                JOIN agent ag ON a.id_agent = ag.id_agent 
                JOIN annee_scolaire an ON a.id_annee = an.id";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE ag.nom_complet LIKE :search 
                      OR a.libelle LIKE :search 
                      OR an.designation_ann LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY a.date_avantage DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantages[] = self::createFromRow($row);
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    // Compter le nombre total d'avantages
    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM avantages";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des avantages : " . $e->getMessage());
            return 0;
        }
    }

    // Calculer le total des avantages par année
    public static function getTotalByAnnee($id_annee) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM avantages WHERE id_annee = :id_annee AND statut = 'actif'";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_annee', $id_annee);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    // Calculer le total des avantages par agent
    public static function getTotalByAgent($id_agent) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM avantages WHERE id_agent = :id_agent AND statut = 'actif'";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_agent', $id_agent);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    // Calculer le total des avantages par agent et année
    public static function getTotalByAgentAndAnnee($id_agent, $id_annee) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM avantages WHERE id_agent = :id_agent AND id_annee = :id_annee AND statut = 'actif'";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_agent', $id_agent);
            $stmt->bindParam(':id_annee', $id_annee);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    // ========== MÉTHODES UTILITAIRES ==========

    // Créer un objet à partir d'une ligne de résultat
    private static function createFromRow($row) {
        return new self(
            $row['id_agent'],
            $row['libelle'] ?? null,
            $row['description'] ?? null,
            $row['type_avantage'] ?? 'autre',
            $row['est_recurrent'] ?? true,
            $row['date_debut'] ?? null,
            $row['date_fin'] ?? null,
            $row['id_annee'],
            $row['montant'],
            $row['date_avantage'],
            $row['mois'] ?? null,
            $row['annee'] ?? null,
            $row['statut'] ?? 'actif',
            $row['id']
        );
    }

    // Récupérer l'agent associé
    public function getAgent() {
        if ($this->agent === null && $this->id_agent) {
            require_once 'Agent.php';
            $this->agent = Agent::getById($this->id_agent);
        }
        return $this->agent;
    }

    // Récupérer l'année scolaire associée
    public function getAnneeScolaire() {
        if ($this->annee_scolaire === null && $this->id_annee) {
            require_once 'AnneeScolaire.php';
            $this->annee_scolaire = AnneeScolaire::getById($this->id_annee);
        }
        return $this->annee_scolaire;
    }

    // Vérifier si l'avantage est récurrent
    public function isRecurrent() {
        return (bool)$this->est_recurrent;
    }

    // Récupérer le libellé du type d'avantage
    public function getTypeLibelle() {
        $types = self::getTypes();
        return $types[$this->type_avantage] ?? $this->type_avantage;
    }

    // Récupérer le libellé du statut
    public function getStatutLibelle() {
        $statuts = self::getStatuts();
        return $statuts[$this->statut] ?? $this->statut;
    }

    // Récupérer tous les types d'avantages disponibles
    public static function getTypes() {
        return [
            'transport' => 'Transport',
            'communication' => 'Communication',
            'logement' => 'Logement',
            'prime' => 'Prime',
            'bonus' => 'Bonus',
            'autre' => 'Autre'
        ];
    }

    // Récupérer tous les statuts disponibles
    public static function getStatuts() {
        return [
            'actif' => 'Actif',
            'inactif' => 'Inactif',
            'en_attente' => 'En attente'
        ];
    }

    // Formater le montant
    public function getMontantFormate() {
        return number_format($this->montant, 2, ',', ' ');
    }

    // Récupérer la date d'avantage formatée
    public function getDateAvantageFormatee() {
        if ($this->date_avantage) {
            return date('d/m/Y', strtotime($this->date_avantage));
        }
        return 'N/A';
    }

    // Récupérer la période (mois/année) formatée
    public function getPeriode() {
        if ($this->mois && $this->annee) {
            return $this->mois . ' ' . $this->annee;
        }
        return 'N/A';
    }
}
?>