<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$remunerations = Remuneration::getAll();

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
$totalRemunerations = count($remunerations);

// Calcul des statistiques
$totalMontant = 0;
foreach ($remunerations as $remuneration) {
    $totalMontant += $remuneration->getMontant();
}

// Mois actuel pour le filtre
$moisActuel = date('F');
$anneeActuelle = date('Y');
$totalMois = Remuneration::getTotalByMonth($moisActuel, $anneeActuelle);

// ========== DÉTERMINER LA PAGE DE RETOUR ==========
$dashboardRetour = '../../Dashboard.php'; // Par défaut (administrateur)
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caissier') {
    $dashboardRetour = '../caissier/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rémunérations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        
        .header { background: white; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex-wrap: wrap; gap: 10px; }
        .header-left { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .header-left h1 { color: #2563eb; font-size: 18px; }
        .nav-links { display: flex; gap: 6px; flex-wrap: wrap; }
        .nav-links a { color: #4b5563; text-decoration: none; font-size: 13px; padding: 4px 10px; border-radius: 4px; transition: 0.2s; }
        .nav-links a:hover { background: #e5e7eb; color: #1f2937; }
        .nav-links a.active { background: #2563eb; color: white; }
        .nav-links a.active:hover { background: #1d4ed8; }
        .header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .header-right .role { color: #666; font-size: 14px; }
        .header-right .username { font-weight: 600; color: #1f2937; font-size: 14px; }
        .header-right .avatar { width: 32px; height: 32px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; }
        .header-right .logout { color: #dc2626; text-decoration: none; font-size: 14px; }
        .header-right .logout:hover { text-decoration: underline; }

        .container { max-width: 1280px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }

        .card { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card .number { font-size: 24px; font-weight: bold; }
        .card .label { font-size: 13px; color: #666; }

        .border-purple { border-left: 4px solid #9333ea; }
        .border-green { border-left: 4px solid #16a34a; }
        .border-blue { border-left: 4px solid #2563eb; }
        .border-orange { border-left: 4px solid #f97316; }
        .border-red { border-left: 4px solid #dc2626; }

        .text-purple { color: #9333ea; }
        .text-green { color: #16a34a; }
        .text-blue { color: #2563eb; }
        .text-orange { color: #f97316; }
        .text-red { color: #dc2626; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8f9fa; }

        .empty { text-align: center; padding: 24px; color: #999; }
        .empty i { font-size: 24px; display: block; margin-bottom: 8px; }

        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-purple { background: #9333ea; color: white; }
        .btn-purple:hover { background: #7c3aed; }
        .btn-orange { background: #f97316; color: white; }
        .btn-orange:hover { background: #ea580c; }
        .btn-red { background: #dc2626; color: white; }
        .btn-red:hover { background: #b91c1c; }
        .btn-pink { background: #ec4899; color: white; }
        .btn-pink:hover { background: #db2777; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-yellow { background: #fef3c7; color: #92400e; }

        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .mt-4 { margin-top: 16px; }
        .mb-3 { margin-bottom: 12px; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-group .btn { display: flex; align-items: center; gap: 6px; }

        .action-icons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .action-icons a {
            padding: 4px 8px;
            border-radius: 4px;
            transition: 0.2s;
            text-decoration: none;
            font-size: 14px;
        }
        .action-icons .edit { color: #ca8a04; }
        .action-icons .edit:hover { background: #fef3c7; }
        .action-icons .delete { color: #dc2626; }
        .action-icons .delete:hover { background: #fee2e2; }
        .action-icons .view { color: #2563eb; }
        .action-icons .view:hover { background: #dbeafe; }
        .action-icons .pay { color: #ec4899; }
        .action-icons .pay:hover { background: #fce7f3; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-4 { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
            .btn-group { flex-direction: column; width: 100%; }
            .btn-group .btn { justify-content: center; }
            .top { flex-direction: column; align-items: stretch; }
            .top .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-money-bill-wave" style="color:#2563eb;"></i> Gestion Rémunérations</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
                <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
                <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
                <a href="index.php" class="active"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php elseif (strtolower($role) === 'caissier'): ?>
                <a href="../caissier/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="index.php" class="active"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<!-- ===== CONTENU ===== -->
<div class="container">

    <!-- ===== BOUTON RETOUR ===== -->
    <div class="mb-3">
        <a href="<?php echo $dashboardRetour; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au Dashboard
        </a>
        <?php if (strtolower($role) === 'caissier'): ?>
            <span style="font-size:12px;color:#16a34a;margin-left:8px;">
                <i class="fas fa-info-circle"></i> Retour vers le dashboard caissier
            </span>
        <?php else: ?>
            <span style="font-size:12px;color:#2563eb;margin-left:8px;">
                <i class="fas fa-info-circle"></i> Retour vers le dashboard principal
            </span>
        <?php endif; ?>
    </div>

    <div class="top">
        <div>
            <h2>Gestion des Rémunérations</h2>
            <p>Gérez les rémunérations des agents</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Ajouter</a>
        </div>
    </div>

    <!-- Boutons d'accès aux états de sortie -->
    <div style="background:#f8f9fa;border-radius:8px;padding:12px 16px;margin-bottom:16px;border:1px solid #e5e7eb;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-weight:600;color:#374151;font-size:14px;">
                <i class="fas fa-file-alt text-purple-600"></i> États de sortie :
            </span>
            <div class="btn-group">
                <a href="report_annual.php?year=<?php echo date('Y'); ?>" target="_blank" class="btn btn-purple">
                    <i class="fas fa-calendar-alt"></i> Rapport annuel
                </a>
                <a href="fiche_periodique.php" class="btn btn-orange">
                    <i class="fas fa-calendar-week"></i> Fiche périodique
                </a>
                <a href="print.php" target="_blank" class="btn btn-primary">
                    <i class="fas fa-print"></i> Liste imprimable
                </a>
                <?php if ($role === 'Administrateur' || $role === 'Comptable'): ?>
                <a href="export_csv.php" class="btn btn-red">
                    <i class="fas fa-file-csv"></i> Exporter CSV
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="grid-4">
        <div class="card border-purple">
            <div class="number text-purple"><?php echo $totalRemunerations; ?></div>
            <div class="label"><i class="fas fa-money-bill-wave"></i> Total Rémunérations</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo number_format($totalMontant, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-dollar-sign"></i> Montant Total</div>
        </div>
        <div class="card border-blue">
            <div class="number text-blue"><?php echo $totalRemunerations > 0 ? number_format($totalMontant / $totalRemunerations, 2, ',', ' ') : '0,00'; ?> $</div>
            <div class="label"><i class="fas fa-chart-bar"></i> Moyenne</div>
        </div>
        <div class="card border-orange">
            <div class="number text-orange"><?php echo number_format($totalMois, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-calendar-alt"></i> <?php echo $moisActuel . ' ' . $anneeActuelle; ?></div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Agent</th>
                        <th>Montant</th>
                        <th>Mois</th>
                        <th>Année</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($remunerations)): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            <i class="fas fa-inbox"></i>
                            Aucune rémunération trouvée
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($remunerations as $remuneration): 
                            $agent = $remuneration->getAgent();
                            $agentId = 0;
                            if ($agent) {
                                if (method_exists($agent, 'getIdAgent')) {
                                    $agentId = $agent->getIdAgent();
                                } elseif (method_exists($agent, 'getId')) {
                                    $agentId = $agent->getId();
                                } elseif (method_exists($agent, 'getAgentId')) {
                                    $agentId = $agent->getAgentId();
                                } elseif (method_exists($agent, 'getID')) {
                                    $agentId = $agent->getID();
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo $remuneration->getId(); ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:28px;height:28px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:bold;">
                                        <?php 
                                        $nom = $agent ? $agent->getNomComplet() : 'N/A';
                                        $initials = '';
                                        $parts = explode(' ', $nom);
                                        foreach ($parts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        echo substr($initials, 0, 2);
                                        ?>
                                    </div>
                                    <?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : '<span style="color:#999;">Agent inconnu</span>'; ?>
                                </div>
                            </td>
                            <td class="text-green" style="font-weight:bold;">
                                <?php echo number_format($remuneration->getMontant(), 2, ',', ' '); ?> $
                            </td>
                            <td>
                                <span class="badge badge-blue">
                                    <?php echo htmlspecialchars($remuneration->getMois()); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-green">
                                    <?php echo htmlspecialchars($remuneration->getAnnee()); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($remuneration->getDateRemun())); ?></td>
                            <td>
                                <div class="action-icons">
                                    <a href="edit.php?id=<?php echo $remuneration->getId(); ?>" class="edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $remuneration->getId(); ?>" class="delete" title="Supprimer"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette rémunération ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="receipt.php?id=<?php echo $remuneration->getId(); ?>" class="view" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($agentId > 0): ?>
                                    <a href="fiche_paie.php?id=<?php echo $remuneration->getId(); ?>&agent_id=<?php echo $agentId; ?>" class="pay" title="Fiche de paie" target="_blank">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
            <span>Total : <strong><?php echo $totalRemunerations; ?></strong> rémunération<?php echo $totalRemunerations > 1 ? 's' : ''; ?></span>
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

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>