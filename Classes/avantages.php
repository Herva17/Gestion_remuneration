<?php

require_once __DIR__ . '/../Config/Database.php';

class Avantage {
    private $id;
    private $id_agent;
    private $id_annee;
    private $mois;
    private $annee;
    private $libelle;
    private $description;
    private $type_avantage;
    private $est_recurrent;
    private $date_debut;
    private $date_fin;
    private $montant;
    private $date_avantage;
    private $statut;
    private $created_at;
    private $updated_at;
    private $db;

    // Constantes pour les types
    const TYPE_TRANSPORT = 'transport';
    const TYPE_COMMUNICATION = 'communication';
    const TYPE_LOGEMENT = 'logement';
    const TYPE_PRIME = 'prime';
    const TYPE_BONUS = 'bonus';
    const TYPE_AUTRE = 'autre';

    // Constantes pour les statuts
    const STATUT_ACTIF = 'actif';
    const STATUT_INACTIF = 'inactif';
    const STATUT_EN_ATTENTE = 'en_attente';

    /**
     * Constructeur de la classe Avantage
     */
    public function __construct(
        $id_agent = null,
        $id_annee = null,
        $mois = null,
        $annee = null,
        $libelle = null,
        $type_avantage = self::TYPE_AUTRE,
        $montant = null,
        $statut = self::STATUT_ACTIF,
        $description = null,
        $est_recurrent = false,
        $date_debut = null,
        $date_fin = null,
        $date_avantage = null,
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->id_annee = $id_annee;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->libelle = $libelle;
        $this->type_avantage = $type_avantage;
        $this->montant = $montant;
        $this->statut = $statut;
        $this->description = $description;
        $this->est_recurrent = $est_recurrent;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->date_avantage = $date_avantage ?? date('Y-m-d');
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        $this->db = Database::getInstance();
    }

    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getIdAnnee() { return $this->id_annee; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }
    public function getLibelle() { return $this->libelle; }
    public function getDescription() { return $this->description; }
    public function getType() { return $this->type_avantage; }
    public function getTypeAvantage() { return $this->type_avantage; }
    public function getMontant() { return $this->montant; }
    public function getDateAvantage() { return $this->date_avantage; }
    public function getDateDebut() { return $this->date_debut; }
    public function getDateFin() { return $this->date_fin; }
    public function getStatut() { return $this->statut; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
    public function isRecurrent() { return $this->est_recurrent; }

    // ========== SETTERS ==========
    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setIdAnnee($id_annee) { $this->id_annee = $id_annee; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }
    public function setLibelle($libelle) { $this->libelle = $libelle; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setType($type_avantage) { $this->type_avantage = $type_avantage; return $this; }
    public function setMontant($montant) { $this->montant = $montant; return $this; }
    public function setDateAvantage($date_avantage) { $this->date_avantage = $date_avantage; return $this; }
    public function setDateDebut($date_debut) { $this->date_debut = $date_debut; return $this; }
    public function setDateFin($date_fin) { $this->date_fin = $date_fin; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setRecurrent($est_recurrent) { $this->est_recurrent = $est_recurrent; return $this; }

    // ========== MÉTHODES UTILITAIRES ==========
    
    public function getTypeLibelle() {
        $types = [
            self::TYPE_TRANSPORT => 'Transport',
            self::TYPE_COMMUNICATION => 'Communication',
            self::TYPE_LOGEMENT => 'Logement',
            self::TYPE_PRIME => 'Prime',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_AUTRE => 'Autre'
        ];
        return $types[$this->type_avantage] ?? $this->type_avantage;
    }

    public function getStatutLibelle() {
        $statuts = [
            self::STATUT_ACTIF => 'Actif',
            self::STATUT_INACTIF => 'Inactif',
            self::STATUT_EN_ATTENTE => 'En attente'
        ];
        return $statuts[$this->statut] ?? $this->statut;
    }

    public function isActif() {
        return $this->statut === self::STATUT_ACTIF;
    }

    public function isInactif() {
        return $this->statut === self::STATUT_INACTIF;
    }

    public function isEnAttente() {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    // ========== MÉTHODES CRUD ==========

    public function insert() {
        if (empty($this->id_agent) || empty($this->montant)) {
            return false;
        }

        $sql = "INSERT INTO avantages (
                    id_agent, id_annee, mois, annee, libelle, 
                    description, type_avantage, est_recurrent, 
                    date_debut, date_fin, montant, date_avantage, 
                    statut, created_at, updated_at
                ) VALUES (
                    :id_agent, :id_annee, :mois, :annee, :libelle,
                    :description, :type_avantage, :est_recurrent,
                    :date_debut, :date_fin, :montant, :date_avantage,
                    :statut, :created_at, :updated_at
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_annee', $this->id_annee);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_avantage', $this->type_avantage);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent);
            $stmt->bindParam(':date_debut', $this->date_debut);
            $stmt->bindParam(':date_fin', $this->date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_avantage', $this->date_avantage);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':created_at', $this->created_at);
            $stmt->bindParam(':updated_at', $this->updated_at);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'avantage : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $this->updated_at = date('Y-m-d H:i:s');

        $sql = "UPDATE avantages SET 
                    id_agent = :id_agent,
                    id_annee = :id_annee,
                    mois = :mois,
                    annee = :annee,
                    libelle = :libelle,
                    description = :description,
                    type_avantage = :type_avantage,
                    est_recurrent = :est_recurrent,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    montant = :montant,
                    date_avantage = :date_avantage,
                    statut = :statut,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_annee', $this->id_annee);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_avantage', $this->type_avantage);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent);
            $stmt->bindParam(':date_debut', $this->date_debut);
            $stmt->bindParam(':date_fin', $this->date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_avantage', $this->date_avantage);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':updated_at', $this->updated_at);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'avantage : " . $e->getMessage());
            return false;
        }
    }

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

    public function activer() {
        $this->statut = self::STATUT_ACTIF;
        return $this->update();
    }

    public function desactiver() {
        $this->statut = self::STATUT_INACTIF;
        return $this->update();
    }

    // ========== MÉTHODES STATIQUES ==========

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['id_annee'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['type_avantage'],
                    $row['montant'],
                    $row['statut'],
                    $row['description'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['date_avantage'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'avantage : " . $e->getMessage());
            return null;
        }
    }

    public static function getByAgent($id_agent, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY annee DESC, mois DESC, created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantages[] = new self(
                    $row['id_agent'],
                    $row['id_annee'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['type_avantage'],
                    $row['montant'],
                    $row['statut'],
                    $row['description'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['date_avantage'],
                    $row['id']
                );
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    public static function getByAgentAndMonth($id_agent, $mois, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avantages 
                WHERE id_agent = :id_agent 
                AND mois = :mois 
                AND annee = :annee";
        $params = [
            ':id_agent' => $id_agent,
            ':mois' => $mois,
            ':annee' => $annee
        ];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantages[] = new self(
                    $row['id_agent'],
                    $row['id_annee'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['type_avantage'],
                    $row['montant'],
                    $row['statut'],
                    $row['description'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['date_avantage'],
                    $row['id']
                );
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    public static function getAll($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT a.*, ag.nom_complet 
                FROM avantages a 
                JOIN agent ag ON a.id_agent = ag.id_agent";
        $params = [];

        if ($statut !== null) {
            $sql .= " WHERE a.statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY a.annee DESC, a.mois DESC, a.created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avantages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avantage = new self(
                    $row['id_agent'],
                    $row['id_annee'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['type_avantage'],
                    $row['montant'],
                    $row['statut'],
                    $row['description'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['date_avantage'],
                    $row['id']
                );
                $avantage->nom_agent = $row['nom_complet'];
                $avantages[] = $avantage;
            }
            return $avantages;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avantages : " . $e->getMessage());
            return [];
        }
    }

    public static function count($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM avantages";
        $params = [];

        if ($statut !== null) {
            $sql .= " WHERE statut = :statut";
            $params[':statut'] = $statut;
        }

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des avantages : " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalByAnnee($id_annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM avantages WHERE id_annee = :id_annee";
        $params = [':id_annee' => $id_annee];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total des avantages par année : " . $e->getMessage());
            return 0;
        }
    }

    public function getAgent() {
        if ($this->id_agent) {
            require_once 'Agent.php';
            return Agent::getById($this->id_agent);
        }
        return null;
    }

    public function getAnneeScolaire() {
        if ($this->id_annee) {
            require_once 'annee_scolaire.php';
            return AnneeScolaire::getById($this->id_annee);
        }
        return null;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'id_agent' => $this->id_agent,
            'id_annee' => $this->id_annee,
            'mois' => $this->mois,
            'annee' => $this->annee,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'type_avantage' => $this->type_avantage,
            'type_libelle' => $this->getTypeLibelle(),
            'montant' => $this->montant,
            'date_avantage' => $this->date_avantage,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'statut' => $this->statut,
            'statut_libelle' => $this->getStatutLibelle(),
            'est_recurrent' => $this->est_recurrent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>