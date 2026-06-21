<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Retenue.php';
require_once __DIR__ . '/../../Classes/Agent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable' && $_SESSION['user_role'] !== 'Secretaire') {
    $_SESSION['message'] = "Accès réservé aux administrateurs, comptables et secrétaires";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$retenue = null;
$is_edit = false;
$title = "Ajouter une Retenue";
$button_text = "Ajouter";

$agents = Agent::getAll();

if (isset($_GET['id'])) {
    $is_edit = true;
    $retenue = Retenue::getById($_GET['id']);
    $title = "Éditer la Retenue";
    $button_text = "Mettre à jour";
    
    if (!$retenue) {
        $_SESSION['message'] = "Retenue non trouvée";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_agent = $_POST['id_agent'] ?? '';
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_retenue = $_POST['type_retenue'] ?? 'autre';
    $est_recurrent = isset($_POST['est_recurrent']) ? 1 : 0;
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    $montant = $_POST['montant'] ?? '';
    $date_retenue = $_POST['date_retenue'] ?? date('Y-m-d');
    $mois = $_POST['mois'] ?? '';
    $annee = $_POST['annee'] ?? '';
    $statut = $_POST['statut'] ?? 'actif';

    // Validation
    if (empty($id_agent)) {
        $error = "L'agent est requis";
    } elseif (empty($libelle)) {
        $error = "Le libellé est requis";
    } elseif (empty($montant) || !is_numeric($montant) || floatval($montant) <= 0) {
        $error = "Le montant est requis et doit être supérieur à 0";
    } elseif (empty($mois)) {
        $error = "Le mois est requis";
    } elseif (empty($annee)) {
        $error = "L'année est requise";
    } else {
        if ($is_edit) {
            // Mise à jour - on crée un nouvel objet avec les mêmes données modifiées
            $update_retenue = new Retenue(
                $id_agent,
                $libelle,
                $description,
                $type_retenue,
                $est_recurrent,
                $date_debut,
                $date_fin,
                $montant,
                $date_retenue,
                $mois,
                $annee,
                $statut,
                $retenue->getId() // On passe l'ID existant
            );
            
            if ($update_retenue->update()) {
                $_SESSION['message'] = "Retenue mise à jour avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de la mise à jour";
            }
        } else {
            // Création
            $new_retenue = new Retenue(
                $id_agent,
                $libelle,
                $description,
                $type_retenue,
                $est_recurrent,
                $date_debut,
                $date_fin,
                $montant,
                $date_retenue,
                $mois,
                $annee,
                $statut
            );
            
            if ($new_retenue->insert()) {
                $_SESSION['message'] = "Retenue ajoutée avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de l'ajout de la retenue";
            }
        }
    }
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// Liste des mois
$moisList = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];

// Types de retenues
$typeList = [
    'impot' => 'Impôt sur le revenu',
    'assurance' => 'Assurance sociale',
    'cotisation' => 'Cotisation mutuelle',
    'avance' => 'Avance sur salaire',
    'penalite' => 'Pénalité',
    'autre' => 'Autre'
];

// Statuts
$statutList = [
    'actif' => 'Actif',
    'inactif' => 'Inactif',
    'en_attente' => 'En attente'
];
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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header { background: #ffffff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .header-left h1 { color: #2563eb; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; gap: 4px; flex-wrap: wrap; }
        .nav-links a { color: #64748b; text-decoration: none; font-size: 13px; padding: 6px 12px; border-radius: 6px; transition: all 0.2s; font-weight: 500; }
        .nav-links a:hover { background: #f1f5f9; color: #0f172a; }
        .nav-links a.active { background: #dc2626; color: #ffffff; }
        .nav-links a.active:hover { background: #b91c1c; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .header-right .role { color: #94a3b8; font-size: 13px; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; }
        .header-right .username { font-weight: 600; color: #0f172a; font-size: 14px; }
        .header-right .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #dc2626, #b91c1c); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 15px; font-weight: 600; }
        .header-right .logout { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .header-right .logout:hover { color: #dc2626; text-decoration: underline; }

        .container { max-width: 900px; margin: 0 auto; padding: 24px 20px; flex: 1; width: 100%; }

        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .top h2 { font-size: 24px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .top p { color: #64748b; font-size: 14px; margin-top: 4px; }

        .card { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 14px; }
        .form-group label .required { color: #ef4444; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; background: #f8fafc; }
        .form-control:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); background: white; }
        .form-control.error { border-color: #ef4444; }
        .form-control:disabled { background: #f1f5f9; cursor: not-allowed; }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }

        .help-text { font-size: 12px; color: #94a3b8; margin-top: 4px; }

        .btn { padding: 10px 24px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-red { background: #dc2626; color: white; }
        .btn-red:hover { background: #b91c1c; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert-danger { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }

        .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #dc2626; cursor: pointer; }
        .checkbox-group label { margin: 0; font-weight: 400; cursor: pointer; }

        .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        .footer { text-align: center; margin-top: 32px; padding: 16px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; background: white; }

        .info-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #991b1b; font-size: 14px; }
        .info-box .label { font-weight: 600; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; padding: 12px 16px; }
            .header-left { flex-direction: column; align-items: stretch; gap: 10px; }
            .nav-links { justify-content: center; gap: 2px; }
            .nav-links a { font-size: 12px; padding: 4px 8px; }
            .header-right { justify-content: center; gap: 10px; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; gap: 16px; }
            .card { padding: 20px; }
            .top { flex-direction: column; align-items: stretch; }
            .top .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Gestion Retenues</h1>
        <nav class="nav-links">
            <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <?php if ($role === 'Administrateur'): ?>
            <a href="../../pages/utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
            <?php endif; ?>
            <a href="../../pages/agents/index.php"><i class="fas fa-users"></i> Agents</a>
            <a href="../../pages/services/index.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../../pages/affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
            <a href="../../pages/remunerations/index.php"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
            <a href="index.php" class="active"><i class="fas fa-arrow-down"></i> Retenues</a>
            <a href="../../pages/avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
            <a href="../../pages/avantages/AnneeScolaire.php"><i class="fas fa-calendar-alt"></i> Années</a>
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

    <div class="top">
        <div>
            <h2><i class="fas <?php echo $is_edit ? 'fa-edit' : 'fa-plus-circle'; ?>" style="color:#dc2626;"></i> <?php echo $title; ?></h2>
            <p><?php echo $is_edit ? 'Modifiez les informations de la retenue' : 'Ajoutez une nouvelle retenue pour un agent'; ?></p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
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
            <span class="label">Retenue #<?php echo $retenue->getId(); ?></span>
            - Modifiez les informations ci-dessous
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Agent -->
            <div class="form-group">
                <label for="id_agent">Agent <span class="required">*</span></label>
                <select name="id_agent" id="id_agent" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                    <option value="">-- Sélectionner un agent --</option>
                    <?php if (!empty($agents)): ?>
                        <?php foreach ($agents as $agent): ?>
                        <option value="<?php echo $agent->getIdAgent(); ?>" <?php echo ($retenue && $retenue->getIdAgent() == $agent->getIdAgent()) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agent->getNomComplet() . ' - ' . $agent->getFonction()); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Aucun agent disponible</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Libellé -->
            <div class="form-group">
                <label for="libelle">Libellé <span class="required">*</span></label>
                <input type="text" name="libelle" id="libelle" class="form-control" 
                       placeholder="Ex: Impôt sur le revenu, Assurance sociale..."
                       value="<?php echo $retenue ? htmlspecialchars($retenue->getLibelle()) : ''; ?>" required>
                <div class="help-text">Nom ou description courte de la retenue</div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" 
                          placeholder="Description détaillée de la retenue (facultatif)"><?php echo $retenue ? htmlspecialchars($retenue->getDescription()) : ''; ?></textarea>
                <div class="help-text">Informations supplémentaires sur la retenue</div>
            </div>

            <!-- Type et Montant -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type_retenue">Type de Retenue <span class="required">*</span></label>
                    <select name="type_retenue" id="type_retenue" class="form-control" required>
                        <?php foreach ($typeList as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($retenue && $retenue->getTypeRetenue() == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="montant">Montant <span class="required">*</span></label>
                    <input type="number" name="montant" id="montant" class="form-control" 
                           step="0.01" min="0" placeholder="0.00"
                           value="<?php echo $retenue ? $retenue->getMontant() : ''; ?>" required>
                    <div class="help-text">Montant de la retenue en dollars ($)</div>
                </div>
            </div>

            <!-- Date de la retenue -->
            <div class="form-group">
                <label for="date_retenue">Date de la retenue</label>
                <input type="date" name="date_retenue" id="date_retenue" class="form-control"
                       value="<?php echo $retenue ? $retenue->getDateRetenue() : date('Y-m-d'); ?>">
                <div class="help-text">Date à laquelle la retenue est effectuée</div>
            </div>

            <!-- Mois et Année -->
            <div class="form-row">
                <div class="form-group">
                    <label for="mois">Mois <span class="required">*</span></label>
                    <select name="mois" id="mois" class="form-control" required>
                        <option value="">-- Sélectionner un mois --</option>
                        <?php foreach ($moisList as $mois): ?>
                            <option value="<?php echo $mois; ?>" <?php echo ($retenue && $retenue->getMois() == $mois) ? 'selected' : ''; ?>>
                                <?php echo $mois; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="annee">Année <span class="required">*</span></label>
                    <select name="annee" id="annee" class="form-control" required>
                        <option value="">-- Sélectionner une année --</option>
                        <?php 
                        $anneeActuelle = date('Y');
                        for ($a = $anneeActuelle - 5; $a <= $anneeActuelle + 1; $a++): 
                        ?>
                            <option value="<?php echo $a; ?>" <?php echo ($retenue && $retenue->getAnnee() == $a) ? 'selected' : ''; ?>>
                                <?php echo $a; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Date début et fin pour récurrent -->
            <div class="form-row">
                <div class="form-group">
                    <label for="date_debut">Date de début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control"
                           value="<?php echo $retenue ? $retenue->getDateDebut() : ''; ?>">
                    <div class="help-text">Date à partir de laquelle la retenue s'applique</div>
                </div>
                <div class="form-group">
                    <label for="date_fin">Date de fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control"
                           value="<?php echo $retenue ? $retenue->getDateFin() : ''; ?>">
                    <div class="help-text">Date jusqu'à laquelle la retenue s'applique</div>
                </div>
            </div>

            <!-- Options -->
            <div class="form-row">
                <div class="form-group">
                    <label>Options</label>
                    <div class="checkbox-group">
                        <input type="checkbox" name="est_recurrent" id="est_recurrent" value="1" 
                               <?php echo ($retenue && $retenue->isRecurrent()) ? 'checked' : ''; ?>>
                        <label for="est_recurrent">
                            <i class="fas fa-sync-alt" style="color:#3b82f6;"></i> 
                            Retenue récurrente (s'applique chaque mois)
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="statut">Statut <span class="required">*</span></label>
                    <select name="statut" id="statut" class="form-control" required>
                        <?php foreach ($statutList as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($retenue && $retenue->getStatut() == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Avertissement -->
            <div style="padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;margin-bottom:16px;font-size:13px;color:#991b1b;">
                <i class="fas fa-exclamation-triangle"></i> 
                Cette retenue sera déduite du salaire de l'agent.
            </div>

            <!-- Boutons -->
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-red">
                    <i class="fas fa-save"></i> <?php echo $button_text; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($is_edit): ?>
                <a href="delete.php?id=<?php echo $retenue->getId(); ?>" class="btn btn-danger" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette retenue ? Cette action est irréversible.')">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> - Gestion des Rémunérations &bull; Tous droits réservés
    </div>
</div>

</body>
</html>