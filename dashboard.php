<?php
require_once __DIR__ . "/config/auth.php";
wajibLogin();
redirectTo(isAdmin() ? "admin/dashboard.php" : "siswa/dashboard.php");
