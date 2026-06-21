<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/entrees.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
if ($_SESSION['user_role'] !== 'administrateur') {
    $_SESSION['message'] = "Accès réservé aux administrateurs";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "ID d'entrée non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$entree = Entree::getById($_GET['id']);
if (!$entree) {
    $_SESSION['message'] = "Entrée non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

if ($entree->delete()) {
    $_SESSION['message'] = "Entrée supprimée avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression";
    $_SESSION['message_type'] = 'error';
}

header('Location: index.php');
exit;
?>