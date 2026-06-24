<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/Remuneration.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/Affectation.php';

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

$remuneration = null;
$is_edit = false;
$title = "Ajouter une Rémunération";
$button_text = "Ajouter";

$agents = Agent::getAll();
$affectations = Affectation::getAll();

if (isset($_GET['id'])) {
    $is_edit = true;
    $remuneration = Remuneration::getById($_GET['id']);
    $title = "Éditer la Rémunération";
    $button_text = "Mettre à jour";
    
    if (!$remuneration) {
        $_SESSION['message'] = "Rémunération non trouvée";
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_agent = $_POST['id_agent'] ?? '';
    $id_affectation = $_POST['id_affectation'] ?? '';
    $mois = $_POST['mois'] ?? '';
    $annee = $_POST['annee'] ?? '';
    $date_remun = $_POST['date_remun'] ?? '';

    if (empty($id_agent)) {
        $error = "L'agent est requis";
    } elseif (empty($id_affectation)) {
        $error = "L'affectation est requise";
    } elseif (empty($mois)) {
        $error = "Le mois est requis";
    } elseif (empty($annee)) {
        $error = "L'année est requise";
    } else {
        // Vérifier que l'affectation existe et appartient bien à l'agent
        $affectation = Affectation::getById($id_affectation);
        if (!$affectation) {
            $error = "Affectation non trouvée";
        } elseif ($affectation->getIdAgent() != $id_agent) {
            $error = "Cette affectation n'appartient pas à l'agent sélectionné";
        } else {
            if ($is_edit) {
                $remuneration->setIdAgent($id_agent);
                $remuneration->setIdAffectation($id_affectation);
                $remuneration->setMois($mois);
                $remuneration->setAnnee($annee);
                $remuneration->setDateRemun($date_remun);
                
                if ($remuneration->update()) {
                    $_SESSION['message'] = "Rémunération mise à jour avec succès";
                    $_SESSION['message_type'] = 'success';
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Erreur lors de la mise à jour";
                }
            } else {
                $new_remuneration = new Remuneration(
                    $id_agent,
                    $id_affectation,
                    $date_remun,
                    $mois,
                    $annee
                );
                
                if ($new_remuneration->insert()) {
                    $_SESSION['message'] = "Rémunération ajoutée avec succès";
                    $_SESSION['message_type'] = 'success';
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Erreur lors de l'ajout de la rémunération";
                }
            }
        }
    }
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';

// ========== DÉTERMINER LA PAGE DE RETOUR ==========
$dashboardRetour = '../../Dashboard.php';
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'caissier') {
    $dashboardRetour = '../caissier/dashboard.php';
}
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

        .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 700px; margin: 0 auto; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .form-group label .required { color: #dc2626; }
        .form-group label .hint { font-size: 12px; color: #6b7280; font-weight: normal; margin-left: 4px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-control.error { border-color: #dc2626; }
        .form-control:disabled { background: #f3f4f6; cursor: not-allowed; }
        select.form-control { appearance: auto; }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-info { background: #0ea5e9; color: white; }
        .btn-info:hover { background: #0284c7; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .text-center { text-align: center; }
        .text-xs { font-size: 12px; }
        .text-gray-400 { color: #9ca3af; }

        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #1e40af; font-size: 14px; }
        .info-box .label { font-weight: 600; }
        .info-box .icon { font-size: 18px; }

        .input-icon { position: relative; }
        .input-icon .icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .input-icon input, .input-icon select { padding-left: 36px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .affectation-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 8px;
            display: none;
        }
        .affectation-info.visible {
            display: block;
        }
        .affectation-info .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
            border-bottom: 1px solid #dcfce7;
        }
        .affectation-info .info-row:last-child {
            border-bottom: none;
        }
        .affectation-info .label { color: #4b5563; font-weight: 500; }
        .affectation-info .value { color: #065f46; font-weight: 600; }

        .montant-auto {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 2px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-left { flex-direction: column; align-items: stretch; }
            .nav-links { justify-content: center; }
            .header-right { justify-content: center; }
            .grid-2 { grid-template-columns: 1fr; }
            .card { margin: 0 10px; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="header-left">
        <h1><i class="fas fa-money-bill-wave" style="color:#2563eb;"></i> Gestion Rémunérations</h1>
        <nav class="nav-links">
            <?php if (strtolower($role) === 'administrateur'): ?>
                <a href="../../Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="../utilisateurs/index.php"><i class="fas fa-user-lock"></i> Utilisateurs</a>
                <a href="../agents/index.php"><i class="fas fa-users"></i> Agents</a>
                <a href="../services/index.php"><i class="fas fa-cogs"></i> Services</a>
                <a href="../affectations/index.php"><i class="fas fa-tasks"></i> Affectations</a>
                <a href="index.php" class="active"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
            <?php elseif (strtolower($role) === 'caissier'): ?>
                <a href="../caissier/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="index.php" class="active"><i class="fas fa-money-bill-wave"></i> Rémunérations</a>
                <a href="../retenues/index.php"><i class="fas fa-arrow-down"></i> Retenues</a>
                <a href="../avantages/index.php"><i class="fas fa-gift"></i> Avantages</a>
                <a href="../avances/index.php"><i class="fas fa-hand-holding-usd"></i> Avances</a>
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

    <div class="top">
        <div>
            <h2><?php echo $title; ?></h2>
            <p><?php echo $is_edit ? 'Modifiez les informations de la rémunération' : 'Ajoutez une nouvelle rémunération'; ?></p>
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
            <span class="label">Rémunération #<?php echo $remuneration->getId(); ?></span>
            - Modifiez les informations ci-dessous
        </div>
        <?php endif; ?>

        <div class="info-box" style="background:#fef3c7;border-color:#fde68a;color:#92400e;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i>
            <span>Le montant est automatiquement récupéré depuis l'affectation sélectionnée.</span>
        </div>

        <form method="POST" id="remunerationForm">
            <!-- Agent -->
            <div class="form-group">
                <label>Agent <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <select name="id_agent" id="id_agent" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner un agent --</option>
                        <?php if (!empty($agents)): ?>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent->getIdAgent(); ?>" <?php echo ($remuneration && $remuneration->getIdAgent() == $agent->getIdAgent()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent->getNomComplet() . ' - ' . $agent->getFonction()); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucun agent disponible</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Affectation -->
            <div class="form-group">
                <label>Affectation <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-tasks"></i></span>
                    <select name="id_affectation" id="id_affectation" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner une affectation --</option>
                        <?php if (!empty($affectations)): ?>
                            <?php foreach ($affectations as $affectation): 
                                $agent = Agent::getById($affectation->getIdAgent());
                                $nomAgent = $agent ? $agent->getNomComplet() : 'Agent inconnu';
                                $montant = $affectation->getMontantRemunerer();
                                $montantDisplay = $montant !== null ? '$ ' . number_format($montant, 2, ',', ' ') : 'Non défini';
                            ?>
                            <option value="<?php echo $affectation->getId(); ?>" 
                                    data-agent="<?php echo $affectation->getIdAgent(); ?>"
                                    data-montant="<?php echo $montant !== null ? $montant : ''; ?>"
                                    data-lieu="<?php echo htmlspecialchars($affectation->getLieuAffectation() ?: 'Non spécifié'); ?>"
                                    <?php echo ($remuneration && $remuneration->getIdAffectation() == $affectation->getId()) ? 'selected' : ''; ?>>
                                #<?php echo $affectation->getId(); ?> - <?php echo htmlspecialchars($affectation->getLieuAffectation() ?: 'Sans lieu'); ?> 
                                (<?php echo $nomAgent; ?>) - <?php echo $montantDisplay; ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucune affectation disponible</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Informations de l'affectation -->
            <div id="affectationInfo" class="affectation-info">
                <div class="info-row">
                    <span class="label"><i class="fas fa-map-marker-alt"></i> Lieu d'affectation</span>
                    <span class="value" id="info_lieu">-</span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-dollar-sign"></i> Montant rémunéré</span>
                    <span class="value" id="info_montant">-</span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-user"></i> Agent associé</span>
                    <span class="value" id="info_agent">-</span>
                </div>
            </div>

            <div class="grid-2">
                <!-- Mois -->
                <div class="form-group">
                    <label>Mois <span class="required">*</span></label>
                    <div class="input-icon">
                        <span class="icon"><i class="fas fa-calendar-alt"></i></span>
                        <select name="mois" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                            <option value="">-- Sélectionner un mois --</option>
                            <?php 
                            $moisList = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                            for ($i = 0; $i < count($moisList); $i++): 
                                $m = $moisList[$i];
                            ?>
                            <option value="<?php echo $m; ?>" <?php echo ($remuneration && $remuneration->getMois() == $m) ? 'selected' : ''; ?>>
                                <?php echo $m; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Année -->
                <div class="form-group">
                    <label>Année <span class="required">*</span></label>
                    <div class="input-icon">
                        <span class="icon"><i class="fas fa-calendar"></i></span>
                        <select name="annee" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                            <option value="">-- Sélectionner une année --</option>
                            <?php 
                            $anneeActuelle = date('Y');
                            for ($a = $anneeActuelle - 5; $a <= $anneeActuelle + 1; $a++): 
                            ?>
                            <option value="<?php echo $a; ?>" <?php echo ($remuneration && $remuneration->getAnnee() == $a) ? 'selected' : ''; ?>>
                                <?php echo $a; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div class="form-group">
                <label>Date de la rémunération</label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-calendar-day"></i></span>
                    <input type="date" name="date_remun" class="form-control"
                           value="<?php echo $remuneration ? $remuneration->getDateRemun() : date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $button_text; ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($is_edit): ?>
                <a href="delete.php?id=<?php echo $remuneration->getId(); ?>" class="btn btn-danger" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette rémunération ?')">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const agentSelect = document.getElementById('id_agent');
    const affectationSelect = document.getElementById('id_affectation');
    const infoDiv = document.getElementById('affectationInfo');
    const infoLieu = document.getElementById('info_lieu');
    const infoMontant = document.getElementById('info_montant');
    const infoAgent = document.getElementById('info_agent');

    // Filtrer les affectations par agent
    function filterAffectations() {
        const selectedAgent = agentSelect.value;
        const options = affectationSelect.options;
        let hasVisible = false;

        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            if (option.value === '') continue;
            const agentId = option.getAttribute('data-agent');
            if (selectedAgent === '' || agentId === selectedAgent) {
                option.style.display = '';
                hasVisible = true;
            } else {
                option.style.display = 'none';
            }
        }

        // Réinitialiser la sélection si l'option sélectionnée est cachée
        if (affectationSelect.value !== '') {
            const selectedOption = affectationSelect.options[affectationSelect.selectedIndex];
            if (selectedOption && selectedOption.style.display === 'none') {
                affectationSelect.value = '';
                updateAffectationInfo();
            }
        }

        // Afficher un message si aucune affectation
        if (!hasVisible) {
            // Ajouter une option temporaire si nécessaire
            const noOption = document.createElement('option');
            noOption.value = '';
            noOption.textContent = '-- Aucune affectation pour cet agent --';
            noOption.disabled = true;
            // Supprimer l'ancienne option "aucune" si elle existe
            const existingNoOption = affectationSelect.querySelector('option[value=""][disabled]');
            if (existingNoOption && existingNoOption.textContent.includes('Aucune affectation')) {
                existingNoOption.remove();
            }
            affectationSelect.appendChild(noOption);
            affectationSelect.value = '';
        } else {
            // Supprimer l'option "aucune affectation" si elle existe
            const noOption = affectationSelect.querySelector('option[value=""][disabled]');
            if (noOption && noOption.textContent.includes('Aucune affectation')) {
                noOption.remove();
            }
        }

        updateAffectationInfo();
    }

    // Mettre à jour les informations de l'affectation
    function updateAffectationInfo() {
        const selectedOption = affectationSelect.options[affectationSelect.selectedIndex];
        if (selectedOption && selectedOption.value !== '') {
            const lieu = selectedOption.getAttribute('data-lieu') || 'Non spécifié';
            const montant = selectedOption.getAttribute('data-montant');
            const agentId = selectedOption.getAttribute('data-agent');
            
            infoLieu.textContent = lieu;
            if (montant && montant !== '') {
                const montantFloat = parseFloat(montant);
                infoMontant.innerHTML = '<span style="color:#16a34a;font-weight:700;">$ ' + montantFloat.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
            } else {
                infoMontant.innerHTML = '<span style="color:#f97316;">Non défini</span>';
            }
            
            // Récupérer le nom de l'agent
            if (agentId) {
                const agentOption = agentSelect.querySelector('option[value="' + agentId + '"]');
                infoAgent.textContent = agentOption ? agentOption.textContent.split(' - ')[0] : 'Agent #' + agentId;
            } else {
                infoAgent.textContent = '-';
            }
            
            infoDiv.classList.add('visible');
        } else {
            infoDiv.classList.remove('visible');
        }
    }

    // Événements
    agentSelect.addEventListener('change', filterAffectations);
    affectationSelect.addEventListener('change', updateAffectationInfo);

    // Initialisation
    filterAffectations();
    
    // Si une affectation est pré-sélectionnée en mode édition
    if (affectationSelect.value !== '') {
        updateAffectationInfo();
    }
});
</script>

</body>
</html>