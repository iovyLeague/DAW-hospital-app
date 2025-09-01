<?php
require_once __DIR__ . '/lib/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $old === '' || $confirm === '') {
        flash_set('All fields are required', 'error');
    } elseif ($new !== $confirm) {
        flash_set('New passwords do not match', 'error');
    } elseif (strlen($new) < 6) {
        flash_set('New password must be at least 6 characters', 'error');
    } else {
        // Load current hash
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
        $stmt->execute([$current_user['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old, $row['password_hash'])) {
            flash_set('Old password is incorrect', 'error');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $upd->execute([$hash, $current_user['id']]);
            flash_set('Password updated successfully');
            redirect('index.php');
        }
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Change password</h2>
  <form method="post">
    <label>Old password</label>
    <input type="password" name="old_password" required>
    <label>New password</label>
    <input type="password" name="new_password" required>
    <label>Confirm new password</label>
    <input type="password" name="confirm_password" required>
    <button class="btn btn-primary">Update password</button>
  </form>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
