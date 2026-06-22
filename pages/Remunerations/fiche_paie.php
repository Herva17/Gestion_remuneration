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

// Récupération des paramètres
$remuneration_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;

if ($remuneration_id <= 0 || $agent_id <= 0) {
    die("Paramètres invalides.");
}

// Récupération des données
$remuneration = Remuneration::getById($remuneration_id);
$agent = Agent::getById($agent_id);

if (!$remuneration || !$agent) {
    die("Données introuvables.");
}

// ============================================================
//  UTILISATION DE LA CLASSE REMUNERATION POUR LES CALCULS
// ============================================================

// 1. Récupérer toutes les années scolaires
$anneesScolaires = AnneeScolaire::getAll();

// 2. Récupérer l'année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$idAnnee = $anneeCourante ? $anneeCourante->getId() : 0;

// 3. Récupérer les informations de l'agent
$nomAgent = $agent->getNomComplet();
$fonction = $agent->getFonction() ?? 'N/A';
$telephone = $agent->getTelephone() ?? 'N/A';
$adresse = $agent->getAdresse() ?? 'N/A';

// 4. Récupérer les données via la classe Remuneration
$moisRemuneration = $remuneration->getMois();
$anneeRemuneration = $remuneration->getAnnee();

// 5. Utiliser les méthodes de calcul de la classe Remuneration
$salaireBase = $remuneration->getMontant();
$totalAvantagesMois = $remuneration->getTotalAvantages();
$totalRetenuesMois = $remuneration->getTotalRetenues();

// ============================================================
//  GESTION AUTOMATIQUE DES AVANCES - CORRIGÉE
// ============================================================

// Récupérer TOUTES les avances en cours de l'agent (quel que soit le mois)
// Cela permet de prendre en compte toutes les avances non remboursées
$avances = Avance::getAvancesEnCours($agent_id);

// Calculer le total des avances en cours
$totalAvances = 0;
foreach ($avances as $av) {
    $totalAvances += $av->getMontant();
}
$nbAvances = count($avances);

// Récupérer également toutes les avances (y compris remboursées) pour les statistiques
$toutesAvances = Avance::getByAgent($agent_id);
$totalAvancesRemboursees = 0;
foreach ($toutesAvances as $av) {
    if ($av->isRembourse()) {
        $totalAvancesRemboursees += $av->getMontant();
    }
}

// Récupérer les avances par catégorie pour l'affichage
$avancesParCategorie = [];
$avancesParStatut = [
    'en_cours' => 0,
    'rembourse' => 0
];

foreach ($toutesAvances as $av) {
    // Par catégorie (libellé)
    $libelle = strtolower($av->getLibelle());
    if (!isset($avancesParCategorie[$libelle])) {
        $avancesParCategorie[$libelle] = 0;
    }
    $avancesParCategorie[$libelle] += $av->getMontant();
    
    // Par statut
    if ($av->isEnCours()) {
        $avancesParStatut['en_cours'] += $av->getMontant();
    } elseif ($av->isRembourse()) {
        $avancesParStatut['rembourse'] += $av->getMontant();
    }
}

// ============================================================
//  CALCUL DU SALAIRE NET APRES DEDUCTION DES AVANCES
// ============================================================

// 1. Salaire brut = Salaire de base + Total avantages
$salaireBrut = $salaireBase + $totalAvantagesMois;

// 2. Salaire net = Salaire brut - Total retenues
$salaireNet = $salaireBrut - $totalRetenuesMois;

// 3. Salaire net à payer = Salaire net - Total avances (SOUSTRACTION EFFECTUEE ICI)
$salaireNetApresAvances = $salaireNet - $totalAvances;

// Si le salaire net après avances est négatif, on le met à 0
if ($salaireNetApresAvances < 0) {
    $salaireNetApresAvances = 0;
}

// ============================================================
//  STATISTIQUES GLOBALES
// ============================================================

