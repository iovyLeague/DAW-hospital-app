<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/config/recaptcha.php';

$site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';
$ok = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $token = $_POST['g-recaptcha-response'] ?? '';

    if (!$name || !$email || !$message) {
        flash_set('All fields are required','error');
    } else {
        $recaptcha_ok = verify_recaptcha($token) ? 1 : 0;
        $priority = ($current_user && $current_user['is_active']) ? 1 : 0;
        $ins = $pdo->prepare("INSERT INTO contact_messages (user_id,name,email,message,priority,recaptcha_ok,status) VALUES (?,?,?,?,?,?,?)");
        $status = $recaptcha_ok ? 'new' : 'spam';
        $ins->execute([$current_user['id'] ?? null, $name, $email, $message, $priority, $recaptcha_ok, $status]);

        // email notify 
        $owner = $_ENV['SMTP_FROM'] ?? null;
        if ($owner) {
            $subject = "[Mini Hospital] Contact message from {$name}";
            $body = "<p>Name: " . htmlspecialchars($name) . "</p><p>Email: " . htmlspecialchars($email) . "</p><p>Priority: {$priority}</p><p>Recaptcha: {$recaptcha_ok}</p><p>Message:</p><pre>" . htmlspecialchars($message) . "</pre>";
            @send_mail($owner, $subject, $body);
        }

        flash_set($recaptcha_ok ? 'Message sent! We will reply soon.' : 'Message saved but flagged as spam.','info');
        redirect('contact.php');
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Contact us</h2>
  <form method="post">
    <label>Name</label>
    <input name="name" value="<?= h($_POST['name'] ?? ($current_user['name'] ?? '')) ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= h($_POST['email'] ?? ($current_user['email'] ?? '')) ?>" required>
    <label>Message</label>
    <textarea name="message" rows="5" required><?= h($_POST['message'] ?? '') ?></textarea>

    <?php if ($site_key): ?>
    <div class="g-recaptcha" data-sitekey="<?= h($site_key) ?>"></div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php else: ?>
    <p class="small">reCAPTCHA disabled (no site key configured).</p>
    <?php endif; ?>

    <button class="btn btn-primary">Send</button>
  </form>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
