<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable' && $_SESSION['user_role'] !== 'Secretaire') {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables et secrétaires";
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

// ========== RÉCUPÉRATION DE TOUTES LES DONNÉES ==========

// 1. Récupérer toutes les années scolaires
$anneesScolaires = AnneeScolaire::getAll();

// 2. Récupérer l'année scolaire en cours
$anneeCourante = AnneeScolaire::getCurrent();
$idAnnee = $anneeCourante ? $anneeCourante->getId() : 0;

// 3. Récupérer TOUS les avantages de l'agent (toutes années)
$tousAvantages = Avantage::getByAgent($agent_id);
$avantagesParAnnee = [];
$totalAvantagesGlobal = 0;

foreach ($tousAvantages as $av) {
    $anneeId = $av->getIdAnnee();
    if (!isset($avantagesParAnnee[$anneeId])) {
        $avantagesParAnnee[$anneeId] = [];
    }
    $avantagesParAnnee[$anneeId][] = $av;
    $totalAvantagesGlobal += $av->getMontant();
}

// 4. Récupérer les avantages de l'année en cours
$avantagesAnneeCourante = [];
$totalAvantagesAnneeCourante = 0;
if ($idAnnee > 0 && isset($avantagesParAnnee[$idAnnee])) {
    $avantagesAnneeCourante = $avantagesParAnnee[$idAnnee];
    foreach ($avantagesAnneeCourante as $av) {
        $totalAvantagesAnneeCourante += $av->getMontant();
    }
}

// 5. Récupérer TOUTES les retenues de l'agent
$toutesRetenues = Retenue::getByAgent($agent_id);
$retenuesParAnnee = [];
$totalRetenuesGlobal = 0;

// Vérifier si la méthode getIdAnnee() existe, sinon utiliser une autre approche
foreach ($toutesRetenues as $rt) {
    // Essayer d'obtenir l'ID de l'année si la méthode existe
    $anneeId = 0;
    if (method_exists($rt, 'getIdAnnee')) {
        $anneeId = $rt->getIdAnnee();
    } else {
        // Si la méthode n'existe pas, on utilise l'année et le mois pour regrouper
        $anneeId = $rt->getAnnee() ?? '0';
    }
    
    if (!isset($retenuesParAnnee[$anneeId])) {
        $retenuesParAnnee[$anneeId] = [];
    }
    $retenuesParAnnee[$anneeId][] = $rt;
    $totalRetenuesGlobal += $rt->getMontant();
}

// 6. Récupérer les retenues de l'année en cours
$retenuesAnneeCourante = [];
$totalRetenuesAnneeCourante = 0;
if ($idAnnee > 0 && isset($retenuesParAnnee[$idAnnee])) {
    $retenuesAnneeCourante = $retenuesParAnnee[$idAnnee];
    foreach ($retenuesAnneeCourante as $rt) {
        $totalRetenuesAnneeCourante += $rt->getMontant();
    }
}

// 7. Récupérer les retenues pour le mois de la rémunération
$moisRemuneration = $remuneration->getMois();
$anneeRemuneration = $remuneration->getAnnee();

$retenuesMois = [];
$totalRetenuesMois = 0;
foreach ($toutesRetenues as $rt) {
    if ($rt->getMois() === $moisRemuneration && $rt->getAnnee() === $anneeRemuneration) {
        $retenuesMois[] = $rt;
        $totalRetenuesMois += $rt->getMontant();
    }
}

// 8. Récupérer les avantages pour le mois de la rémunération
$avantagesMois = [];
$totalAvantagesMois = 0;
foreach ($tousAvantages as $av) {
    if ($av->getMois() === $moisRemuneration && $av->getAnnee() === $anneeRemuneration) {
        $avantagesMois[] = $av;
        $totalAvantagesMois += $av->getMontant();
    }
}

// 9. Récupérer les informations de l'agent
$nomAgent = $agent->getNomComplet();
$fonction = $agent->getFonction() ?? 'N/A';
$telephone = $agent->getTelephone() ?? 'N/A';
$adresse = $agent->getAdresse() ?? 'N/A';

// 10. Calculs du salaire
$salaireBase = $remuneration->getMontant();
$salaireBrut = $salaireBase + $totalAvantagesMois;
$salaireNet = $salaireBrut - $totalRetenuesMois;

// 11. Avances (si vous avez une table d'avances, à adapter)
$avanceTransport = isset($_GET['transport']) ? (float)$_GET['transport'] : 0;
$avanceCommunication = isset($_GET['communication']) ? (float)$_GET['communication'] : 0;
$avanceLogement = isset($_GET['logement']) ? (float)$_GET['logement'] : 0;
$avanceAutres = isset($_GET['autres']) ? (float)$_GET['autres'] : 0;
$totalAvances = $avanceTransport + $avanceCommunication + $avanceLogement + $avanceAutres;

