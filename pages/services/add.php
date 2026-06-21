<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Service.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et comptables";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$service = null;
$is_edit = false;
$title = "Ajouter un Service";
$button_text = "Ajouter";

if (isset($_GET['id'])) {
    $is_edit = true;
    $service = Service::getById($_GET['id']);
    $title = "Éditer le Service";
    $button_text = "Mettre à jour";
    
    if (!$service) {
        $_SESSION['message'] = "Service non trouvé";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = trim($_POST['designation'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($designation)) {
        $error = "La désignation est requise";
    } else {
        if ($is_edit) {
            $service->setDesignation($designation);
            $service->setDescription($description);
            
            if ($service->update()) {
                $_SESSION['message'] = "Service mis à jour avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de la mise à jour";
            }
        } else {
            $new_service = new Service($designation, $description);
            
            if ($new_service->insert()) {
                $_SESSION['message'] = "Service ajouté avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de l'ajout";
            }
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
    <title><?php echo $title; ?></title>
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

        .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-group label .required { color: #dc2626; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-control.error { border-color: #dc2626; }
        textarea.form-control { resize: vertical; min-height: 100px; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .text-center { text-align: center; }

        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #1e40af; font-size: 14px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1>💰 Gestion Rémunération</h1>
        <div class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
            <a href="../../pages/utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="../../pages/agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="index.php" class="active"><i class="fas fa-cogs"></i> Services</a>
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

    <div class="top">
        <div>
            <h2><?php echo $title; ?></h2>
            <p><?php echo $is_edit ? 'Modifiez les informations du service' : 'Ajoutez un nouveau service'; ?></p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if ($is_edit): ?>
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Service #<?php echo $service->getId(); ?> - Modifiez les informations ci-dessous
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Désignation <span class="required">*</span></label>
                <input type="text" name="designation" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                       value="<?php echo $service ? htmlspecialchars($service->getDesignation()) : ''; ?>" 
                       placeholder="Ex: Direction, Enseignement, Administration..." required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" 
                          placeholder="Décrivez brièvement ce service..."><?php echo $service ? htmlspecialchars($service->getDescription()) : ''; ?></textarea>
            </div>

            <?php if ($is_edit): ?>
            <div style="padding:12px;background:#f8f9fa;border-radius:6px;margin-bottom:16px;font-size:13px;color:#6b7280;">
                <i class="fas fa-info-circle"></i> 
                Créé le : <?php echo date('d/m/Y H:i', strtotime($service->getDateCreation())); ?>
                <?php if ($service->getDateCreation() !== $service->getDateModification()): ?>
                - Modifié le : <?php echo date('d/m/Y H:i', strtotime($service->getDateModification())); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $button_text; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($is_edit): ?>
                <a href="delete.php?id=<?php echo $service->getId(); ?>" class="btn btn-danger" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="mt-4 text-center text-xs text-gray-400">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>