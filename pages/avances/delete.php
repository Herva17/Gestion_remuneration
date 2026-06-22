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

$agent = Agent::getById($avance->getAgentId());
$nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
$montant = number_format($avance->getMontant(), 2, ',', ' ');
$libelle = $avance->getLibelle();

$moisNoms = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$mois = isset($moisNoms[$avance->getMois()]) ? $moisNoms[$avance->getMois()] : $avance->getMois();
$periode = $mois . ' ' . $avance->getAnnee();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if ($avance->delete()) {
        $_SESSION['message'] = "Avance sur salaire supprimée avec succès !";
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['message'] = "Erreur lors de la suppression de l'avance.";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
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
    <title>Supprimer l'Avance</title>
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

        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }

        .warning-icon { font-size: 64px; color: #dc2626; margin-bottom: 20px; }
        .card h2 { color: #1f2937; margin-bottom: 12px; }
        .card p { color: #6b7280; margin-bottom: 20px; line-height: 1.6; }

        .avance-details {
            background: #f8fafc; border-radius: 8px; padding: 16px 20px; margin: 20px 0; text-align: left;
        }
        .avance-details .detail-row {
            display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;
        }
        .avance-details .detail-row:last-child { border-bottom: none; }
        .avance-details .label { font-weight: 600; color: #374151; }
        .avance-details .value { color: #6b7280; }
        .avance-details .value.highlight { font-weight: bold; color: #2563eb; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-green { background: #d1fae5; color: #065f46; }

        .btn { padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: 0.2s; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }

        .btn-group { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }
        .mb-3 { margin-bottom: 12px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .container { padding: 20px 10px; }
            .card { padding: 20px; }
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

    <div class="card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2>Confirmation de suppression</h2>
        <p>Êtes-vous sûr de vouloir supprimer cette avance sur salaire ? Cette action est <strong>irréversible</strong>.</p>

        <div class="avance-details">
            <div class="detail-row">
                <span class="label">N°</span>
                <span class="value highlight">#<?php echo $avance->getId(); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Agent</span>
                <span class="value"><?php echo htmlspecialchars($nomAgent); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Libellé</span>
                <span class="value"><?php echo htmlspecialchars($libelle); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Montant</span>
                <span class="value highlight"><?php echo $montant; ?> $</span>
            </div>
            <div class="detail-row">
                <span class="label">Période</span>
                <span class="value"><?php echo htmlspecialchars($periode); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Statut</span>
                <span class="value">
                    <span class="badge <?php echo $avance->isEnCours() ? 'badge-yellow' : 'badge-green'; ?>">
                        <?php echo $avance->getStatutLibelle(); ?>
                    </span>
                </span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="btn-group">
                <button type="submit" name="confirm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Oui, supprimer
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Avances sur Salaire
    </div>
</div>

</body>
</html>