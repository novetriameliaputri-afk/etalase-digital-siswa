<?php
require_once __DIR__ . "/config/auth.php";
if (empty($_SESSION["user_id"])) {
    redirectTo("login.php");
}
redirectTo(isAdmin() ? "admin/dashboard.php" : "siswa/dashboard.php");
