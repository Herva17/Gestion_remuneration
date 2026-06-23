<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Utilisateur.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur') {
    $_SESSION['message'] = "Accès réservé aux administrateurs";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$search = $_GET['search'] ?? '';
$users = Utilisateur::getAll($search);

$totalUsers = count($users);
$admins = count(Utilisateur::getByRole('Administrateur'));
$comptables = count(Utilisateur::getByRole('Comptable'));
$secretaires = count(Utilisateur::getByRole('Secretaire'));

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
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

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }

        .card { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card .number { font-size: 24px; font-weight: bold; }
        .card .label { font-size: 13px; color: #666; }

        .border-red { border-left: 4px solid #dc2626; }
        .border-blue { border-left: 4px solid #2563eb; }
        .border-green { border-left: 4px solid #16a34a; }
        .border-yellow { border-left: 4px solid #ca8a04; }
        .border-purple { border-left: 4px solid #9333ea; }

        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .text-green { color: #16a34a; }
        .text-yellow { color: #ca8a04; }
        .text-purple { color: #9333ea; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; background: #f8f9fa; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8f9fa; }

        .empty { text-align: center; padding: 24px; color: #999; }
        .empty i { font-size: 24px; display: block; margin-bottom: 8px; }

        .btn { background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }
        .btn-gray { background: #6b7280; }
        .btn-gray:hover { background: #4b5563; }

        .tag { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .tag-green { background: #d1fae5; color: #065f46; }
        .tag-yellow { background: #fef3c7; color: #92400e; }
        .tag-gray { background: #f3f4f6; color: #4b5563; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-3 { gap: 12px; }
        .gap-2 { gap: 8px; }
        .gap-4 { gap: 16px; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .mt-4 { margin-top: 16px; }
        .mb-4 { margin-bottom: 16px; }
        .w-full { width: 100%; }

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

<div class="header">
    <div class="header-left">
        <h1>💰 Gestion Rémunération</h1>
        <div class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="index.php" class="active"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
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

    <div class="top">
        <div>
            <h2>Gestion des Utilisateurs</h2>
            <p>Gérez les comptes utilisateurs du système</p>
        </div>
        <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Ajouter un utilisateur</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="grid-4">
        <div class="card border-purple">
            <div class="number text-purple"><?php echo $totalUsers; ?></div>
            <div class="label"><i class="fas fa-users"></i> Total Utilisateurs</div>
        </div>
        <div class="card border-red">
            <div class="number text-red"><?php echo $admins; ?></div>
            <div class="label"><i class="fas fa-user-shield"></i> Administrateurs</div>
        </div>
        <div class="card border-green">
            <div class="number text-green"><?php echo $comptables; ?></div>
            <div class="label"><i class="fas fa-user-tie"></i> Comptables</div>
        </div>
        <div class="card border-yellow">
            <div class="number text-yellow"><?php echo $secretaires; ?></div>
            <div class="label"><i class="fas fa-user-clock"></i> Secrétaires</div>
        </div>
    </div>

    <!-- Recherche -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Rechercher un utilisateur..." 
                   class="px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:border-blue-500 w-64">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-lg transition">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <?php if ($search): ?>
        <a href="index.php" class="btn btn-gray"><i class="fas fa-times"></i> Effacer</a>
        <?php endif; ?>
        <span class="text-sm text-gray-500"><?php echo $totalUsers; ?> utilisateur<?php echo $totalUsers > 1 ? 's' : ''; ?></span>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            <i class="fas fa-inbox"></i>
                            Aucun utilisateur trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user->getId(); ?></td>
                            <td><strong><?php echo htmlspecialchars($user->getNom()); ?></strong></td>
                            <td><?php echo htmlspecialchars($user->getEmail()); ?></td>
                            <td>
                                <?php 
                                $roleUser = $user->getRole();
                                $color = 'gray';
                                if ($roleUser === 'Administrateur') $color = 'red';
                                elseif ($roleUser === 'Caissier') $color = 'green';
                                ?>
                                <span class="tag tag-<?php echo $color; ?>"><?php echo htmlspecialchars($roleUser); ?></span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user->getDateCreation())); ?></td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="edit.php?id=<?php echo $user->getId(); ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user->getId() !== $_SESSION['user_id']): ?>
                                    <a href="delete.php?id=<?php echo $user->getId(); ?>" 
                                       class="text-red-600 hover:text-red-800" title="Supprimer"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400" title="Vous ne pouvez pas vous supprimer vous-même">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
            <span>Total : <strong><?php echo $totalUsers; ?></strong> utilisateur<?php echo $totalUsers > 1 ? 's' : ''; ?></span>
            <div class="flex gap-2">
                <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 transition" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="px-3 py-1 bg-blue-600 text-white rounded text-sm">1</span>
                <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 transition" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center text-xs text-gray-400">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>