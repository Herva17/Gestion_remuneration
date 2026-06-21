<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Agent.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès (avec gestion des différents rôles)
$allowed_roles = ['Administrateur', 'administrateur', 'Comptable', 'Secretaire'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables et secrétaires";
    $_SESSION['message_type'] = 'error';
    header('Location: ../Dashboard.php');
    exit;
}

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID de l'agent non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id_agent = (int)$_GET['id'];

// Récupération de l'agent
$agent = Agent::getById($id_agent);

if (!$agent) {
    $_SESSION['message'] = "Agent non trouvé";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Vérification si l'agent a des enregistrements liés (retenues, rémunérations, avantages)
$hasRelations = false;
$relations = [];

// Vérifier les rémunérations
if (method_exists($agent, 'getRemunerations')) {
    $remunerations = $agent->getRemunerations();
    if (!empty($remunerations)) {
        $hasRelations = true;
        $relations[] = count($remunerations) . ' rémunération(s)';
    }
}

// Vérifier les retenues
if (method_exists($agent, 'getRetenues')) {
    $retenues = $agent->getRetenues();
    if (!empty($retenues)) {
        $hasRelations = true;
        $relations[] = count($retenues) . ' retenue(s)';
    }
}

// Vérifier les avantages
if (method_exists($agent, 'getAvantages')) {
    $avantages = $agent->getAvantages();
    if (!empty($avantages)) {
        $hasRelations = true;
        $relations[] = count($avantages) . ' avantage(s)';
    }
}

// Vérifier les affectations
if (method_exists($agent, 'getAffectations')) {
    $affectations = $agent->getAffectations();
    if (!empty($affectations)) {
        $hasRelations = true;
        $relations[] = count($affectations) . ' affectation(s)';
    }
}

if ($hasRelations) {
    $_SESSION['message'] = "Impossible de supprimer cet agent car il a des enregistrements liés : " . implode(', ', $relations) . ". Veuillez d'abord supprimer ces enregistrements.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Demander une confirmation supplémentaire si l'agent est actif
if ($agent->getStatut() === 'actif' || $agent->getStatut() === 'Actif') {
    // On pourrait ajouter une confirmation ici, mais pour le moment on continue
    // La confirmation est déjà gérée par le JavaScript dans la page index
}

// Suppression de l'agent
try {
    if ($agent->delete()) {
        $_SESSION['message'] = "Agent supprimé avec succès";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Erreur lors de la suppression de l'agent";
        $_SESSION['message_type'] = 'error';
    }
} catch (Exception $e) {
    $_SESSION['message'] = "Erreur lors de la suppression : " . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

header('Location: index.php');
exit;
?>