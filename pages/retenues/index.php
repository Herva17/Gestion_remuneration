<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès - Administrateur, Comptable, Secretaire ou Caissier
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable' && $_SESSION['user_role'] !== 'Secretaire' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables, secrétaires et caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$retenues = Retenue::getAll();

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
$totalRetenues = count($retenues);

// Calcul des statistiques
$totalMontant = 0;
$totalActif = 0;
$totalInactif = 0;

foreach ($retenues as $retenue) {
    $totalMontant += $retenue->getMontant();
    if ($retenue->getStatut() === 'actif') {
        $totalActif++;
    } else {
        $totalInactif++;
    }
}

// Mois et année actuelle
$moisActuel = date('F');
$anneeActuelle = date('Y');

// Calcul du total du mois
$totalMois = 0;
foreach ($retenues as $retenue) {
    if ($retenue->getMois() === $moisActuel && $retenue->getAnnee() == $anneeActuelle) {
        $totalMois += $retenue->getMontant();
    }
}

// ========== DÉTERMINER LA PAGE DE RETOUR ==========
$dashboardRetour = '../../Dashboard.php'; // Par défaut (administrateur)
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caissier') {
    $dashboardRetour = '../caissier/dashboard.php';
}

// Récupérer le type de retenue pour l'affichage
function getTypeBadge($type) {
    $colors = [
        'impot' => 'badge-purple',
        'assurance' => 'badge-blue',
        'cotisation' => 'badge-green',
        'avance' => 'badge-orange',
        'penalite' => 'badge-red',
        'autre' => 'badge-gray'
    ];
    return $colors[$type] ?? 'badge-gray';
}

