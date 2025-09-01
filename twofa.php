<?php
require_once __DIR__ . '/lib/auth.php';
if ($current_user) redirect('index.php');

$userId = $_SESSION['twofa_user_id'] ?? null;
if (!$userId) redirect('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    $master = $_ENV['DEV_2FA_CODE'] ?? '';
    if (!empty($_ENV['DEV_MODE']) && $_ENV['DEV_MODE'] == '1' && $master !== '' && $code === $master) {
        $_SESSION['user_id'] = $userId;
        $pdo->prepare("UPDATE users SET twofa_code=NULL,twofa_expires=NULL WHERE id=?")->execute([$userId]);
        unset($_SESSION['twofa_user_id']);
        flash_set('Logged in (DEV master code).');
        redirect('index.php');
    }

    $stmt = $pdo->prepare("SELECT id,twofa_code,twofa_expires FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();

    if ($u && $u['twofa_code'] === $code && strtotime($u['twofa_expires']) >= time()) {
        $_SESSION['user_id'] = $u['id'];
        $pdo->prepare("UPDATE users SET twofa_code=NULL,twofa_expires=NULL WHERE id=?")->execute([$u['id']]);
        unset($_SESSION['twofa_user_id']);
        flash_set('Welcome back!');
        redirect('index.php');
    } else {
        if (defined('APP_OFFLINE') && APP_OFFLINE) {
            flash_set('Invalid/expired code. Remember: offline codes are in storage/dev_2fa.log', 'error');
        } else {
            flash_set('Invalid or expired code', 'error');
        }
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Enter code</h2>
  <form method="post">
    <label>6-digit code</label>
    <input name="code" required>
    <button class="btn btn-primary">Verify</button>
  </form>
  <?php if (defined('APP_OFFLINE') && APP_OFFLINE): ?>
    <p class="small">Offline mode: check <code>storage/dev_2fa.log</code> for the code.</p>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
