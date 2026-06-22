<?php

require_once __DIR__ . '/../Config/Database.php';

class Avance {
    private $id;
    private $agent_id;
    private $mois;
    private $annee;
    private $libelle;
    private $montant;
    private $date_creation;
    private $statut;
    private $db;

    // Constantes pour les statuts (uniquement ceux présents dans la table)
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_REMBOURSE = 'rembourse';

    public function __construct(
        $agent_id = null,
        $mois = null,
        $annee = null,
        $libelle = null,
        $montant = null,
        $statut = self::STATUT_EN_COURS,
        $id = null
    ) {
        $this->id = $id;
        $this->agent_id = $agent_id;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->libelle = $libelle;
        $this->montant = $montant;
        $this->statut = $statut;
        $this->date_creation = date('Y-m-d H:i:s');
        $this->db = Database::getInstance();
    }

    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getAgentId() { return $this->agent_id; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }
    public function getLibelle() { return $this->libelle; }
    public function getMontant() { return $this->montant; }
    public function getDateCreation() { return $this->date_creation; }
    public function getStatut() { return $this->statut; }

    // ========== SETTERS ==========
    public function setId($id) { $this->id = $id; return $this; }
    public function setAgentId($agent_id) { $this->agent_id = $agent_id; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }
    public function setLibelle($libelle) { $this->libelle = $libelle; return $this; }
    public function setMontant($montant) { $this->montant = $montant; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }

    // ========== MÉTHODES STATUT ==========
    public function isEnCours() { return $this->statut === self::STATUT_EN_COURS; }
    public function isRembourse() { return $this->statut === self::STATUT_REMBOURSE; }

    public function getStatutLibelle() {
        $statuts = [
            self::STATUT_EN_COURS => 'En cours',
            self::STATUT_REMBOURSE => 'Remboursé'
        ];
        return $statuts[$this->statut] ?? $this->statut;
    }

    public function getStatutBadge() {
        $badges = [
            self::STATUT_EN_COURS => 'badge-yellow',
            self::STATUT_REMBOURSE => 'badge-green'
        ];
        return $badges[$this->statut] ?? 'badge-gray';
    }

    // ========== MÉTHODES CRUD ==========

