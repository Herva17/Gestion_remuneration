<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès
$allowed_roles = ['Administrateur', 'Comptable', 'Secretaire'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables et secrétaires";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

// Récupération des données utilisateur
$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Récupération des avantages
$avantages = Avantage::getAll();

// Gestion des messages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);

// Statistiques
$total_avantages = count($avantages);
$total_montant = 0;
$total_actif = 0;
$total_inactif = 0;

foreach ($avantages as $avantage) {
    $total_montant += $avantage->getMontant();
    if ($avantage->getStatut() === 'actif') {
        $total_actif++;
    } else {
        $total_inactif++;
    }
}

$moyenne = $total_avantages > 0 ? $total_montant / $total_avantages : 0;

// Année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$anneeDesignation = $anneeCourante ? $anneeCourante->getDesignationAnn() : 'Non définie';
$totalAnnee = $anneeCourante ? Avantage::getTotalByAnnee($anneeCourante->getId()) : 0;

// Fonction pour le badge de type
function getTypeBadge($type) {
    $colors = [
        'transport' => 'badge-blue',
        'communication' => 'badge-purple',
        'logement' => 'badge-orange',
        'prime' => 'badge-green',
        'bonus' => 'badge-yellow',
        'autre' => 'badge-gray'
    ];
    return $colors[$type] ?? 'badge-gray';
}

function getTypeIcon($type) {
    $icons = [
        'transport' => 'fa-car',
        'communication' => 'fa-phone',
        'logement' => 'fa-home',
        'prime' => 'fa-star',
        'bonus' => 'fa-award',
        'autre' => 'fa-ellipsis-h'
    ];
    return $icons[$type] ?? 'fa-ellipsis-h';
}

// Fonction pour le badge de statut
function getStatutBadge($statut) {
    $colors = [
        'actif' => 'badge-green',
        'inactif' => 'badge-gray',
        'en_attente' => 'badge-orange'
    ];
    return $colors[$statut] ?? 'badge-gray';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Avantages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER ===== */
        .header {
            background: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            flex-wrap: wrap;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .header-left h1 {
            color: #2563eb;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-links {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .nav-links a:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .nav-links a.active {
            background: #eab308;
            color: #ffffff;
        }
        .nav-links a.active:hover {
            background: #ca8a04;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .header-right .role {
            color: #94a3b8;
            font-size: 13px;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .header-right .username {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }
        .header-right .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #eab308, #ca8a04);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            font-weight: 600;
        }
        .header-right .logout {
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .header-right .logout:hover {
            color: #dc2626;
            text-decoration: underline;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 20px;
            flex: 1;
            width: 100%;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }
        .top-bar p {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }
        .top-bar .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border-left: 4px solid #2563eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-card .label {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .stat-card .label i {
            margin-right: 6px;
        }

        .stat-yellow { border-color: #eab308; }
        .stat-green { border-color: #22c55e; }
        .stat-blue { border-color: #3b82f6; }
        .stat-purple { border-color: #a855f7; }

        .text-yellow { color: #eab308; }
        .text-green { color: #22c55e; }
        .text-blue { color: #3b82f6; }
        .text-purple { color: #a855f7; }

        .export-bar {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .export-bar .label {
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        .export-bar .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-success {
            background: #22c55e;
            color: white;
        }
        .btn-success:hover {
            background: #16a34a;
        }
        .btn-yellow {
            background: #eab308;
            color: white;
        }
        .btn-yellow:hover {
            background: #ca8a04;
        }
        .btn-orange {
            background: #f97316;
            color: white;
        }
        .btn-orange:hover {
            background: #ea580c;
        }
        .btn-purple {
            background: #a855f7;
            color: white;
        }
        .btn-purple:hover {
            background: #9333ea;
        }

        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .table-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .table-wrap {
            overflow-x: auto;
            padding: 0 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:hover td {
            background: #f8fafc;
        }
        tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 40px;
            display: block;
            margin-bottom: 12px;
            color: #cbd5e1;
        }

        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-green {
            background: #dcfce7;
            color: #166534;
        }
        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-orange {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-purple {
            background: #f3e8ff;
            color: #6d28d9;
        }
        .badge-gray {
            background: #f1f5f9;
            color: #475569;
        }
        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .agent-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .agent-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-links {
            display: flex;
            gap: 8px;
        }
        .action-links a {
            color: #94a3b8;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .action-links a:hover {
            background: #f1f5f9;
        }
        .action-links .edit { color: #eab308; }
        .action-links .edit:hover { color: #ca8a04; background: #fef3c7; }
        .action-links .delete { color: #ef4444; }
        .action-links .delete:hover { color: #dc2626; background: #fee2e2; }
        .action-links .view { color: #3b82f6; }
        .action-links .view:hover { color: #2563eb; background: #dbeafe; }

        .table-footer {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 12px;
        }
        .table-footer .total strong {
            color: #0f172a;
        }
        .pagination {
            display: flex;
            gap: 4px;
        }
        .pagination button,
        .pagination span {
            padding: 6px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            color: #475569;
        }
        .pagination button:hover:not(:disabled) {
            background: #f1f5f9;
        }
        .pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pagination .active {
            background: #eab308;
            border-color: #eab308;
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            padding: 16px 0;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
            background: white;
        }

        /* Style pour la colonne description */
        .description-cell {
            max-width: 200px;
            word-wrap: break-word;
            white-space: normal;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 16px;
            }
            .header-left {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .nav-links {
                justify-content: center;
                gap: 2px;
            }
            .nav-links a {
                font-size: 12px;
                padding: 4px 8px;
            }
            .header-right {
                justify-content: center;
                gap: 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stat-card {
                padding: 16px;
            }
            .stat-card .number {
                font-size: 22px;
            }
            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .top-bar .actions .btn {
                width: 100%;
                justify-content: center;
            }
            .export-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .export-bar .btn-group .btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }
            .table-footer {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .action-links {
                gap: 4px;
            }
            .action-links a {
                padding: 4px 6px;
            }
            .description-cell {
                max-width: 120px;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .stat-card {
                padding: 12px 16px;
            }
            .stat-card .number {
                font-size: 18px;
            }
            .stat-card .label {
                font-size: 11px;
            }
            .description-cell {
                max-width: 80px;
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="header">
    <div class="header-left">
        <h1><i class="fas fa-gift" style="color:#eab308;"></i> Gestion Avantages</h1>
        <nav class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
                <a href="../../pages/utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="../../pages/agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="../../pages/services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../../pages/affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="../../pages/remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="../../pages/retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="index.php" class="active"><i class="fas fa-gift"></i> Avantages</a>
            <a href="../../avantages/AnneeScolaire.php"><i class="fas fa-calendar-alt"></i> Années</a>
        </nav>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</header>

<!-- ===== CONTENU PRINCIPAL ===== -->
<main class="container">

    <div class="top-bar">
        <div>
            <h2>Liste des Avantages</h2>
            <p><i class="fas fa-info-circle"></i> Gérez les avantages accordés aux agents</p>
        </div>
        <div class="actions">
            <a href="add.php" class="btn btn-yellow">
                <i class="fas fa-plus-circle"></i> Nouvel Avantage
            </a>
        </div>
    </div>

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
                <a href="export_csv.php" class="btn btn-success">
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

    <div class="stats-grid">
        <div class="stat-card stat-yellow">
            <div class="number text-yellow"><?php echo $total_avantages; ?></div>
            <div class="label"><i class="fas fa-gift"></i> Total Avantages</div>
        </div>
        <div class="stat-card stat-green">
            <div class="number text-green"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-dollar-sign"></i> Montant Total</div>
        </div>
        <div class="stat-card stat-blue">
            <div class="number text-blue"><?php echo $total_avantages > 0 ? number_format($moyenne, 2, ',', ' ') : '0,00'; ?> $</div>
            <div class="label"><i class="fas fa-chart-bar"></i> Moyenne par avantage</div>
        </div>
        <div class="stat-card stat-purple">
            <div class="number text-purple"><?php echo number_format($totalAnnee, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($anneeDesignation); ?></div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Agent</th>
                        <th>Libellé</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Période</th>
                        <th>Statut</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avantages)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Aucun avantage enregistré pour le moment.</p>
                                    <a href="add.php" class="btn btn-yellow" style="margin-top:12px;">
                                        <i class="fas fa-plus-circle"></i> Ajouter un avantage
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avantages as $index => $avantage): 
                            $agent = $avantage->getAgent();
                            $annee = $avantage->getAnneeScolaire();
                            $nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
                            $initials = '';
                            $parts = explode(' ', $nomAgent);
                            foreach ($parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                            $colors = ['#eab308', '#22c55e', '#3b82f6', '#a855f7', '#ef4444', '#ec4899', '#14b8a6', '#f59e0b'];
                            $avatarColor = $colors[$index % count($colors)];
                            
                            $typeClass = getTypeBadge($avantage->getTypeAvantage());
                            $typeIcon = getTypeIcon($avantage->getTypeAvantage());
                            
                            $statutBadge = getStatutBadge($avantage->getStatut());
                            
                            $description = $avantage->getDescription();
                            $descriptionDisplay = $description ? htmlspecialchars($description) : 'N/A';
                            if (strlen($descriptionDisplay) > 50) {
                                $descriptionDisplay = substr($descriptionDisplay, 0, 50) . '...';
                            }
                        ?>
                            <tr>
                                <td><span style="color:#94a3b8;font-weight:600;"><?php echo $avantage->getId(); ?></span></td>
                                <td>
                                    <div class="agent-info">
                                        <div class="agent-avatar" style="background:<?php echo $avatarColor; ?>;">
                                            <?php echo $initials ?: '?'; ?>
                                        </div>
                                        <?php echo htmlspecialchars($nomAgent); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($avantage->getLibelle() ?? 'N/A'); ?>
                                    <?php if ($avantage->isRecurrent()): ?>
                                        <span class="badge badge-blue" style="font-size:10px;margin-left:4px;">
                                            <i class="fas fa-sync-alt"></i> Récurrent
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $typeClass; ?>">
                                        <i class="fas <?php echo $typeIcon; ?>"></i>
                                        <?php echo $avantage->getTypeLibelle(); ?>
                                    </span>
                                </td>
                                <td style="font-weight:600;color:#eab308;">
                                    + <?php echo number_format($avantage->getMontant(), 2, ',', ' '); ?> $
                                </td>
                                <td>
                                    <?php if ($avantage->getMois() && $avantage->getAnnee()): ?>
                                        <span class="badge badge-blue">
                                            <?php echo htmlspecialchars($avantage->getMois() . ' ' . $avantage->getAnnee()); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statutBadge; ?>">
                                        <?php echo $avantage->getStatutLibelle(); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="view.php?id=<?php echo $avantage->getId(); ?>" class="view" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $avantage->getId(); ?>" class="edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $avantage->getId(); ?>" class="delete" title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avantage ? Cette action est irréversible.')">
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
                Total : <strong><?php echo $total_avantages; ?></strong> avantage<?php echo $total_avantages > 1 ? 's' : ''; ?>
                <?php if ($total_actif > 0): ?>
                    <span style="color:#22c55e;margin-left:12px;">
                        <i class="fas fa-check-circle"></i> <?php echo $total_actif; ?> actif<?php echo $total_actif > 1 ? 's' : ''; ?>
                    </span>
                <?php endif; ?>
                <?php if ($total_inactif > 0): ?>
                    <span style="color:#94a3b8;margin-left:8px;">
                        <i class="fas fa-circle"></i> <?php echo $total_inactif; ?> inactif<?php echo $total_inactif > 1 ? 's' : ''; ?>
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

</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> - Gestion des Rémunérations &bull; Tous droits réservés
</footer>

</body>
</html>