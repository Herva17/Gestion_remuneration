<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Affectation.php';
require_once __DIR__ . '/../../Classes/Agent.php';
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

$affectation = null;
$is_edit = false;
$title = "Ajouter une Affectation";
$button_text = "Ajouter";
$error = null;

$agents = Agent::getAll();
$services = Service::getAll();

if (isset($_GET['id'])) {
    $is_edit = true;
    $affectation = Affectation::getById($_GET['id']);
    $title = "Éditer l'Affectation";
    $button_text = "Mettre à jour";
    
    if (!$affectation) {
        $_SESSION['message'] = "Affectation non trouvée";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lieu_affectation = trim($_POST['lieu_affectation'] ?? '');
    $date_affectation = $_POST['date_affectation'] ?? '';
    $id_agent = $_POST['id_agent'] ?? '';
    $id_service = $_POST['id_service'] ?? '';

    $errors = [];
    if (empty($lieu_affectation)) {
        $errors[] = "Le lieu d'affectation est requis";
    }
    if (empty($id_agent)) {
        $errors[] = "L'agent est requis";
    }
    if (empty($id_service)) {
        $errors[] = "Le service est requis";
    }

    if (empty($errors)) {
        if ($is_edit) {
            $affectation->setLieuAffectation($lieu_affectation);
            $affectation->setDateAffectation($date_affectation);
            $affectation->setIdAgent($id_agent);
            $affectation->setIdService($id_service);
            
            if ($affectation->update()) {
                $_SESSION['message'] = "Affectation mise à jour avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de la mise à jour de l'affectation";
            }
        } else {
            $new_affectation = new Affectation(
                $id_agent,
                $id_service,
                $lieu_affectation,
                $date_affectation
            );
            
            if ($new_affectation->insert()) {
                $_SESSION['message'] = "Affectation ajoutée avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de l'ajout de l'affectation";
            }
        }
    } else {
        $error = implode("<br>", $errors);
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
        select.form-control { appearance: auto; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-purple { background: #9333ea; color: white; }
        .btn-purple:hover { background: #7c3aed; }
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
        .info-box .label { font-weight: 600; }

        .input-icon { position: relative; }
        .input-icon .icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .input-icon input, .input-icon select { padding-left: 36px; }

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
            <a href="../../pages/services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="index.php" class="active"><i class="fas fa-tasks"></i> Affectations</a>
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
            <p><?php echo $is_edit ? 'Modifiez les informations de l\'affectation' : 'Ajoutez une nouvelle affectation'; ?></p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if ($is_edit): ?>
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <span class="label">Affectation #<?php echo $affectation->getId(); ?></span>
            - Modifiez les informations ci-dessous
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Agent -->
            <div class="form-group">
                <label>Agent <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <select name="id_agent" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner un agent --</option>
                        <?php if (!empty($agents)): ?>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent->getIdAgent(); ?>" <?php echo ($affectation && $affectation->getIdAgent() == $agent->getIdAgent()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent->getNomComplet() . ' - ' . $agent->getFonction()); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucun agent disponible</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Service -->
            <div class="form-group">
                <label>Service <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-cogs"></i></span>
                    <select name="id_service" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner un service --</option>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service->getId(); ?>" <?php echo ($affectation && $affectation->getIdService() == $service->getId()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service->getDesignation()); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucun service disponible</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Lieu -->
            <div class="form-group">
                <label>Lieu d'affectation <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-map-marker-alt"></i></span>
                    <input type="text" name="lieu_affectation" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                           value="<?php echo $affectation ? htmlspecialchars($affectation->getLieuAffectation()) : ''; ?>" 
                           placeholder="Ex: Service Technique, Direction, Bureau Principal..." required>
                </div>
            </div>

            <!-- Date -->
            <div class="form-group">
                <label>Date d'affectation</label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-calendar-alt"></i></span>
                    <input type="date" name="date_affectation" class="form-control"
                           value="<?php echo $affectation ? $affectation->getDateAffectation() : date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-purple">
                    <i class="fas fa-save"></i> <?php echo $button_text; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($is_edit): ?>
                <a href="delete.php?id=<?php echo $affectation->getId(); ?>" class="btn btn-danger" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette affectation ?')">
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