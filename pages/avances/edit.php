<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Avance.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID d'avance manquant.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);
$avance = Avance::getById($id);

if (!$avance) {
    $_SESSION['message'] = "Avance introuvable.";
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$agents = Agent::getAll();

$agent_id = $avance->getAgentId();
$mois = $avance->getMois();
$annee = $avance->getAnnee();
$libelle = $avance->getLibelle();
$montant = $avance->getMontant();
$statut = $avance->getStatut();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
    $mois = isset($_POST['mois']) ? $_POST['mois'] : date('m');
    $annee = isset($_POST['annee']) ? intval($_POST['annee']) : date('Y');
    $libelle = isset($_POST['libelle']) ? trim($_POST['libelle']) : '';
    $montant = isset($_POST['montant']) ? floatval(str_replace(',', '.', $_POST['montant'])) : 0;
    $statut = isset($_POST['statut']) ? $_POST['statut'] : Avance::STATUT_EN_COURS;

    if ($agent_id <= 0) {
        $erreurs[] = "Veuillez sélectionner un agent.";
    }
    if (empty($mois) || !in_array($mois, ['01','02','03','04','05','06','07','08','09','10','11','12'])) {
        $erreurs[] = "Veuillez sélectionner un mois valide.";
    }
    if (empty($annee) || $annee < 2000 || $annee > 2100) {
        $erreurs[] = "Veuillez entrer une année valide.";
    }
    if (empty($libelle)) {
        $erreurs[] = "Veuillez saisir un libellé.";
    }
    if ($montant <= 0) {
        $erreurs[] = "Le montant doit être supérieur à 0.";
    }

    if (empty($erreurs)) {
        $avance->setAgentId($agent_id)
               ->setMois($mois)
               ->setAnnee($annee)
               ->setLibelle($libelle)
               ->setMontant($montant)
               ->setStatut($statut);

        if ($avance->update()) {
            $_SESSION['message'] = "Avance sur salaire modifiée avec succès !";
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $erreurs[] = "Erreur lors de la modification de l'avance.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Avance</title>
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

        .container { max-width: 800px; margin: 0 auto; padding: 20px; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top h2 { color: #1f2937; font-size: 22px; }
        .top p { color: #6b7280; font-size: 14px; }

        .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 14px; }
        .form-group label .required { color: #dc2626; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-group .help { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .mb-3 { margin-bottom: 12px; }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-box i { color: #2563eb; font-size: 18px; }
        .info-box .info-label { font-weight: 600; color: #374151; }
        .info-box .info-value { color: #6b7280; }

        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; padding: 10px 0; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-hand-holding-usd" style="color:#2563eb;"></i> Avances sur Salaire</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
                <a href="index.php" class="active"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php elseif (strtolower($role) === 'caissier'): ?>
                <a href="../caissier/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="index.php" class="active"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="header-right">
        <span class="role"><?php echo htmlspecialchars($role); ?></span>
        <span class="username"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <a href="../../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="container">
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="top">
        <div>
            <h2>Modifier l'Avance sur Salaire</h2>
            <p>Modifiez les informations de l'avance</p>
        </div>
    </div>

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <span class="info-label">Avance N°</span>
        <span class="info-value">#<?php echo $avance->getId(); ?></span>
        <span class="info-label">| Date :</span>
        <span class="info-value"><?php echo date('d/m/Y', strtotime($avance->getDateCreation())); ?></span>
    </div>

    <?php if (!empty($erreurs)): ?>
        <?php foreach ($erreurs as $erreur): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($erreur); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="agent_id">Agent <span class="required">*</span></label>
                <select id="agent_id" name="agent_id" required>
                    <option value="">Sélectionnez un agent</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?php echo $agent->getIdAgent(); ?>" <?php echo ($agent_id == $agent->getIdAgent()) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agent->getNomComplet()); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="mois">Mois <span class="required">*</span></label>
                    <select id="mois" name="mois" required>
                        <option value="">Sélectionnez</option>
                        <?php 
                        $moisNoms = [
                            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
                            '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
                            '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
                        ];
                        foreach ($moisNoms as $num => $nom): 
                        ?>
                            <option value="<?php echo $num; ?>" <?php echo ($mois == $num) ? 'selected' : ''; ?>>
                                <?php echo $nom; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="annee">Année <span class="required">*</span></label>
                    <select id="annee" name="annee" required>
                        <option value="">Sélectionnez</option>
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($annee == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="libelle">Libellé <span class="required">*</span></label>
                <input type="text" id="libelle" name="libelle" 
                       value="<?php echo htmlspecialchars($libelle); ?>" 
                       placeholder="Ex: Avance pour transport, Achat fournitures, etc." required>
            </div>

            <div class="form-group">
                <label for="montant">Montant (en $) <span class="required">*</span></label>
                <input type="number" id="montant" name="montant" step="0.01" min="0.01" 
                       value="<?php echo htmlspecialchars($montant); ?>" required>
            </div>

            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut">
                    <option value="<?php echo Avance::STATUT_EN_COURS; ?>" <?php echo ($statut == Avance::STATUT_EN_COURS) ? 'selected' : ''; ?>>
                        En cours
                    </option>
                    <option value="<?php echo Avance::STATUT_REMBOURSE; ?>" <?php echo ($statut == Avance::STATUT_REMBOURSE) ? 'selected' : ''; ?>>
                        Remboursé
                    </option>
                </select>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> - Gestion des Avances sur Salaire
    </div>
</div>

</body>
</html>