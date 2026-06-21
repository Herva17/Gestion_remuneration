<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Agent.php';

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

$agent = null;
$is_edit = false;
$title = "Ajouter un Agent";
$button_text = "Ajouter";

if (isset($_GET['id'])) {
    $is_edit = true;
    $agent = Agent::getById($_GET['id']);
    $title = "Éditer l'Agent";
    $button_text = "Mettre à jour";
    
    if (!$agent) {
        $_SESSION['message'] = "Agent non trouvé";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $fonction = trim($_POST['fonction'] ?? '');

    if (empty($nom_complet)) {
        $error = "Le nom complet est requis";
    } else {
        if ($is_edit) {
            $agent->setNomComplet($nom_complet);
            $agent->setAdresse($adresse);
            $agent->setDateNaissance($date_naissance);
            $agent->setTelephone($telephone);
            $agent->setProfil($profil);
            $agent->setLieuNaissance($lieu_naissance);
            $agent->setFonction($fonction);
            
            if ($agent->update()) {
                $_SESSION['message'] = "Agent mis à jour avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de la mise à jour";
            }
        } else {
            $new_agent = new Agent(
                $nom_complet,
                $adresse ?: null,
                $date_naissance ?: null,
                $telephone ?: null,
                $profil ?: null,
                $lieu_naissance ?: null,
                $fonction ?: null
            );
            
            if ($new_agent->insert()) {
                $_SESSION['message'] = "Agent ajouté avec succès";
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
        
        .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-title { font-size: 20px; font-weight: bold; color: #1f2937; margin-bottom: 6px; }
        .card-sub { font-size: 14px; color: #6b7280; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-group label .required { color: #dc2626; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-control.error { border-color: #dc2626; }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .text-center { text-align: center; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-2 { grid-template-columns: 1fr; }
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
            <a href="index.php" class="active"><i class="fas fa-users"></i> Agents</a>
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
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800"><?php echo $title; ?></h2>
            <p class="text-sm text-gray-500"><?php echo $is_edit ? 'Modifiez les informations de l\'agent' : 'Ajoutez un nouvel agent'; ?></p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label>Nom Complet <span class="required">*</span></label>
                    <input type="text" name="nom_complet" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                           value="<?php echo $agent ? htmlspecialchars($agent->getNomComplet()) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" 
                           value="<?php echo $agent ? htmlspecialchars($agent->getTelephone()) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Date de Naissance</label>
                    <input type="date" name="date_naissance" class="form-control" 
                           value="<?php echo $agent ? htmlspecialchars($agent->getDateNaissance()) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Lieu de Naissance</label>
                    <input type="text" name="lieu_naissance" class="form-control" 
                           value="<?php echo $agent ? htmlspecialchars($agent->getLieuNaissance()) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Fonction</label>
                    <input type="text" name="fonction" class="form-control" 
                           value="<?php echo $agent ? htmlspecialchars($agent->getFonction()) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Profil</label>
                    <select name="profil" class="form-control">
                        <option value="">Sélectionner un profil</option>
                        <option value="Enseignant" <?php echo $agent && $agent->getProfil() === 'Enseignant' ? 'selected' : ''; ?>>Enseignant</option>
                        <option value="Administratif" <?php echo $agent && $agent->getProfil() === 'Administratif' ? 'selected' : ''; ?>>Administratif</option>
                        <option value="Direction" <?php echo $agent && $agent->getProfil() === 'Direction' ? 'selected' : ''; ?>>Direction</option>
                        <option value="Technique" <?php echo $agent && $agent->getProfil() === 'Technique' ? 'selected' : ''; ?>>Technique</option>
                        <option value="Autre" <?php echo $agent && $agent->getProfil() === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Adresse</label>
                <textarea name="adresse" class="form-control" rows="3"><?php echo $agent ? htmlspecialchars($agent->getAdresse()) : ''; ?></textarea>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $button_text; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($is_edit): ?>
                <a href="delete.php?id=<?php echo $agent->getIdAgent(); ?>" class="btn btn-danger" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet agent ?')">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

</body>
</html>