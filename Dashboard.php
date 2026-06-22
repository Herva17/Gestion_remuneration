<?php
session_start();
require_once __DIR__ . '/Config/Database.php';
require_once __DIR__ . '/Classes/Agent.php';
require_once __DIR__ . '/Classes/Service.php';
require_once __DIR__ . '/Classes/Affectation.php';
require_once __DIR__ . '/Classes/remuneration.php';
require_once __DIR__ . '/Classes/retenue.php';
require_once __DIR__ . '/Classes/avantages.php';
require_once __DIR__ . '/Classes/annee_scolaire.php';
require_once __DIR__ . '/Classes/Dashboard.php';
require_once __DIR__ . '/Classes/Utilisateur.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Invité';

$dashboard = new Dashboard();

$totalAgents = $dashboard->getTotalAgents();
$totalServices = $dashboard->getTotalServices();
$totalRemunerations = Remuneration::count();
$totalRetenues = Retenue::count();
$totalAvantages = Avantage::count();

// Statistiques utilisateurs
$totalUtilisateurs = Utilisateur::count();
$administrateurs = count(Utilisateur::getByRole('Administrateur'));
$comptables = count(Utilisateur::getByRole('Comptable'));
$secretaires = count(Utilisateur::getByRole('Secretaire'));

$agents = Agent::getAll();
$services = Service::getAll();

$totalMontantRemunerations = 0;
$remunerations = Remuneration::getAll();
foreach ($remunerations as $remuneration) {
    $totalMontantRemunerations += $remuneration->getMontant();
}

$totalMontantRetenues = 0;
$retenues = Retenue::getAll();
foreach ($retenues as $retenue) {
    $totalMontantRetenues += $retenue->getMontant();
}

$totalMontantAvantages = 0;
$avantages = Avantage::getAll();
foreach ($avantages as $avantage) {
    $totalMontantAvantages += $avantage->getMontant();
}

$salaireNetTotal = $totalMontantRemunerations - $totalMontantRetenues;

$moisCourant = date('F');
$anneeCourante = date('Y');

// ========== CORRECTION : Utilisation des bonnes méthodes ==========

// Récupérer les totaux du mois via la classe Dashboard ou directement
// Option 1: Utiliser la classe Dashboard si elle a les méthodes
if (method_exists($dashboard, 'getTotalRemunerationsByMonth')) {
    $totalRemunerationsMois = $dashboard->getTotalRemunerationsByMonth($moisCourant, $anneeCourante);
    $totalRetenuesMois = $dashboard->getTotalRetenuesByMonth($moisCourant, $anneeCourante);
} else {
    // Option 2: Utiliser les méthodes statiques si elles existent
    if (method_exists('Remuneration', 'getTotalByMonth')) {
        $totalRemunerationsMois = Remuneration::getTotalByMonth($moisCourant, $anneeCourante);
    } else {
        // Option 3: Calcul manuel
        $totalRemunerationsMois = 0;
        foreach ($remunerations as $remuneration) {
            if ($remuneration->getMois() === $moisCourant && $remuneration->getAnnee() == $anneeCourante) {
                $totalRemunerationsMois += $remuneration->getMontant();
            }
        }
    }
    
    if (method_exists('Retenue', 'getTotalByMonth')) {
        $totalRetenuesMois = Retenue::getTotalByMonth($moisCourant, $anneeCourante);
    } else {
        // Calcul manuel des retenues du mois
        $totalRetenuesMois = 0;
        foreach ($retenues as $retenue) {
            if ($retenue->getMois() === $moisCourant && $retenue->getAnnee() == $anneeCourante) {
                $totalRetenuesMois += $retenue->getMontant();
            }
        }
    }
}

$salaireNetMois = $totalRemunerationsMois - $totalRetenuesMois;

$tauxRetenue = 0;
if ($totalMontantRemunerations > 0) {
    $tauxRetenue = ($totalMontantRetenues / $totalMontantRemunerations) * 100;
}

$recent_agents = array_slice($agents, -5);
$recent_agents = array_reverse($recent_agents);

$recent_remunerations = array_slice($remunerations, -5);
$recent_remunerations = array_reverse($recent_remunerations);

$agentsParService = $dashboard->getAgentsParService();
$topAgents = $dashboard->getTopAgents(5);
$agentsAvecRetenues = $dashboard->getAgentsAvecRetenues($moisCourant, $anneeCourante);
$salaireMoyen = $dashboard->getSalaireMoyen($moisCourant, $anneeCourante);

$anneeScolaireCourante = AnneeScolaire::getCurrent();
$anneeScolaireDesignation = $anneeScolaireCourante ? $anneeScolaireCourante->getDesignationAnn() : 'Non définie';

