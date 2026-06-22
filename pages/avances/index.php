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

// Récupération des avances
$avances = Avance::getAll();

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
$totalAvances = count($avances);

// Calcul des statistiques
$totalMontant = 0;
$totalEnCours = 0;
$totalRembourse = 0;

foreach ($avances as $avance) {
    $montant = $avance->getMontant();
    $totalMontant += $montant;
    
    if ($avance->isEnCours()) {
        $totalEnCours += $montant;
    } elseif ($avance->isRembourse()) {
        $totalRembourse += $montant;
    }
}

// Mois actuel pour le filtre
$moisActuel = date('m');
$anneeActuelle = date('Y');

// ========== DÉTERMINER LA PAGE DE RETOUR ==========
$dashboardRetour = '../../Dashboard.php';
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caissier') {
    $dashboardRetour = '../caissier/dashboard.php';
}

// Filtrer par statut si demandé
$filtreStatut = isset($_GET['statut']) ? $_GET['statut'] : null;
if ($filtreStatut && in_array($filtreStatut, [Avance::STATUT_EN_COURS, Avance::STATUT_REMBOURSE])) {
    $avances = Avance::getAll($filtreStatut);
    $totalAvances = count($avances);
}

// Filtrer par libellé si demandé
$filtreLibelle = isset($_GET['libelle']) ? $_GET['libelle'] : null;
if ($filtreLibelle) {
    $avances = Avance::getByLibelle($filtreLibelle, $filtreStatut);
    $totalAvances = count($avances);
}

