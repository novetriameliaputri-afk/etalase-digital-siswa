<?php
require_once __DIR__ . "/config/auth.php";
$error = "";
$values = ["nama" => "", "username" => "", "nis" => "", "kelas" => "", "jurusan" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibCsrf();
    foreach ($values as $key => $_value) {
        $value = $_POST[$key] ?? "";
        $values[$key] = trim(is_string($value) ? $value : "");
    }
    $passwordValue = $_POST["password"] ?? "";
    $password = is_string($passwordValue) ? $passwordValue : "";
    if (in_array("", $values, true) || $password === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } elseif (!preg_match("/^[a-zA-Z0-9._-]{3,50}$/", $values["username"])) {
        $error = "Username harus 3-50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.";
    } else {
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'Siswa')");
            $statement->execute([$values["nama"], $values["username"], password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int)$pdo->lastInsertId();
            $statement = $pdo->prepare("INSERT INTO siswa (user_id, nis, nama, kelas, jurusan) VALUES (?, ?, ?, ?, ?)");
            $statement->execute([$userId, $values["nis"], $values["nama"], $values["kelas"], $values["jurusan"]]);
            $pdo->commit();
            redirectTo("login.php?daftar=berhasil");
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getCode() === "23000"
                ? "Username atau NIS sudah digunakan. Periksa kembali datamu."
                : "Pendaftaran gagal. Periksa koneksi database dan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Siswa | Etalase Digital</title>
    <link rel="stylesheet" href="<?= h(url("assets/css/style.css")) ?>">
</head>
<body class="auth-page">
<main class="auth-layout auth-layout-compact">
    <section class="auth-aside">
        <a class="brand brand-light" href="<?= h(url("index.php")) ?>"><span class="brand-mark">E</span><span>Etalase Digital<br><strong>Siswa</strong></span></a>
        <div class="auth-aside-copy"><span class="eyebrow">RUANG KARYA SEKOLAH</span><h1>Karya kecil,<br><em>dampak besar.</em></h1><p>Buat akun siswa dan mulai publikasikan proyekmu.</p></div>
        <div class="aside-note"><span>02</span><span>Setiap karya punya cerita.</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-card auth-card-register">
            <div class="school-mark" aria-hidden="true">✳</div>
            <p class="eyebrow">AKUN SISWA</p>
            <h2>Daftar di etalase</h2>
            <p class="muted">Lengkapi data untuk mulai berbagi karya.</p>
            <?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
            <form method="post" class="stack-form">
                <?= csrfField() ?>
                <label for="nama">Nama lengkap</label>
                <input id="nama" name="nama" required maxlength="100" autocomplete="name" value="<?= h($values["nama"]) ?>">
                <div class="form-row">
                    <div><label for="nis">NIS</label><input id="nis" name="nis" required maxlength="30" value="<?= h($values["nis"]) ?>"></div>
                    <div><label for="username">Username</label><input id="username" name="username" required minlength="3" maxlength="50" autocomplete="username" value="<?= h($values["username"]) ?>"></div>
                </div>
                <div class="form-row">
                    <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" required maxlength="50" placeholder="Contoh: XI RPL 1" value="<?= h($values["kelas"]) ?>"></div>
                    <div><label for="jurusan">Jurusan</label><input id="jurusan" name="jurusan" required maxlength="100" placeholder="Contoh: Rekayasa Perangkat Lunak" value="<?= h($values["jurusan"]) ?>"></div>
                </div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                <button class="button button-primary button-wide" type="submit">Buat Akun <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="<?= h(url("login.php")) ?>">Login di sini</a></p>
        </div>
    </section>
</main>
</body>
</html>
