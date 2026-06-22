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

$message = '';
$message_type = '';
$remunerations = [];
$periode = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mois = $_POST['mois'] ?? '';
    $annee = $_POST['annee'] ?? '';
    
    if (empty($mois) || empty($annee)) {
        $message = "Veuillez sélectionner un mois et une année";
        $message_type = "error";
    } else {
        $periode = $mois . ' ' . $annee;
        
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE mois = :mois AND annee = :annee ORDER BY date_remun DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':mois', $mois);
        $stmt->bindParam(':annee', $annee);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $remuneration = new Remuneration(
                $row['id_agent'],
                $row['montant'],
                $row['date_remun'],
                $row['mois'],
                $row['annee'],
                $row['id']
            );
            $remunerations[] = $remuneration;
        }
        
        if (empty($remunerations)) {
            $message = "Aucune rémunération trouvée pour cette période";
            $message_type = "warning";
        }
    }
}

// Calcul des totaux
$total_montant = 0;
$total_remunerations = count($remunerations);
foreach ($remunerations as $r) {
    $total_montant += $r->getMontant();
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Liste des mois
$moisList = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$anneeActuelle = date('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Périodique des Rémunérations</title>
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

        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #9333ea, #2563eb); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 0 -20px; }
        .card-header h1 { font-size: 24px; }
        .card-header p { font-size: 14px; opacity: 0.9; margin-top: 4px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8f9fa; }

        .empty { text-align: center; padding: 24px; color: #999; }
        .empty i { font-size: 24px; display: block; margin-bottom: 8px; }

        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-purple { background: #9333ea; color: white; }
        .btn-purple:hover { background: #7c3aed; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #2563eb; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin: 16px 0; }

        .stat-box { text-align: center; padding: 12px; background: #f8f9fa; border-radius: 6px; }
        .stat-box .number { font-size: 24px; font-weight: bold; }
        .stat-box .label { font-size: 13px; color: #6b7280; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .items-end { align-items: flex-end; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .print-container { margin: 0; padding: 20px; }
            .header { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .card-header { border-radius: 0 !important; }
            .page-break { page-break-after: always; }
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-4 { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
        }
    </style>
</head>
<body>


<div class="container print-container">

    <div class="top no-print">
        <div>
            <h2>Fiche Périodique des Rémunérations</h2>
            <p>Consultez les rémunérations par mois et année</p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <!-- Formulaire de sélection de période -->
    <div class="card no-print" style="margin-bottom:20px;">
        <form method="POST" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                <select name="mois" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    <option value="">-- Sélectionner un mois --</option>
                    <?php foreach ($moisList as $m): ?>
                    <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                <select name="annee" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                    <option value="">-- Sélectionner une année --</option>
                    <?php for ($a = $anneeActuelle - 5; $a <= $anneeActuelle + 1; $a++): ?>
                    <option value="<?php echo $a; ?>" <?php echo $a == $anneeActuelle ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-purple"><i class="fas fa-search mr-2"></i>Générer</button>
            </div>
            <div>
                <button type="button" onclick="window.print()" class="btn btn-primary"><i class="fas fa-print mr-2"></i>Imprimer</button>
            </div>
        </form>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> no-print">
        <i class="fas <?php echo $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Fiche Périodique -->
    <?php if (!empty($remunerations)): ?>
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-money-bill-wave mr-2"></i>FICHE PÉRIODIQUE DES RÉMUNÉRATIONS</h1>
            <p><?php echo $periode; ?></p>
            <p style="font-size:12px;opacity:0.8;">Généré le <?php echo date('d/m/Y à H:i:s'); ?></p>
        </div>

        <!-- Résumé -->
        <div class="grid-4">
            <div class="stat-box">
                <p class="label">Total Rémunérations</p>
                <p class="number" style="color:#9333ea;"><?php echo $total_remunerations; ?></p>
            </div>
            <div class="stat-box">
                <p class="label">Montant Total</p>
                <p class="number" style="color:#16a34a;"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</p>
            </div>
            <div class="stat-box">
                <p class="label">Moyenne par rémunération</p>
                <p class="number" style="color:#2563eb;">
                    <?php echo $total_remunerations > 0 ? number_format($total_montant / $total_remunerations, 2, ',', ' ') : '0'; ?> $
                </p>
            </div>
            <div class="stat-box">
                <p class="label">Nombre d'agents</p>
                <p class="number" style="color:#f97316;">
                    <?php 
                    $agentsIds = [];
                    foreach ($remunerations as $r) {
                        if (!in_array($r->getIdAgent(), $agentsIds)) {
                            $agentsIds[] = $r->getIdAgent();
                        }
                    }
                    echo count($agentsIds);
                    ?>
                </p>
            </div>
        </div>

        <!-- Tableau -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Agent</th>
                        <th>Montant</th>
                        <th>Mois</th>
                        <th>Année</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($remunerations as $remuneration): 
                        $agent = Agent::getById($remuneration->getIdAgent());
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
                        <td style="font-weight:bold;color:#16a34a;"><?php echo number_format($remuneration->getMontant(), 2, ',', ' '); ?> $</td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($remuneration->getMois()); ?></span></td>
                        <td><span class="badge badge-green"><?php echo htmlspecialchars($remuneration->getAnnee()); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($remuneration->getDateRemun())); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#f8f9fa;font-weight:bold;">
                    <tr>
                        <td colspan="2" style="text-align:right;">TOTAL :</td>
                        <td style="color:#16a34a;"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pied de page -->
        <div style="text-align:center;font-size:12px;color:#9ca3af;padding:12px 0 0 0;border-top:1px solid #e5e7eb;margin-top:16px;">
            <p>Document généré automatiquement - Fiche périodique des rémunérations</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>