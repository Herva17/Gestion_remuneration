<?php

require_once __DIR__ . '/../Config/Database.php';

class Retenue {
    private $id;
    private $id_agent;
    private $libelle;
    private $description;
    private $type_retenue;
    private $est_recurrent;
    private $date_debut;
    private $date_fin;
    private $montant;
    private $date_retenue;
    private $mois;
    private $annee;
    private $statut;
    private $created_at;
    private $updated_at;
    private $db;

    // Constantes pour les types
    const TYPE_IMPOT = 'impot';
    const TYPE_ASSURANCE = 'assurance';
    const TYPE_COTISATION = 'cotisation';
    const TYPE_AVANCE = 'avance';
    const TYPE_PENALITE = 'penalite';
    const TYPE_AUTRE = 'autre';

    // Constantes pour les statuts
    const STATUT_ACTIF = 'actif';
    const STATUT_INACTIF = 'inactif';
    const STATUT_EN_ATTENTE = 'en_attente';

    public function __construct(
        $id_agent = null,
        $libelle = null,
        $description = null,
        $type_retenue = self::TYPE_AUTRE,
        $est_recurrent = 0,
        $date_debut = null,
        $date_fin = null,
        $montant = null,
        $date_retenue = null,
        $mois = null,
        $annee = null,
        $statut = self::STATUT_ACTIF,
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->libelle = $libelle;
        $this->description = $description;
        $this->type_retenue = $type_retenue;
        $this->est_recurrent = $est_recurrent;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->montant = $montant;
        $this->date_retenue = $date_retenue ?? date('Y-m-d');
        $this->mois = $mois;
        $this->annee = $annee;
        $this->statut = $statut;
        $this->db = Database::getInstance();
    }

    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }
    public function getLibelle() { return $this->libelle; }
    public function getDescription() { return $this->description; }
    public function getType() { return $this->type_retenue; }
    public function getTypeRetenue() { return $this->type_retenue; }
    public function getMontant() { return $this->montant; }
    public function getDateRetenue() { return $this->date_retenue; }
    public function getDateDebut() { return $this->date_debut; }
    public function getDateFin() { return $this->date_fin; }
    public function getStatut() { return $this->statut; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
    public function isRecurrent() { return $this->est_recurrent == 1; }

    // ========== MÉTHODES UTILITAIRES ==========
    
    public function getTypeLibelle() {
        $types = [
            self::TYPE_IMPOT => 'Impôt sur le revenu',
            self::TYPE_ASSURANCE => 'Assurance sociale',
            self::TYPE_COTISATION => 'Cotisation mutuelle',
            self::TYPE_AVANCE => 'Avance sur salaire',
            self::TYPE_PENALITE => 'Pénalité',
            self::TYPE_AUTRE => 'Autre'
        ];
        return $types[$this->type_retenue] ?? $this->type_retenue;
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

    // ========== MÉTHODES CRUD ==========

    public function insert() {
        if (empty($this->id_agent) || empty($this->montant)) {
            return false;
        }

        $sql = "INSERT INTO retenue (
                    id_agent, libelle, description, type_retenue, 
                    est_recurrent, date_debut, date_fin, montant, 
                    date_retenue, mois, annee, statut
                ) VALUES (
                    :id_agent, :libelle, :description, :type_retenue,
                    :est_recurrent, :date_debut, :date_fin, :montant,
                    :date_retenue, :mois, :annee, :statut
                )";

        try {
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_retenue', $this->type_retenue);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent);
            $stmt->bindParam(':date_debut', $this->date_debut);
            $stmt->bindParam(':date_fin', $this->date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_retenue', $this->date_retenue);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':statut', $this->statut);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de la retenue : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE retenue SET 
                    id_agent = :id_agent,
                    libelle = :libelle,
                    description = :description,
                    type_retenue = :type_retenue,
                    est_recurrent = :est_recurrent,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    montant = :montant,
                    date_retenue = :date_retenue,
                    mois = :mois,
                    annee = :annee,
                    statut = :statut
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_retenue', $this->type_retenue);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent);
            $stmt->bindParam(':date_debut', $this->date_debut);
            $stmt->bindParam(':date_fin', $this->date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_retenue', $this->date_retenue);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la retenue : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM retenue WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la retenue : " . $e->getMessage());
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
        $sql = "SELECT * FROM retenue WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['libelle'],
                    $row['description'],
                    $row['type_retenue'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['montant'],
                    $row['date_retenue'],
                    $row['mois'],
                    $row['annee'],
                    $row['statut'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la retenue : " . $e->getMessage());
            return null;
        }
    }

    public static function getByAgent($id_agent, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM retenue WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY annee DESC, mois DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenues[] = new self(
                    $row['id_agent'],
                    $row['libelle'],
                    $row['description'],
                    $row['type_retenue'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['montant'],
                    $row['date_retenue'],
                    $row['mois'],
                    $row['annee'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les retenues d'un agent pour un mois et une année donnés
     * @param int $id_agent
     * @param string $mois
     * @param int $annee
     * @param string|null $statut
     * @return array
     */
    public static function getByAgentAndMonth($id_agent, $mois, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM retenue 
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

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenues[] = new self(
                    $row['id_agent'],
                    $row['libelle'],
                    $row['description'],
                    $row['type_retenue'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['montant'],
                    $row['date_retenue'],
                    $row['mois'],
                    $row['annee'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    public static function getByAgentAndYear($id_agent, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM retenue 
                WHERE id_agent = :id_agent 
                AND annee = :annee";
        $params = [
            ':id_agent' => $id_agent,
            ':annee' => $annee
        ];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY mois DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenues[] = new self(
                    $row['id_agent'],
                    $row['libelle'],
                    $row['description'],
                    $row['type_retenue'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['montant'],
                    $row['date_retenue'],
                    $row['mois'],
                    $row['annee'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    public static function getAll($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT r.*, ag.nom_complet 
                FROM retenue r 
                JOIN agent ag ON r.id_agent = ag.id_agent";
        $params = [];

        if ($statut !== null) {
            $sql .= " WHERE r.statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY r.annee DESC, r.mois DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenue = new self(
                    $row['id_agent'],
                    $row['libelle'],
                    $row['description'],
                    $row['type_retenue'],
                    $row['est_recurrent'],
                    $row['date_debut'],
                    $row['date_fin'],
                    $row['montant'],
                    $row['date_retenue'],
                    $row['mois'],
                    $row['annee'],
                    $row['statut'],
                    $row['id']
                );
                $retenue->nom_agent = $row['nom_complet'];
                $retenues[] = $retenue;
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    public static function count($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM retenue";
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
            error_log("Erreur lors du comptage des retenues : " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalByMonth($mois, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM retenue WHERE mois = :mois AND annee = :annee";
        $params = [
            ':mois' => $mois,
            ':annee' => $annee
        ];

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
            error_log("Erreur lors du calcul du total des retenues : " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalByAgentAndMonth($id_agent, $mois, $annee, $statut = null) {
        $retenues = self::getByAgentAndMonth($id_agent, $mois, $annee, $statut);
        $total = 0;
        foreach ($retenues as $retenue) {
            $total += $retenue->getMontant();
        }
        return $total;
    }

    public function getAgent() {
        if ($this->id_agent) {
            require_once 'Agent.php';
            return Agent::getById($this->id_agent);
        }
        return null;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'id_agent' => $this->id_agent,
            'mois' => $this->mois,
            'annee' => $this->annee,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'type_retenue' => $this->type_retenue,
            'type_libelle' => $this->getTypeLibelle(),
            'montant' => $this->montant,
            'date_retenue' => $this->date_retenue,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'statut' => $this->statut,
            'statut_libelle' => $this->getStatutLibelle(),
            'est_recurrent' => $this->est_recurrent
        ];
    }
}
?>