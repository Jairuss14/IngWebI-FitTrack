<?php
declare(strict_types=1);

require_once __DIR__ . "/php/auth.php";
require_once __DIR__ . "/php/db.php";
require_login();

$pdo = db();
$userId = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT username, full_name, email, bio, dob, avatar_path FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
  // Algo raro: sesión sin usuario real
  header("Location: php/logout.php");
  exit;
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

$avatar = $user["avatar_path"] ? e($user["avatar_path"]) : "assets/avatar-default.svg";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi perfil · FitTrack</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>
<body>
  <header class="site-header">
        <div class="container header-inner">
        <a class="brand" href="dashboard.php" aria-label="Volver al dashboard">
            <span class="brand-mark">FT</span>
            <span class="brand-name">FitTrack</span>
        </a>

        <nav class="nav" aria-label="Navegación principal">
            <a href="dashboard.php">Dashboard</a>
            <a class="btn btn--outline" href="php/logout.php">Cerrar sesión</a>
        </nav>

        <button class="burger" type="button" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        </div>
    </header>

    <main class="dash">
        <div class="container">
        <section class="panel panel--inner">
    <header class="panel-head panel-head--row">
        <div>
        <h2>Mi perfil</h2>
        <p class="panel-lead">Consulta tu información y edítala cuando lo necesites.</p>
        </div>

        <div class="profile-top-actions">
        <button class="btn btn--ghost" type="button" id="changePasswordBtn">Cambiar contraseña</button>
        <button class="btn btn--ghost" type="button" id="editProfileBtn">Modificar</button>
        </div>
    </header>

    <!-- MODO VISTA: tarjeta -->
    <div id="profileView" class="profile-view">
        <div class="profile-header">
        <img src="<?php echo $avatar; ?>" alt="Foto de perfil" class="avatar avatar--lg" />
        <div>
            <p class="hint">@<?php echo e((string)$user["username"]); ?></p>
            <h3 class="profile-name"><?php echo e((string)($user["full_name"] ?? "—")); ?></h3>
        </div>
        </div>

        <div class="profile-row">
        <span class="profile-k">Fecha de nacimiento</span>
        <span class="profile-v">
            <?php echo $user["dob"] ? e((string)$user["dob"]) : "—"; ?>
        </span>
        </div>

        <div class="profile-row">
        <span class="profile-k">Email</span>
        <span class="profile-v"><?php echo e((string)($user["email"] ?? "—")); ?></span>
        </div>

        <div class="profile-row">
        <span class="profile-k">Bio</span>
        <span class="profile-v"><?php echo e((string)($user["bio"] ?? "—")); ?></span>
        </div>
    </div>

    <!-- MODO EDICIÓN: inputs + cambiar foto (oculto al inicio) -->
    <form id="profileForm" class="form is-hidden" novalidate>
        <div class="profile-edit-head">
        <div class="profile-edit-avatar">
            <img src="<?php echo $avatar; ?>" alt="Foto de perfil" class="avatar avatar--lg" id="avatarPreview" />
            <div class="field">
            <label for="avatar">Cambiar foto (opcional)</label>
            <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" />
            <p class="hint">PNG/JPG/WEBP · máx 2MB</p>
            <p class="error" id="err-avatar" aria-live="polite"></p>
            </div>
        </div>

        <div class="field">
            <label>Fecha de nacimiento</label>
            <input type="text" value="<?php echo $user["dob"] ? e((string)$user["dob"]) : "—"; ?>" disabled />
            <p class="hint">Por seguridad, este dato no se modifica desde aquí.</p>
        </div>
        </div>

        <div class="field">
        <label for="full_name">Nombre (opcional)</label>
        <input id="full_name" name="full_name" type="text" maxlength="60"
                value="<?php echo e((string)($user["full_name"] ?? "")); ?>" />
        <p class="error" id="err-full_name" aria-live="polite"></p>
        </div>

        <div class="field">
        <label for="email">Email (opcional)</label>
        <input id="email" name="email" type="email" maxlength="120"
                value="<?php echo e((string)($user["email"] ?? "")); ?>" />
        <p class="error" id="err-email" aria-live="polite"></p>
        </div>

        <div class="field">
        <label for="bio">Bio (opcional)</label>
        <textarea id="bio" name="bio" rows="3" maxlength="255"><?php echo e((string)($user["bio"] ?? "")); ?></textarea>
        <p class="error" id="err-bio" aria-live="polite"></p>
        </div>

        <div class="form-actions">
        <button class="btn btn--primary" type="submit">Guardar cambios</button>
        <button class="btn btn--outline" type="button" id="cancelEditBtn">Cancelar</button>
        </div>

        <p class="form-success" id="profileSuccess" aria-live="polite"></p>
    </form>

    <!-- CAMBIAR CONTRASEÑA (oculto al inicio) -->
    <form id="passwordForm" class="form is-hidden" novalidate>
        <div class="field">
        <label for="current_password">Contraseña actual</label>
        <input id="current_password" name="current_password" type="password" required />
        <p class="error" id="err-current_password" aria-live="polite"></p>
        </div>

        <div class="field">
        <label for="new_password">Nueva contraseña</label>
        <input id="new_password" name="new_password" type="password" required />
        <p class="error" id="err-new_password" aria-live="polite"></p>
        </div>

        <div class="field">
        <label for="new_password2">Repetir nueva contraseña</label>
        <input id="new_password2" name="new_password2" type="password" required />
        <p class="error" id="err-new_password2" aria-live="polite"></p>
        </div>

        <div class="form-actions">
        <button class="btn btn--primary" type="submit">Actualizar contraseña</button>
        <button class="btn btn--outline" type="button" id="cancelPasswordBtn">Cancelar</button>
        </div>

        <p class="form-success" id="passwordSuccess" aria-live="polite"></p>
    </form>
    </section>
    </div>
  </main>

  <script src="js/script.js"></script>
  <script src="js/profile.js"></script>
</body>
</html>