    /**
     * Insère une nouvelle avance dans la base de données
     * @return bool
     */
    public function insert() {
        if (empty($this->agent_id) || empty($this->montant) || $this->montant <= 0) {
            return false;
        }

        $sql = "INSERT INTO avances (agent_id, mois, annee, libelle, montant, statut) 
                VALUES (:agent_id, :mois, :annee, :libelle, :montant, :statut)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':agent_id', $this->agent_id);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':statut', $this->statut);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de l'avance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une avance existante
     * @return bool
     */
    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE avances SET 
                agent_id = :agent_id,
                mois = :mois,
                annee = :annee,
                libelle = :libelle,
                montant = :montant,
                statut = :statut
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':agent_id', $this->agent_id);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'avance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une avance
     * @return bool
     */
    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM avances WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'avance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque une avance comme remboursée
     * @return bool
     */
    public function marquerRembourse() {
        $this->statut = self::STATUT_REMBOURSE;
        return $this->update();
    }

    // ========== MÉTHODES STATIQUES ==========

    /**
     * Récupère une avance par son ID
     * @param int $id
     * @return self|null
     */
    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avances WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'avance : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère toutes les avances d'un agent
     * @param int $agent_id
     * @param string|null $statut
     * @return array
     */
    public static function getByAgent($agent_id, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avances WHERE agent_id = :agent_id";
        $params = [':agent_id' => $agent_id];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY annee DESC, mois DESC, date_creation DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avances = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avances[] = new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $avances;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les avances d'un agent pour un mois et une année donnés
     * @param int $agent_id
     * @param string $mois
     * @param int $annee
     * @param string|null $statut
     * @return array
     */
    public static function getByAgentAndMonth($agent_id, $mois, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avances 
                WHERE agent_id = :agent_id 
                AND mois = :mois 
                AND annee = :annee";
        $params = [
            ':agent_id' => $agent_id,
            ':mois' => $mois,
            ':annee' => $annee
        ];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY date_creation DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avances = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avances[] = new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $avances;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les avances en cours d'un agent
     * @param int $agent_id
     * @return array
     */
    public static function getAvancesEnCours($agent_id) {
        return self::getByAgent($agent_id, self::STATUT_EN_COURS);
    }

    /**
     * Récupère le total des avances d'un agent pour une période
     * @param int $agent_id
     * @param string $mois
     * @param int $annee
     * @param string|null $statut
     * @return float
     */
    public static function getTotalByAgentAndMonth($agent_id, $mois, $annee, $statut = null) {
        $avances = self::getByAgentAndMonth($agent_id, $mois, $annee, $statut);
        $total = 0;
        foreach ($avances as $avance) {
            $total += $avance->getMontant();
        }
        return $total;
    }

    /**
     * Récupère le total des avances en cours d'un agent
     * @param int $agent_id
     * @return float
     */
    public static function getTotalEnCours($agent_id) {
        $avances = self::getAvancesEnCours($agent_id);
        $total = 0;
        foreach ($avances as $avance) {
            $total += $avance->getMontant();
        }
        return $total;
    }

    /**
     * Récupère toutes les avances pour un mois et une année donnés
     * @param string $mois
     * @param int $annee
     * @param string|null $statut
     * @return array
     */
    public static function getByMonth($mois, $annee, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avances WHERE mois = :mois AND annee = :annee";
        $params = [
            ':mois' => $mois,
            ':annee' => $annee
        ];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY date_creation DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avances = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avances[] = new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $avances;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les avances
     * @param string|null $statut
     * @return array
     */
    public static function getAll($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT a.*, ag.nom_complet 
                FROM avances a 
                JOIN agent ag ON a.agent_id = ag.id_agent";
        $params = [];

        if ($statut !== null) {
            $sql .= " WHERE a.statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY a.annee DESC, a.mois DESC, a.date_creation DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avances = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avance = new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
                $avance->nom_agent = $row['nom_complet'];
                $avances[] = $avance;
            }
            return $avances;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre d'avances
     * @param string|null $statut
     * @return int
     */
    public static function count($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM avances";
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
            error_log("Erreur lors du comptage des avances : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le montant total des avances
     * @param string|null $statut
     * @return float
     */
    public static function getTotal($statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) FROM avances";
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
            return (float)($stmt->fetchColumn() ?? 0);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total des avances : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les avances par libellé (recherche partielle)
     * @param string $libelle
     * @param string|null $statut
     * @return array
     */
    public static function getByLibelle($libelle, $statut = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM avances WHERE libelle LIKE :libelle";
        $params = [':libelle' => '%' . $libelle . '%'];

        if ($statut !== null) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY date_creation DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $avances = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avances[] = new self(
                    $row['agent_id'],
                    $row['mois'],
                    $row['annee'],
                    $row['libelle'],
                    $row['montant'],
                    $row['statut'],
                    $row['id']
                );
            }
            return $avances;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des avances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un agent a des avances en cours pour une période donnée
     * @param int $agent_id
     * @param string $mois
     * @param int $annee
     * @return bool
     */
    public static function hasAvancesEnCours($agent_id, $mois, $annee) {
        $avances = self::getByAgentAndMonth($agent_id, $mois, $annee, self::STATUT_EN_COURS);
        return count($avances) > 0;
    }

    // ========== MÉTHODES DE CONVERSION ==========

    /**
     * Convertit l'objet en tableau associatif
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'mois' => $this->mois,
            'annee' => $this->annee,
            'libelle' => $this->libelle,
            'montant' => $this->montant,
            'statut' => $this->statut,
            'statut_libelle' => $this->getStatutLibelle(),
            'date_creation' => $this->date_creation
        ];
    }
}