// 6. Récupérer les listes détaillées
$avantagesMois = $remuneration->getAvantages();
$retenuesMois = $remuneration->getRetenues();

// 7. Récupérer les avantages/retenues par catégorie
$avantagesParCategorie = $remuneration->getAvantagesParCategorie();
$retenuesParCategorie = $remuneration->getRetenuesParCategorie();

// 8. Récupérer TOUS les avantages/retenues (toutes années) pour les statistiques
$tousAvantages = Avantage::getByAgent($agent_id);
$toutesRetenues = Retenue::getByAgent($agent_id);

// 9. Calculs des totaux globaux
$totalAvantagesGlobal = 0;
$avantagesParAnnee = [];
foreach ($tousAvantages as $av) {
    $totalAvantagesGlobal += $av->getMontant();
    $anneeId = method_exists($av, 'getIdAnnee') ? $av->getIdAnnee() : 0;
    if (!isset($avantagesParAnnee[$anneeId])) {
        $avantagesParAnnee[$anneeId] = [];
    }
    $avantagesParAnnee[$anneeId][] = $av;
}

$totalRetenuesGlobal = 0;
$retenuesParAnnee = [];
foreach ($toutesRetenues as $rt) {
    $totalRetenuesGlobal += $rt->getMontant();
    $anneeId = method_exists($rt, 'getIdAnnee') ? $rt->getIdAnnee() : 0;
    if (!isset($retenuesParAnnee[$anneeId])) {
        $retenuesParAnnee[$anneeId] = [];
    }
    $retenuesParAnnee[$anneeId][] = $rt;
}

// 10. Statistiques globales
$totalAvantages = count($tousAvantages);
$totalRetenues = count($toutesRetenues);
$totalAvantagesActifs = 0;
$totalRetenuesActives = 0;

foreach ($tousAvantages as $av) {
    if (method_exists($av, 'getStatut') && $av->getStatut() === 'actif') $totalAvantagesActifs++;
}
foreach ($toutesRetenues as $rt) {
    if (method_exists($rt, 'getStatut') && $rt->getStatut() === 'actif') $totalRetenuesActives++;
}

// 11. Avantages et retenues de l'année en cours
$totalAvantagesAnneeCourante = 0;
$totalRetenuesAnneeCourante = 0;
if ($idAnnee > 0) {
    if (isset($avantagesParAnnee[$idAnnee])) {
        foreach ($avantagesParAnnee[$idAnnee] as $av) {
            $totalAvantagesAnneeCourante += $av->getMontant();
        }
    }
    if (isset($retenuesParAnnee[$idAnnee])) {
        foreach ($retenuesParAnnee[$idAnnee] as $rt) {
            $totalRetenuesAnneeCourante += $rt->getMontant();
        }
    }
}

// 12. Récupérer le résumé complet
$resumeSalaire = $remuneration->getResumeSalaire();