$salaireNetApresAvances = $salaireNet - $totalAvances;

// 12. Statistiques globales
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

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .grid-2 { grid-template-columns: 1fr; gap: 16px; }
            .header-paie { flex-direction: column; align-items: flex-start; gap: 8px; }
            .total-general { flex-direction: column; text-align: center; }
            .total-general .amount { font-size: 22px; }
            .signature { grid-template-columns: 1fr; gap: 10px; }
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
            <div class="info-supp">
                <i class="fas fa-info-circle"></i>
                Total avantages : <?php echo $totalAvantages; ?> | Total retenues : <?php echo $totalRetenues; ?>
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
            <div class="info-row">
                <span class="label">Salaire brut</span>
                <span class="value" style="color:#2563eb;"><?php echo number_format($salaireBrut, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row">
                <span class="label">Total retenues (<?php echo count($retenuesMois); ?>)</span>
                <span class="value" style="color:#dc2626;">- <?php echo number_format($totalRetenuesMois, 2, ',', ' '); ?> $</span>
            </div>
            <div class="info-row">
                <span class="label">Salaire net</span>
                <span class="value" style="color:#16a34a;font-size:16px;"><?php echo number_format($salaireNet, 2, ',', ' '); ?> $</span>
            </div>
        </div>
    </div>

    <!-- Détails des avantages, retenues et avances -->
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

    <!-- Avances -->
    <div class="section" style="margin-top:16px;">
        <div class="section-title">
            <span><i class="fas fa-hand-holding-usd" style="color:#f97316;"></i> Avances</span>
            <span class="count"><?php echo ($avanceTransport > 0 ? 1 : 0) + ($avanceCommunication > 0 ? 1 : 0) + ($avanceLogement > 0 ? 1 : 0) + ($avanceAutres > 0 ? 1 : 0); ?></span>
        </div>
        <?php if ($totalAvances == 0): ?>
            <div class="empty-data">
                <i class="fas fa-info-circle"></i> Aucune avance enregistrée
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
                <?php if ($avanceTransport > 0): ?>
                <div class="info-row" style="border-bottom:none;flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="label">Transport</span>
                    <span class="value" style="color:#f97316;"><?php echo number_format($avanceTransport, 2, ',', ' '); ?> $</span>
                </div>
                <?php endif; ?>
                <?php if ($avanceCommunication > 0): ?>
                <div class="info-row" style="border-bottom:none;flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="label">Communication</span>
                    <span class="value" style="color:#f97316;"><?php echo number_format($avanceCommunication, 2, ',', ' '); ?> $</span>
                </div>
                <?php endif; ?>
                <?php if ($avanceLogement > 0): ?>
                <div class="info-row" style="border-bottom:none;flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="label">Logement</span>
                    <span class="value" style="color:#f97316;"><?php echo number_format($avanceLogement, 2, ',', ' '); ?> $</span>
                </div>
                <?php endif; ?>
                <?php if ($avanceAutres > 0): ?>
                <div class="info-row" style="border-bottom:none;flex-direction:column;align-items:flex-start;gap:4px;">
                    <span class="label">Autres</span>
                    <span class="value" style="color:#f97316;"><?php echo number_format($avanceAutres, 2, ',', ' '); ?> $</span>
                </div>
                <?php endif; ?>
            </div>
            <div style="border-top:2px solid #e2e8f0;padding-top:12px;margin-top:8px;display:flex;justify-content:space-between;">
                <span style="font-weight:700;color:#0f172a;">Total avances</span>
                <span style="font-weight:700;color:#f97316;"><?php echo number_format($totalAvances, 2, ',', ' '); ?> $</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Total général -->
    <div class="total-general">
        <div>
            <div class="label"><i class="fas fa-check-circle"></i> Salaire net à payer</div>
            <div style="font-size:13px;opacity:0.8;">Après déduction des retenues et avances</div>
            <div style="font-size:12px;opacity:0.7;margin-top:4px;">
                Période : <?php echo $moisRemuneration . ' ' . $anneeRemuneration; ?>
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
            <span style="font-size:12px;color:#94a3b8;">Année scolaire en cours</span>
            <div style="font-weight:700;color:#2563eb;"><?php echo $anneeCourante ? htmlspecialchars($anneeCourante->getDesignationAnn()) : 'N/A'; ?></div>
            <span style="font-size:11px;color:#94a3b8;">
                Avantages: <?php echo number_format($totalAvantagesAnneeCourante, 2, ',', ' '); ?> $ | 
                Retenues: <?php echo number_format($totalRetenuesAnneeCourante, 2, ',', ' '); ?> $
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