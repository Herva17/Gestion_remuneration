<?php
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$db = Database::getInstance();

// Récupérer toutes les rémunérations de l'année
$sql = "SELECT r.*, 
               a.nom_complet as agent_nom,
               a.fonction as agent_fonction
        FROM remuneration r
        LEFT JOIN agent a ON r.id_agent = a.id_agent
        WHERE r.annee = :annee 
        ORDER BY r.date_remun DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':annee', $year);
$stmt->execute();
$remunerations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques
$total_remunerations = count($remunerations);
$total_montant = 0;
$agents_ids = [];

foreach ($remunerations as $r) {
    $total_montant += floatval($r['montant']);
    if (!in_array($r['id_agent'], $agents_ids)) {
        $agents_ids[] = $r['id_agent'];
    }
}

$total_agents = count($agents_ids);
$moyenne = $total_remunerations > 0 ? $total_montant / $total_remunerations : 0;

// Statistiques par mois
$stats_par_mois = [];
$mois_list = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
              'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

foreach ($remunerations as $r) {
    $mois = $r['mois'];
    if (!isset($stats_par_mois[$mois])) {
        $stats_par_mois[$mois] = [
            'count' => 0,
            'total' => 0
        ];
    }
    $stats_par_mois[$mois]['count']++;
    $stats_par_mois[$mois]['total'] += floatval($r['montant']);
}

$title = "Rapport annuel des rémunérations - $year";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
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

        .card { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card .number { font-size: 24px; font-weight: bold; }
        .card .label { font-size: 13px; color: #666; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }

        .border-purple { border-left: 4px solid #9333ea; }
        .border-green { border-left: 4px solid #16a34a; }
        .border-blue { border-left: 4px solid #2563eb; }
        .border-orange { border-left: 4px solid #f97316; }

        .text-purple { color: #9333ea; }
        .text-green { color: #16a34a; }
        .text-blue { color: #2563eb; }
        .text-orange { color: #f97316; }

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
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .mt-4 { margin-top: 16px; }
        .text-right { text-align: right; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        .report-header { background: linear-gradient(135deg, #9333ea, #2563eb); color: white; padding: 24px; border-radius: 8px 8px 0 0; margin: -16px -16px 0 -16px; }
        .report-header h1 { font-size: 22px; }
        .report-header p { opacity: 0.9; font-size: 14px; margin-top: 4px; }

        .stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 16px 0; }

        .stat-item { background: #f8f9fa; border-radius: 6px; padding: 12px; text-align: center; }
        .stat-item .value { font-size: 22px; font-weight: bold; }
        .stat-item .label { font-size: 13px; color: #6b7280; }

        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .header { display: none !important; }
            .container { padding: 0; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .report-header { border-radius: 0 !important; }
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



<div class="container">

    <div class="top no-print">
        <div>
            <h2>Rapport Annuel des Rémunérations</h2>
            <p>Consultez le récapitulatif des rémunérations par année</p>
        </div>
        <div class="flex gap-3">
            <div style="display:flex;gap:4px;">
                <a href="?year=<?php echo $year - 1; ?>" class="btn btn-secondary"><i class="fas fa-chevron-left"></i></a>
                <select id="yearSelect" onchange="window.location.href='?year='+this.value" class="btn btn-primary" style="appearance:auto;padding:8px 16px;">
                    <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <a href="?year=<?php echo $year + 1; ?>" class="btn btn-secondary"><i class="fas fa-chevron-right"></i></a>
            </div>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimer</button>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="card" style="margin-bottom:16px;">
        <div class="report-header">
            <h1><i class="fas fa-chart-line mr-2"></i>RAPPORT ANNUEL DES RÉMUNÉRATIONS</h1>
            <p>Année <?php echo $year; ?> - Généré le <?php echo date('d/m/Y à H:i:s'); ?></p>
        </div>

        <div class="stat-row" style="margin-top:16px;">
            <div class="stat-item">
                <div class="value text-purple"><?php echo $total_remunerations; ?></div>
                <div class="label">Total Rémunérations</div>
            </div>
            <div class="stat-item">
                <div class="value text-green"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</div>
                <div class="label">Montant Total</div>
            </div>
            <div class="stat-item">
                <div class="value text-blue"><?php echo $total_agents; ?></div>
                <div class="label">Agents concernés</div>
            </div>
            <div class="stat-item">
                <div class="value text-orange"><?php echo number_format($moyenne, 2, ',', ' '); ?> $</div>
                <div class="label">Moyenne par rémunération</div>
            </div>
        </div>

        <!-- Statistiques par mois -->
        <div style="margin-top:16px;">
            <h3 style="font-size:16px;color:#1f2937;margin-bottom:8px;"><i class="fas fa-calendar-alt"></i> Répartition par mois</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;">
                <?php foreach ($mois_list as $mois): ?>
                    <?php if (isset($stats_par_mois[$mois])): ?>
                        <div style="background:#f8f9fa;border-radius:6px;padding:8px;text-align:center;border-left:3px solid #2563eb;">
                            <div style="font-size:12px;color:#6b7280;"><?php echo $mois; ?></div>
                            <div style="font-weight:bold;color:#2563eb;"><?php echo number_format($stats_par_mois[$mois]['total'], 2, ',', ' '); ?> $</div>
                            <div style="font-size:11px;color:#9ca3af;"><?php echo $stats_par_mois[$mois]['count']; ?> rém.</div>
                        </div>
                    <?php else: ?>
                        <div style="background:#f8f9fa;border-radius:6px;padding:8px;text-align:center;border-left:3px solid #e5e7eb;opacity:0.5;">
                            <div style="font-size:12px;color:#6b7280;"><?php echo $mois; ?></div>
                            <div style="font-weight:bold;color:#9ca3af;">-</div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tableau des rémunérations -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Agent</th>
                        <th>Fonction</th>
                        <th>Montant</th>
                        <th>Mois</th>
                        <th>Année</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($remunerations)): ?>
                        <tr>
                            <td colspan="7" class="empty">
                                <i class="fas fa-inbox"></i>
                                Aucune rémunération pour cette année
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($remunerations as $r): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:28px;height:28px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:bold;">
                                            <?php 
                                            $nom = $r['agent_nom'] ?? 'N/A';
                                            $initials = '';
                                            $parts = explode(' ', $nom);
                                            foreach ($parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            echo substr($initials, 0, 2);
                                            ?>
                                        </div>
                                        <?php echo htmlspecialchars($r['agent_nom'] ?? 'Agent inconnu'); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($r['agent_fonction'] ?? '-'); ?></td>
                                <td style="font-weight:bold;color:#16a34a;"><?php echo number_format($r['montant'], 2, ',', ' '); ?> $</td>
                                <td><span class="badge badge-blue"><?php echo htmlspecialchars($r['mois']); ?></span></td>
                                <td><span class="badge badge-green"><?php echo htmlspecialchars($r['annee']); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($r['date_remun'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot style="background:#f8f9fa;font-weight:bold;">
                    <tr>
                        <td colspan="3" style="text-align:right;">TOTAL :</td>
                        <td style="color:#16a34a;"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
            <span>Total : <strong><?php echo $total_remunerations; ?></strong> rémunération<?php echo $total_remunerations > 1 ? 's' : ''; ?></span>
            <span>Montant total : <strong style="color:#16a34a;"><?php echo number_format($total_montant, 2, ',', ' '); ?> $</strong></span>
        </div>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>