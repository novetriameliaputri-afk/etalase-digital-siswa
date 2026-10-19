<?php
require_once __DIR__ . "/config/auth.php";

if (!empty($_SESSION["user_id"])) {
    redirectTo(isAdmin() ? "admin/dashboard.php" : "siswa/dashboard.php");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibCsrf();
    $usernameValue = $_POST["username"] ?? "";
    $passwordValue = $_POST["password"] ?? "";
    $username = trim(is_string($usernameValue) ? $usernameValue : "");
    $password = is_string($passwordValue) ? $passwordValue : "";
    $statement = $pdo->prepare("SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1");
    $statement->execute([$username]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["nama"] = $user["nama"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];
        unset($_SESSION["csrf_token"]);
        redirectTo(isAdmin() ? "admin/dashboard.php" : "siswa/dashboard.php");
    }
    $error = "Username atau password tidak sesuai.";
}

$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('SA', 'Admin')")->fetchColumn();
$localRequest = in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f5ff">
    <title>Login | Etalase Digital Siswa</title>
    <link rel="stylesheet" href="<?= h(url("assets/css/style.css")) ?>">
    <script src="<?= h(url("assets/js/script.js")) ?>" defer></script>
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-aside">
        <a class="brand brand-light" href="<?= h(url("index.php")) ?>"><span class="brand-mark">E</span><span>Etalase Digital<br><strong>Siswa</strong></span></a>
        <div class="auth-aside-copy"><span class="eyebrow">RUANG KARYA SEKOLAH</span><h1>Ide baik layak<br>untuk <em>dilihat.</em></h1><p>Tempat siswa berbagi karya, proyek, dan proses kreatifnya.</p></div>
        <div class="aside-note"><span>01</span><span>Belajar, berkarya, dan tumbuh bersama.</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-card">
            <div class="school-mark" aria-hidden="true">✳</div>
            <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
            <h2>Masuk ke etalase</h2>
            <p class="muted">Gunakan akun sekolahmu untuk melanjutkan.</p>
            <?php if (isset($_GET["daftar"])): ?><div class="notice notice-success" role="status">Akun berhasil dibuat. Silakan login.</div><?php endif; ?>
            <?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
            <form method="post" class="stack-form">
                <?= csrfField() ?>
                <label for="username">Username</label>
                <input id="username" name="username" required maxlength="50" autocomplete="username" placeholder="Masukkan username" value="<?= h($_POST["username"] ?? "") ?>">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
                <button class="button button-primary button-wide" type="submit">Login Sekarang <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="<?= h(url("register.php")) ?>">Daftar di sini</a></p>
            <?php if ($adminCount === 0 && $localRequest): ?><p class="setup-link"><a href="<?= h(url("setup_admin.php")) ?>">Buat akun admin pertama</a></p><?php endif; ?>
        </div>
        <p class="auth-footnote">Etalase Digital Siswa · Sistem Publikasi Karya Siswa</p>
    </section>
</main>
</body>
</html>
