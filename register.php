<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/config/recaptcha.php';

if ($current_user)
    redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$name || !$email || !$pass) {
        flash_set('All fields are required', 'error');
    } else {

        if (!empty($_ENV['RECAPTCHA_SECRET'])) {
            $token = $_POST['g-recaptcha-response'] ?? '';
            if (!verify_recaptcha($token)) {      // verify_recaptcha() 
                flash_set('reCAPTCHA failed. Please try again.', 'error');
                redirect('register.php');
            }
        }
        $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            flash_set('Email already registered', 'error');
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));
            $exp = date('Y-m-d H:i:s', time() + 3600);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,is_active,verification_token,verification_expires) VALUES (?,?,?,?,0,?,?)");
            $stmt->execute([$name, $email, $hash, 'patient', $token, $exp]);

            $verifyUrl = rtrim(SITE_URL, '/') . '/verify.php?token=' . urlencode($token) . '&email=' . urlencode($email);
            $ok = send_mail($email, 'Verify your Mini Hospital account', '<p>Hi ' . htmlspecialchars($name) . ',</p><p>Please verify your account by clicking: <a href="' . htmlspecialchars($verifyUrl) . '">Verify Account</a></p>');
            if ($ok) {
                flash_set('Registered! Check your email for verification.');
                redirect('login.php');
            } else {
                flash_set('Registered, but failed to send email. Contact admin.', 'error');
            }
        }
    }
}
require_once __DIR__ . '/_header.php';
?>
<div class="card">
    <h2>Create account</h2>
    <form method="post">
        <label>Name</label>
        <input name="name" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <?php if (!empty($_ENV['RECAPTCHA_SITE_KEY'])): ?>
            <div class="g-recaptcha" data-sitekey="<?= h($_ENV['RECAPTCHA_SITE_KEY']) ?>"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>

        <button class="btn btn-primary">Register</button>
    </form>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>