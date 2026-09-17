<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ../Controller/export_laporan.php' . $qs);
exit;