<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/avantages.php';
require_once __DIR__ . '/../../Classes/Agent.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Caissier') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et aux caissiers";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
$error = null;
$success = false;

$agents = Agent::getAll();
$annees = AnneeScolaire::getAll();

// Liste des mois
$moisList = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];

// Types d'avantages
$typeList = [
    'transport' => 'Transport',
    'communication' => 'Communication',
    'logement' => 'Logement',
    'prime' => 'Prime',
    'bonus' => 'Bonus',
    'autre' => 'Autre'
];

// Statuts
$statutList = [
    'actif' => 'Actif',
    'inactif' => 'Inactif',
    'en_attente' => 'En attente'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $id_agent = isset($_POST['id_agent']) ? (int)$_POST['id_agent'] : 0;
    $id_annee = isset($_POST['id_annee']) ? (int)$_POST['id_annee'] : 0;
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_avantage = $_POST['type_avantage'] ?? 'autre';
    $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
    $date_avantage = $_POST['date_avantage'] ?? date('Y-m-d');
    $mois = $_POST['mois'] ?? '';
    $annee = isset($_POST['annee']) ? (int)$_POST['annee'] : date('Y');
    $est_recurrent = isset($_POST['est_recurrent']) ? 1 : 0;
    $statut = $_POST['statut'] ?? 'actif';
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

    // Validation
    if ($id_agent <= 0) {
        $error = "L'agent est requis";
    } elseif (empty($libelle)) {
        $error = "Le libellé est requis";
    } elseif ($id_annee <= 0) {
        $error = "L'année scolaire est requise";
    } elseif ($montant <= 0) {
        $error = "Le montant est requis et doit être supérieur à 0";
    } elseif (empty($mois)) {
        $error = "Le mois est requis";
    } elseif (empty($annee)) {
        $error = "L'année est requise";
    } else {
        try {
            // ========== CORRECTION : Utilisation du constructeur correct ==========
            // Le constructeur de Avantage est : 
            // __construct($id_agent, $id_annee, $mois, $annee, $libelle, $type, $montant, $statut, $description, $est_recurrent, $id)
            
            $avantage = new Avantage(
                $id_agent,           // id_agent
                $id_annee,           // id_annee
                $mois,               // mois
                $annee,              // annee
                $libelle,            // libelle
                $type_avantage,      // type
                $montant,            // montant
                $statut,             // statut
                $description,        // description
                $est_recurrent       // est_recurrent
                // $id est optionnel (null par défaut)
            );
            
            if ($avantage->insert()) {
                $_SESSION['message'] = "Avantage ajouté avec succès";
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $error = "Erreur lors de l'ajout de l'avantage. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Avantage</title>
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
        .nav-links a.active { background: #eab308; color: #ffffff; }
        .nav-links a.active:hover { background: #ca8a04; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .header-right .role { color: #94a3b8; font-size: 13px; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; }
        .header-right .username { font-weight: 600; color: #0f172a; font-size: 14px; }
        .header-right .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #eab308, #ca8a04); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 15px; font-weight: 600; }
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
        .form-control:focus { outline: none; border-color: #eab308; box-shadow: 0 0 0 3px rgba(234,179,8,0.1); background: white; }
        .form-control.error { border-color: #ef4444; }
        .form-control:disabled { background: #f1f5f9; cursor: not-allowed; }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .help-text { font-size: 12px; color: #94a3b8; margin-top: 4px; }

        .btn { padding: 10px 24px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-yellow { background: #eab308; color: white; }
        .btn-yellow:hover { background: #ca8a04; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert-danger { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }

        .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #eab308; cursor: pointer; }
        .checkbox-group label { margin: 0; font-weight: 400; cursor: pointer; }

        .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .footer { text-align: center; margin-top: 32px; padding: 16px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; background: white; }

        .info-box { background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #92400e; font-size: 14px; }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; padding: 12px 16px; }
            .header-left { flex-direction: column; align-items: stretch; gap: 10px; }
            .nav-links { justify-content: center; gap: 2px; }
            .nav-links a { font-size: 12px; padding: 4px 8px; }
            .header-right { justify-content: center; gap: 10px; }
            .form-row { grid-template-columns: 1fr; gap: 16px; }
            .card { padding: 20px; }
            .top { flex-direction: column; align-items: stretch; }
            .top .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="top">
        <div>
            <h2><i class="fas fa-plus-circle" style="color:#eab308;"></i> Ajouter un Avantage</h2>
            <p><i class="fas fa-info-circle"></i> Accordez un avantage à un agent</p>
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

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Avantage ajouté avec succès !
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Remplissez tous les champs obligatoires pour ajouter un avantage à un agent.
        </div>

        <form method="POST">
            <!-- Agent et Année Scolaire -->
            <div class="form-row">
                <div class="form-group">
                    <label for="id_agent">Agent <span class="required">*</span></label>
                    <select name="id_agent" id="id_agent" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner un agent --</option>
                        <?php if (!empty($agents)): ?>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent->getIdAgent(); ?>" 
                                    <?php echo (isset($_POST['id_agent']) && $_POST['id_agent'] == $agent->getIdAgent()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent->getNomComplet() . ' - ' . $agent->getFonction()); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucun agent disponible</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_annee">Année Scolaire <span class="required">*</span></label>
                    <select name="id_annee" id="id_annee" class="form-control <?php echo $error ? 'error' : ''; ?>" required>
                        <option value="">-- Sélectionner une année --</option>
                        <?php if (!empty($annees)): ?>
                            <?php foreach ($annees as $annee): ?>
                            <option value="<?php echo $annee->getId(); ?>"
                                    <?php echo (isset($_POST['id_annee']) && $_POST['id_annee'] == $annee->getId()) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($annee->getDesignationAnn()); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Aucune année disponible</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Libellé -->
            <div class="form-group">
                <label for="libelle">Libellé <span class="required">*</span></label>
                <input type="text" name="libelle" id="libelle" class="form-control" 
                       placeholder="Ex: Prime de transport, Allocation logement..."
                       value="<?php echo isset($_POST['libelle']) ? htmlspecialchars($_POST['libelle']) : ''; ?>" required>
                <div class="help-text">Nom ou description courte de l'avantage</div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" 
                          placeholder="Description détaillée de l'avantage (facultatif)"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <div class="help-text">Informations supplémentaires sur l'avantage</div>
            </div>

            <!-- Type et Montant -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type_avantage">Type d'Avantage <span class="required">*</span></label>
                    <select name="type_avantage" id="type_avantage" class="form-control" required>
                        <?php foreach ($typeList as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['type_avantage']) && $_POST['type_avantage'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="montant">Montant <span class="required">*</span></label>
                    <input type="number" name="montant" id="montant" class="form-control" 
                           step="0.01" min="0" placeholder="0.00"
                           value="<?php echo isset($_POST['montant']) ? htmlspecialchars($_POST['montant']) : ''; ?>" required>
                    <div class="help-text">Montant de l'avantage en dollars ($)</div>
                </div>
            </div>

            <!-- Date de l'avantage -->
            <div class="form-group">
                <label for="date_avantage">Date de l'avantage</label>
                <input type="date" name="date_avantage" id="date_avantage" class="form-control"
                       value="<?php echo isset($_POST['date_avantage']) ? $_POST['date_avantage'] : date('Y-m-d'); ?>">
                <div class="help-text">Date à laquelle l'avantage est accordé</div>
            </div>

            <!-- Mois et Année -->
            <div class="form-row">
                <div class="form-group">
                    <label for="mois">Mois <span class="required">*</span></label>
                    <select name="mois" id="mois" class="form-control" required>
                        <option value="">-- Sélectionner un mois --</option>
                        <?php foreach ($moisList as $mois): ?>
                            <option value="<?php echo $mois; ?>" 
                                    <?php echo (isset($_POST['mois']) && $_POST['mois'] == $mois) ? 'selected' : ''; ?>>
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
                        for ($a = $anneeActuelle - 2; $a <= $anneeActuelle + 2; $a++): 
                        ?>
                            <option value="<?php echo $a; ?>" 
                                    <?php echo (isset($_POST['annee']) && $_POST['annee'] == $a) ? 'selected' : ''; ?>>
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
                           value="<?php echo isset($_POST['date_debut']) ? $_POST['date_debut'] : ''; ?>">
                    <div class="help-text">Date à partir de laquelle l'avantage s'applique</div>
                </div>
                <div class="form-group">
                    <label for="date_fin">Date de fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control"
                           value="<?php echo isset($_POST['date_fin']) ? $_POST['date_fin'] : ''; ?>">
                    <div class="help-text">Date jusqu'à laquelle l'avantage s'applique</div>
                </div>
            </div>

            <!-- Options -->
            <div class="form-row">
                <div class="form-group">
                    <label>Options</label>
                    <div class="checkbox-group">
                        <input type="checkbox" name="est_recurrent" id="est_recurrent" value="1" 
                               <?php echo isset($_POST['est_recurrent']) ? 'checked' : ''; ?>>
                        <label for="est_recurrent">
                            <i class="fas fa-sync-alt" style="color:#3b82f6;"></i> 
                            Avantage récurrent (s'applique chaque mois)
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="statut">Statut <span class="required">*</span></label>
                    <select name="statut" id="statut" class="form-control" required>
                        <?php foreach ($statutList as $key => $label): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_POST['statut']) && $_POST['statut'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Avertissement -->
            <div style="padding:12px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;margin-bottom:16px;font-size:13px;color:#92400e;">
                <i class="fas fa-info-circle"></i> 
                Cet avantage sera ajouté au dossier de l'agent pour la période sélectionnée.
            </div>

            <!-- Boutons -->
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-yellow">
                    <i class="fas fa-save"></i> Ajouter
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> - Gestion des Rémunérations &bull; Tous droits réservés
    </div>
</div>

</body>
</html>