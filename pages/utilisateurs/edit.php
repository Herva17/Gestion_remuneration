<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Redirection vers add.php avec l'ID pour éditer
if (isset($_GET['id'])) {
    header('Location: add.php?id=' . $_GET['id']);
} else {
    header('Location: index.php');
}
exit;
?>