// Filtrer par mois et année
$filtreMois = isset($_GET['mois']) ? $_GET['mois'] : null;
$filtreAnnee = isset($_GET['annee']) ? $_GET['annee'] : null;
if ($filtreMois && $filtreAnnee) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM avances WHERE mois = :mois AND annee = :annee";
    if ($filtreStatut) {
        $sql .= " AND statut = :statut";
    }
    if ($filtreLibelle) {
        $sql .= " AND libelle = :libelle";
    }
    $params = [':mois' => $filtreMois, ':annee' => $filtreAnnee];
    if ($filtreStatut) {
        $params[':statut'] = $filtreStatut;
    }
    if ($filtreLibelle) {
        $params[':libelle'] = $filtreLibelle;
    }
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $avances = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $avances[] = new Avance(
            $row['agent_id'],
            $row['mois'],
            $row['annee'],
            $row['libelle'],
            $row['montant'],
            $row['statut'],
            null,
            null,
            $row['id']
        );
    }
    $totalAvances = count($avances);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Avances sur Salaire</title>
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
        .border-yellow { border-left: 4px solid #eab308; }

        .text-purple { color: #9333ea; }
        .text-green { color: #16a34a; }
        .text-blue { color: #2563eb; }
        .text-yellow { color: #eab308; }

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
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }

        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .mt-4 { margin-top: 16px; }
        .mb-3 { margin-bottom: 12px; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        .action-icons { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-icons a { padding: 4px 8px; border-radius: 4px; transition: 0.2s; text-decoration: none; font-size: 14px; }
        .action-icons .edit { color: #ca8a04; }
        .action-icons .edit:hover { background: #fef3c7; }
        .action-icons .delete { color: #dc2626; }
        .action-icons .delete:hover { background: #fee2e2; }
        .action-icons .view { color: #2563eb; }
        .action-icons .view:hover { background: #dbeafe; }
        .action-icons .rembourse { color: #16a34a; }
        .action-icons .rembourse:hover { background: #d1fae5; }

        .filter-bar {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-bar label { font-weight: 600; color: #374151; font-size: 14px; }
        .filter-bar select { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; font-size: 14px; }
        .filter-bar .btn-filter { padding: 6px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .filter-bar .btn-filter:hover { background: #1d4ed8; }
        .filter-bar .btn-reset { padding: 6px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; }
        .filter-bar .btn-reset:hover { background: #d1d5db; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-4 { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
            .filter-bar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-hand-holding-usd" style="color:#2563eb;"></i> Avances sur Salaire</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
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

<!-- ===== CONTENU ===== -->
<div class="container">

    <div class="mb-3">
        <a href="<?php echo $dashboardRetour; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au Dashboard
        </a>
    </div>

    <div class="top">
        <div>
            <h2>Gestion des Avances sur Salaire</h2>
            <p>Gérez les avances sur salaire des agents</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Nouvelle Avance</a>
        </div>
    </div>

    <!-- Barre de filtres -->
    <div class="filter-bar">
        <label for="filtreStatut"><i class="fas fa-filter"></i> Statut :</label>
        <select id="filtreStatut" name="statut">
            <option value="">Tous</option>
            <option value="<?php echo Avance::STATUT_EN_COURS; ?>" <?php echo ($filtreStatut === Avance::STATUT_EN_COURS) ? 'selected' : ''; ?>>En cours</option>
            <option value="<?php echo Avance::STATUT_REMBOURSE; ?>" <?php echo ($filtreStatut === Avance::STATUT_REMBOURSE) ? 'selected' : ''; ?>>Remboursé</option>
        </select>

        <label for="filtreLibelle"><i class="fas fa-tag"></i> Libellé :</label>
        <input type="text" id="filtreLibelle" name="libelle" placeholder="Rechercher..." value="<?php echo htmlspecialchars($filtreLibelle); ?>">

        <label for="filtreMois"><i class="fas fa-calendar-alt"></i> Mois :</label>
        <select id="filtreMois" name="mois">
            <option value="">Tous</option>
            <?php 
            $moisNoms = [
                '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
                '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
                '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
            ];
            foreach ($moisNoms as $num => $nom): 
            ?>
                <option value="<?php echo $num; ?>" <?php echo ($filtreMois === $num) ? 'selected' : ''; ?>>
                    <?php echo $nom; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="filtreAnnee"><i class="fas fa-calendar"></i> Année :</label>
        <select id="filtreAnnee" name="annee">
            <option value="">Toutes</option>
            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                <option value="<?php echo $i; ?>" <?php echo ($filtreAnnee == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="submit" class="btn-filter" onclick="applyFilters()">
            <i class="fas fa-search"></i> Filtrer
        </button>
        <a href="index.php" class="btn-reset"><i class="fas fa-times"></i> Réinitialiser</a>
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
            <div class="number text-purple"><?php echo $totalAvances; ?></div>
            <div class="label"><i class="fas fa-hand-holding-usd"></i> Total Avances</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo number_format($totalMontant, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-dollar-sign"></i> Montant Total</div>
        </div>
        <div class="card border-yellow">
            <div class="number text-yellow"><?php echo number_format($totalEnCours, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-clock"></i> En cours</div>
        </div>
        <div class="card border-blue">
            <div class="number text-blue"><?php echo number_format($totalRembourse, 2, ',', ' '); ?> $</div>
            <div class="label"><i class="fas fa-check-circle"></i> Remboursé</div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Agent</th>
                        <th>Libellé</th>
                        <th>Montant</th>
                        <th>Période</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avances)): ?>
                    <tr>
                        <td colspan="8" class="empty">
                            <i class="fas fa-inbox"></i>
                            Aucune avance sur salaire trouvée
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($avances as $avance): 
                            $agent = Agent::getById($avance->getAgentId());
                            $statutBadge = $avance->isEnCours() ? 'badge-yellow' : 'badge-green';
                            $statutLibelle = $avance->getStatutLibelle();
                            $libelle = $avance->getLibelle();
                            
                            $moisNoms = [
                                '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
                                '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
                                '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
                            ];
                            $mois = $avance->getMois();
                            $moisAff = isset($moisNoms[$mois]) ? $moisNoms[$mois] : $mois;
                        ?>
                        <tr>
                            <td><strong>#<?php echo $avance->getId(); ?></strong></td>
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
                            <td><?php echo htmlspecialchars($libelle); ?></td>
                            <td style="font-weight:bold;color:#2563eb;">
                                <?php echo number_format($avance->getMontant(), 2, ',', ' '); ?> $
                            </td>
                            <td><?php echo $moisAff . ' ' . $avance->getAnnee(); ?></td>
                            <td>
                                <span class="badge <?php echo $statutBadge; ?>">
                                    <?php if ($avance->isEnCours()): ?>
                                        <i class="fas fa-spinner fa-spin"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php endif; ?>
                                    <?php echo $statutLibelle; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($avance->getDateCreation())); ?></td>
                            <td>
                                <div class="action-icons">
                                    <a href="view.php?id=<?php echo $avance->getId(); ?>" class="view" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $avance->getId(); ?>" class="edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($avance->isEnCours()): ?>
                                        <a href="rembourser.php?id=<?php echo $avance->getId(); ?>" class="rembourse" title="Marquer remboursé"
                                           onclick="return confirm('Confirmer le remboursement de cette avance ?')">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $avance->getId(); ?>" class="delete" title="Supprimer"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette avance ?')">
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
        <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
            <span>Total : <strong><?php echo $totalAvances; ?></strong> avance<?php echo $totalAvances > 1 ? 's' : ''; ?></span>
        </div>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Avances sur Salaire
    </div>
</div>

<script>
    function applyFilters() {
        const statut = document.getElementById('filtreStatut').value;
        const libelle = document.getElementById('filtreLibelle').value;
        const mois = document.getElementById('filtreMois').value;
        const annee = document.getElementById('filtreAnnee').value;
        
        let url = 'index.php?';
        if (statut) url += 'statut=' + statut + '&';
        if (libelle) url += 'libelle=' + encodeURIComponent(libelle) + '&';
        if (mois) url += 'mois=' + mois + '&';
        if (annee) url += 'annee=' + annee;
        
        window.location.href = url;
    }
</script>

</body>
</html>