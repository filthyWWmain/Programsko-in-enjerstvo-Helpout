<?php
session_start();
session_destroy();
header('Location: ../pages/Helpout_main.php');
exit;