<?php
// Redirection vers add.php avec l'ID pour éditer
if (isset($_GET['id'])) {
    header('Location: add.php?id=' . $_GET['id']);
} else {
    header('Location: index.php');
}
exit;
?>