function getTypeIcon($type) {
    $icons = [
        'impot' => 'fa-landmark',
        'assurance' => 'fa-shield-alt',
        'cotisation' => 'fa-handshake',
        'avance' => 'fa-hand-holding-usd',
        'penalite' => 'fa-exclamation-triangle',
        'autre' => 'fa-ellipsis-h'
    ];
    return $icons[$type] ?? 'fa-ellipsis-h';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Retenues</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header { background: #ffffff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .header-left h1 { color: #2563eb; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; gap: 4px; flex-wrap: wrap; }
        .nav-links a { color: #64748b; text-decoration: none; font-size: 13px; padding: 6px 12px; border-radius: 6px; transition: all 0.2s; font-weight: 500; }
        .nav-links a:hover { background: #f1f5f9; color: #0f172a; }
        .nav-links a.active { background: #dc2626; color: #ffffff; }
        .nav-links a.active:hover { background: #b91c1c; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .header-right .role { color: #94a3b8; font-size: 13px; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; }
        .header-right .username { font-weight: 600; color: #0f172a; font-size: 14px; }
        .header-right .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #dc2626, #b91c1c); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 15px; font-weight: 600; }
        .header-right .logout { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .header-right .logout:hover { color: #dc2626; text-decoration: underline; }

        .container { max-width: 1280px; margin: 0 auto; padding: 24px 20px; flex: 1; width: 100%; }

        .top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .top h2 { font-size: 24px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .top p { color: #64748b; font-size: 14px; margin-top: 4px; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }

        .card { background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 4px solid #2563eb; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .card .number { font-size: 28px; font-weight: 700; }
        .card .label { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .card .label i { margin-right: 6px; }

        .border-red { border-color: #dc2626; }
        .border-green { border-color: #22c55e; }
        .border-blue { border-color: #3b82f6; }
        .border-orange { border-color: #f97316; }
        .border-purple { border-color: #a855f7; }

        .text-red { color: #dc2626; }
        .text-green { color: #22c55e; }
        .text-blue { color: #3b82f6; }
        .text-orange { color: #f97316; }
        .text-purple { color: #a855f7; }

        .table-card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
        .table-wrap { overflow-x: auto; padding: 0 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 14px 16px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background: #f8fafc; }
        tr:last-child td { border-bottom: none; }

        .empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
        .empty i { font-size: 40px; display: block; margin-bottom: 12px; color: #cbd5e1; }

        .btn { padding: 8px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-red { background: #dc2626; color: white; }
        .btn-red:hover { background: #b91c1c; }
        .btn-orange { background: #f97316; color: white; }
        .btn-orange:hover { background: #ea580c; }
        .btn-purple { background: #a855f7; color: white; }
        .btn-purple:hover { background: #9333ea; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-danger { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }

        .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-purple { background: #f3e8ff; color: #6d28d9; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-yellow { background: #fef3c7; color: #92400e; }

        .agent-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .agent-info { display: flex; align-items: center; gap: 10px; }

        .action-icons { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-icons a { padding: 4px 8px; border-radius: 4px; transition: 0.2s; text-decoration: none; font-size: 14px; }
        .action-icons .edit { color: #ca8a04; }
        .action-icons .edit:hover { background: #fef3c7; }
        .action-icons .delete { color: #dc2626; }
        .action-icons .delete:hover { background: #fee2e2; }
        .action-icons .view { color: #3b82f6; }
        .action-icons .view:hover { background: #dbeafe; }

        .export-bar { background: #f8fafc; border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .export-bar .label { font-weight: 600; color: #334155; font-size: 14px; }
        .export-bar .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

        .table-footer { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 12px; }
        .table-footer .total strong { color: #0f172a; }
        .pagination { display: flex; gap: 4px; }
        .pagination button, .pagination span { padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 13px; cursor: pointer; transition: all 0.2s; color: #475569; }
        .pagination button:hover:not(:disabled) { background: #f1f5f9; }
        .pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
        .pagination .active { background: #dc2626; border-color: #dc2626; color: white; }

        .footer { text-align: center; margin-top: 32px; padding: 16px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; background: white; }

        .statut-actif { color: #22c55e; }
        .statut-inactif { color: #94a3b8; }
        .statut-en-attente { color: #f97316; }

        .type-icon { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 6px; }

        .description-cell { max-width: 200px; word-wrap: break-word; white-space: normal; }
        .description-cell .badge-gray { cursor: pointer; }

        .mb-3 { margin-bottom: 12px; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; padding: 12px 16px; }
            .header-left { flex-direction: column; align-items: stretch; gap: 10px; }
            .nav-links { justify-content: center; gap: 2px; }
            .nav-links a { font-size: 12px; padding: 4px 8px; }
            .header-right { justify-content: center; gap: 10px; }
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .card { padding: 16px; }
            .card .number { font-size: 22px; }
            .top { flex-direction: column; align-items: stretch; }
            .top .actions .btn { width: 100%; justify-content: center; }
            .export-bar { flex-direction: column; align-items: stretch; }
            .export-bar .btn-group .btn { flex: 1; justify-content: center; min-width: 120px; }
            .table-footer { flex-direction: column; align-items: center; text-align: center; }
            .action-icons { gap: 4px; }
            .action-icons a { padding: 4px 6px; }
            .description-cell { max-width: 120px; }
        }
        @media (max-width: 480px) {
            .grid-4 { grid-template-columns: 1fr 1fr; gap: 10px; }
            .card { padding: 12px 16px; }
            .card .number { font-size: 18px; }
            .card .label { font-size: 11px; }
            .description-cell { max-width: 80px; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Gestion Retenues</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
                <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
                <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
                <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="index.php" class="active"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php elseif (strtolower($role) === 'caissier'): ?>
                <a href="../caissier/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="index.php" class="active"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php else: ?>
                <!-- Autres rôles (Comptable, Secretaire) -->
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
                <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
                <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
                <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="index.php" class="active"><i class="fas fa-arrow-down"></i> Retenues</a>
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
            <h2><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Gestion des Retenues</h2>
            <p><i class="fas fa-info-circle"></i> Gérez les retenues effectuées sur les salaires des agents</p>
        </div>
        <div class="actions" style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="add.php" class="btn btn-red"><i class="fas fa-plus-circle"></i> Nouvelle Retenue</a>
        </div>
    </div>

    <!-- Export Bar -->
    <div class="export-bar">
        <span class="label"><i class="fas fa-file-export"></i> États de sortie :</span>
        <div class="btn-group">
            <a href="report_annual.php?year=<?php echo date('Y'); ?>" target="_blank" class="btn btn-purple">
                <i class="fas fa-calendar-alt"></i> Rapport annuel
            </a>
            <a href="fiche_periodique.php" class="btn btn-orange">
                <i class="fas fa-calendar-week"></i> Fiche périodique
            </a>
            <a href="print.php" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimer
            </a>
            <?php if ($role === 'Administrateur' || $role === 'Comptable'): ?>
            <a href="export_csv.php" class="btn btn-red">
                <i class="fas fa-file-csv"></i> Exporter CSV
            </a>
            <?php endif; ?>
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
        <div class="card border-red">
            <div class="number text-red"><?php echo $totalRetenues; ?></div>
            <div class="label"><i class="fas fa-arrow-down"></i> Total Retenues</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo number_format($totalMontant, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-dollar-sign"></i> Montant Total</div>
        </div>
        <div class="card border-blue">
            <div class="number text-blue"><?php echo $totalRetenues > 0 ? number_format($totalMontant / $totalRetenues, 2, ',', ' ') : '0,00'; ?> $</div>
            <div class="label"><i class="fas fa-chart-bar"></i> Moyenne par retenue</div>
        </div>
        <div class="card border-orange">
            <div class="number text-orange"><?php echo number_format($totalMois, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-calendar-alt"></i> <?php echo $moisActuel . ' ' . $anneeActuelle; ?></div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Agent</th>
                        <th>Libellé</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Période</th>
                        <th>Statut</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retenues)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty">
                                <i class="fas fa-inbox"></i>
                                <p>Aucune retenue enregistrée pour le moment.</p>
                                <a href="add.php" class="btn btn-red" style="margin-top:12px;">
                                    <i class="fas fa-plus-circle"></i> Ajouter une retenue
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($retenues as $index => $retenue): 
                            $agent = $retenue->getAgent();
                            $nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
                            $initials = '';
                            $parts = explode(' ', $nomAgent);
                            foreach ($parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                            $colors = ['#dc2626', '#b91c1c', '#ef4444', '#f87171', '#fca5a5'];
                            $avatarColor = $colors[$index % count($colors)];
                            
                            $typeClass = getTypeBadge($retenue->getType());
                            $typeIcon = getTypeIcon($retenue->getType());
                            
                            $statutClass = $retenue->getStatut() === 'actif' ? 'statut-actif' : ($retenue->getStatut() === 'en_attente' ? 'statut-en-attente' : 'statut-inactif');
                            $statutBadge = $retenue->getStatut() === 'actif' ? 'badge-green' : ($retenue->getStatut() === 'en_attente' ? 'badge-orange' : 'badge-gray');
                            
                            $description = $retenue->getDescription();
                            $descriptionDisplay = $description ? htmlspecialchars($description) : 'N/A';
                            if (strlen($descriptionDisplay) > 50) {
                                $descriptionDisplay = substr($descriptionDisplay, 0, 50) . '...';
                            }
                        ?>
                        <tr>
                            <td><span style="color:#94a3b8;font-weight:600;"><?php echo $retenue->getId(); ?></span></td>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar" style="background:<?php echo $avatarColor; ?>;">
                                        <?php echo $initials ?: '?'; ?>
                                    </div>
                                    <?php echo htmlspecialchars($nomAgent); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($retenue->getLibelle() ?? 'N/A'); ?>
                                <?php if ($retenue->isRecurrent()): ?>
                                    <span class="badge badge-blue" style="font-size:10px;margin-left:4px;">
                                        <i class="fas fa-sync-alt"></i> Récurrent
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="description-cell">
                                <?php echo $descriptionDisplay; ?>
                                <?php if ($description && strlen($description) > 50): ?>
                                    <span class="badge badge-gray" style="font-size:10px;cursor:pointer;" title="<?php echo htmlspecialchars($description); ?>">
                                        <i class="fas fa-info-circle"></i> + 
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $typeClass; ?>">
                                    <i class="fas <?php echo $typeIcon; ?>"></i>
                                    <?php echo $retenue->getTypeLibelle(); ?>
                                </span>
                            </td>
                            <td style="font-weight:600;color:#dc2626;">
                                - <?php echo number_format($retenue->getMontant(), 2, ',', ' '); ?> $
                            </td>
                            <td>
                                <?php if ($retenue->getMois() && $retenue->getAnnee()): ?>
                                    <span class="badge badge-blue">
                                        <?php echo htmlspecialchars($retenue->getMois() . ' ' . $retenue->getAnnee()); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $statutBadge; ?> <?php echo $statutClass; ?>">
                                    <?php echo $retenue->getStatutLibelle(); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-icons">
                                    <a href="view.php?id=<?php echo $retenue->getId(); ?>" class="view" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $retenue->getId(); ?>" class="edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $retenue->getId(); ?>" class="delete" title="Supprimer"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette retenue ? Cette action est irréversible.')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="total">
                Total : <strong><?php echo $totalRetenues; ?></strong> retenue<?php echo $totalRetenues > 1 ? 's' : ''; ?>
                <?php if ($totalActif > 0): ?>
                    <span style="color:#22c55e;margin-left:12px;">
                        <i class="fas fa-check-circle"></i> <?php echo $totalActif; ?> actif<?php echo $totalActif > 1 ? 's' : ''; ?>
                    </span>
                <?php endif; ?>
                <?php if ($totalInactif > 0): ?>
                    <span style="color:#94a3b8;margin-left:8px;">
                        <i class="fas fa-circle"></i> <?php echo $totalInactif; ?> inactif<?php echo $totalInactif > 1 ? 's' : ''; ?>
                    </span>
                <?php endif; ?>
            </span>
            <div class="pagination">
                <button disabled><i class="fas fa-chevron-left"></i></button>
                <span class="active">1</span>
                <button disabled><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> - Gestion des Rémunérations &bull; Tous droits réservés
    </div>
</div>

</body>
</html>