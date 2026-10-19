<?php
require_once __DIR__ . "/config/auth.php";
wajibLogin();
redirectTo(isAdmin() ? "admin/tambah.php" : "siswa/tambah.php");