// 13. Récupérer les avances en cours pour l'agent (toutes périodes) - déjà fait
$totalAvancesEnCours = $totalAvances; // Utiliser le total déjà calculé
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de Paie - <?php echo htmlspecialchars($nomAgent); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f1f5f9; 
            padding: 20px;
            color: #1e293b;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .header-paie {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-paie .title {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
        }
        .header-paie .subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .info-entreprise {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .info-entreprise .nom {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .info-entreprise .details {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 24px;
        }

        .section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-title i {
            color: #2563eb;
        }
        .section-title .count {
            font-size: 12px;
            font-weight: 500;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row .label {
            color: #64748b;
            font-weight: 500;
        }
        .info-row .value {
            font-weight: 600;
            color: #0f172a;
        }

        .montant-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .montant-table td {
            padding: 8px 4px;
            border-bottom: 1px solid #f1f5f9;
        }
        .montant-table .libelle {
            color: #475569;
        }
        .montant-table .montant {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }
        .montant-table .total-row td {
            font-weight: 700;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
        }
        .montant-table .positive { color: #16a34a; }
        .montant-table .negative { color: #dc2626; }
        .montant-table .highlight { color: #2563eb; }
        .montant-table .avance-color { color: #f97316; }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-purple { background: #f3e8ff; color: #6d28d9; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-orange { background: #ffedd5; color: #9a3412; }

        .total-general {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border-radius: 8px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .total-general .label {
            font-size: 16px;
            font-weight: 600;
            opacity: 0.9;
        }
        .total-general .amount {
            font-size: 28px;
            font-weight: 700;
        }
        .total-general .sub-info {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 4px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-warning { background: #f97316; color: white; }
        .btn-warning:hover { background: #ea580c; }

        .signature {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .signature .sig-item {
            text-align: center;
        }
        .signature .sig-item .label {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }
        .signature .sig-item .line {
            border-bottom: 1px solid #cbd5e1;
            margin-top: 30px;
            padding-bottom: 4px;
            font-size: 12px;
            color: #94a3b8;
        }

        .empty-data {
            color: #94a3b8;
            font-size: 13px;
            text-align: center;
            padding: 15px 0;
            font-style: italic;
        }

        .info-supp {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 12px;
            font-size: 13px;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-supp.warning {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }
        .info-supp.danger {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .stats-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stats-card .number {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
        }
        .stats-card .label {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .progress-bar {
            background: #e2e8f0;
            border-radius: 8px;
            height: 8px;
            margin-top: 8px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s;
        }
        .fill-green { background: #16a34a; }
        .fill-red { background: #dc2626; }
        .fill-blue { background: #2563eb; }
        .fill-orange { background: #f97316; }
        .fill-purple { background: #8b5cf6; }

        .avance-summary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-top: 8px;
        }
        .avance-summary .item {
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .avance-summary .item .montant {
            font-weight: 700;
            font-size: 16px;
        }
        .avance-summary .item .label {
            font-size: 11px;
            color: #94a3b8;
        }
        .text-orange { color: #f97316; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }

        .calcul-detail {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 12px 0;
            border: 1px solid #e2e8f0;
        }
        .calcul-detail .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .calcul-detail .row .label {
            color: #64748b;
        }
        .calcul-detail .row .value {
            font-weight: 600;
        }
        .calcul-detail .row.total {
            border-top: 2px solid #cbd5e1;
            padding-top: 10px;
            margin-top: 6px;
            font-size: 16px;
        }
        .calcul-detail .row.total .label {
            font-weight: 700;
            color: #0f172a;
        }
        .calcul-detail .row.total .value {
            font-size: 18px;
        }

        .salaire-final {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .salaire-final .label {
            font-size: 18px;
            font-weight: 700;
        }
        .salaire-final .amount {
            font-size: 28px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .header-paie { flex-direction: column; align-items: flex-start; gap: 8px; }
            .total-general { flex-direction: column; text-align: center; }
            .total-general .amount { font-size: 22px; }
            .signature { grid-template-columns: 1fr; gap: 10px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .avance-summary { grid-template-columns: 1fr; }
        }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 20px; }
            .actions { display: none; }
            .header-paie { border-bottom-color: #000; }
            .total-general { background: #000; }
            .section { background: #f9fafb; }
        }
    </style>
</head>
<body>

<div class="container" id="fiche-paie">

    <!-- En-tête -->
    <div class="header-paie">
        <div>
            <div class="title"><i class="fas fa-file-invoice"></i> FICHE DE PAIE</div>
            <div class="subtitle">Bulletin de salaire - <?php echo $moisRemuneration . ' ' . $anneeRemuneration; ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-weight:600;color:#0f172a;">N° <?php echo str_pad($remuneration->getId(), 6, '0', STR_PAD_LEFT); ?></div>
            <div style="font-size:12px;color:#94a3b8;">Émis le <?php echo date('d/m/Y'); ?></div>
        </div>
    </div>

    <!-- Informations Entreprise -->
    <div class="info-entreprise">
        <div class="nom">🏢 Gestion Rémunération SARL</div>
        <div class="details">
            <i class="fas fa-map-marker-alt"></i> 123 Avenue de la République, Kinshasa, RDC &nbsp;|&nbsp;
            <i class="fas fa-phone"></i> +243 82 123 4567 &nbsp;|&nbsp;
            <i class="fas fa-envelope"></i> contact@gestionremuneration.rdc
        </div>
    </div>

    <!-- Informations Agent + Salaire -->
    <div class="grid-2">
        <!-- Informations Agent -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-user"></i> Informations Agent</span>
            </div>
            <div class="info-row">
                <span class="label">Nom complet</span>
                <span class="value"><?php echo htmlspecialchars($nomAgent); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Fonction</span>
                <span class="value"><?php echo htmlspecialchars($fonction); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Téléphone</span>
                <span class="value"><?php echo htmlspecialchars($telephone); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Adresse</span>
                <span class="value"><?php echo htmlspecialchars($adresse); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Période</span>
                <span class="value"><?php echo $moisRemuneration . ' ' . $anneeRemuneration; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Année scolaire</span>
                <span class="value"><?php echo $anneeCourante ? htmlspecialchars($anneeCourante->getDesignationAnn()) : 'N/A'; ?></span>
            </div>
            <div class="info-supp <?php echo ($nbAvances > 0) ? 'warning' : ''; ?>">
                <i class="fas <?php echo ($nbAvances > 0) ? 'fa-exclamation-triangle' : 'fa-info-circle'; ?>"></i>
                Avances : <?php echo $nbAvances; ?> | Montant total : <?php echo number_format($totalAvances, 2, ',', ' '); ?> $
                <?php if ($totalAvancesEnCours > 0): ?>
                    <span style="margin-left:8px;color:#dc2626;">(<?php echo number_format($totalAvancesEnCours, 2, ',', ' '); ?> $ en cours)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Récapitulatif -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-money-bill-wave"></i> Récapitulatif</span>
            </div>
            <div class="info-row">
                <span class="label">Salaire de base</span>
                <span class="value" style="color:#16a34a;"><?php echo number_format($salaireBase, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row">
                <span class="label">Total avantages (<?php echo count($avantagesMois); ?>)</span>
                <span class="value" style="color:#16a34a;">+ <?php echo number_format($totalAvantagesMois, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row" style="border-bottom:2px solid #2563eb;padding-bottom:8px;">
                <span class="label" style="font-weight:700;color:#2563eb;">= Salaire brut</span>
                <span class="value" style="font-weight:700;color:#2563eb;font-size:16px;"><?php echo number_format($salaireBrut, 2, ',', ' '); ?> $</span>
            </div>
            
            <div class="info-row" style="padding-top:8px;">
                <span class="label">Total retenues (<?php echo count($retenuesMois); ?>)</span>
                <span class="value" style="color:#dc2626;">- <?php echo number_format($totalRetenuesMois, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row" style="border-bottom:2px solid #16a34a;padding-bottom:8px;">
                <span class="label" style="font-weight:700;color:#16a34a;">= Salaire net</span>
                <span class="value" style="font-weight:700;color:#16a34a;font-size:16px;"><?php echo number_format($salaireNet, 2, ',', ' '); ?> $</span>
            </div>
            
            <?php if ($nbAvances > 0): ?>
            <div class="info-row" style="padding-top:8px;background:#fffbeb;padding:8px 12px;border-radius:6px;margin:6px 0;">
                <span class="label" style="font-weight:700;color:#f97316;font-size:15px;">
                    <i class="fas fa-hand-holding-usd" style="color:#f97316;"></i> Total avances (<?php echo $nbAvances; ?>)
                </span>
                <span class="value" style="font-weight:700;color:#f97316;font-size:16px;">- <?php echo number_format($totalAvances, 2, ',', ' '); ?> $</span>
            </div>
            <?php else: ?>
            <div class="info-row" style="padding-top:8px;">
                <span class="label" style="font-weight:700;color:#94a3b8;">
                    <i class="fas fa-hand-holding-usd" style="color:#94a3b8;"></i> Total avances
                </span>
                <span class="value" style="font-weight:700;color:#94a3b8;">0,00 $</span>
            </div>
            <?php endif; ?>
            
            <div class="salaire-final">
                <span class="label"><i class="fas fa-check-circle"></i> Salaire net à payer</span>
                <span class="amount"><?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $</span>
            </div>
            
            <?php if ($nbAvances > 0): ?>
            <div style="font-size:11px;color:#94a3b8;margin-top:8px;text-align:center;padding:6px 12px;background:#f8fafc;border-radius:4px;border:1px solid #e2e8f0;">
                <i class="fas fa-calculator"></i> 
                <strong>Formule :</strong> 
                <?php echo number_format($salaireNet, 2, ',', ' '); ?> $ - <?php echo number_format($totalAvances, 2, ',', ' '); ?> $ = 
                <strong style="color:#2563eb;font-size:13px;"><?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $</strong>
            </div>
            <?php endif; ?>
            
            <?php if ($salaireNetApresAvances == 0 && $totalAvances > 0): ?>
            <div style="background:#fee2e2;border-radius:6px;padding:8px 12px;margin-top:8px;color:#991b1b;font-weight:600;font-size:13px;border:1px solid #fca5a5;">
                <i class="fas fa-exclamation-triangle"></i> 
                Le montant des avances (<?php echo number_format($totalAvances, 2, ',', ' '); ?> $) est supérieur ou égal au salaire net (<?php echo number_format($salaireNet, 2, ',', ' '); ?> $).
                Le salaire net à payer est de 0,00 $.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Détails des avantages et retenues -->
    <div class="grid-2">
        <!-- Avantages du mois -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-gift" style="color:#eab308;"></i> Avantages</span>
                <span class="count"><?php echo count($avantagesMois); ?></span>
            </div>
            <?php if (empty($avantagesMois)): ?>
                <div class="empty-data">
                    <i class="fas fa-info-circle"></i> Aucun avantage pour cette période
                </div>
            <?php else: ?>
                <table class="montant-table">
                    <?php foreach ($avantagesMois as $av): ?>
                    <tr>
                        <td class="libelle">
                            <?php echo htmlspecialchars($av->getLibelle() ?? 'Avantage'); ?>
                            <span style="font-size:11px;color:#94a3b8;display:block;">
                                <span class="badge badge-blue"><?php echo $av->getTypeLibelle(); ?></span>
                                <?php if (method_exists($av, 'isRecurrent') && $av->isRecurrent()): ?>
                                    <span class="badge badge-yellow"><i class="fas fa-sync-alt"></i> Récurrent</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="montant positive">+ <?php echo number_format($av->getMontant(), 2, ',', ' '); ?> $</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Total avantages</strong></td>
                        <td class="montant positive"><strong>+ <?php echo number_format($totalAvantagesMois, 2, ',', ' '); ?> $</strong></td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>

        <!-- Retenues du mois -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Retenues</span>
                <span class="count"><?php echo count($retenuesMois); ?></span>
            </div>
            <?php if (empty($retenuesMois)): ?>
                <div class="empty-data">
                    <i class="fas fa-info-circle"></i> Aucune retenue pour cette période
                </div>
            <?php else: ?>
                <table class="montant-table">
                    <?php foreach ($retenuesMois as $rt): ?>
                    <tr>
                        <td class="libelle">
                            <?php echo htmlspecialchars($rt->getLibelle() ?? 'Retenue'); ?>
                            <span style="font-size:11px;color:#94a3b8;display:block;">
                                <span class="badge badge-red"><?php echo $rt->getTypeLibelle(); ?></span>
                                <?php if (method_exists($rt, 'isRecurrent') && $rt->isRecurrent()): ?>
                                    <span class="badge badge-yellow"><i class="fas fa-sync-alt"></i> Récurrent</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="montant negative">- <?php echo number_format($rt->getMontant(), 2, ',', ' '); ?> $</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Total retenues</strong></td>
                        <td class="montant negative"><strong>- <?php echo number_format($totalRetenuesMois, 2, ',', ' '); ?> $</strong></td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Avances détaillées -->
    <div class="section" style="margin-top:16px;border-color:#fcd34d;background:#fffbeb;">
        <div class="section-title">
            <span><i class="fas fa-hand-holding-usd" style="color:#f97316;"></i> Avances sur Salaire</span>
            <span class="count" style="background:#fef3c7;color:#92400e;"><?php echo count($avances); ?></span>
        </div>
        
        <?php if (empty($avances)): ?>
            <div class="empty-data">
                <i class="fas fa-check-circle" style="color:#16a34a;"></i> Aucune avance en cours
            </div>
        <?php else: ?>
            <div class="avance-summary">
                <div class="item">
                    <div class="montant text-orange"><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</div>
                    <div class="label">Total avances en cours</div>
                </div>
                <div class="item">
                    <div class="montant text-orange"><?php echo number_format($avancesParStatut['en_cours'], 2, ',', ' '); ?> $</div>
                    <div class="label">En cours</div>
                </div>
                <div class="item">
                    <div class="montant text-green"><?php echo number_format($avancesParStatut['rembourse'], 2, ',', ' '); ?> $</div>
                    <div class="label">Remboursées</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;margin-top:12px;">
                <?php foreach ($avances as $avance): ?>
                <div style="background:white;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($avance->getLibelle()); ?></div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                            <span class="badge <?php echo $avance->isEnCours() ? 'badge-yellow' : 'badge-green'; ?>">
                                <?php echo $avance->getStatutLibelle(); ?>
                            </span>
                            <span style="margin-left:8px;"><?php echo $avance->getMois(); ?>/<?php echo $avance->getAnnee(); ?></span>
                        </div>
                    </div>
                    <div style="font-weight:700;color:#f97316;font-size:16px;">
                        <?php echo number_format($avance->getMontant(), 2, ',', ' '); ?> $
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top:2px solid #fcd34d;padding-top:12px;margin-top:12px;display:flex;justify-content:space-between;background:#fef3c7;padding:12px 16px;border-radius:8px;">
                <span style="font-weight:700;color:#0f172a;">
                    <i class="fas fa-calculator"></i> Total des avances à déduire
                </span>
                <span style="font-weight:700;color:#f97316;font-size:20px;">
                    <?php echo number_format($totalAvances, 2, ',', ' '); ?> $
                </span>
            </div>

            <div class="info-supp danger" style="margin-top:8px;">
                <i class="fas fa-exclamation-circle"></i>
                <strong><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</strong> d'avances sont encore en cours. Elles seront déduites du salaire.
            </div>
        <?php endif; ?>
    </div>

    <!-- Barres de progression -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="number" style="color:#16a34a;"><?php echo number_format($totalAvantagesMois, 2, ',', ' '); ?> $</div>
            <div class="label">Avantages du mois</div>
            <div class="progress-bar">
                <div class="fill fill-green" style="width: <?php echo min(100, ($salaireBase > 0 ? ($totalAvantagesMois / $salaireBase) * 100 : 0)); ?>%;"></div>
            </div>
        </div>
        <div class="stats-card">
            <div class="number" style="color:#dc2626;"><?php echo number_format($totalRetenuesMois, 2, ',', ' '); ?> $</div>
            <div class="label">Retenues du mois</div>
            <div class="progress-bar">
                <div class="fill fill-red" style="width: <?php echo min(100, ($salaireBrut > 0 ? ($totalRetenuesMois / $salaireBrut) * 100 : 0)); ?>%;"></div>
            </div>
        </div>
        <div class="stats-card">
            <div class="number" style="color:#f97316;"><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</div>
            <div class="label">Total avances</div>
            <div class="progress-bar">
                <div class="fill fill-orange" style="width: <?php echo min(100, ($salaireNet > 0 ? ($totalAvances / $salaireNet) * 100 : 0)); ?>%;"></div>
            </div>
        </div>
        <div class="stats-card">
            <div class="number" style="color:#2563eb;"><?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $</div>
            <div class="label">Net à payer</div>
            <div class="progress-bar">
                <div class="fill fill-blue" style="width: <?php echo min(100, ($salaireBrut > 0 ? ($salaireNetApresAvances / $salaireBrut) * 100 : 0)); ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- Total général -->
    <div class="total-general">
        <div>
            <div class="label"><i class="fas fa-check-circle"></i> Salaire net à payer</div>
            <div class="sub-info">Après déduction des retenues et avances</div>
            <div class="sub-info" style="margin-top:4px;">
                Période : <?php echo $moisRemuneration . ' ' . $anneeRemuneration; ?>
                <?php if ($nbAvances > 0): ?>
                    <span style="margin-left:12px;background:rgba(255,255,255,0.2);padding:2px 12px;border-radius:4px;">
                        Avances déduites : <?php echo number_format($totalAvances, 2, ',', ' '); ?> $
                    </span>
                <?php endif; ?>
            </div>
            <div class="sub-info" style="margin-top:6px;background:rgba(255,255,255,0.15);padding:4px 12px;border-radius:4px;font-size:12px;">
                <?php echo number_format($salaireNet, 2, ',', ' '); ?> $ - <?php echo number_format($totalAvances, 2, ',', ' '); ?> $ = 
                <strong style="color:#fcd34d;font-size:14px;"><?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $</strong>
            </div>
        </div>
        <div class="amount">
            <?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $
        </div>
    </div>

    <!-- Récapitulatif global -->
    <div style="background:#f8fafc;border-radius:8px;padding:16px 20px;margin-top:16px;border:1px solid #e2e8f0;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total avantages (toutes années)</span>
            <div style="font-weight:700;color:#16a34a;"><?php echo number_format($totalAvantagesGlobal, 2, ',', ' '); ?> $</div>
            <span style="font-size:11px;color:#94a3b8;"><?php echo $totalAvantages; ?> avantages (<?php echo $totalAvantagesActifs; ?> actifs)</span>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total retenues (toutes années)</span>
            <div style="font-weight:700;color:#dc2626;"><?php echo number_format($totalRetenuesGlobal, 2, ',', ' '); ?> $</div>
            <span style="font-size:11px;color:#94a3b8;"><?php echo $totalRetenues; ?> retenues (<?php echo $totalRetenuesActives; ?> actives)</span>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total avances (toutes années)</span>
            <div style="font-weight:700;color:#f97316;"><?php echo number_format($totalAvancesEnCours + $totalAvancesRemboursees, 2, ',', ' '); ?> $</div>
            <span style="font-size:11px;color:#94a3b8;">
                En cours: <?php echo number_format($totalAvancesEnCours, 2, ',', ' '); ?> $ | 
                Remboursées: <?php echo number_format($totalAvancesRemboursees, 2, ',', ' '); ?> $
            </span>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Pourcentage retenues</span>
            <div style="font-weight:700;color:#2563eb;"><?php echo number_format($resumeSalaire['pourcentage_retenues'], 1); ?>%</div>
            <span style="font-size:11px;color:#94a3b8;">
                Avantages: <?php echo number_format($resumeSalaire['pourcentage_avantages'], 1); ?>% du salaire de base
            </span>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature">
        <div class="sig-item">
            <div class="label">Payeur</div>
            <div class="line">Signature</div>
        </div>
        <div class="sig-item">
            <div class="label">Comptable</div>
            <div class="line">Signature</div>
        </div>
        <div class="sig-item">
            <div class="label">Agent</div>
            <div class="line">Signature</div>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

</div>

</body>
</html>