<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';

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

// Inclure les classes disponibles
require_once __DIR__ . '/../../Classes/remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/Avance.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

// Vérifier si l'ID est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID de rémunération non spécifié";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$remuneration_id = (int)$_GET['id'];

// Récupérer la rémunération
$remuneration = Remuneration::getById($remuneration_id);

if (!$remuneration) {
    $_SESSION['message'] = "Rémunération non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Récupérer l'agent associé
$agent = null;
if ($remuneration->getIdAgent()) {
    $agent = Agent::getById($remuneration->getIdAgent());
}

// Récupérer l'année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$idAnnee = $anneeCourante ? $anneeCourante->getId() : 0;

// Récupérer les avantages de l'agent pour la période
$avantages = [];
$totalAvantages = 0;

// Récupérer tous les avantages de l'agent
$allAvantages = Avantage::getByAgent($remuneration->getIdAgent());
foreach ($allAvantages as $av) {
    // Vérifier si l'avantage correspond au mois et à l'année de la rémunération
    if ($av->getMois() === $remuneration->getMois() && $av->getAnnee() === $remuneration->getAnnee()) {
        if (method_exists($av, 'getStatut') && $av->getStatut() === 'actif') {
            $avantages[] = $av;
            $totalAvantages += $av->getMontant();
        }
    }
}

// Récupérer les retenues de l'agent pour la période
$retenues = [];
$totalRetenues = 0;

// Récupérer toutes les retenues de l'agent
$allRetenues = Retenue::getByAgent($remuneration->getIdAgent());
foreach ($allRetenues as $rt) {
    // Vérifier si la retenue correspond au mois et à l'année de la rémunération
    if ($rt->getMois() === $remuneration->getMois() && $rt->getAnnee() === $remuneration->getAnnee()) {
        if (method_exists($rt, 'getStatut') && $rt->getStatut() === 'actif') {
            $retenues[] = $rt;
            $totalRetenues += $rt->getMontant();
        }
    }
}

// ============================================================
//  GESTION DES AVANCES
// ============================================================

// Récupérer TOUTES les avances en cours de l'agent (quel que soit le mois)
$avances = Avance::getAvancesEnCours($remuneration->getIdAgent());
$totalAvances = 0;
foreach ($avances as $av) {
    $totalAvances += $av->getMontant();
}
$nbAvances = count($avances);

// Récupérer toutes les avances pour les statistiques
$toutesAvances = Avance::getByAgent($remuneration->getIdAgent());
$totalAvancesRemboursees = 0;
foreach ($toutesAvances as $av) {
    if ($av->isRembourse()) {
        $totalAvancesRemboursees += $av->getMontant();
    }
}

// ============================================================
//  CALCULS
// ============================================================

$salaireBase = $remuneration->getMontant();
$salaireBrut = $salaireBase + $totalAvantages;
$salaireNet = $salaireBrut - $totalRetenues;
$salaireNetApresAvances = $salaireNet - $totalAvances;

// Si le salaire net après avances est négatif, on le met à 0
if ($salaireNetApresAvances < 0) {
    $salaireNetApresAvances = 0;
}

// Génération d'un numéro de reçu unique
$numeroRecu = 'REC-' . date('Ymd') . '-' . str_pad($remuneration->getId(), 6, '0', STR_PAD_LEFT);

// Récupérer l'utilisateur (si disponible)
$user = null;
if (file_exists(__DIR__ . '/../../Classes/User.php')) {
    require_once __DIR__ . '/../../Classes/User.php';
    if (class_exists('User')) {
        $user = User::getById($_SESSION['user_id'] ?? 0);
    }
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Mois en lettres
$moisNoms = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$moisLibelle = isset($moisNoms[$remuneration->getMois()]) ? $moisNoms[$remuneration->getMois()] : $remuneration->getMois();

// Nombre en lettres pour le montant
function nombreEnLettres($nombre) {
    $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    $dizaines = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
    
    $nombre = round($nombre, 2);
    $partieEntiere = floor($nombre);
    $partieDecimale = round(($nombre - $partieEntiere) * 100);
    
    if ($partieEntiere == 0) {
        $texte = 'zéro';
    } else {
        $texte = '';
        if ($partieEntiere >= 1000000) {
            $millions = floor($partieEntiere / 1000000);
            $partieEntiere = $partieEntiere % 1000000;
            $texte .= nombreEnLettres($millions) . ' million' . ($millions > 1 ? 's' : '') . ' ';
        }
        if ($partieEntiere >= 1000) {
            $milliers = floor($partieEntiere / 1000);
            $partieEntiere = $partieEntiere % 1000;
            if ($milliers == 1) {
                $texte .= 'mille ';
            } else {
                $texte .= nombreEnLettres($milliers) . ' mille ';
            }
        }
        if ($partieEntiere >= 100) {
            $centaines = floor($partieEntiere / 100);
            $partieEntiere = $partieEntiere % 100;
            if ($centaines == 1) {
                $texte .= 'cent ';
            } else {
                $texte .= $unites[$centaines] . ' cent ';
            }
        }
        if ($partieEntiere > 0) {
            if ($partieEntiere < 20) {
                $texte .= $unites[$partieEntiere];
            } else {
                $dizaine = floor($partieEntiere / 10);
                $unite = $partieEntiere % 10;
                if ($unite == 0) {
                    $texte .= $dizaines[$dizaine];
                } elseif ($dizaine == 7 || $dizaine == 9) {
                    $texte .= $dizaines[$dizaine] . '-' . $unites[10 + $unite];
                } else {
                    $texte .= $dizaines[$dizaine] . '-' . $unites[$unite];
                }
            }
        }
    }
    
    $texte .= ' dollar' . ($partieEntiere > 1 ? 's' : '');
    if ($partieDecimale > 0) {
        $texte .= ' et ' . $partieDecimale . ' centime' . ($partieDecimale > 1 ? 's' : '');
    }
    
    return ucfirst($texte);
}

$montantEnLettres = nombreEnLettres($salaireNetApresAvances);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - <?php echo htmlspecialchars($remuneration->getId()); ?></title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .header-recu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-recu .title {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
        }
        .header-recu .subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .badge-statut {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-statut.paye { background: #dcfce7; color: #166534; }
        .badge-statut.en_attente { background: #fef3c7; color: #92400e; }
        .badge-statut.annule { background: #fee2e2; color: #991b1b; }

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
            gap: 20px;
        }

        .section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            margin-left: auto;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
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

        .montant-principal {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border-radius: 12px;
            padding: 24px 32px;
            margin: 20px 0 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .montant-principal .label {
            font-size: 16px;
            font-weight: 600;
            opacity: 0.9;
        }
        .montant-principal .amount {
            font-size: 32px;
            font-weight: 700;
        }

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
        .badge-orange { background: #ffedd5; color: #9a3412; }

        .montant-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .montant-table td {
            padding: 6px 4px;
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
            padding-top: 8px;
        }
        .montant-table .positive { color: #16a34a; }
        .montant-table .negative { color: #dc2626; }
        .montant-table .avance-color { color: #f97316; }

        .section-avances {
            background: #fffbeb;
            border-radius: 8px;
            padding: 16px 20px;
            border: 1px solid #fcd34d;
            margin-top: 16px;
        }
        .section-avances .section-title i {
            color: #f97316;
        }

        .signature {
            margin-top: 30px;
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

        .footer-recu {
            text-align: center;
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
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
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }

        .empty-data {
            color: #94a3b8;
            font-size: 13px;
            text-align: center;
            padding: 10px 0;
            font-style: italic;
        }

        .recap-avances {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }
        .recap-avances .item {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .recap-avances .item .montant {
            font-weight: 700;
            font-size: 16px;
        }
        .recap-avances .item .label {
            font-size: 11px;
            color: #94a3b8;
        }
        .text-orange { color: #f97316; }
        .text-green { color: #16a34a; }

        .montant-lettres {
            background: #f8fafc;
            padding: 10px 16px;
            border-radius: 6px;
            margin: 12px 0;
            border: 1px dashed #94a3b8;
            font-size: 14px;
            font-style: italic;
            color: #1e293b;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .header-recu { flex-direction: column; align-items: flex-start; gap: 12px; }
            .montant-principal { flex-direction: column; text-align: center; }
            .montant-principal .amount { font-size: 26px; }
            .signature { grid-template-columns: 1fr; gap: 10px; }
            .recap-avances { grid-template-columns: 1fr 1fr; }
        }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 20px; }
            .actions { display: none; }
            .header-recu { border-bottom-color: #000; }
            .montant-principal { background: #000; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container">

    <?php if ($remuneration): ?>
    <!-- En-tête -->
    <div class="header-recu">
        <div>
            <div class="title"><i class="fas fa-receipt"></i> REÇU DE PAIEMENT</div>
            <div class="subtitle">Bulletin de salaire - <?php echo $moisLibelle . ' ' . $remuneration->getAnnee(); ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-weight:600;color:#0f172a;">N° <?php echo htmlspecialchars($numeroRecu); ?></div>
            <div style="font-size:12px;color:#94a3b8;">Émis le <?php echo date('d/m/Y à H:i'); ?></div>
            <span class="badge-statut paye">
                <i class="fas fa-check-circle"></i> Payé
            </span>
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

    <!-- Montant Principal -->
    <div class="montant-principal">
        <div>
            <div class="label"><i class="fas fa-money-bill-wave"></i> Salaire net à payer</div>
            <div style="font-size:13px;opacity:0.8;">Période : <?php echo $moisLibelle . ' ' . $remuneration->getAnnee(); ?></div>
            <?php if ($nbAvances > 0): ?>
                <div style="font-size:12px;opacity:0.7;margin-top:4px;">
                    <i class="fas fa-hand-holding-usd"></i> Avances déduites : <?php echo number_format($totalAvances, 2, ',', ' '); ?> $
                </div>
            <?php endif; ?>
        </div>
        <div class="amount">
            <?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> <span style="font-size:18px;">USD</span>
        </div>
    </div>

    <!-- Montant en lettres -->
    <div class="montant-lettres">
        <i class="fas fa-pen" style="color:#2563eb;"></i>
        <strong>Arrêté à la somme de :</strong> <?php echo $montantEnLettres; ?>
    </div>

    <!-- Informations détaillées -->
    <div class="grid-2">
        <div class="section">
            <div class="section-title"><i class="fas fa-info-circle"></i> Informations générales</div>
            <div class="info-row">
                <span class="label">Référence</span>
                <span class="value">#<?php echo str_pad($remuneration->getId(), 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Salaire de base</span>
                <span class="value" style="color:#16a34a;"><?php echo number_format($salaireBase, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row">
                <span class="label">Total avantages (<?php echo count($avantages); ?>)</span>
                <span class="value" style="color:#16a34a;">+ <?php echo number_format($totalAvantages, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row" style="border-bottom:2px solid #2563eb;">
                <span class="label" style="font-weight:700;color:#2563eb;">Salaire brut</span>
                <span class="value" style="font-weight:700;color:#2563eb;"><?php echo number_format($salaireBrut, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row" style="padding-top:8px;">
                <span class="label">Total retenues (<?php echo count($retenues); ?>)</span>
                <span class="value" style="color:#dc2626;">- <?php echo number_format($totalRetenues, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row" style="border-bottom:2px solid #16a34a;">
                <span class="label" style="font-weight:700;color:#16a34a;">Salaire net</span>
                <span class="value" style="font-weight:700;color:#16a34a;"><?php echo number_format($salaireNet, 2, ',', ' '); ?> $</span>
            </div>
            <?php if ($nbAvances > 0): ?>
            <div class="info-row" style="background:#fffbeb;padding:8px 12px;border-radius:6px;margin-top:4px;">
                <span class="label" style="font-weight:700;color:#f97316;">
                    <i class="fas fa-hand-holding-usd" style="color:#f97316;"></i> Avances (<?php echo $nbAvances; ?>)
                </span>
                <span class="value" style="font-weight:700;color:#f97316;">- <?php echo number_format($totalAvances, 2, ',', ' '); ?> $</span>
            </div>
            <?php endif; ?>
            <div class="info-row" style="border-top:3px solid #2563eb;padding-top:10px;margin-top:4px;background:linear-gradient(135deg, #2563eb, #1d4ed8);border-radius:6px;padding:10px 14px;">
                <span class="label" style="font-weight:700;color:white;font-size:16px;">
                    <i class="fas fa-check-circle"></i> NET À PAYER
                </span>
                <span class="value" style="font-weight:700;color:white;font-size:18px;">
                    <?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $
                </span>
            </div>
            <?php if ($nbAvances > 0): ?>
            <div style="font-size:11px;color:#94a3b8;margin-top:6px;text-align:center;">
                <?php echo number_format($salaireNet, 2, ',', ' '); ?> $ - <?php echo number_format($totalAvances, 2, ',', ' '); ?> $ = <?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $
            </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title"><i class="fas fa-user"></i> Bénéficiaire</div>
            <div class="info-row">
                <span class="label">Agent</span>
                <span class="value"><?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : 'N/A'; ?></span>
            </div>
            <?php if ($agent && method_exists($agent, 'getFonction')): ?>
            <div class="info-row">
                <span class="label">Fonction</span>
                <span class="value"><?php echo htmlspecialchars($agent->getFonction() ?? 'N/A'); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($agent && method_exists($agent, 'getTelephone')): ?>
            <div class="info-row">
                <span class="label">Téléphone</span>
                <span class="value"><?php echo htmlspecialchars($agent->getTelephone() ?? 'N/A'); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">Période</span>
                <span class="value"><?php echo $moisLibelle . ' ' . $remuneration->getAnnee(); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Année scolaire</span>
                <span class="value"><?php echo $anneeCourante ? htmlspecialchars($anneeCourante->getDesignationAnn()) : 'N/A'; ?></span>
            </div>
            <?php if ($user): ?>
            <div class="info-row">
                <span class="label">Émis par</span>
                <span class="value"><?php echo htmlspecialchars($user->getNom() ?? $user->getUsername()); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Détails des avantages et retenues -->
    <div class="grid-2">
        <!-- Avantages -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-gift" style="color:#eab308;"></i> Avantages</span>
                <span class="count"><?php echo count($avantages); ?></span>
            </div>
            <?php if (empty($avantages)): ?>
                <div class="empty-data">Aucun avantage pour cette période</div>
            <?php else: ?>
                <table class="montant-table">
                    <?php foreach ($avantages as $av): ?>
                    <tr>
                        <td class="libelle">
                            <?php echo htmlspecialchars($av->getLibelle() ?? 'Avantage'); ?>
                            <span style="font-size:10px;color:#94a3b8;display:block;">
                                <span class="badge badge-blue"><?php echo method_exists($av, 'getTypeLibelle') ? $av->getTypeLibelle() : 'N/A'; ?></span>
                            </span>
                        </td>
                        <td class="montant positive">+ <?php echo number_format($av->getMontant(), 2, ',', ' '); ?> $</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Total avantages</strong></td>
                        <td class="montant positive"><strong>+ <?php echo number_format($totalAvantages, 2, ',', ' '); ?> $</strong></td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>

        <!-- Retenues -->
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Retenues</span>
                <span class="count"><?php echo count($retenues); ?></span>
            </div>
            <?php if (empty($retenues)): ?>
                <div class="empty-data">Aucune retenue pour cette période</div>
            <?php else: ?>
                <table class="montant-table">
                    <?php foreach ($retenues as $rt): ?>
                    <tr>
                        <td class="libelle">
                            <?php echo htmlspecialchars($rt->getLibelle() ?? 'Retenue'); ?>
                            <span style="font-size:10px;color:#94a3b8;display:block;">
                                <span class="badge badge-red"><?php echo method_exists($rt, 'getTypeLibelle') ? $rt->getTypeLibelle() : 'N/A'; ?></span>
                            </span>
                        </td>
                        <td class="montant negative">- <?php echo number_format($rt->getMontant(), 2, ',', ' '); ?> $</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Total retenues</strong></td>
                        <td class="montant negative"><strong>- <?php echo number_format($totalRetenues, 2, ',', ' '); ?> $</strong></td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Avances détaillée -->
    <div class="section-avances">
        <div class="section-title">
            <span><i class="fas fa-hand-holding-usd" style="color:#f97316;"></i> Avances sur Salaire</span>
            <span class="count" style="background:#fef3c7;color:#92400e;"><?php echo $nbAvances; ?></span>
        </div>
        
        <?php if ($nbAvances == 0): ?>
            <div class="empty-data" style="color:#16a34a;">
                <i class="fas fa-check-circle"></i> Aucune avance en cours
            </div>
        <?php else: ?>
            <!-- Résumé des avances -->
            <div class="recap-avances">
                <div class="item">
                    <div class="montant text-orange"><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</div>
                    <div class="label">Total avances en cours</div>
                </div>
                <div class="item">
                    <div class="montant text-orange"><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</div>
                    <div class="label">Montant déduit</div>
                </div>
                <div class="item">
                    <div class="montant text-green"><?php echo number_format($totalAvancesRemboursees, 2, ',', ' '); ?> $</div>
                    <div class="label">Déjà remboursées</div>
                </div>
            </div>

            <!-- Liste des avances -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;margin-top:10px;">
                <?php foreach ($avances as $avance): ?>
                <div style="background:white;padding:10px 14px;border-radius:6px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;color:#0f172a;font-size:13px;"><?php echo htmlspecialchars($avance->getLibelle()); ?></div>
                        <div style="font-size:10px;color:#94a3b8;">
                            <span class="badge badge-yellow">En cours</span>
                            <span><?php echo $avance->getMois(); ?>/<?php echo $avance->getAnnee(); ?></span>
                        </div>
                    </div>
                    <div style="font-weight:700;color:#f97316;font-size:14px;">
                        <?php echo number_format($avance->getMontant(), 2, ',', ' '); ?> $
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Total à déduire -->
            <div style="border-top:2px solid #fcd34d;padding-top:10px;margin-top:10px;display:flex;justify-content:space-between;background:#fef3c7;padding:10px 16px;border-radius:6px;">
                <span style="font-weight:700;color:#92400e;">
                    <i class="fas fa-calculator"></i> Total des avances à déduire
                </span>
                <span style="font-weight:700;color:#f97316;font-size:18px;">
                    <?php echo number_format($totalAvances, 2, ',', ' '); ?> $
                </span>
            </div>

            <div style="background:#fef2f2;border-radius:6px;padding:8px 12px;margin-top:8px;color:#991b1b;font-weight:500;font-size:13px;border:1px solid #fca5a5;">
                <i class="fas fa-exclamation-circle"></i>
                Ces avances seront déduites du salaire net.
            </div>
        <?php endif; ?>
    </div>

    <!-- Récapitulatif global -->
    <div style="background:#f8fafc;border-radius:8px;padding:16px 20px;margin-top:16px;border:1px solid #e2e8f0;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
        <div>
            <span style="font-size:12px;color:#94a3b8;">Salaire de base</span>
            <div style="font-weight:700;color:#16a34a;"><?php echo number_format($salaireBase, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total avantages</span>
            <div style="font-weight:700;color:#16a34a;">+ <?php echo number_format($totalAvantages, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total retenues</span>
            <div style="font-weight:700;color:#dc2626;">- <?php echo number_format($totalRetenues, 2, ',', ' '); ?> $</div>
        </div>
        <div>
            <span style="font-size:12px;color:#94a3b8;">Total avances</span>
            <div style="font-weight:700;color:#f97316;">- <?php echo number_format($totalAvances, 2, ',', ' '); ?> $</div>
        </div>
        <div style="grid-column:span 2;background:linear-gradient(135deg, #2563eb, #1d4ed8);border-radius:6px;padding:8px 14px;">
            <span style="font-size:13px;color:rgba(255,255,255,0.8);">NET À PAYER</span>
            <div style="font-weight:700;color:white;font-size:20px;"><?php echo number_format($salaireNetApresAvances, 2, ',', ' '); ?> $</div>
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
            <div class="label">Agent bénéficiaire</div>
            <div class="line">Signature</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-recu">
        <p><i class="fas fa-check-circle" style="color:#16a34a;"></i> Ce document fait office de justificatif de paiement.</p>
        <p style="margin-top:4px;">Merci de votre confiance.</p>
        <p style="margin-top:8px;font-size:11px;color:#cbd5e1;">
            Reçu généré automatiquement le <?php echo date('d/m/Y à H:i:s'); ?>
        </p>
    </div>
    <?php else: ?>
        <div style="text-align:center;padding:40px 20px;">
            <i class="fas fa-exclamation-triangle" style="font-size:48px;color:#ef4444;"></i>
            <h2 style="margin-top:16px;color:#1e293b;">Rémunération non trouvée</h2>
            <p style="color:#64748b;">Aucune rémunération avec l'ID <?php echo htmlspecialchars($remuneration_id); ?> n'a été trouvée.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top:16px;">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
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

</div>

</body>
</html>