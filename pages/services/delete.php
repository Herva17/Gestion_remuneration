<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et comptables";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../pages/services/index.php');
    exit;
}

// Inclusion de la classe Service
require_once __DIR__ . '/../../Classes/Service.php';

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID du service non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$service_id = (int)$_GET['id'];

// Récupération du service
$service = Service::getById($service_id);

if (!$service) {
    $_SESSION['message'] = "Service non trouvé";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Vérification si le service peut être supprimé (pas d'enfants)
if (method_exists($service, 'hasDependances') && $service->hasDependances()) {
    $_SESSION['message'] = "Impossible de supprimer ce service car il est utilisé par d'autres éléments";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Suppression du service
if ($service->delete()) {
    $_SESSION['message'] = "Service supprimé avec succès";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Erreur lors de la suppression du service";
    $_SESSION['message_type'] = 'error';
}

// Redirection vers la liste
header('Location: index.php');
exit;
?>