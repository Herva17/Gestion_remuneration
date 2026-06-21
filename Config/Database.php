<?php

class Database {
    private static $host = 'localhost';
    private static $db_name = 'gestion_remuneration';
    private static $user = 'root';
    private static $password = '';
    private static $conn;

    /**
     * Établit une connexion à la base de données
     */
    public static function connect() {
        self::$conn = null;

        try {
            $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$db_name . ';charset=utf8';
            self::$conn = new PDO($dsn, self::$user, self::$password);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Erreur de connexion : ' . $e->getMessage());
        }

        return self::$conn;
    }

    /**
     * Retourne la connexion existante
     */
    public static function getInstance() {
        if (self::$conn === null) {
            self::connect();
        }
        return self::$conn;
    }

    /**
     * Ferme la connexion
     */
    public static function disconnect() {
        self::$conn = null;
    }
}
?>
