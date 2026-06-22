<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès (avec les bons noms de rôles)
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable' && $_SESSION['user_role'] !== 'Secretaire') {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables et secrétaires";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../pages/affectations/index.php');
    exit;
}

// Inclusion de la classe Affectation
require_once __DIR__ . '/../../Classes/Affectation.php';

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID d'affectation non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$affectation_id = (int)$_GET['id'];

// Récupération de l'affectation
$affectation = Affectation::getById($affectation_id);

if (!$affectation) {
    $_SESSION['message'] = "Affectation non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Suppression de l'affectation
if ($affectation->delete()) {
    $_SESSION['message'] = "Affectation supprimée avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression de l'affectation";
    $_SESSION['message_type'] = 'error';
}

// Redirection vers la liste
header('Location: index.php');
exit;
?>