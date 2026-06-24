<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/Affectation.php';
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

$message = '';
$message_type = '';
$remunerations = [];
$periode = '';

// INITIALISATION DES VARIABLES POUR LES TOTAUX
$total_montant = 0;
$total_remunerations = 0;
$agentsIds = [];
$total_agents = 0;
$moyenne = 0;

// Variables pour les accessoires
$total_avantages = 0;
$total_retenues = 0;
$total_avances = 0;
$total_brut = 0;
$total_net = 0;
$total_agents_avec_avances = 0;
$remunerations_sans_affectation = 0;

// Liste des mois
$moisList = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$anneeActuelle = date('Y');

// Récupérer l'année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$anneeScolaireLibelle = $anneeCourante ? $anneeCourante->getDesignationAnn() : 'N/A';

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
        $sql = "SELECT r.*, 
                       aff.lieu_affectation,
                       aff.montant_remunerer as montant_affectation
                FROM remuneration r
                LEFT JOIN affectation aff ON r.id_affectation = aff.id
                WHERE r.mois = :mois AND r.annee = :annee 
                ORDER BY r.date_remun DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':mois', $mois);
        $stmt->bindParam(':annee', $annee);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $remuneration = new Remuneration(
                $row['id_agent'],
                $row['id_affectation'],
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
        } else {
            // Calcul des totaux avec les accessoires
            $total_remunerations = count($remunerations);
            $agents_avec_avances = [];
            
            foreach ($remunerations as $r) {
                $id_agent = $r->getIdAgent();
                
                // Récupérer le montant depuis l'affectation
                $salaire_base = $r->getMontant() ?? 0;
                if ($salaire_base == 0 && $r->getIdAffectation() === null) {
                    $remunerations_sans_affectation++;
                }
                
                // Récupérer les avantages du mois
                $avantages_mois = $r->getTotalAvantages();
                $total_avantages += $avantages_mois;
                
                // Récupérer les retenues du mois
                $retenues_mois = $r->getTotalRetenues();
                $total_retenues += $retenues_mois;
                
                // Récupérer les avances en cours
                $avances_agent = 0;
                $avances = Avance::getAvancesEnCours($id_agent);
                foreach ($avances as $av) {
                    $avances_agent += $av->getMontant();
                }
                $total_avances += $avances_agent;
                
                if ($avances_agent > 0 && !in_array($id_agent, $agents_avec_avances)) {
                    $agents_avec_avances[] = $id_agent;
                }
                
                // Calculs
                $salaire_brut = $salaire_base + $avantages_mois;
                $salaire_net = $salaire_brut - $retenues_mois - $avances_agent;
                if ($salaire_net < 0) $salaire_net = 0;
                
                $total_brut += $salaire_brut;
                $total_net += $salaire_net;
                $total_montant += $salaire_net; // Net à payer
                
                if (!in_array($id_agent, $agentsIds)) {
                    $agentsIds[] = $id_agent;
                }
            }
            
            $total_agents = count($agentsIds);
            $total_agents_avec_avances = count($agents_avec_avances);
            $moyenne = $total_remunerations > 0 ? $total_net / $total_remunerations : 0;
        }
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
    <title>Fiche Périodique des Rémunérations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }

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

        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }

        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #1e40af, #2563eb); color: white; padding: 20px 24px; text-align: center; border-radius: 12px 12px 0 0; margin: -20px -20px 0 -20px; }
        .card-header h1 { font-size: 24px; }
        .card-header p { font-size: 14px; opacity: 0.9; margin-top: 4px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        tr:hover { background: #f8f9fa; }

        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .empty i { font-size: 32px; display: block; margin-bottom: 12px; }

        .btn { padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-purple { background: #9333ea; color: white; }
        .btn-purple:hover { background: #7c3aed; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-warning { background: #f97316; color: white; }
        .btn-warning:hover { background: #ea580c; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-info { background: #dbeafe; border: 1px solid #93c5fd; color: #1e40af; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-purple { background: #f3e8ff; color: #6d28d9; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin: 16px 0; }
        .stat-box { text-align: center; padding: 14px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e5e7eb; }
        .stat-box .number { font-size: 22px; font-weight: 700; }
        .stat-box .label { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .stat-box .sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .text-blue { color: #2563eb; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-orange { color: #f97316; }
        .text-purple { color: #8b5cf6; }
        .text-yellow { color: #eab308; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .items-end { align-items: flex-end; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .text-sm { font-size: 12px; }

        .agent-info { display: flex; align-items: center; gap: 8px; }
        .agent-avatar { width: 30px; height: 30px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0; }

        .total-row td { font-weight: 700; border-top: 2px solid #2563eb; padding-top: 12px; background: #eff6ff; }
        .subtotal-row td { font-weight: 600; border-top: 1px solid #94a3b8; background: #f8fafc; }

        .footer { text-align: center; margin-top: 20px; color: #94a3b8; font-size: 12px; padding: 10px 0; }
        .recap-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px 20px; margin-top: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .recap-box .label { font-weight: 600; color: #166534; font-size: 15px; }
        .recap-box .amount { font-size: 28px; font-weight: 700; color: #16a34a; }

        .info-supp { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 8px 14px; font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 8px; }
        .info-supp.blue { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }

        .montant-zero { color: #94a3b8; font-style: italic; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .print-container { margin: 0; padding: 20px; }
            .header { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .card-header { background: #1e40af !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .stat-box { background: #f8f9fa !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .badge { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            th { background: #f8f9fa !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .total-row td { background: #eff6ff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .recap-box { background: #f0fdf4 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .page-break { page-break-after: always; }
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-box .number { font-size: 18px; }
            .recap-box { flex-direction: column; text-align: center; }
            .recap-box .amount { font-size: 22px; }
        }
    </style>
</head>
<body>

<!-- Barre de navigation -->
<nav class="header no-print">
    <div class="header-left">
        <a href="../../Dashboard.php" class="text-gray-600 hover:text-blue-600" style="text-decoration:none;font-size:18px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1><i class="fas fa-coins mr-2"></i>Gestion Rémunération</h1>
        <div class="nav-links">
            <a href="../Agents/index.php">Agents</a>
            <a href="index.php" class="active">Rémunérations</a>
            <a href="rapport_paiement.php">Rapport</a>
            <a href="../Rapports/index.php">Analyses</a>
        </div>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><i class="fas fa-user"></i></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>

<div class="container print-container">

    <div class="top no-print">
        <div>
            <h2>Fiche Périodique des Rémunérations</h2>
            <p>Consultez les rémunérations par mois et année avec tous les accessoires</p>
        </div>
        <div class="flex gap-3">
            <a href="rapport_paiement.php" class="btn btn-warning"><i class="fas fa-file-invoice-dollar"></i> Rapport paiement</a>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                <button type="submit" class="btn btn-purple"><i class="fas fa-search mr-2"></i>Générer</button>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
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

    <?php if ($remunerations_sans_affectation > 0): ?>
    <div class="alert alert-warning no-print">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Attention :</strong> <?php echo $remunerations_sans_affectation; ?> rémunération(s) n'ont pas d'affectation associée. 
            Le montant de base est considéré comme 0 $.
        </div>
    </div>
    <?php endif; ?>

    <!-- Fiche Périodique -->
    <?php if (!empty($remunerations)): ?>
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> FICHE PÉRIODIQUE DES RÉMUNÉRATIONS</h1>
            <p><?php echo $periode; ?> | Année scolaire : <?php echo $anneeScolaireLibelle; ?></p>
            <p style="font-size:12px;opacity:0.8;">Généré le <?php echo date('d/m/Y à H:i:s'); ?></p>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="number text-purple"><?php echo $total_remunerations; ?></div>
                <div class="label">Total Rémunérations</div>
                <div class="sub"><?php echo $total_agents; ?> agents concernés</div>
            </div>
            <div class="stat-box">
                <div class="number text-blue">$ <?php echo number_format($total_brut, 2, ',', ' '); ?></div>
                <div class="label">Salaire Brut Total</div>
                <div class="sub">Base + Avantages</div>
            </div>
            <div class="stat-box">
                <div class="number text-orange">$ <?php echo number_format($total_avantages, 2, ',', ' '); ?></div>
                <div class="label">Total Avantages</div>
                <div class="sub">Accessoires accordés</div>
            </div>
            <div class="stat-box">
                <div class="number text-red">$ <?php echo number_format($total_retenues, 2, ',', ' '); ?></div>
                <div class="label">Total Retenues</div>
                <div class="sub">Déductions effectuées</div>
            </div>
            <div class="stat-box">
                <div class="number text-yellow">$ <?php echo number_format($total_avances, 2, ',', ' '); ?></div>
                <div class="label">Total Avances</div>
                <div class="sub"><?php echo $total_agents_avec_avances; ?> agents concernés</div>
            </div>
            <div class="stat-box" style="border-color:#16a34a;background:#f0fdf4;">
                <div class="number text-green">$ <?php echo number_format($total_net, 2, ',', ' '); ?></div>
                <div class="label">Salaire Net Total</div>
                <div class="sub">Moyenne : $ <?php echo number_format($moyenne, 2, ',', ' '); ?></div>
            </div>
        </div>

        <!-- Tableau détaillé -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Agent</th>
                        <th>Fonction</th>
                        <th>Affectation</th>
                        <th>Salaire Base</th>
                        <th>Avantages</th>
                        <th>Salaire Brut</th>
                        <th>Retenues</th>
                        <th>Avances</th>
                        <th>Salaire Net</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total_base = 0;
                    $grand_total_avantages = 0;
                    $grand_total_brut = 0;
                    $grand_total_retenues = 0;
                    $grand_total_avances = 0;
                    $grand_total_net = 0;
                    $compteur = 0;
                    ?>
                    <?php foreach ($remunerations as $remuneration): 
                        $compteur++;
                        $id_agent = $remuneration->getIdAgent();
                        
                        // Récupérer le montant depuis l'affectation
                        $salaire_base = $remuneration->getMontant() ?? 0;
                        
                        // Récupérer l'affectation pour le lieu
                        $affectation = $remuneration->getAffectation();
                        $lieu_affectation = $affectation ? $affectation->getLieuAffectation() : 'Non définie';
                        
                        // Récupérer les avantages du mois
                        $avantages_mois = $remuneration->getTotalAvantages();
                        $liste_avantages = $remuneration->getAvantages();
                        
                        // Récupérer les retenues du mois
                        $retenues_mois = $remuneration->getTotalRetenues();
                        $liste_retenues = $remuneration->getRetenues();
                        
                        // Récupérer les avances en cours
                        $avances_agent = 0;
                        $liste_avances = Avance::getAvancesEnCours($id_agent);
                        foreach ($liste_avances as $av) {
                            $avances_agent += $av->getMontant();
                        }
                        
                        // Calculs
                        $salaire_brut = $salaire_base + $avantages_mois;
                        $salaire_net = $salaire_brut - $retenues_mois - $avances_agent;
                        if ($salaire_net < 0) $salaire_net = 0;
                        
                        // Accumuler les totaux
                        $grand_total_base += $salaire_base;
                        $grand_total_avantages += $avantages_mois;
                        $grand_total_brut += $salaire_brut;
                        $grand_total_retenues += $retenues_mois;
                        $grand_total_avances += $avances_agent;
                        $grand_total_net += $salaire_net;
                        
                        $agent = Agent::getById($id_agent);
                        $nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
                        $fonction = $agent ? $agent->getFonction() : '-';
                        
                        $initials = '';
                        $parts = explode(' ', $nomAgent);
                        foreach ($parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                        
                        // Déterminer le statut
                        $statut = $salaire_net > 0 ? 'Payé' : 'En attente';
                        $statut_badge = $salaire_net > 0 ? 'badge-green' : 'badge-yellow';
                        $statut_icon = $salaire_net > 0 ? 'fa-check-circle' : 'fa-hourglass-half';
                    ?>
                    <tr>
                        <td><?php echo $compteur; ?></td>
                        <td>
                            <div class="agent-info">
                                <div class="agent-avatar"><?php echo $initials; ?></div>
                                <?php echo htmlspecialchars($nomAgent); ?>
                            </div>
                            <?php if (!empty($liste_avances)): ?>
                                <div style="font-size:10px;color:#f97316;margin-top:2px;">
                                    <i class="fas fa-hand-holding-usd"></i> <?php echo count($liste_avances); ?> avance(s)
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($fonction); ?></td>
                        <td>
                            <span class="badge badge-blue" style="font-size:11px;">
                                <?php echo htmlspecialchars($lieu_affectation); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <?php if ($salaire_base > 0): ?>
                                $ <?php echo number_format($salaire_base, 2, ',', ' '); ?>
                            <?php else: ?>
                                <span class="montant-zero">$ 0,00</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right text-green">
                            $ <?php echo number_format($avantages_mois, 2, ',', ' '); ?>
                            <?php if (!empty($liste_avantages)): ?>
                                <div style="font-size:9px;color:#94a3b8;">
                                    <?php foreach ($liste_avantages as $av): ?>
                                        <span class="badge badge-blue" style="font-size:8px;"><?php echo htmlspecialchars($av->getLibelle()); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right text-purple">$ <?php echo number_format($salaire_brut, 2, ',', ' '); ?></td>
                        <td class="text-right text-red">
                            $ <?php echo number_format($retenues_mois, 2, ',', ' '); ?>
                            <?php if (!empty($liste_retenues)): ?>
                                <div style="font-size:9px;color:#94a3b8;">
                                    <?php foreach ($liste_retenues as $rt): ?>
                                        <span class="badge badge-red" style="font-size:8px;"><?php echo htmlspecialchars($rt->getLibelle()); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right text-yellow">$ <?php echo number_format($avances_agent, 2, ',', ' '); ?></td>
                        <td class="text-right font-bold text-green">$ <?php echo number_format($salaire_net, 2, ',', ' '); ?></td>
                        <td class="text-center">
                            <span class="badge <?php echo $statut_badge; ?>">
                                <i class="fas <?php echo $statut_icon; ?>"></i> <?php echo $statut; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Ligne Total -->
                    <tr class="total-row">
                        <td colspan="4" style="text-align:right;font-size:14px;">TOTAUX :</td>
                        <td class="text-right">$ <?php echo number_format($grand_total_base, 2, ',', ' '); ?></td>
                        <td class="text-right text-green">$ <?php echo number_format($grand_total_avantages, 2, ',', ' '); ?></td>
                        <td class="text-right text-purple">$ <?php echo number_format($grand_total_brut, 2, ',', ' '); ?></td>
                        <td class="text-right text-red">$ <?php echo number_format($grand_total_retenues, 2, ',', ' '); ?></td>
                        <td class="text-right text-yellow">$ <?php echo number_format($grand_total_avances, 2, ',', ' '); ?></td>
                        <td class="text-right text-green" style="font-size:16px;">$ <?php echo number_format($grand_total_net, 2, ',', ' '); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Récapitulatif du paiement -->
        <div class="recap-box">
            <div>
                <div class="label"><i class="fas fa-wallet"></i> MONTANT NET TOTAL À PAYER</div>
                <div style="font-size:12px;color:#166534;opacity:0.8;">
                    <?php echo $total_remunerations; ?> rémunération<?php echo $total_remunerations > 1 ? 's' : ''; ?> 
                    - <?php echo $total_agents; ?> agent<?php echo $total_agents > 1 ? 's' : ''; ?>
                    <?php if ($total_agents_avec_avances > 0): ?>
                        <span style="margin-left:8px;background:#fef3c7;padding:2px 10px;border-radius:4px;color:#92400e;">
                            <?php echo $total_agents_avec_avances; ?> avec avances
                        </span>
                    <?php endif; ?>
                    <?php if ($remunerations_sans_affectation > 0): ?>
                        <span style="margin-left:8px;background:#fee2e2;padding:2px 10px;border-radius:4px;color:#991b1b;">
                            <?php echo $remunerations_sans_affectation; ?> sans affectation
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="amount">
                $ <?php echo number_format($grand_total_net, 2, ',', ' '); ?>
            </div>
        </div>

        <!-- Détail du calcul -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:16px;background:#f8fafc;border-radius:8px;padding:16px 20px;border:1px solid #e2e8f0;">
            <div>
                <div style="font-size:11px;color:#94a3b8;">Salaire Brut Total</div>
                <div style="font-weight:700;color:#2563eb;">$ <?php echo number_format($grand_total_brut, 2, ',', ' '); ?></div>
                <div style="font-size:10px;color:#94a3b8;">Base + Avantages</div>
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;">Total Retenues</div>
                <div style="font-weight:700;color:#dc2626;">- $ <?php echo number_format($grand_total_retenues, 2, ',', ' '); ?></div>
                <div style="font-size:10px;color:#94a3b8;"><?php echo $grand_total_brut > 0 ? number_format(($grand_total_retenues / $grand_total_brut) * 100, 1) : 0; ?>% du brut</div>
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;">Total Avances</div>
                <div style="font-weight:700;color:#f97316;">- $ <?php echo number_format($grand_total_avances, 2, ',', ' '); ?></div>
                <div style="font-size:10px;color:#94a3b8;"><?php echo $total_agents_avec_avances; ?> agents concernés</div>
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;">= Salaire Net Total</div>
                <div style="font-weight:700;color:#16a34a;font-size:20px;">$ <?php echo number_format($grand_total_net, 2, ',', ' '); ?></div>
                <div style="font-size:10px;color:#94a3b8;">À payer aux agents</div>
            </div>
        </div>

        <!-- Informations supplémentaires -->
        <?php if ($total_remunerations > 0): ?>
        <div style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div class="info-supp">
                <i class="fas fa-percentage"></i>
                <span>Taux de retenues : <strong><?php echo $grand_total_brut > 0 ? number_format(($grand_total_retenues / $grand_total_brut) * 100, 1) : 0; ?>%</strong></span>
            </div>
            <div class="info-supp">
                <i class="fas fa-gift"></i>
                <span>Taux d'avantages : <strong><?php echo $grand_total_base > 0 ? number_format(($grand_total_avantages / $grand_total_base) * 100, 1) : 0; ?>%</strong></span>
            </div>
            <div class="info-supp">
                <i class="fas fa-hand-holding-usd"></i>
                <span>Avances / Brut : <strong><?php echo $grand_total_brut > 0 ? number_format(($grand_total_avances / $grand_total_brut) * 100, 1) : 0; ?>%</strong></span>
            </div>
            <div class="info-supp blue">
                <i class="fas fa-calculator"></i>
                <span>Formule : Brut - Retenues - Avances = <strong>$ <?php echo number_format($grand_total_net, 2, ',', ' '); ?></strong></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pied de page -->
        <div style="text-align:center;font-size:12px;color:#94a3b8;padding-top:16px;border-top:1px solid #e5e7eb;margin-top:16px;">
            <p>Document généré automatiquement - Fiche périodique des rémunérations</p>
            <p style="font-size:10px;">© <?php echo date('Y'); ?> - Gestion des Rémunérations</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer no-print">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations | Tous droits réservés
    </div>
</div>

</body>
</html>