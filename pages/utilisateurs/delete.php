<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Utilisateur.php';

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
    $_SESSION['message'] = "Utilisateur introuvable";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$user = Utilisateur::getById($_GET['id']);

if (!$user) {
    $_SESSION['message'] = "Utilisateur introuvable";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

if ($user->getIdUtilisateur() === $_SESSION['user_id']) {
    $_SESSION['message'] = "Vous ne pouvez pas supprimer votre propre compte.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

if ($user->delete()) {
    $_SESSION['message'] = "Utilisateur supprimé avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression de l'utilisateur";
    $_SESSION['message_type'] = 'error';
}

header('Location: index.php');
exit;
