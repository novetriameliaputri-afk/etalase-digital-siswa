<?php
require_once __DIR__ . "/config/auth.php";

$localRequest = in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true);
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('SA', 'Admin')")->fetchColumn();
$error = "";
$created = false;

if (!$localRequest) {
    http_response_code(403);
    exit("Setup admin hanya tersedia dari komputer lokal.");
}
if ($adminCount > 0) {
    http_response_code(403);
    exit("Akun admin sudah tersedia. Halaman setup ini telah dikunci.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibCsrf();
    $nameValue = $_POST["nama"] ?? "";
    $usernameValue = $_POST["username"] ?? "";
    $passwordValue = $_POST["password"] ?? "";
    $name = trim(is_string($nameValue) ? $nameValue : "");
    $username = trim(is_string($usernameValue) ? $usernameValue : "");
    $password = is_string($passwordValue) ? $passwordValue : "";
    if ($name === "" || !preg_match("/^[a-zA-Z0-9._-]{3,50}$/", $username)) {
        $error = "Nama dan username yang valid wajib diisi.";
    } elseif (strlen($password) < 12) {
        $error = "Password admin minimal 12 karakter.";
    } else {
        $lockAcquired = (int)$pdo->query("SELECT GET_LOCK('etalse_first_admin', 5)")->fetchColumn() === 1;
        if (!$lockAcquired) {
            $error = "Setup sedang diproses. Muat ulang halaman dan coba lagi.";
        } else {
            try {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('SA', 'Admin')")->fetchColumn();
                if ($count > 0) {
                    $error = "Admin pertama sudah dibuat. Halaman setup telah dikunci.";
                } else {
                    $statement = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'SA')");
                    $statement->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
                    $created = true;
                }
            } catch (PDOException $exception) {
                $error = $exception->getCode() === "23000"
                    ? "Username sudah digunakan. Pilih username lain."
                    : "Admin tidak dapat dibuat. Periksa konfigurasi database.";
            } finally {
                $pdo->query("SELECT RELEASE_LOCK('etalse_first_admin')");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Admin | Etalase Digital</title>
    <link rel="stylesheet" href="<?= h(url("assets/css/style.css")) ?>">
</head>
<body class="auth-page">
<main class="auth-layout auth-layout-compact">
    <section class="auth-aside">
        <a class="brand brand-light" href="<?= h(url("index.php")) ?>"><span class="brand-mark">E</span><span>Etalase Digital<br><strong>Siswa</strong></span></a>
        <div class="auth-aside-copy"><span class="eyebrow">PENGATURAN PERTAMA</span><h1>Kelola ruang<br><em>berkarya.</em></h1><p>Buat akun super admin awal untuk mengelola seluruh katalog.</p></div>
        <div class="aside-note"><span>SA</span><span>Akses admin hanya untuk pengelola sekolah.</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-card">
            <div class="school-mark" aria-hidden="true">✳</div>
            <p class="eyebrow">SUPER ADMIN PERTAMA</p>
            <h2><?= $created ? "Admin berhasil dibuat" : "Buat akun admin" ?></h2>
            <?php if ($created): ?>
                <div class="notice notice-success" role="status">Akun SA sudah aktif. Halaman setup ini tidak dapat digunakan lagi.</div>
                <a class="button button-primary button-wide" href="<?= h(url("login.php")) ?>">Lanjut ke Login <span aria-hidden="true">→</span></a>
            <?php else: ?>
                <p class="muted">Halaman ini hanya aktif di komputer lokal dan otomatis terkunci setelah admin pertama dibuat.</p>
                <?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
                <form method="post" class="stack-form">
                    <?= csrfField() ?>
                    <label for="nama">Nama admin</label>
                    <input id="nama" name="nama" required maxlength="100" autocomplete="name" value="<?= h($_POST["nama"] ?? "") ?>">
                    <label for="username">Username</label>
                    <input id="username" name="username" required minlength="3" maxlength="50" autocomplete="username" value="<?= h($_POST["username"] ?? "") ?>">
                    <label for="password">Password admin</label>
                    <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
                    <button class="button button-primary button-wide" type="submit">Buat Super Admin <span aria-hidden="true">→</span></button>
                </form>
            <?php endif; ?>
            <p class="auth-switch"><a href="<?= h(url("login.php")) ?>">Kembali ke login</a></p>
        </div>
    </section>
</main>
</body>
</html>
