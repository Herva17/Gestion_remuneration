<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Avance.php';
require_once __DIR__ . '/../../Classes/Agent.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification du rôle - Seul le Caissier a accès
$role = $_SESSION['user_role'] ?? '';
if ($role !== 'Caissier') {
    $_SESSION['message'] = "Accès non autorisé";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Statistiques
$totalRemunerations = Remuneration::count();
$totalRetenues = Retenue::count();
$totalAvantages = Avantage::count();
$totalAvances = Avance::count();
$totalAgents = Agent::count();

// Calcul des montants totaux
$remunerations = Remuneration::getAll();
$totalMontantRemunerations = 0;
foreach ($remunerations as $rem) {
    $totalMontantRemunerations += $rem->getMontant();
}

$retenues = Retenue::getAll();
$totalMontantRetenues = 0;
foreach ($retenues as $ret) {
    $totalMontantRetenues += $ret->getMontant();
}

$avantages = Avantage::getAll();
$totalMontantAvantages = 0;
foreach ($avantages as $av) {
    $totalMontantAvantages += $av->getMontant();
}

$avances = Avance::getAll();
$totalMontantAvances = 0;
foreach ($avances as $av) {
    $totalMontantAvances += $av->getMontant();
}

$salaireNetTotal = $totalMontantRemunerations - $totalMontantRetenues;

// Mois courant
$moisCourant = date('F');
$anneeCourante = date('Y');

// Dernières rémunérations
$recent_remunerations = array_slice($remunerations, -5);
$recent_remunerations = array_reverse($recent_remunerations);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Caissier - Gestion des Rémunérations</title>
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
        .nav-links a.active { background: #16a34a; color: #ffffff; }
        .nav-links a.active:hover { background: #15803d; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .header-right .role { color: #94a3b8; font-size: 13px; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; }
        .header-right .username { font-weight: 600; color: #0f172a; font-size: 14px; }
        .header-right .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #16a34a, #15803d); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 15px; font-weight: 600; }
        .header-right .logout { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .header-right .logout:hover { color: #dc2626; text-decoration: underline; }

        .container { max-width: 1280px; margin: 0 auto; padding: 24px 20px; flex: 1; width: 100%; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .top h2 { font-size: 24px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .top p { color: #64748b; font-size: 14px; margin-top: 4px; }
        .top .badge { background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 13px; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 4px solid #2563eb; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .card .number { font-size: 28px; font-weight: 700; }
        .card .label { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .card .label i { margin-right: 6px; }

        .border-green { border-color: #22c55e; }
        .border-red { border-color: #dc2626; }
        .border-yellow { border-color: #eab308; }
        .border-blue { border-color: #3b82f6; }
        .border-purple { border-color: #a855f7; }

        .text-green { color: #22c55e; }
        .text-red { color: #dc2626; }
        .text-yellow { color: #eab308; }
        .text-blue { color: #3b82f6; }
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

        .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }

        .footer { text-align: center; margin-top: 32px; padding: 16px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; background: white; }

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
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-cash-register" style="color:#16a34a;"></i> Gestion Caisse</h1>
        <nav class="nav-links">
            <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
            <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
        </nav>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="container">

    <div class="top">
        <div>
            <h2><i class="fas fa-chart-line" style="color:#16a34a;"></i> Tableau de Bord - Caisse</h2>
            <p><i class="fas fa-info-circle"></i> Vue d'ensemble des opérations de caisse</p>
        </div>
        <span class="badge">📅 <?php echo date('d/m/Y'); ?></span>
    </div>

    <div class="grid-4">
        <div class="card border-green">
            <div class="number text-green"><?php echo $totalRemunerations; ?></div>
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
        <div class="card border-purple">
            <div class="number text-purple"><?php echo $totalAvances; ?></div>
            <div class="label"><i class="fas fa-hand-holding-usd"></i> Avances</div>
        </div>
    </div>

    <div class="grid-4">
        <div class="card border-green">
            <div class="number text-green"><?php echo number_format($totalMontantRemunerations, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-arrow-up"></i> Total Rémunérations</div>
        </div>
        <div class="card border-red">
            <div class="number text-red"><?php echo number_format($totalMontantRetenues, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-arrow-down"></i> Total Retenues</div>
        </div>
        <div class="card border-yellow">
            <div class="number text-yellow"><?php echo number_format($totalMontantAvantages, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-gift"></i> Total Avantages</div>
        </div>
        <div class="card border-blue">
            <div class="number text-blue"><?php echo number_format($salaireNetTotal, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-calculator"></i> Salaire Net Total</div>
        </div>
    </div>

    <div class="table-card">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;">
            <h3 style="font-size:16px;color:#0f172a;"><i class="fas fa-clock"></i> Dernières Rémunérations</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Montant</th>
                        <th>Mois</th>
                        <th>Année</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_remunerations)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty">
                                <i class="fas fa-inbox"></i>
                                <p>Aucune rémunération récente</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_remunerations as $rem): 
                            $agent = $rem->getAgent();
                        ?>
                        <tr>
                            <td><?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : 'Inconnu'; ?></td>
                            <td style="font-weight:600;color:#22c55e;"><?php echo number_format($rem->getMontant(), 2, ',', ' '); ?> $</td>
                            <td><?php echo htmlspecialchars($rem->getMois()); ?></td>
                            <td><?php echo htmlspecialchars($rem->getAnnee()); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
            <a href="../remunerations/index.php" class="btn btn-primary">
                <i class="fas fa-list"></i> Voir toutes
            </a>
            <a href="../remunerations/add.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> - CS MAMAN MAPENDO - Gestion des Rémunérations
    </div>
</div>

</body>
</html>