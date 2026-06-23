<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Utilisateur.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits (seul l'administrateur peut modifier les utilisateurs)
if ($_SESSION['user_role'] !== 'Administrateur') {
    $_SESSION['message'] = "Accès réservé aux administrateurs";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

// Vérification de l'ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID de l'utilisateur manquant";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);
$utilisateur = Utilisateur::getById($id);

// Vérification si l'utilisateur existe
if (!$utilisateur) {
    $_SESSION['message'] = "Utilisateur non trouvé";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');
    $confirmer_mot_de_passe = trim($_POST['confirmer_mot_de_passe'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($role)) {
        $errors[] = "Le rôle est obligatoire";
    }
    
    // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
    if (Utilisateur::emailExiste($email, $id)) {
        $errors[] = "Cet email est déjà utilisé par un autre utilisateur";
    }
    
    // Vérification du mot de passe si modifié
    if (!empty($mot_de_passe)) {
        if (strlen($mot_de_passe) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        }
        if ($mot_de_passe !== $confirmer_mot_de_passe) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
    }
    
    if (empty($errors)) {
        // Mise à jour de l'utilisateur
        $utilisateur->setNom($nom);
        $utilisateur->setEmail($email);
        $utilisateur->setRole($role);
        
        // Si un nouveau mot de passe est fourni, le mettre à jour
        if (!empty($mot_de_passe)) {
            $utilisateur->setMotDePasse($mot_de_passe);
        }
        
        if ($utilisateur->update()) {
            $_SESSION['message'] = "Utilisateur modifié avec succès";
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $message = "Erreur lors de la modification de l'utilisateur";
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Utilisateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<!-- Barre de navigation -->
<nav class="bg-white shadow px-6 py-3 flex justify-between items-center flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 transition" title="Retour">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <h1 class="text-xl font-bold text-blue-600">
            <i class="fas fa-user-edit mr-2"></i>Modifier l'Utilisateur
        </h1>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($role); ?></span>
        <span class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($username); ?></span>
        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white">
            <i class="fas fa-user text-sm"></i>
        </div>
        <a href="../../logout.php" class="text-sm text-red-600 hover:text-red-800">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<!-- Contenu principal -->
<div class="max-w-3xl mx-auto px-4 py-6">

    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Modifier l'utilisateur</h2>
            <p class="text-sm text-gray-500">Modification de l'utilisateur n°<?php echo $utilisateur->getId(); ?></p>
        </div>
        <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
    <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Formulaire de modification -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="">
            <!-- ID Utilisateur (lecture seule) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Utilisateur</label>
                <input type="text" value="<?php echo $utilisateur->getId(); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600" 
                       disabled>
            </div>

            <!-- Nom -->
            <div class="mb-4">
                <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">
                    Nom <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nom" name="nom" 
                       value="<?php echo htmlspecialchars($utilisateur->getNom()); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       required>
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($utilisateur->getEmail()); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       required>
            </div>

            <!-- Rôle -->
            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                    Rôle <span class="text-red-500">*</span>
                </label>
                <select id="role" name="role" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="Administrateur" <?php echo $utilisateur->getRole() === 'Administrateur' ? 'selected' : ''; ?>>Administrateur</option>
                    <option value="Comptable" <?php echo $utilisateur->getRole() === 'Comptable' ? 'selected' : ''; ?>>Comptable</option>
                    <option value="Secretaire" <?php echo $utilisateur->getRole() === 'Secretaire' ? 'selected' : ''; ?>>Secrétaire</option>
                    <option value="Caissier" <?php echo $utilisateur->getRole() === 'Caissier' ? 'selected' : ''; ?>>Caissier</option>
                </select>
            </div>

            <!-- Date de création (lecture seule) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de création</label>
                <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($utilisateur->getDateCreation())); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600" 
                       disabled>
            </div>

            <!-- Mot de passe (optionnel) -->
            <div class="mb-4">
                <label for="mot_de_passe" class="block text-sm font-medium text-gray-700 mb-1">
                    Nouveau mot de passe <span class="text-gray-400 text-xs">(laisser vide pour ne pas modifier)</span>
                </label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Nouveau mot de passe (6 caractères minimum)">
            </div>

            <!-- Confirmation du mot de passe -->
            <div class="mb-6">
                <label for="confirmer_mot_de_passe" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmer le nouveau mot de passe
                </label>
                <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Confirmer le nouveau mot de passe">
            </div>

            <!-- Boutons -->
            <div class="flex gap-3">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="index.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <!-- Pied de page -->
    <div class="mt-6 text-center text-xs text-gray-400">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>