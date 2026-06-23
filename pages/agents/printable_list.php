<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Agent.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et comptables";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$agents = Agent::getAll();
$totalAgents = count($agents);

// Compter les statistiques
$totalFonctions = 0;
$fonctions = [];
foreach ($agents as $agent) {
    $fonction = $agent->getFonction();
    if ($fonction && !in_array($fonction, $fonctions)) {
        $fonctions[] = $fonction;
        $totalFonctions++;
    }
}

$totalProfils = 0;
$profils = [];
foreach ($agents as $agent) {
    $profil = $agent->getProfil();
    if ($profil && !in_array($profil, $profils)) {
        $profils[] = $profil;
        $totalProfils++;
    }
}

$aAffectation = 0;
foreach ($agents as $agent) {
    $affectations = $agent->getAffectations();
    if (!empty($affectations)) {
        $aAffectation++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Agents - Imprimable</title>
    <style>
        /* Styles pour l'impression */
        @media print {
            body {
                font-family: 'Times New Roman', serif;
                font-size: 12pt;
                margin: 20mm;
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                font-size: 11pt;
            }
            th, td {
                border: 1px solid #333;
                padding: 6px 8px;
                text-align: left;
            }
            th {
                background-color: #f0f0f0 !important;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header h1 {
                font-size: 18pt;
                margin: 0;
                color: #1a56db;
            }
            .header p {
                font-size: 11pt;
                color: #666;
                margin: 5px 0;
            }
            .stats {
                display: flex;
                justify-content: space-around;
                margin: 15px 0;
                padding: 10px;
                background: #f5f5f5;
                border-radius: 5px;
            }
            .stats-item {
                text-align: center;
            }
            .stats-item .label {
                font-size: 10pt;
                color: #666;
            }
            .stats-item .value {
                font-size: 16pt;
                font-weight: bold;
                color: #1a56db;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #ddd;
                font-size: 10pt;
                color: #666;
            }
            .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 9pt;
                font-weight: bold;
            }
            .badge-green {
                background-color: #d1fae5;
                color: #065f46;
            }
            .badge-yellow {
                background-color: #fef3c7;
                color: #92400e;
            }
            .badge-blue {
                background-color: #dbeafe;
                color: #1e40af;
            }
            .badge-purple {
                background-color: #ede9fe;
                color: #5b21b6;
            }
            .badge-orange {
                background-color: #fed7aa;
                color: #9a3412;
            }
            .badge-gray {
                background-color: #f3f4f6;
                color: #4b5563;
            }
        }
        
        /* Styles pour l'écran */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
        }
        .print-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24pt;
            margin: 0;
            color: #1a56db;
        }
        .header p {
            font-size: 12pt;
            color: #6b7280;
            margin: 5px 0;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .stats-item {
            text-align: center;
        }
        .stats-item .label {
            font-size: 11pt;
            color: #6b7280;
        }
        .stats-item .value {
            font-size: 20pt;
            font-weight: bold;
            color: #1a56db;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11pt;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 10pt;
            color: #6b7280;
        }
        .no-print {
            margin-top: 20px;
            text-align: center;
        }
        .btn-print {
            background: #1a56db;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-print:hover {
            background: #1e40af;
        }
        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back:hover {
            background: #4b5563;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-orange { background: #fed7aa; color: #9a3412; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }
        
        .badge-status-green { background: #d1fae5; color: #065f46; }
        .badge-status-yellow { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

<div class="print-container">
    <!-- En-tête -->
    <div class="header">
        <h1>📋 Liste des Agents</h1>
        <p>Complexe Scolaire maman mapendo</p>
        <p style="font-size: 10pt; color: #9ca3af;">
            Généré le <?php echo date('d/m/Y à H:i'); ?>
        </p>
    </div>

    <!-- Statistiques -->
    <div class="stats">
        <div class="stats-item">
            <div class="value"><?php echo $totalAgents; ?></div>
            <div class="label">Total Agents</div>
        </div>
        <div class="stats-item">
            <div class="value"><?php echo $totalFonctions; ?></div>
            <div class="label">Fonctions</div>
        </div>
        <div class="stats-item">
            <div class="value"><?php echo $totalProfils; ?></div>
            <div class="label">Profils</div>
        </div>
        <div class="stats-item">
            <div class="value"><?php echo $aAffectation; ?></div>
            <div class="label">Agents affectés</div>
        </div>
    </div>

    <!-- Tableau des agents -->
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Nom Complet</th>
                <th>Téléphone</th>
                <th>Fonction</th>
                <th>Profil</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($agents)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 30px; color: #9ca3af;">
                    Aucun agent trouvé
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($agents as $agent): 
                    $affectations = $agent->getAffectations();
                    $aAffectation = !empty($affectations);
                    $profil = $agent->getProfil();
                    
                    // Couleur du profil
                    $profilColor = 'gray';
                    if ($profil === 'Enseignant') $profilColor = 'blue';
                    elseif ($profil === 'Administratif') $profilColor = 'green';
                    elseif ($profil === 'Direction') $profilColor = 'purple';
                    elseif ($profil === 'Technique') $profilColor = 'orange';
                ?>
                <tr>
                    <td><?php echo $agent->getIdAgent(); ?></td>
                    <td><strong><?php echo htmlspecialchars($agent->getNomComplet()); ?></strong></td>
                    <td><?php echo $agent->getTelephone() ? htmlspecialchars($agent->getTelephone()) : '-'; ?></td>
                    <td><?php echo $agent->getFonction() ? htmlspecialchars($agent->getFonction()) : '-'; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $profilColor; ?>">
                            <?php echo htmlspecialchars($profil ?: '-'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($aAffectation): ?>
                        <span class="badge badge-status-green">✓ Affecté</span>
                        <?php else: ?>
                        <span class="badge badge-status-yellow">⏳ En attente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pied de page -->
    <div class="footer">
        <p>
            Total : <strong><?php echo $totalAgents; ?></strong> agent<?php echo $totalAgents > 1 ? 's' : ''; ?>
            &nbsp;|&nbsp; © <?php echo date('Y'); ?> - Gestion des Rémunérations
        </p>
    </div>

    <!-- Boutons d'action (non imprimables) -->
    <div class="no-print" style="margin-top: 20px; text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Imprimer
        </button>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

<!-- Font Awesome pour les icônes (uniquement pour l'écran) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    // Fonction pour imprimer automatiquement si le paramètre print=1 est présent
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.print();
        }
    };
</script>

</body>
</html>