$totalAvantagesAnnee = 0;
if ($anneeScolaireCourante && method_exists('Avantage', 'getTotalByAnnee')) {
    $totalAvantagesAnnee = Avantage::getTotalByAnnee($anneeScolaireCourante->getId());
}

// Derniers utilisateurs
$recent_users = Utilisateur::getAll();
$recent_users = array_slice($recent_users, -5);
$recent_users = array_reverse($recent_users);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestion des Rémunérations</title>
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
        .header-right .avatar-link { display: inline-block; text-decoration: none; }
        .header-right .avatar { width: 32px; height: 32px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; cursor: pointer; transition: 0.2s; }
        .header-right .avatar:hover { background: #1d4ed8; transform: scale(1.05); }
        .header-right .avatar .fa-user { font-size: 14px; }
        .header-right .logout { color: #dc2626; text-decoration: none; font-size: 14px; }
        .header-right .logout:hover { text-decoration: underline; }

        .container { max-width: 1280px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }
        .top .badge { background: #dbeafe; color: #2563eb; padding: 4px 12px; border-radius: 12px; font-size: 13px; }

        .grid-6 { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 16px; }

        .card { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card .number { font-size: 24px; font-weight: bold; }
        .card .label { font-size: 13px; color: #666; }
        .card .sub { font-size: 12px; color: #999; margin-top: 4px; }

        .border-blue { border-left: 4px solid #2563eb; }
        .border-green { border-left: 4px solid #16a34a; }
        .border-purple { border-left: 4px solid #9333ea; }
        .border-red { border-left: 4px solid #dc2626; }
        .border-yellow { border-left: 4px solid #ca8a04; }
        .border-indigo { border-left: 4px solid #4f46e5; }
        .border-orange { border-left: 4px solid #f97316; }
        .border-pink { border-left: 4px solid #ec4899; }

        .text-blue { color: #2563eb; }
        .text-green { color: #16a34a; }
        .text-purple { color: #9333ea; }
        .text-red { color: #dc2626; }
        .text-yellow { color: #ca8a04; }
        .text-indigo { color: #4f46e5; }
        .text-orange { color: #f97316; }
        .text-pink { color: #ec4899; }

        .gradient { padding: 16px; border-radius: 8px; text-align: center; color: white; }
        .gradient.green { background: linear-gradient(135deg, #10b981, #059669); }
        .gradient.rose { background: linear-gradient(135deg, #f43f5e, #e11d48); }
        .gradient.violet { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .progress { width: 100%; background: #e5e7eb; border-radius: 4px; height: 6px; margin-top: 6px; }
        .progress-bar { background: #f97316; height: 6px; border-radius: 4px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8f9fa; }

        .empty { text-align: center; padding: 24px; color: #999; }
        .empty i { font-size: 24px; display: block; margin-bottom: 8px; }

        .btn { background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }

        .admin-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .admin-box h3 { color: #1e40af; font-size: 16px; }
        .admin-box p { color: #3b82f6; font-size: 14px; }

        .user-box { background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .user-box h3 { color: #92400e; font-size: 16px; }
        .user-box p { color: #78350f; font-size: 14px; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }
        
        .tag { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .tag-blue { background: #dbeafe; color: #2563eb; }
        .tag-green { background: #d1fae5; color: #065f46; }
        .tag-purple { background: #ede9fe; color: #5b21b6; }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .tag-yellow { background: #fef3c7; color: #92400e; }
        .tag-gray { background: #f3f4f6; color: #4b5563; }

        .redirect-link { display: inline-flex; align-items: center; gap: 6px; color: #2563eb; text-decoration: none; font-weight: 500; }
        .redirect-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-6, .grid-4, .grid-3, .grid-2 { grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1>💰 Gestion Rémunération</h1>
        <div class="nav-links">
            <a href="Dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
            <a href="pages/utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="pages/agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="pages/services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="pages/affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="pages/remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="pages/retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="pages/avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
            <a href="pages/avances/index.php"><i class="fas fa-calendar-alt"></i> Avances</a>
        </div>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <?php if ($role === 'Administrateur'): ?>
        <a href="pages/utilisateurs/index.php" class="avatar-link" title="Gérer les utilisateurs">
            <div class="avatar"><i class="fas fa-user"></i></div>
        </a>
        <?php else: ?>
        <div class="avatar"><i class="fas fa-user"></i></div>
        <?php endif; ?>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="container">

    <div class="top">
        <div>
            <h2>Tableau de Bord</h2>
            <p>Vue d'ensemble du système</p>
        </div>
        <span class="badge">📅 <?php echo $anneeScolaireDesignation; ?></span>
    </div>

    <!-- Cartes statistiques -->
    <div class="grid-6">
        <div class="card border-blue">
            <div class="number text-blue"><?php echo $totalAgents; ?></div>
            <div class="label"><i class="fas fa-users"></i> Agents</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo $totalServices; ?></div>
            <div class="label"><i class="fas fa-cogs"></i> Services</div>
        </div>
        <div class="card border-purple">
            <div class="number text-purple"><?php echo $totalRemunerations; ?></div>
            <div class="label"><i class="fas fa-money-bill-wave"></i> Rémunérations</div>
        </div>
        <div class="card border-red">
            <div class="number text-red"><?php echo $totalRetenues; ?></div>
            <div class="label"><i class="fas fa-arrow-down"></i> Retenues</div>
        </div>
        <div class="card border-yellow">
            <div class="number text-yellow"><?php echo $totalAvantages; ?></div>
            <div class="label"><i class="fas fa-gift"></i> Avantages</div>
        </div>
        <div class="card border-indigo">
            <div class="number text-indigo"><?php echo $agentsAvecRetenues; ?></div>
            <div class="label"><i class="fas fa-exclamation-triangle"></i> Avec retenues</div>
        </div>
    </div>

    <!-- Cartes Utilisateurs -->
    <div class="grid-4" style="margin-bottom:16px;">
        <div class="card border-pink">
            <div class="number text-pink"><?php echo $totalUtilisateurs; ?></div>
            <div class="label"><i class="fas fa-user-lock"></i> Total Utilisateurs</div>
        </div>
        <div class="card border-blue">
            <div class="number text-blue"><?php echo $administrateurs; ?></div>
            <div class="label"><i class="fas fa-user-shield"></i> Administrateurs</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo $comptables; ?></div>
            <div class="label"><i class="fas fa-user-tie"></i> Comptables</div>
        </div>
        <div class="card border-yellow">
            <div class="number text-yellow"><?php echo $secretaires; ?></div>
            <div class="label"><i class="fas fa-user-clock"></i> Secrétaires</div>
        </div>
    </div>

    <!-- Financières -->
    <div class="grid-4">
        <div class="card">
            <div class="label">💰 Total Rémunérations</div>
            <div class="number text-green"><?php echo number_format($totalMontantRemunerations, 2, ',', ' '); ?> $</div>
        </div>
        <div class="card">
            <div class="label">📉 Total Retenues</div>
            <div class="number text-red"><?php echo number_format($totalMontantRetenues, 2, ',', ' '); ?> $</div>
        </div>
        <div class="card">
            <div class="label">🏦 Salaire Net Total</div>
            <div class="number text-blue"><?php echo number_format($salaireNetTotal, 2, ',', ' '); ?> $</div>
        </div>
        <div class="card">
            <div class="label">📊 Salaire Moyen</div>
            <div class="number text-purple"><?php echo number_format($salaireMoyen, 2, ',', ' '); ?> $</div>
        </div>
    </div>

    <!-- Mois -->
    <div class="grid-3">
        <div class="gradient green">
            <div style="opacity:0.9;font-size:14px;">Rémunérations (<?php echo $moisCourant; ?>)</div>
            <div style="font-size:24px;font-weight:bold;"><?php echo number_format($totalRemunerationsMois, 2, ',', ' '); ?> $</div>
        </div>
        <div class="gradient rose">
            <div style="opacity:0.9;font-size:14px;">Retenues (<?php echo $moisCourant; ?>)</div>
            <div style="font-size:24px;font-weight:bold;"><?php echo number_format($totalRetenuesMois, 2, ',', ' '); ?> $</div>
        </div>
        <div class="gradient violet">
            <div style="opacity:0.9;font-size:14px;">Salaire Net (<?php echo $moisCourant; ?>)</div>
            <div style="font-size:24px;font-weight:bold;"><?php echo number_format($salaireNetMois, 2, ',', ' '); ?> $</div>
        </div>
    </div>

    <!-- Taux -->
    <div class="grid-2">
        <div class="card">
            <div class="label">📊 Taux de Retenue Global</div>
            <div class="number text-orange"><?php echo number_format($tauxRetenue, 1); ?>%</div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo min($tauxRetenue, 100); ?>%"></div></div>
        </div>
        <div class="card">
            <div class="label">🎁 Avantages (<?php echo $anneeScolaireDesignation; ?>)</div>
            <div class="number text-yellow"><?php echo number_format($totalAvantagesAnnee, 2, ',', ' '); ?> $</div>
        </div>
    </div>

    <!-- Agents par service -->
    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-bottom:12px;font-size:16px;color:#1f2937;">🏢 Répartition des Agents par Service</h3>
        <?php if (!empty($agentsParService)): ?>
            <?php 
            $maxAgents = 0;
            foreach ($agentsParService as $item) {
                if ($item['nombre'] > $maxAgents) $maxAgents = $item['nombre'];
            }
            ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Service</th><th>Nombre</th><th>Progression</th></tr></thead>
                    <tbody>
                        <?php foreach ($agentsParService as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['designation']); ?></td>
                            <td><strong><?php echo $item['nombre']; ?></strong></td>
                            <td>
                                <div style="width:150px;background:#e5e7eb;border-radius:4px;height:6px;">
                                    <div style="background:#2563eb;height:6px;border-radius:4px;width:<?php echo $maxAgents > 0 ? ($item['nombre'] / $maxAgents) * 100 : 0; ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty"><i class="fas fa-inbox"></i>Aucun service</div>
        <?php endif; ?>
    </div>

    <!-- Top, Derniers Agents -->
    <div class="grid-2">
        <div class="card">
            <h3 style="margin-bottom:12px;font-size:16px;color:#1f2937;">🏆 Top 5 Agents</h3>
            <?php if (!empty($topAgents)): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Agent</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($topAgents as $agent): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agent['nom_complet']); ?></td>
                                <td class="text-green"><strong><?php echo number_format($agent['total_remuneration'], 2, ',', ' '); ?> $</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><i class="fas fa-inbox"></i>Aucun agent</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-bottom:12px;font-size:16px;color:#1f2937;">🆕 Derniers Agents</h3>
            <?php if (!empty($recent_agents)): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nom</th><th>Fonction</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_agents as $agent): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agent->getNomComplet()); ?></td>
                                <td><?php echo htmlspecialchars($agent->getFonction() ?: 'Non défini'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><i class="fas fa-inbox"></i>Aucun agent</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dernières rémunérations -->
    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-bottom:12px;font-size:16px;color:#1f2937;">📜 Dernières Rémunérations</h3>
        <?php if (!empty($recent_remunerations)): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Agent</th><th>Montant</th><th>Mois</th><th>Année</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent_remunerations as $remuneration): 
                            $agent = $remuneration->getAgent();
                        ?>
                        <tr>
                            <td><?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : 'Inconnu'; ?></td>
                            <td class="text-green"><strong><?php echo number_format($remuneration->getMontant(), 2, ',', ' '); ?> $</strong></td>
                            <td><?php echo htmlspecialchars($remuneration->getMois()); ?></td>
                            <td><?php echo htmlspecialchars($remuneration->getAnnee()); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty"><i class="fas fa-inbox"></i>Aucune rémunération</div>
        <?php endif; ?>
    </div>

    <!-- ===== GESTION DES UTILISATEURS - ADMIN SEULEMENT ===== -->
    <?php if ($role === 'Administrateur'): ?>
    <div class="user-box">
        <div>
            <h3><i class="fas fa-user-cog"></i> Gestion des Utilisateurs</h3>
            <p>Gérez les comptes utilisateurs : Ajouter, Modifier, Supprimer</p>
            <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                <span class="tag tag-red"><i class="fas fa-user-shield"></i> <?php echo $administrateurs; ?> Admins</span>
                <span class="tag tag-green"><i class="fas fa-user-tie"></i> <?php echo $comptables; ?> Comptables</span>
                <span class="tag tag-yellow"><i class="fas fa-user-clock"></i> <?php echo $secretaires; ?> Secrétaires</span>
                <span class="tag tag-blue"><i class="fas fa-users"></i> <?php echo $totalUtilisateurs; ?> Total</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="pages/utilisateurs/index.php" class="btn" style="background:#2563eb;">
                <i class="fas fa-list"></i> Voir tous
            </a>
            <a href="pages/utilisateurs/add.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </div>
    </div>

    <!-- Liste des derniers utilisateurs -->
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <h3 style="font-size:16px;color:#1f2937;">
                <i class="fas fa-user-clock"></i> Derniers Utilisateurs
            </h3>
            <a href="pages/utilisateurs/index.php" class="redirect-link">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (!empty($recent_users)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user->getNom()); ?></td>
                            <td><?php echo htmlspecialchars($user->getEmail()); ?></td>
                            <td>
                                <?php 
                                $roleUser = $user->getRole();
                                $color = 'gray';
                                if ($roleUser === 'Administrateur') $color = 'red';
                                elseif ($roleUser === 'Comptable') $color = 'green';
                                elseif ($roleUser === 'Secretaire') $color = 'yellow';
                                ?>
                                <span class="tag tag-<?php echo $color; ?>"><?php echo htmlspecialchars($roleUser); ?></span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user->getDateCreation())); ?></td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="pages/utilisateurs/edit.php?id=<?php echo $user->getId(); ?>" 
                                       style="color:#2563eb;text-decoration:none;" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="pages/utilisateurs/delete.php?id=<?php echo $user->getId(); ?>" 
                                       style="color:#dc2626;text-decoration:none;" title="Supprimer"
                                       onclick="return confirm('Supprimer cet utilisateur ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty"><i class="fas fa-inbox"></i>Aucun utilisateur</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        © <?php echo date('Y'); ?> - Système de Gestion des Rémunérations
    </div>
</div>

</body>
</html>