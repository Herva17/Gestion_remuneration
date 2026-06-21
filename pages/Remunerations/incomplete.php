<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Prestation.php';
require_once __DIR__ . '/../../Classes/Paiement.php';
require_once __DIR__ . '/../../Classes/Affectation.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if (!in_array($_SESSION['user_role'] ?? '', ['administrateur', 'caissier'])) {
    $_SESSION['message'] = 'Accès réservé au service Paiements';
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$prestations = Prestation::getAll();
$incompletes = [];
foreach ($prestations as $pr) {
    $remaining = Paiement::getRemainingByPrestation($pr->getNumeroPrest());
    if ($remaining !== null && $remaining > 0) {
        $incompletes[] = ['prestation' => $pr, 'remaining' => $remaining];
    }
}

$title = 'Prestations avec paiements incomplets';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body{font-family: Arial, Helvetica, sans-serif;margin:20px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ddd;padding:8px;text-align:left}
        th{background:#f4f4f4}
        .right{text-align:right}
        .center{text-align:center}
        @media print{ .no-print{display:none} }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="window.print()">Imprimer</button>
        <a href="index.php" style="margin-left:8px;">Retour</a>
    </div>

    <h2><?php echo htmlspecialchars($title); ?></h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Prestation</th>
                <th>Montant</th>
                <th>Déjà payé</th>
                <th>Reste</th>
                <th>Agent</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($incompletes)): ?>
                <tr><td colspan="6" class="center">Aucune prestation incomplète</td></tr>
            <?php else: ?>
                <?php $i = 1;
                foreach ($incompletes as $row):
                    $pr = $row['prestation'];
                          $reste = max(0, floatval($row['remaining']));
        $deja = Paiement::getTotalPaidByPrestation($pr->getNumeroPrest());
        $montantTotal = $deja + $reste;
                    $agentName = 'N/A';

                    $aff = $pr->getIdAffectation() ? Affectation::getById($pr->getIdAffectation()) : null;
                    if ($aff) {
                        $ag = Agent::getById($aff->getIdAgent());
                        if ($ag) {
                            $agentName = $ag->getNomComplet();
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($pr->getLibelle()); ?></td>
                        <td><?php echo number_format($montantTotal, 2, ',', ' '); ?> $</td>
                        <td><?php echo number_format($deja, 2, ',', ' '); ?> $</td>
                        <td><?php echo number_format($reste, 2, ',', ' '); ?> $</td>
                        <td><?php echo htmlspecialchars($agentName); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
