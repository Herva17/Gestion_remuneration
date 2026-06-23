<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/Avance.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

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

$db = Database::getInstance();

// Paramètres de filtrage
$mois = isset($_GET['mois']) ? $_GET['mois'] : date('F');
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : intval(date('Y'));
$agent_id = isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0;

// Liste des mois
$mois_list = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
              'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// Récupérer tous les agents
$sql_agents = "SELECT * FROM agent ORDER BY nom_complet";
$stmt_agents = $db->query($sql_agents);
$all_agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// Construction de la requête
$sql = "SELECT r.*, 
               a.nom_complet as agent_nom,
               a.adresse as agent_adresse,
               a.telephone as agent_telephone,
               a.fonction as agent_fonction,
               a.profil as agent_profil
        FROM remuneration r
        LEFT JOIN agent a ON r.id_agent = a.id_agent
        WHERE r.mois = :mois AND r.annee = :annee";

$params = [':mois' => $mois, ':annee' => $annee];

if ($agent_id > 0) {
    $sql .= " AND r.id_agent = :agent_id";
    $params[':agent_id'] = $agent_id;
}

$sql .= " ORDER BY a.nom_complet";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$remunerations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// INITIALISATION DES VARIABLES AVANT LA BOUCLE
$total_remunerations = count($remunerations);
$total_montant_brut = 0;
$total_avantages = 0;
$total_retenues = 0;
$total_avances = 0;
$total_net = 0;
$agents_ids = [];

// INITIALISATION DES VARIABLES POUR LES TOTAUX DU TABLEAU
$total_base = 0;
$total_avantages_calc = 0;
$total_brut_calc = 0;
$total_retenues_calc = 0;
$total_avances_calc = 0;
$total_net_calc = 0;

foreach ($remunerations as $r) {
    $id_agent = $r['id_agent'];
    if (!in_array($id_agent, $agents_ids)) {
        $agents_ids[] = $id_agent;
    }
    
    // Récupérer les données détaillées pour chaque rémunération
    $remun = Remuneration::getById($r['id']);
    if ($remun) {
        $avantages_mois = $remun->getTotalAvantages();
        $retenues_mois = $remun->getTotalRetenues();
        
        $total_avantages += $avantages_mois;
        $total_retenues += $retenues_mois;
        
        // Récupérer les avances
        $avances = Avance::getAvancesEnCours($id_agent);
        $total_avances_agent = 0;
        foreach ($avances as $av) {
            $total_avances_agent += $av->getMontant();
        }
        $total_avances += $total_avances_agent;
        
        $montant_brut = $r['montant'] + $avantages_mois;
        $montant_net = $montant_brut - $retenues_mois - $total_avances_agent;
        if ($montant_net < 0) $montant_net = 0;
        
        $total_montant_brut += $montant_brut;
        $total_net += $montant_net;
        
        // Ajouter aux totaux du tableau
        $total_base += $r['montant'];
        $total_avantages_calc += $avantages_mois;
        $total_brut_calc += $montant_brut;
        $total_retenues_calc += $retenues_mois;
        $total_avances_calc += $total_avances_agent;
        $total_net_calc += $montant_net;
    }
}

$total_agents = count($agents_ids);
$moyenne_brut = $total_remunerations > 0 ? $total_montant_brut / $total_remunerations : 0;
$moyenne_net = $total_remunerations > 0 ? $total_net / $total_remunerations : 0;

$title = "Rapport de Paiement - $mois $annee";

