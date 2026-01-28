<?php
declare(strict_types=1);
use function App\{pdo, e, require_user};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/totp.php';

$me = \App\require_user();
$msg = '';
$stmt = pdo()->prepare("SELECT email, mfa_enabled, mfa_secret FROM users WHERE id=?");
$stmt->execute([$me['id']]);
$u = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \App\csrf_verify();
    if (isset($_POST['enable'])) {
        // Generate secret and keep in session until confirmed
        $_SESSION['mfa_tmp_secret'] = \App\Totp\random_base32_secret();
    } elseif (isset($_POST['verify'])) {
        $secret = $_SESSION['mfa_tmp_secret'] ?? '';
        $code   = $_POST['totp_code'] ?? '';
        if ($secret && \App\Totp\verify($secret, $code, 1)) {
            $up = pdo()->prepare("UPDATE users SET mfa_secret=?, mfa_enabled=1, updated_at=NOW() WHERE id=?");
            $up->execute([$secret, $me['id']]);
            unset($_SESSION['mfa_tmp_secret']);
            $u['mfa_enabled'] = 1;
            $u['mfa_secret'] = $secret;
            $msg = 'MFA enabled.';
        } else {
            $msg = 'Invalid code.';
        }
    } elseif (isset($_POST['disable'])) {
        // For simplicity require only a POST with CSRF (you can add password+code check later)
        $up = pdo()->prepare("UPDATE users SET mfa_secret=NULL, mfa_enabled=0, updated_at=NOW() WHERE id=?");
        $up->execute([$me['id']]);
        unset($_SESSION['mfa_tmp_secret']);
        $u['mfa_enabled'] = 0;
        $u['mfa_secret'] = null;
        $msg = 'MFA disabled.';
    }
}
$issuer = \App\config()['app_name'];
$uri = '';
if (!empty($_SESSION['mfa_tmp_secret'])) {
    $uri = \App\Totp\provisioning_uri($_SESSION['mfa_tmp_secret'], $u['email'], $issuer);
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>MFA</title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container narrow">
  <header class="admin-header">
    <h1>Multi‑factor Authentication</h1>
    <nav>
      <a href="/admin/">Dashboard</a>
      <a href="/admin/logout.php" class="danger">Logout</a>
    </nav>
  </header>
  <?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
  <?php if ((int)$u['mfa_enabled'] === 1): ?>
    <div class="card">
      <p>MFA is currently <strong>enabled</strong>.</p>
      <form method="post">
        <?= \App\csrf_field() ?>
        <button class="danger" name="disable" value="1" type="submit">Disable MFA</button>
      </form>
    </div>
  <?php else: ?>
    <?php if (empty($_SESSION['mfa_tmp_secret'])): ?>
      <form method="post">
        <?= \App\csrf_field() ?>
        <button name="enable" value="1" type="submit">Enable MFA</button>
      </form>
    <?php else: ?>
      <div class="card">
        <h2>Scan this QR in your authenticator</h2>
        <div id="qr" style="width:180px;height:180px;"></div>
        <p>Or enter this secret: <code><?= e($_SESSION['mfa_tmp_secret']) ?></code></p>
        <form method="post">
          <?= \App\csrf_field() ?>
          <label>Enter code to verify<br><input name="totp_code" inputmode="numeric" pattern="[0-9]{6}" required></label>
          <button name="verify" value="1" type="submit">Verify & Enable</button>
        </form>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
      <script>
        new QRCode(document.getElementById('qr'), {
          text: "<?= htmlspecialchars($uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>",
          width: 180, height: 180
        });
      </script>
    <?php endif; ?>
  <?php endif; ?></main></body></html>
