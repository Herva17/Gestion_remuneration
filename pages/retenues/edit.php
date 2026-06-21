<?php
if (isset($_GET['id'])) {
    header('Location: add.php?id=' . $_GET['id']);
} else {
    header('Location: index.php');
}
exit;
?>
