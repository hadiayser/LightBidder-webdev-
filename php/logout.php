<?php
session_start();
session_destroy();
header("Location: ../front-php/index.php");
exit();
?> 