// Récupérer l'année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$anneeScolaireLibelle = $anneeCourante ? $anneeCourante->getDesignationAnn() : 'N/A';
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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; padding: 20px; color: #1e293b; }

        .container { max-width: 1280px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 30px; }

        .report-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
            padding: 24px 30px;
            border-radius: 10px;
            margin-bottom: 24px;
        }
        .report-header h1 { font-size: 24px; font-weight: 700; }
        .report-header p { opacity: 0.9; font-size: 14px; margin-top: 4px; }
        .report-header .infos {
            display: flex;
            gap: 24px;
            margin-top: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            opacity: 0.85;
        }
        .report-header .infos span i { margin-right: 6px; }

        .filters {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            border: 1px solid #e2e8f0;
        }
        .filters label { font-weight: 600; font-size: 13px; color: #475569; }
        .filters select, .filters input {
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 13px;
        }
        .filters select:focus, .filters input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #f97316; color: white; }
        .btn-warning:hover { background: #ea580c; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-card .value {
            font-size: 22px;
            font-weight: 700;
        }
        .stat-card .label {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .stat-card .sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .text-blue { color: #2563eb; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-orange { color: #f97316; }
        .text-purple { color: #8b5cf6; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            text-align: left;
            padding: 10px 12px;
            background: #f1f5f9;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover { background: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-purple { background: #f3e8ff; color: #6d28d9; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .total-row td {
            font-weight: 700;
            border-top: 2px solid #2563eb;
            padding-top: 12px;
            background: #eff6ff;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .agent-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }

        .recap-paiement {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .recap-paiement .label {
            font-weight: 600;
            color: #166534;
            font-size: 15px;
        }
        .recap-paiement .amount {
            font-size: 28px;
            font-weight: 700;
            color: #16a34a;
        }

        .info-supp {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 12px;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 20px; }
            .filters { display: none !important; }
            .actions { display: none !important; }
            .no-print { display: none !important; }
            .report-header { background: #1e40af !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .stat-card { background: #f8fafc !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .badge { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .recap-paiement { background: #f0fdf4 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .total-row td { background: #eff6ff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }

        @media (max-width: 768px) {
            .container { padding: 16px; }
            .report-header { padding: 16px 20px; }
            .report-header h1 { font-size: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card .value { font-size: 18px; }
            .filters { flex-direction: column; align-items: stretch; }
            .recap-paiement { flex-direction: column; text-align: center; }
            .recap-paiement .amount { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- En-tête du rapport -->
    <div class="report-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> RAPPORT DE PAIEMENT MAMAN MAPENDO</h1>
        <p>État récapitulatif des rémunérations pour la période</p>
        <div class="infos">
            <span><i class="fas fa-calendar-alt"></i> <?php echo $mois; ?> <?php echo $annee; ?></span>
            <span><i class="fas fa-graduation-cap"></i> Année scolaire : <?php echo $anneeScolaireLibelle; ?></span>
            <span><i class="fas fa-clock"></i> Généré le <?php echo date('d/m/Y à H:i:s'); ?></span>
            <?php if ($agent_id > 0): ?>
                <?php 
                $agent_nom = '';
                foreach ($all_agents as $ag) {
                    if ($ag['id_agent'] == $agent_id) {
                        $agent_nom = $ag['nom_complet'];
                        break;
                    }
                }
                ?>
                <span><i class="fas fa-user"></i> Agent : <?php echo htmlspecialchars($agent_nom); ?></span>
            <?php else: ?>
                <span><i class="fas fa-users"></i> Tous les agents</span>
            <?php endif; ?>
            <span><i class="fas fa-file-invoice"></i> <?php echo $total_remunerations; ?> rémunération<?php echo $total_remunerations > 1 ? 's' : ''; ?></span>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters no-print">
        <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;width:100%;">
            <label for="mois">Mois :</label>
            <select name="mois" id="mois">
                <?php foreach ($mois_list as $m): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $mois ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="annee">Année :</label>
            <select name="annee" id="annee">
                <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $annee ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <label for="agent_id">Agent :</label>
            <select name="agent_id" id="agent_id">
                <option value="0">Tous les agents</option>
                <?php foreach ($all_agents as $ag): ?>
                    <option value="<?php echo $ag['id_agent']; ?>" <?php echo $ag['id_agent'] == $agent_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ag['nom_complet']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrer
            </button>

            <button type="button" onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Imprimer
            </button>

            <a href="rapport_paiement.php?mois=<?php echo urlencode($mois); ?>&annee=<?php echo $annee; ?>&agent_id=<?php echo $agent_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </form>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="value text-blue"><?php echo $total_remunerations; ?></div>
            <div class="label">Total Rémunérations</div>
            <div class="sub"><?php echo $total_agents; ?> agents concernés</div>
        </div>
        <div class="stat-card">
            <div class="value text-purple"><?php echo number_format($total_montant_brut, 2, ',', ' '); ?> $</div>
            <div class="label">Salaire Brut Total</div>
            <div class="sub">Moyenne : <?php echo number_format($moyenne_brut, 2, ',', ' '); ?> $</div>
        </div>
        <div class="stat-card">
            <div class="value text-orange"><?php echo number_format($total_avantages, 2, ',', ' '); ?> $</div>
            <div class="label">Total Avantages</div>
            <div class="sub">Avantages accordés</div>
        </div>
        <div class="stat-card">
            <div class="value text-red"><?php echo number_format($total_retenues, 2, ',', ' '); ?> $</div>
            <div class="label">Total Retenues</div>
            <div class="sub">Retenues effectuées</div>
        </div>
        <div class="stat-card">
            <div class="value text-orange"><?php echo number_format($total_avances, 2, ',', ' '); ?> $</div>
            <div class="label">Total Avances</div>
            <div class="sub">Avances en cours</div>
        </div>
        <div class="stat-card" style="border-color:#16a34a;background:#f0fdf4;">
            <div class="value text-green"><?php echo number_format($total_net, 2, ',', ' '); ?> $</div>
            <div class="label">Salaire Net Total</div>
            <div class="sub">Moyenne : <?php echo number_format($moyenne_net, 2, ',', ' '); ?> $</div>
        </div>
    </div>

    <!-- Tableau des rémunérations -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Agent</th>
                    <th>Fonction</th>
                    <th>Salaire Base</th>
                    <th>Avantages</th>
                    <th>Brut</th>
                    <th>Retenues</th>
                    <th>Avances</th>
                    <th>Net à payer</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($remunerations)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center;padding:40px;color:#94a3b8;">
                            <i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                            Aucune rémunération trouvée pour cette période
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $compteur = 0;
                    // Réinitialisation des totaux pour le tableau
                    $total_base = 0;
                    $total_avantages_calc = 0;
                    $total_brut_calc = 0;
                    $total_retenues_calc = 0;
                    $total_avances_calc = 0;
                    $total_net_calc = 0;
                    ?>
                    <?php foreach ($remunerations as $r): 
                        $compteur++;
                        $id_agent = $r['id_agent'];
                        
                        // Récupérer les données détaillées
                        $remun = Remuneration::getById($r['id']);
                        $avantages_mois = 0;
                        $retenues_mois = 0;
                        $avances_agent = 0;
                        
                        if ($remun) {
                            $avantages_mois = $remun->getTotalAvantages();
                            $retenues_mois = $remun->getTotalRetenues();
                            
                            // Récupérer les avances
                            $avances = Avance::getAvancesEnCours($id_agent);
                            foreach ($avances as $av) {
                                $avances_agent += $av->getMontant();
                            }
                        }
                        
                        $salaire_base = $r['montant'];
                        $salaire_brut = $salaire_base + $avantages_mois;
                        $salaire_net = $salaire_brut - $retenues_mois - $avances_agent;
                        if ($salaire_net < 0) $salaire_net = 0;
                        
                        // Accumuler les totaux
                        $total_base += $salaire_base;
                        $total_avantages_calc += $avantages_mois;
                        $total_brut_calc += $salaire_brut;
                        $total_retenues_calc += $retenues_mois;
                        $total_avances_calc += $avances_agent;
                        $total_net_calc += $salaire_net;
                        
                        $nom = $r['agent_nom'] ?? 'Agent inconnu';
                        $initials = '';
                        $parts = explode(' ', $nom);
                        foreach ($parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                    ?>
                        <tr>
                            <td><?php echo $compteur; ?></td>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar"><?php echo $initials; ?></div>
                                    <?php echo htmlspecialchars($nom); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($r['agent_fonction'] ?? '-'); ?></td>
                            <td class="text-right"><?php echo number_format($salaire_base, 2, ',', ' '); ?></td>
                            <td class="text-right text-green"><?php echo number_format($avantages_mois, 2, ',', ' '); ?></td>
                            <td class="text-right text-purple"><?php echo number_format($salaire_brut, 2, ',', ' '); ?></td>
                            <td class="text-right text-red"><?php echo number_format($retenues_mois, 2, ',', ' '); ?></td>
                            <td class="text-right text-orange"><?php echo number_format($avances_agent, 2, ',', ' '); ?></td>
                            <td class="text-right font-bold text-green"><?php echo number_format($salaire_net, 2, ',', ' '); ?></td>
                            <td class="text-center">
                                <?php if ($salaire_net > 0): ?>
                                    <span class="badge badge-green"><i class="fas fa-check-circle"></i> Payé</span>
                                <?php else: ?>
                                    <span class="badge badge-yellow"><i class="fas fa-hourglass-half"></i> En attente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- Ligne Total -->
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;font-size:14px;">TOTAUX :</td>
                        <td class="text-right"><?php echo number_format($total_base, 2, ',', ' '); ?></td>
                        <td class="text-right text-green"><?php echo number_format($total_avantages_calc, 2, ',', ' '); ?></td>
                        <td class="text-right text-purple"><?php echo number_format($total_brut_calc, 2, ',', ' '); ?></td>
                        <td class="text-right text-red"><?php echo number_format($total_retenues_calc, 2, ',', ' '); ?></td>
                        <td class="text-right text-orange"><?php echo number_format($total_avances_calc, 2, ',', ' '); ?></td>
                        <td class="text-right text-green" style="font-size:15px;"><?php echo number_format($total_net_calc, 2, ',', ' '); ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Récapitulatif du paiement -->
    <div class="recap-paiement">
        <div>
            <div class="label"><i class="fas fa-wallet"></i> MONTANT NET TOTAL À PAYER</div>
            <div style="font-size:12px;color:#166534;opacity:0.8;">
                <?php echo $total_remunerations; ?> rémunération<?php echo $total_remunerations > 1 ? 's' : ''; ?> 
                - <?php echo $total_agents; ?> agent<?php echo $total_agents > 1 ? 's' : ''; ?>
            </div>
        </div>
        <div class="amount">
            <?php echo number_format($total_net_calc, 2, ',', ' '); ?> $
        </div>
    </div>

    <!-- Détail du calcul -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:16px;background:#f8fafc;border-radius:8px;padding:16px 20px;border:1px solid #e2e8f0;">
        <div>
            <div style="font-size:11px;color:#94a3b8;">Salaire Brut Total</div>
            <div style="font-weight:700;color:#2563eb;"><?php echo number_format($total_brut_calc, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <div style="font-size:11px;color:#94a3b8;">Total Retenues</div>
            <div style="font-weight:700;color:#dc2626;">- <?php echo number_format($total_retenues_calc, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <div style="font-size:11px;color:#94a3b8;">Total Avances</div>
            <div style="font-weight:700;color:#f97316;">- <?php echo number_format($total_avances_calc, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <div style="font-size:11px;color:#94a3b8;">= Salaire Net Total</div>
            <div style="font-weight:700;color:#16a34a;font-size:18px;"><?php echo number_format($total_net_calc, 2, ',', ' '); ?> $</div>
        </div>
    </div>

    <!-- Informations supplémentaires -->
    <?php if ($total_remunerations > 0): ?>
    <div style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
        <div class="info-supp">
            <i class="fas fa-percentage"></i>
            <span>Taux de retenues : <strong><?php echo $total_brut_calc > 0 ? number_format(($total_retenues_calc / $total_brut_calc) * 100, 1) : 0; ?>%</strong></span>
        </div>
        <div class="info-supp">
            <i class="fas fa-gift"></i>
            <span>Taux d'avantages : <strong><?php echo $total_base > 0 ? number_format(($total_avantages_calc / $total_base) * 100, 1) : 0; ?>%</strong></span>
        </div>
        <div class="info-supp">
            <i class="fas fa-hand-holding-usd"></i>
            <span>Avances / Brut : <strong><?php echo $total_brut_calc > 0 ? number_format(($total_avances_calc / $total_brut_calc) * 100, 1) : 0; ?>%</strong></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations | Rapport généré automatiquement
    </div>

</div>

</body>
</html>