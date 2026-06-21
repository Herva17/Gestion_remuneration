<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et comptables";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$agents = Agent::getAll();

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
$totalAgents = count($agents);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Agents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<!-- Barre de navigation -->
<nav class="bg-white shadow px-6 py-3 flex justify-between items-center flex-wrap gap-3">
    <h1 class="text-xl font-bold text-blue-600">
        <i class="fas fa-coins mr-2"></i>Gestion Rémunération
    </h1>
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
<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Agents</h2>
            <p class="text-sm text-gray-500">Liste des agents de l'établissement (<?php echo $totalAgents; ?> agent<?php echo $totalAgents > 1 ? 's' : ''; ?>)</p>
        </div>
        <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> Ajouter
        </a>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
    <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">Total Agents</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo $totalAgents; ?></p>
        </div>
        <?php 
        $totalFonctions = 0;
        $fonctions = [];
        foreach ($agents as $agent) {
            $fonction = $agent->getFonction();
            if ($fonction && !in_array($fonction, $fonctions)) {
                $fonctions[] = $fonction;
                $totalFonctions++;
            }
        }
        ?>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500">Fonctions</p>
            <p class="text-2xl font-bold text-green-600"><?php echo $totalFonctions; ?></p>
        </div>
        <?php 
        $totalProfils = 0;
        $profils = [];
        foreach ($agents as $agent) {
            $profil = $agent->getProfil();
            if ($profil && !in_array($profil, $profils)) {
                $profils[] = $profil;
                $totalProfils++;
            }
        }
        ?>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500">Profils</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo $totalProfils; ?></p>
        </div>
        <?php 
        $aAffectation = 0;
        foreach ($agents as $agent) {
            $affectations = $agent->getAffectations();
            if (!empty($affectations)) {
                $aAffectation++;
            }
        }
        ?>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">Agents affectés</p>
            <p class="text-2xl font-bold text-orange-600"><?php echo $aAffectation; ?></p>
        </div>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">ID</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Nom</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Téléphone</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Fonction</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Profil</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Aucun agent trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($agents as $agent): 
                            $affectations = $agent->getAffectations();
                            $aAffectation = !empty($affectations);
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-800"><?php echo $agent->getIdAgent(); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs font-bold">
                                        <?php 
                                        $nom = $agent->getNomComplet();
                                        $initials = '';
                                        $parts = explode(' ', $nom);
                                        foreach ($parts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        echo substr($initials, 0, 2);
                                        ?>
                                    </div>
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($agent->getNomComplet()); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?php echo $agent->getTelephone() ? htmlspecialchars($agent->getTelephone()) : '<span class="text-gray-400">-</span>'; ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo $agent->getFonction() ? htmlspecialchars($agent->getFonction()) : '<span class="text-gray-400">-</span>'; ?></td>
                            <td class="px-4 py-3">
                                <?php 
                                $profil = $agent->getProfil();
                                $color = 'gray';
                                if ($profil === 'Enseignant') $color = 'blue';
                                elseif ($profil === 'Administratif') $color = 'green';
                                elseif ($profil === 'Direction') $color = 'purple';
                                elseif ($profil === 'Technique') $color = 'orange';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                    <?php echo htmlspecialchars($profil ?: '-'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($aAffectation): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Affecté
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>En attente
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="view.php?id=<?php echo $agent->getIdAgent(); ?>" class="text-blue-600 hover:text-blue-800" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $agent->getIdAgent(); ?>" class="text-yellow-600 hover:text-yellow-800" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $agent->getIdAgent(); ?>" class="text-red-600 hover:text-red-800" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet agent ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Footer du tableau -->
        <div class="px-4 py-3 bg-gray-50 border-t flex justify-between items-center flex-wrap gap-2">
            <span class="text-sm text-gray-600">Total : <strong><?php echo $totalAgents; ?></strong> agent<?php echo $totalAgents > 1 ? 's' : ''; ?></span>
            <div class="flex gap-2">
                <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 transition" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="px-3 py-1 bg-blue-600 text-white rounded text-sm">1</span>
                <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 transition" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="mt-6 text-center text-xs text-gray-400">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>