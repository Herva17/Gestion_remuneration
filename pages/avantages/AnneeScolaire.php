<?php
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Classes/annee_scolaire.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Vérification des droits d'accès
if ($_SESSION['user_role'] !== 'Administrateur' && $_SESSION['user_role'] !== 'Comptable') {
    $_SESSION['message'] = "Accès réservé aux administrateurs et comptables";
    $_SESSION['message_type'] = 'error';
    header('Location: ../../Dashboard.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'Invité';
$username = $_SESSION['nom'] ?? $_SESSION['username'] ?? 'Utilisateur';
$error = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation_ann = trim($_POST['designation_ann'] ?? '');

    if (empty($designation_ann)) {
        $error = "La désignation de l'année scolaire est requise";
    } else {
        $annee = new AnneeScolaire($designation_ann);
        
        if ($annee->insert()) {
            $_SESSION['message'] = "Année scolaire ajoutée avec succès";
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $error = "Erreur lors de l'ajout de l'année scolaire";
        }
    }
}

// Récupération des années existantes
$annees = AnneeScolaire::getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Années Scolaires</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }

        .container { max-width: 1280px; margin: 0 auto; padding: 24px 20px; }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }
        .top-bar p {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-group label .required { color: #ef4444; }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-control.error { border-color: #ef4444; }

        .input-icon { position: relative; }
        .input-icon .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .input-icon input { padding-left: 40px; }

        .help-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-purple {
            background: #a855f7;
            color: white;
        }
        .btn-purple:hover { background: #9333ea; }
        .btn-success {
            background: #22c55e;
            color: white;
        }
        .btn-success:hover { background: #16a34a; }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }

        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1e40af;
            font-size: 14px;
        }

        .warning-box {
            padding: 12px 16px;
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .flex-wrap { flex-wrap: wrap; }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .table-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-top: 24px;
        }
        .table-wrap {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:hover td { background: #f8fafc; }
        tr:last-child td { border-bottom: none; }

        .footer {
            text-align: center;
            margin-top: 32px;
            padding: 16px 0;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .top-bar .actions {
                width: 100%;
            }
            .top-bar .actions .btn {
                width: 100%;
                justify-content: center;
            }
            .flex.gap-3 {
                flex-direction: column;
            }
            .flex.gap-3 .btn {
                width: 100%;
                justify-content: center;
            }
            .card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div>
            <h2><i class="fas fa-calendar-alt" style="color:#a855f7;"></i> Gestion des Années Scolaires</h2>
            <p><i class="fas fa-info-circle"></i> Créez et gérez les années scolaires</p>
        </div>
        
    </div>

    <!-- MESSAGES -->
    <?php if (isset($_SESSION['message']) && $_SESSION['message']): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
            <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message'], $_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE D'AJOUT -->
    <div class="card">
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Exemple de format : <strong>2024-2025</strong>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Désignation de l'année <span class="required">*</span></label>
                <div class="input-icon">
                    <span class="icon"><i class="fas fa-calendar-alt"></i></span>
                    <input type="text" name="designation_ann" class="form-control <?php echo $error ? 'error' : ''; ?>" 
                           placeholder="Ex: 2024-2025, 2025-2026..." 
                           pattern="\d{4}-\d{4}"
                           title="Format: AAAA-AAAA"
                           required>
                </div>
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> Format recommandé : <strong>AAAA-AAAA</strong> (ex: 2024-2025)
                </div>
            </div>

            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i> 
                L'année scolaire est utilisée pour les avantages, rémunérations et autres modules.
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-purple">
                    <i class="fas fa-save"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

   

  

</div>

</body>
</html>