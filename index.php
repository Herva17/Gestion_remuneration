<?php
session_start();
require_once __DIR__ . '/Config/Database.php';
require_once __DIR__ . '/Classes/Utilisateur.php';

// Détruire toute session existante pour éviter les conflits
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Vérifier si déjà connecté
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    
    // Redirection selon le rôle (insensible à la casse)
    if (strtolower($role) === 'caissier') {
        header('Location: pages/caissier/dashboard.php');
        exit;
    } elseif (strtolower($role) === 'administrateur') {
        header('Location: Dashboard.php');
        exit;
    } else {
        // Si le rôle n'est pas reconnu, déconnecter
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $mot_de_passe === '') {
        $error = 'Veuillez renseigner l\'email et le mot de passe.';
    } else {
        $user = Utilisateur::authenticate($email, $mot_de_passe);

        if ($user) {
            // Nettoyer la session avant de la remplir
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['nom'] = $user->getNom();
            $_SESSION['email'] = $user->getEmail();
            $_SESSION['user_role'] = $user->getRole();
            $_SESSION['login_time'] = time();
            
            $role = $user->getRole();
            
            // Redirection selon le rôle (insensible à la casse)
            if (strtolower($role) === 'caissier') {
                header('Location: pages/caissier/dashboard.php');
                exit;
            } elseif (strtolower($role) === 'administrateur') {
                header('Location: Dashboard.php');
                exit;
            } else {
                // Par défaut, rediriger vers Dashboard
                header('Location: Dashboard.php');
                exit;
            }
        }
        $error = 'Email ou mot de passe invalide.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion des Rémunérations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles identiques à ceux précédemment... */
        body {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .login-card .logo {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            color: white;
        }
        .login-card h1 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
        }
        .login-card .subtitle {
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            font-size: 14px;
            margin-top: 4px;
        }
        .login-card .welcome {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            font-size: 13px;
            margin-top: 8px;
        }
        .form-group label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }
        .form-group .input-wrap {
            position: relative;
        }
        .form-group .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
        }
        .form-group .input-wrap input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .form-group .input-wrap input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-group .input-wrap input:focus {
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            font-size: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #1d4ed8;
            transform: scale(1.02);
        }
        .btn-login i {
            margin-right: 8px;
        }
        .alert-error {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .roles-info {
            margin-top: 16px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }
        .roles-info .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin: 0 4px;
        }
        .badge-admin { background: rgba(220, 38, 38, 0.3); color: #fca5a5; }
        .badge-caissier { background: rgba(34, 197, 94, 0.3); color: #86efac; }
        .footer-link {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
        }
        .footer-link a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
        }
        .footer-link a:hover {
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">
        <i class="fas fa-coins"></i>
    </div>
    <h1>CS MAMAN MAPENDO</h1>
    <p class="subtitle">Gestion des Rémunérations</p>
    <p class="welcome">Connectez-vous à votre espace de travail</p>

    <?php if ($error): ?>
    <div class="alert-error" style="margin-top:16px;">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" style="margin-top:24px;">
        <div class="form-group" style="margin-bottom:16px;">
            <label>Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="exemple@email.com" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
            <label>Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
    </form>

    <div class="roles-info">
        <p style="margin-bottom:6px;">Rôles disponibles :</p>
        <span class="badge badge-admin">Administrateur</span>
        <span class="badge badge-caissier">Caissier</span>
    </div>

    <div class="footer-link">
        <a href="index.php?logout=1">Déconnexion forcée</a>
    </div>
</div>

</body>
</html>