<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Avance.php';
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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID d'avance manquant.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);
$avance = Avance::getById($id);

if (!$avance) {
    $_SESSION['message'] = "Avance introuvable.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
$agent = Agent::getById($avance->getAgentId());
$nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
$montant = number_format($avance->getMontant(), 2, ',', ' ');
$libelle = $avance->getLibelle();
$statutLibelle = $avance->getStatutLibelle();
$statutBadge = $avance->isEnCours() ? 'badge-yellow' : 'badge-green';

$moisNoms = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$mois = isset($moisNoms[$avance->getMois()]) ? $moisNoms[$avance->getMois()] : $avance->getMois();
$periode = $mois . ' ' . $avance->getAnnee();

$dashboardRetour = '../../Dashboard.php';
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caissier') {
    $dashboardRetour = '../caissier/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Avance</title>
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

        .container { max-width: 800px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }

        .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; transition: 0.2s; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .mb-3 { margin-bottom: 12px; }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-item .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .detail-item .value {
            font-size: 16px;
            color: #1f2937;
            margin-top: 4px;
            font-weight: 500;
        }
        .detail-item .value.highlight {
            color: #2563eb;
            font-weight: bold;
            font-size: 18px;
        }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 500; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }

        .status-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .status-header .icon { font-size: 32px; }
        .status-header .info h3 { font-size: 16px; color: #1f2937; }
        .status-header .info p { font-size: 13px; color: #6b7280; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .detail-grid { grid-template-columns: 1fr; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-hand-holding-usd" style="color:#2563eb;"></i> Avances sur Salaire</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="index.php" class="active"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php elseif (strtolower($role) === 'caissier'): ?>
                <a href="../caissier/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="index.php" class="active"><i class="fas fa-hand-holding-usd"></i> Avances</a>
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

<div class="container">
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="top">
        <div>
            <h2>Détails de l'Avance</h2>
            <p>Informations complètes sur l'avance sur salaire</p>
        </div>
        <div>
            <span class="badge <?php echo $statutBadge; ?>" style="font-size:16px;padding:8px 16px;">
                <?php echo $statutLibelle; ?>
            </span>
        </div>
    </div>

    <div class="status-header">
        <div class="icon">
            <i class="fas fa-hand-holding-usd" style="color:#2563eb;"></i>
        </div>
        <div class="info">
            <h3>Avance N° #<?php echo $avance->getId(); ?></h3>
            <p><?php echo htmlspecialchars($nomAgent); ?> - <?php echo $periode; ?></p>
        </div>
    </div>

    <div class="card">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="label"><i class="fas fa-user"></i> Agent</div>
                <div class="value"><?php echo htmlspecialchars($nomAgent); ?></div>
            </div>

            <div class="detail-item">
                <div class="label"><i class="fas fa-tag"></i> Libellé</div>
                <div class="value"><?php echo htmlspecialchars($libelle); ?></div>
            </div>

            <div class="detail-item">
                <div class="label"><i class="fas fa-dollar-sign"></i> Montant</div>
                <div class="value highlight"><?php echo $montant; ?> $</div>
            </div>

            <div class="detail-item">
                <div class="label"><i class="fas fa-calendar-alt"></i> Période</div>
                <div class="value"><?php echo htmlspecialchars($periode); ?></div>
            </div>

            <div class="detail-item">
                <div class="label"><i class="fas fa-info-circle"></i> Statut</div>
                <div class="value">
                    <span class="badge <?php echo $statutBadge; ?>">
                        <?php echo $statutLibelle; ?>
                    </span>
                </div>
            </div>

            <div class="detail-item">
                <div class="label"><i class="fas fa-calendar-plus"></i> Date d'octroi</div>
                <div class="value"><?php echo date('d/m/Y H:i', strtotime($avance->getDateCreation())); ?></div>
            </div>
        </div>

        <div class="btn-group" style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e7eb;">
            <a href="edit.php?id=<?php echo $avance->getId(); ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <?php if ($avance->isEnCours()): ?>
                <a href="rembourser.php?id=<?php echo $avance->getId(); ?>" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Marquer remboursé
                </a>
            <?php endif; ?>
            <a href="delete.php?id=<?php echo $avance->getId(); ?>" class="btn btn-danger" style="background:#dc2626;color:white;"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette avance ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Avances sur Salaire
    </div>
</div>

</body>
</html>