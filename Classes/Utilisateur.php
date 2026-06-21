<?php

require_once __DIR__ . '/../Config/Database.php';

class Utilisateur {
    private $id;
    private $nom;
    private $email;
    private $mot_de_passe;
    private $role;
    private $date_creation;
    private $db;

    // Constantes pour les rôles
    const ROLE_ADMINISTRATEUR = 'Administrateur';
    const ROLE_COMPTABLE = 'Comptable';
    const ROLE_SECRETAIRE = 'Secretaire';

    public function __construct(
        $nom = null,
        $email = null,
        $mot_de_passe = null,
        $role = null,
        $date_creation = null,
        $id = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->mot_de_passe = $mot_de_passe;
        $this->role = $role;
        $this->date_creation = $date_creation;
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getNom() {
        return $this->nom;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getMotDePasse() {
        return $this->mot_de_passe;
    }

    public function getRole() {
        return $this->role;
    }

    public function getDateCreation() {
        return $this->date_creation;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setNom($nom) {
        $this->nom = $nom;
        return $this;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function setMotDePasse($mot_de_passe) {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    public function setRole($role) {
        $valid_roles = [self::ROLE_ADMINISTRATEUR, self::ROLE_COMPTABLE, self::ROLE_SECRETAIRE];
        if (in_array($role, $valid_roles)) {
            $this->role = $role;
        } else {
            throw new InvalidArgumentException("Rôle invalide. Les rôles valides sont : " . implode(', ', $valid_roles));
        }
        return $this;
    }

    public function setDateCreation($date_creation) {
        $this->date_creation = $date_creation;
        return $this;
    }

    // Méthodes utilitaires
    public function estAdministrateur() {
        return $this->role === self::ROLE_ADMINISTRATEUR;
    }

    public function estComptable() {
        return $this->role === self::ROLE_COMPTABLE;
    }

    public function estSecretaire() {
        return $this->role === self::ROLE_SECRETAIRE;
    }

    public function verifierMotDePasse($mot_de_passe) {
        return password_verify($mot_de_passe, $this->mot_de_passe);
    }

    // CRUD Operations
    public function insert() {
        if (empty($this->nom) || empty($this->email) || empty($this->mot_de_passe) || empty($this->role)) {
            return false;
        }

        // Vérifier si l'email existe déjà
        if (self::emailExiste($this->email)) {
            return false;
        }

        $sql = "INSERT INTO utilisateur (nom, email, mot_de_passe, role) 
                VALUES (:nom, :email, :mot_de_passe, :role)";

        try {
            $stmt = $this->db->prepare($sql);
            $passwordHash = password_hash($this->mot_de_passe, PASSWORD_DEFAULT);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':mot_de_passe', $passwordHash);
            $stmt->bindParam(':role', $this->role);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            echo "Erreur lors de l'insertion : " . $e->getMessage();
            return false;
        }
    }

    public function update() {
        if (empty($this->nom) || empty($this->email) || empty($this->role) || empty($this->id)) {
            return false;
        }

        // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
        if (self::emailExiste($this->email, $this->id)) {
            return false;
        }

        $sql = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role";
        
        if ($this->mot_de_passe !== null && !empty($this->mot_de_passe)) {
            $sql .= ", mot_de_passe = :mot_de_passe";
        }
        
        $sql .= " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':role', $this->role);
            $stmt->bindParam(':id', $this->id);

            if ($this->mot_de_passe !== null && !empty($this->mot_de_passe)) {
                $passwordHash = password_hash($this->mot_de_passe, PASSWORD_DEFAULT);
                $stmt->bindParam(':mot_de_passe', $passwordHash);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Erreur lors de la mise à jour : " . $e->getMessage();
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM utilisateur WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Erreur lors de la suppression : " . $e->getMessage();
            return false;
        }
    }

    // Méthodes statiques
    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilisateur WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['nom'],
                    $row['email'],
                    $row['mot_de_passe'],
                    $row['role'],
                    $row['date_creation'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération : " . $e->getMessage();
            return null;
        }
    }

    public static function getByEmail($email) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilisateur WHERE email = :email LIMIT 1";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['nom'],
                    $row['email'],
                    $row['mot_de_passe'],
                    $row['role'],
                    $row['date_creation'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération : " . $e->getMessage();
            return null;
        }
    }

    public static function authenticate($email, $mot_de_passe) {
        $user = self::getByEmail($email);
        if (!$user) {
            return null;
        }

        if ($user->verifierMotDePasse($mot_de_passe)) {
            return $user;
        }

        return null;
    }

    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilisateur";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE nom LIKE :search OR email LIKE :search OR role LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY nom";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new self(
                    $row['nom'],
                    $row['email'],
                    $row['mot_de_passe'],
                    $row['role'],
                    $row['date_creation'],
                    $row['id']
                );
            }
            return $users;
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération : " . $e->getMessage();
            return [];
        }
    }

    public static function getByRole($role) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilisateur WHERE role = :role ORDER BY nom";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new self(
                    $row['nom'],
                    $row['email'],
                    $row['mot_de_passe'],
                    $row['role'],
                    $row['date_creation'],
                    $row['id']
                );
            }
            return $users;
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération : " . $e->getMessage();
            return [];
        }
    }

    public static function emailExiste($email, $excludeId = null) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM utilisateur WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            echo "Erreur lors de la vérification : " . $e->getMessage();
            return false;
        }
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM utilisateur";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            echo "Erreur lors du comptage : " . $e->getMessage();
            return 0;
        }
    }
}
?>