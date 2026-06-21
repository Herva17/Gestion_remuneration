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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validation
    if (empty($nom)) {
        $error = "Le nom est requis";
    } elseif (empty($email)) {
        $error = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide";
    } elseif (empty($mot_de_passe)) {
        $error = "Le mot de passe est requis";
    } elseif (strlen($mot_de_passe) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (empty($role)) {
        $error = "Le rôle est requis";
    } else {
        // Vérifier si l'email existe déjà
        if (Utilisateur::emailExiste($email)) {
            $error = "Cet email est déjà utilisé";
        } else {
            $utilisateur = new Utilisateur(
                $nom,
                $email,
                $mot_de_passe,
                $role
            );

            if ($utilisateur->insert()) {
                $_SESSION['message'] = "Utilisateur ajouté avec succès !";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de l'ajout de l'utilisateur";
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
    <title>Ajouter un Utilisateur</title>
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

        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .text-center { text-align: center; }

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
            <a href="index.php" class="active"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="../remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
            <a href="../annees/index.php"><i class="fas fa-calendar-alt"></i> Années</a>
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
            <h2>Ajouter un Utilisateur</h2>
            <p>Créez un nouveau compte utilisateur</p>
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
        <form method="POST">
            <div class="form-group">
                <label>Nom complet <span class="required">*</span></label>
                <input type="text" name="nom" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Mot de passe <span class="required">*</span></label>
                <input type="password" name="mot_de_passe" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                       placeholder="Minimum 6 caractères" required>
                <small style="color:#6b7280;font-size:12px;">Minimum 6 caractères</small>
            </div>

            <div class="form-group">
                <label>Rôle <span class="required">*</span></label>
                <select name="role" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="Administrateur" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Administrateur') ? 'selected' : ''; ?>>Administrateur</option>
                    <option value="Comptable" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Comptable') ? 'selected' : ''; ?>>Comptable</option>
                    <option value="Secretaire" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Secretaire') ? 'selected' : ''; ?>>Secrétaire</option>
                </select>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Ajouter
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <div class="mt-4 text-center text-xs text-gray-400">
        © <?php echo date('Y'); ?> - Gestion des Rémunérations
    </div>
</div>

</body>
</html>