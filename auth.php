<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   NO CACHE
========================= */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* =========================
   AUTH
========================= */

if (
    !isset($_SESSION["usuario_panel"])
    || empty($_SESSION["usuario_panel"])
) {

    header("Location: login.php");
    exit;
}
