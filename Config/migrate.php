<?php
require_once __DIR__ . '/Database.php';

$db = Database::getInstance();

try {
    $result = $db->query("SHOW COLUMNS FROM Service LIKE 'photo'");
    if ($result->rowCount() === 0) {
        $db->exec("ALTER TABLE Service ADD COLUMN photo VARCHAR(255) NULL DEFAULT NULL");
        echo "Colonne 'photo' ajoutée à la table Service.\n";
    } else {
        echo "La colonne 'photo' existe déjà dans la table Service.\n";
    }

    $result2 = $db->query("SHOW COLUMNS FROM Service LIKE 'short_description'");
    if ($result2->rowCount() === 0) {
        $db->exec("ALTER TABLE Service ADD COLUMN short_description VARCHAR(255) NULL DEFAULT NULL");
        echo "Colonne 'short_description' ajoutée à la table Service.\n";
    } else {
        echo "La colonne 'short_description' existe déjà dans la table Service.\n";
    }
    // Ajouter id_agent à Paiement si nécessaire
    $result3 = $db->query("SHOW COLUMNS FROM Paiement LIKE 'id_agent'");
    if ($result3->rowCount() === 0) {
        $db->exec("ALTER TABLE Paiement ADD COLUMN id_agent INT NULL DEFAULT NULL");
        echo "Colonne 'id_agent' ajoutée à la table Paiement.\n";
    } else {
        echo "La colonne 'id_agent' existe déjà dans la table Paiement.\n";
    }
    // Ajouter contrainte FK si nécessaire
    try {
        $q = $db->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Paiement' AND COLUMN_NAME = 'id_agent' AND REFERENCED_TABLE_NAME = 'Agent'");
        $q->execute();
        if ($q->rowCount() === 0) {
            // Add foreign key constraint
            $db->exec("ALTER TABLE Paiement ADD CONSTRAINT fk_paiement_agent FOREIGN KEY (id_agent) REFERENCES Agent(id_agent) ON DELETE SET NULL ON UPDATE CASCADE");
            echo "Contrainte FK 'fk_paiement_agent' ajoutée.\n";
        } else {
            echo "La contrainte FK 'fk_paiement_agent' existe déjà.\n";
        }
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout de la contrainte FK : " . $e->getMessage() . "\n";
    }
} catch (PDOException $e) {
    echo "Erreur de migration : " . $e->getMessage();
}
?>