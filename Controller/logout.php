<?php
session_start();
session_unset();
session_destroy();

header('Location: ../Controller/login.php');
exit;