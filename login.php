<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/config/mailer.php'; 
require_once __DIR__ . '/config/recaptcha.php';

if ($current_user)
    redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    // reCAPTCHA
    if (!empty($_ENV['RECAPTCHA_SECRET'])) {
        $token = $_POST['g-recaptcha-response'] ?? '';
        if (!verify_recaptcha($token)) {          // verify_recaptcha() 
            flash_set('reCAPTCHA failed. Please try again.', 'error');
            redirect('login.php');
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pass, $u['password_hash'])) {
        flash_set('Invalid credentials', 'error');

    } elseif (!$u['is_active']) {
        flash_set('Please verify your email first.', 'error');

    } else {
        if (!empty($_ENV['DISABLE_2FA']) && $_ENV['DISABLE_2FA'] == '1') {
            $_SESSION['user_id'] = $u['id'];
            $pdo->prepare("UPDATE users SET twofa_code=NULL,twofa_expires=NULL WHERE id=?")->execute([$u['id']]);
            flash_set('Logged in (2FA disabled).');
            redirect('index.php');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $exp = date('Y-m-d H:i:s', time() + 600);
        $pdo->prepare("UPDATE users SET twofa_code=?, twofa_expires=? WHERE id=?")->execute([$code, $exp, $u['id']]);
        $_SESSION['twofa_user_id'] = $u['id'];

        $sent = send_mail(
            $u['email'],
            'Your Mini Hospital login code',
            '<p>Your login code is: <b>' . htmlspecialchars($code) . '</b> (valid 10 minutes)</p>'
        );

        if (APP_OFFLINE) {

            @file_put_contents(__DIR__ . '/storage/dev_2fa.log', "[" . date('c') . "] {$u['email']} -> {$code}\n", FILE_APPEND);
            flash_set('Offline mode: code saved to storage/dev_2fa.log.');
        } else {
            if ($sent) {
                flash_set('A login code was sent to your email.');
            } else {
                @file_put_contents(__DIR__ . '/storage/dev_2fa.log', "[" . date('c') . "] (send FAIL) {$u['email']} -> {$code}\n", FILE_APPEND);
                flash_set('Email failed; code saved to storage/dev_2fa.log.', 'error');
            }
        }

        if (!headers_sent()) {
            header('Location: twofa.php', true, 302);  
            exit;
        }
        echo '<p>Continue: <a href="twofa.php">Two-Factor Code</a></p>';
        exit;
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
    <h2>Login</h2>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <?php if (!empty($_ENV['RECAPTCHA_SITE_KEY'])): ?>
            <div class="g-recaptcha" data-sitekey="<?= h($_ENV['RECAPTCHA_SITE_KEY']) ?>"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>

        <button class="btn btn-primary">Send login code</button>
    </form>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>