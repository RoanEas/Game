<?php
header("Location: ../taboo/index.php" . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
exit();
?>