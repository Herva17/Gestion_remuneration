<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Affectation.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/Service.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification STRICTE : SEUL l'administrateur peut accéder
// Si le rôle n'est pas exactement "Administrateur", on bloque l'accès
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Administrateur') {
    // Message d'erreur
    $_SESSION['message'] = "Accès refusé. Cette page est réservée aux administrateurs.";
    $_SESSION['message_type'] = 'error';
    
    // Redirection vers le dashboard approprié
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Caissier') {
        header('Location: ../caissier/dashboard.php');
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Comptable') {
        header('Location: ../comptable/dashboard.php');
    } else {
        header('Location: ../../Dashboard.php');
    }
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['message'] = "ID d'affectation invalide";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$affectation = Affectation::getById($id);

if (!$affectation) {
    $_SESSION['message'] = "Affectation non trouvée";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Récupérer les informations liées
$agent = $affectation->getAgent();
$service = $affectation->getService();

// Récupérer les rémunérations liées à cette affectation
$remunerations = [];
$remuneration = Remuneration::getByAffectation($id);
if ($remuneration) {
    $remunerations[] = $remuneration;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Déterminer la page de retour
$retour = 'index.php';
if (isset($_SERVER['HTTP_REFERER'])) {
    $retour = $_SERVER['HTTP_REFERER'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'affectation #<?php echo $affectation->getId(); ?></title>
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

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }

        .btn { padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-warning { background: #f97316; color: white; }
        .btn-warning:hover { background: #ea580c; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-purple { background: #9333ea; color: white; }
        .btn-purple:hover { background: #7c3aed; }

        .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: #2563eb; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-purple { background: #f3e8ff; color: #6d28d9; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .info-item .label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .info-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }
        .info-item .value .sub {
            font-size: 13px;
            font-weight: 400;
            color: #6b7280;
        }
        .info-item .value i { margin-right: 6px; }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .gap-6 { gap: 24px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .text-center { text-align: center; }
        .text-sm { font-size: 13px; }
        .text-gray { color: #6b7280; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8fafc; }

        .empty { text-align: center; padding: 30px; color: #94a3b8; }
        .empty i { font-size: 32px; display: block; margin-bottom: 8px; }

        .footer { text-align: center; margin-top: 20px; color: #94a3b8; font-size: 12px; padding: 10px 0; }

        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }

        .montant { font-weight: 700; color: #16a34a; }
        .montant-non-defini { color: #94a3b8; font-style: italic; }

        .remuneration-linked {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        /* Page d'accès refusé */
        .access-denied-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f1f5f9;
            padding: 20px;
        }
        .access-denied-card {
            background: white;
            border-radius: 16px;
            padding: 50px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-top: 6px solid #dc2626;
        }
        .access-denied-card i {
            font-size: 64px;
            color: #dc2626;
            margin-bottom: 20px;
        }
        .access-denied-card h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .access-denied-card p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .access-denied-card .btn {
            padding: 10px 30px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .info-grid { grid-template-columns: 1fr; }
            .top { flex-direction: column; align-items: stretch; }
            .action-buttons { justify-content: center; }
            .access-denied-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- PAGE D'ACCÈS REFUSÉ (si l'utilisateur n'est pas admin) -->
<!-- ============================================ -->
<?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Administrateur'): ?>
<div class="access-denied-page">
    <div class="access-denied-card">
        <i class="fas fa-lock"></i>
        <h2>Accès Refusé</h2>
        <p>
            <strong>⛔ Vous n'êtes pas autorisé à accéder à cette page.</strong><br><br>
            Cette section est réservée exclusivement aux <strong>Administrateurs</strong>.
            <br><br>
            Veuillez contacter l'administrateur du système si vous pensez avoir besoin d'accéder à cette page.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;align-items:center;">
            <a href="../../Dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour au Dashboard
            </a>
            <a href="../../logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Se déconnecter
            </a>
        </div>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#94a3b8;">
            <i class="fas fa-user-shield"></i> Rôle actuel : <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Non défini'); ?></strong>
        </div>
    </div>
</div>
<?php else: ?>

<!-- ============================================ -->
<!-- PAGE PRINCIPALE (pour administrateur uniquement) -->
<!-- ============================================ -->

<!-- Header -->
<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-tasks" style="color:#2563eb;"></i> Gestion Affectations</h1>
        <div class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
            <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="index.php" class="active"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
        </div>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><i class="fas fa-user"></i></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="container">

    <!-- En-tête -->
    <div class="top">
        <div>
            <h2>Détails de l'affectation</h2>
            <p>Informations complètes sur l'affectation #<?php echo $affectation->getId(); ?></p>
        </div>
        <div class="action-buttons">
            <a href="<?php echo htmlspecialchars($retour); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <a href="edit.php?id=<?php echo $affectation->getId(); ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="delete.php?id=<?php echo $affectation->getId(); ?>" class="btn btn-danger" 
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette affectation ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
            <a href="../remunerations/add.php?affectation_id=<?php echo $affectation->getId(); ?>" class="btn btn-success">
                <i class="fas fa-money-bill-wave"></i> Créer rémunération
            </a>
        </div>
    </div>

    <!-- Carte d'identité de l'affectation -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-id-card"></i>
            Informations générales
            <span class="badge badge-blue" style="margin-left:auto;">
                #<?php echo $affectation->getId(); ?>
            </span>
        </div>

        <div class="info-grid">
            <!-- Agent -->
            <div class="info-item">
                <div class="label"><i class="fas fa-user"></i> Agent</div>
                <div class="value">
                    <div class="flex items-center gap-3" style="margin-top:4px;">
                        <div class="profile-avatar" style="width:40px;height:40px;font-size:18px;">
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
                        <div>
                            <div style="font-weight:600;font-size:16px;">
                                <?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : '<span class="text-gray">Non assigné</span>'; ?>
                            </div>
                            <?php if ($agent): ?>
                            <div class="text-sm text-gray">
                                <?php echo htmlspecialchars($agent->getFonction() ?: 'Fonction non spécifiée'); ?>
                                <?php if ($agent->getTelephone()): ?>
                                    <span style="margin-left:12px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($agent->getTelephone()); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service -->
            <div class="info-item">
                <div class="label"><i class="fas fa-cogs"></i> Service</div>
                <div class="value">
                    <?php if ($service): ?>
                        <div>
                            <span style="font-weight:600;font-size:16px;"><?php echo htmlspecialchars($service->getDesignation()); ?></span>
                            <div class="text-sm text-gray">
                                <?php echo htmlspecialchars($service->getDescription() ?: 'Aucune description'); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="text-gray">Service non défini</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lieu -->
            <div class="info-item">
                <div class="label"><i class="fas fa-map-marker-alt"></i> Lieu d'affectation</div>
                <div class="value">
                    <?php 
                    $lieu = $affectation->getLieuAffectation();
                    if (!empty($lieu)): 
                    ?>
                        <i class="fas fa-location-dot" style="color:#2563eb;"></i>
                        <?php echo htmlspecialchars($lieu); ?>
                    <?php else: ?>
                        <span class="text-gray">Non spécifié</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Montant -->
            <div class="info-item">
                <div class="label"><i class="fas fa-dollar-sign"></i> Montant rémunéré</div>
                <div class="value">
                    <?php 
                    $montant = $affectation->getMontantRemunerer();
                    if ($montant !== null && $montant > 0): 
                    ?>
                        <span class="montant">$ <?php echo number_format($montant, 2, ',', ' '); ?></span>
                    <?php elseif ($montant !== null && $montant == 0): ?>
                        <span class="text-gray">$ 0,00</span>
                    <?php else: ?>
                        <span class="montant-non-defini">Non défini</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Date -->
            <div class="info-item">
                <div class="label"><i class="fas fa-calendar-alt"></i> Date d'affectation</div>
                <div class="value">
                    <?php 
                    $date = $affectation->getDateAffectation();
                    if ($date): 
                    ?>
                        <i class="fas fa-calendar-day" style="color:#2563eb;"></i>
                        <?php echo date('d/m/Y', strtotime($date)); ?>
                    <?php else: ?>
                        <span class="text-gray">Non définie</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statut / Rémunération liée -->
            <div class="info-item">
                <div class="label"><i class="fas fa-link"></i> Rémunération liée</div>
                <div class="value">
                    <?php if (!empty($remunerations)): ?>
                        <?php foreach ($remunerations as $rem): ?>
                            <div class="remuneration-linked">
                                <i class="fas fa-money-bill-wave" style="color:#16a34a;"></i>
                                <a href="../remunerations/view.php?id=<?php echo $rem->getId(); ?>" style="color:#2563eb;text-decoration:none;font-weight:600;">
                                    Rémunération #<?php echo $rem->getId(); ?>
                                </a>
                                <span class="text-sm text-gray">
                                    (<?php echo $rem->getMois(); ?> <?php echo $rem->getAnnee(); ?>)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray">
                            <i class="fas fa-unlink"></i> Aucune rémunération liée
                        </span>
                        <div style="margin-top:8px;">
                            <a href="../remunerations/add.php?affectation_id=<?php echo $affectation->getId(); ?>" class="btn btn-success" style="font-size:12px;padding:4px 12px;">
                                <i class="fas fa-plus"></i> Créer
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails supplémentaires -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-info-circle"></i>
            Informations complémentaires
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <div>
                <div class="label" style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">ID Affectation</div>
                <div style="font-weight:600;font-size:16px;">#<?php echo $affectation->getId(); ?></div>
            </div>
            <div>
                <div class="label" style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">ID Agent</div>
                <div style="font-weight:600;font-size:16px;">
                    <?php echo $agent ? $agent->getIdAgent() : 'N/A'; ?>
                </div>
            </div>
            <div>
                <div class="label" style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">ID Service</div>
                <div style="font-weight:600;font-size:16px;">
                    <?php echo $service ? $service->getId() : 'N/A'; ?>
                </div>
            </div>
            <div>
                <div class="label" style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">Statut</div>
                <div style="margin-top:4px;">
                    <?php if (!empty($remunerations)): ?>
                        <span class="badge badge-green"><i class="fas fa-check-circle"></i> Rémunérée</span>
                    <?php else: ?>
                        <span class="badge badge-yellow"><i class="fas fa-clock"></i> En attente</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Rémunérations liées (détail) -->
    <?php if (!empty($remunerations)): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-money-bill-wave"></i>
            Rémunérations liées
            <span class="badge badge-blue" style="margin-left:auto;"><?php echo count($remunerations); ?></span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mois</th>
                        <th>Année</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($remunerations as $rem): ?>
                    <tr>
                        <td>#<?php echo $rem->getId(); ?></td>
                        <td><?php echo htmlspecialchars($rem->getMois()); ?></td>
                        <td><?php echo htmlspecialchars($rem->getAnnee()); ?></td>
                        <td class="montant">$ <?php echo number_format($rem->getMontant() ?? 0, 2, ',', ' '); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($rem->getDateRemun())); ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="../remunerations/view.php?id=<?php echo $rem->getId(); ?>" class="btn btn-primary" style="font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../remunerations/edit.php?id=<?php echo $rem->getId(); ?>" class="btn btn-warning" style="font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pied de page -->
    <div class="footer">
        <p>© <?php echo date('Y'); ?> - Gestion des Rémunérations | Détails de l'affectation #<?php echo $affectation->getId(); ?></p>
    </div>

</div>

<?php endif; ?>
<!-- ============================================ -->
<!-- FIN DE LA PAGE -->
<!-- ============================================ -->

</body>
</html>