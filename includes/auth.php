<?php
// includes/auth.php

// Start session early (before ANY output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Call this on pages that require login
function require_login(): void
{
    if (!isset($_SESSION["user"])) {
        header("Location: /auth/login.php");
        exit;
    }
}
