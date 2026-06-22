<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/remuneration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID de rémunération non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$remuneration_id = (int)$_GET['id'];

// Récupération de la rémunération
$remuneration = Remuneration::getById($remuneration_id);

if (!$remuneration) {
    $_SESSION['message'] = "Rémunération non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Suppression de la rémunération
if ($remuneration->delete()) {
    $_SESSION['message'] = "Rémunération supprimée avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression de la rémunération";
    $_SESSION['message_type'] = 'error';
}

// Redirection vers la liste
header('Location: index.php');
exit;
?>