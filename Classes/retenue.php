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
    private $db;
    private $agent;

    // Constructeur avec tous les champs
    public function __construct(
        $id_agent = null,
        $libelle = null,
        $description = null,
        $type_retenue = 'autre',
        $est_recurrent = true,
        $date_debut = null,
        $date_fin = null,
        $montant = null,
        $date_retenue = null,
        $mois = null,
        $annee = null,
        $statut = 'actif',
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->libelle = $libelle;
        $this->description = $description;
        $this->type_retenue = $type_retenue;
        $this->est_recurrent = (bool)$est_recurrent;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->montant = $montant;
        $this->date_retenue = $date_retenue;
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
    public function getTypeRetenue() { return $this->type_retenue; }
    public function getEstRecurrent() { return $this->est_recurrent; }
    public function getDateDebut() { return $this->date_debut; }
    public function getDateFin() { return $this->date_fin; }
    public function getMontant() { return $this->montant; }
    public function getDateRetenue() { return $this->date_retenue; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }
    public function getStatut() { return $this->statut; }

    // ========== SETTERS ==========
    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setLibelle($libelle) { $this->libelle = $libelle; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setTypeRetenue($type_retenue) { $this->type_retenue = $type_retenue; return $this; }
    public function setEstRecurrent($est_recurrent) { $this->est_recurrent = (bool)$est_recurrent; return $this; }
    public function setDateDebut($date_debut) { $this->date_debut = $date_debut; return $this; }
    public function setDateFin($date_fin) { $this->date_fin = $date_fin; return $this; }
    public function setMontant($montant) { $this->montant = $montant; return $this; }
    public function setDateRetenue($date_retenue) { $this->date_retenue = $date_retenue; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }

    // ========== MÉTHODES CRUD ==========

    // Insérer une retenue
    public function insert() {
        if (empty($this->id_agent) || empty($this->montant) || empty($this->libelle)) {
            error_log("Données manquantes pour l'insertion: id_agent=" . $this->id_agent . ", montant=" . $this->montant . ", libelle=" . $this->libelle);
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
            
            // Conversion des valeurs null pour les dates
            $date_debut = !empty($this->date_debut) ? $this->date_debut : null;
            $date_fin = !empty($this->date_fin) ? $this->date_fin : null;
            $date_retenue = !empty($this->date_retenue) ? $this->date_retenue : date('Y-m-d');
            
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_retenue', $this->type_retenue);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent, PDO::PARAM_BOOL);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_retenue', $date_retenue);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':statut', $this->statut);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            
            error_log("Erreur lors de l'exécution de la requête: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de la retenue : " . $e->getMessage());
            return false;
        }
    }

    // Mettre à jour une retenue
    public function update() {
        if (empty($this->id) || empty($this->id_agent) || empty($this->montant) || empty($this->libelle)) {
            error_log("Données manquantes pour la mise à jour: id=" . $this->id . ", id_agent=" . $this->id_agent . ", montant=" . $this->montant);
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
            
            $date_debut = !empty($this->date_debut) ? $this->date_debut : null;
            $date_fin = !empty($this->date_fin) ? $this->date_fin : null;
            $date_retenue = !empty($this->date_retenue) ? $this->date_retenue : date('Y-m-d');
            
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':libelle', $this->libelle);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':type_retenue', $this->type_retenue);
            $stmt->bindParam(':est_recurrent', $this->est_recurrent, PDO::PARAM_BOOL);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':montant', $this->montant);
            $stmt->bindParam(':date_retenue', $date_retenue);
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

    // Supprimer une retenue
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

    // ========== MÉTHODES STATIQUES ==========

    // Récupérer une retenue par son ID
    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM retenue WHERE id = :id";

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
            error_log("Erreur lors de la récupération de la retenue : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer les retenues d'un agent
    public static function getByAgent($id_agent, $mois = null, $annee = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM retenue WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($mois !== null) {
            $sql .= " AND mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $sql .= " AND annee = :annee";
            $params[':annee'] = $annee;
        }

        $sql .= " AND statut = 'actif' ORDER BY date_retenue DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenues[] = self::createFromRow($row);
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer toutes les retenues
    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT r.*, a.nom_complet 
                FROM retenue r 
                JOIN agent a ON r.id_agent = a.id_agent";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE a.nom_complet LIKE :search 
                      OR r.libelle LIKE :search 
                      OR r.mois LIKE :search 
                      OR r.annee LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY r.date_retenue DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $retenues = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $retenues[] = self::createFromRow($row);
            }
            return $retenues;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des retenues : " . $e->getMessage());
            return [];
        }
    }

    // Compter le nombre total de retenues
    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM retenue";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des retenues : " . $e->getMessage());
            return 0;
        }
    }

    // Calculer le total des retenues pour un mois donné
    public static function getTotalByMonth($mois, $annee) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM retenue WHERE mois = :mois AND annee = :annee AND statut = 'actif'";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':mois', $mois);
            $stmt->bindParam(':annee', $annee);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    // Calculer le total des retenues pour un agent
    public static function getTotalByAgent($id_agent) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant) as total FROM retenue WHERE id_agent = :id_agent AND statut = 'actif'";

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

    // ========== MÉTHODES UTILITAIRES ==========

    // Créer un objet à partir d'une ligne de résultat
    private static function createFromRow($row) {
        return new self(
            $row['id_agent'],
            $row['libelle'] ?? null,
            $row['description'] ?? null,
            $row['type_retenue'] ?? 'autre',
            $row['est_recurrent'] ?? true,
            $row['date_debut'] ?? null,
            $row['date_fin'] ?? null,
            $row['montant'],
            $row['date_retenue'] ?? null,
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

    // Vérifier si la retenue est récurrente
    public function isRecurrent() {
        return (bool)$this->est_recurrent;
    }

    // Récupérer le libellé du type de retenue
    public function getTypeLibelle() {
        $types = self::getTypes();
        return $types[$this->type_retenue] ?? $this->type_retenue;
    }

    // Récupérer le libellé du statut
    public function getStatutLibelle() {
        $statuts = self::getStatuts();
        return $statuts[$this->statut] ?? $this->statut;
    }

    // Récupérer tous les types de retenues disponibles
    public static function getTypes() {
        return [
            'impot' => 'Impôt sur le revenu',
            'assurance' => 'Assurance sociale',
            'cotisation' => 'Cotisation mutuelle',
            'avance' => 'Avance sur salaire',
            'penalite' => 'Pénalité',
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

    // Récupérer la date de retenue formatée
    public function getDateRetenueFormatee() {
        if ($this->date_retenue) {
            return date('d/m/Y', strtotime($this->date_retenue));
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