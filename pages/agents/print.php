<?php
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Agent.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$agents = Agent::getAll();
$title = 'Liste des agents enregistrés';

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
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

        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

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
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .actions a { color: #2563eb; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
        .actions .delete { color: #dc2626; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        @media print {
            .no-print { display: none !important; }
            .header { display: none !important; }
            .container { padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="header no-print">
    <div class="header-left">
        <h1>💰 Gestion Rémunération</h1>
        <div class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
            <a href="../../pages/utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="../index.php" class="active"><i class="fas fa-users"></i> Agents</a>
            <a href="../../pages/services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../../pages/affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="../../pages/remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="../../pages/retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="../../pages/avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
            <a href="../../pages/annees/index.php"><i class="fas fa-calendar-alt"></i> Années</a>
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
    <div class="top no-print">
        <div>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo count($agents); ?> agent<?php echo count($agents) > 1 ? 's' : ''; ?> enregistré<?php echo count($agents) > 1 ? 's' : ''; ?></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom complet</th>
                        <th>Téléphone</th>
                        <th>Fonction</th>
                        <th>Profil</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                        <tr>
                            <td colspan="6" class="empty">
                                <i class="fas fa-inbox"></i>
                                Aucun agent trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($agents as $agent): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:28px;height:28px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:bold;">
                                            <?php 
                                            $nom = $agent->getNomComplet();
                                            $initials = '';
                                            $parts = explode(' ', $nom);
                                            foreach ($parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            echo substr($initials, 0, 2);
                                            ?>
                                        </div>
                                        <?php echo htmlspecialchars($agent->getNomComplet()); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($agent->getTelephone() ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($agent->getFonction() ?: '-'); ?></td>
                                <td>
                                    <?php 
                                    $profil = $agent->getProfil();
                                    $color = 'gray';
                                    if ($profil === 'Enseignant') $color = 'blue';
                                    elseif ($profil === 'Administratif') $color = 'green';
                                    elseif ($profil === 'Direction') $color = 'purple';
                                    ?>
                                    <span class="badge badge-<?php echo $color; ?>">
                                        <?php echo htmlspecialchars($profil ?: '-'); ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <div class="actions">
                                        <a href="view.php?id=<?php echo $agent->getIdAgent(); ?>" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $agent->getIdAgent(); ?>" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $agent->getIdAgent(); ?>" class="delete" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet agent ?')" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;font-size:14px;color:#6b7280;">
            <span>Total : <strong><?php echo count($agents); ?></strong> agent<?php echo count($agents) > 1 ? 's' : ''; ?></span>
            <div style="display:flex;gap:4px;">
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:12px;" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span style="padding:4px 12px;background:#2563eb;color:white;border-radius:4px;font-size:12px;">1</span>
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:12px;" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>