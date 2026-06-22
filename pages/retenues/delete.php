<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Retenue.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits (Administrateur ou Caissier)
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: ../paiements/index.php');
    exit;
}

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID de retenue manquant";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

// Récupération de la retenue
$retenue = Retenue::getById($id);

if (!$retenue) {
    $_SESSION['message'] = "Retenue non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Suppression de la retenue
if ($retenue->delete()) {
    $_SESSION['message'] = "Retenue supprimée avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression de la retenue";
    $_SESSION['message_type'] = 'error';
}

header('Location: index.php');
exit;
?>