<?php
require_once __DIR__ . '/lib/auth.php';
require_role('admin');

$action = $_GET['action'] ?? 'list';

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id === $current_user['id']) { flash_set('Cannot delete yourself','error'); redirect('admin_users.php'); }
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    flash_set('User deleted');
    redirect('admin_users.php');
}

if ($action === 'role') {
    ensure_post();
    $id = intval($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? 'patient';
    if (!in_array($role, ['admin','doctor','patient'], true)) { flash_set('Invalid role','error'); redirect('admin_users.php'); }
    $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$id]);
    flash_set('Role updated');
    redirect('admin_users.php');
}

if ($action === 'edit') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,name,email,is_active,role,email_verified_at FROM users WHERE id=?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) { flash_set('User not found','error'); redirect('admin_users.php'); }

    require_once __DIR__ . '/_header.php';
    ?>
    <div class="card">
      <h2>Edit user</h2>
      <form method="post" action="admin_users.php?action=update">
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <label>Name</label>
        <input name="name" value="<?= h($u['name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= h($u['email']) ?>" required>

        <label>Active (verified)</label>
        <label style="display:flex;align-items:center;gap:.5rem">
          <input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?>>
          <span class="small">If you activate an unverified account, the “verified at” timestamp will be set if empty.</span>
        </label>

        <p class="small">Role: <?= role_badge($u['role']) ?> (change in list view if needed)</p>

        <button class="btn btn-primary">Save</button>
        <a class="btn" href="admin_users.php">Cancel</a>
      </form>
    </div>
    <?php
    require_once __DIR__ . '/_footer.php';
    exit;
}

if ($action === 'update') {
    ensure_post();
    $id   = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    if (!$id || $name === '' || $email === '') {
        flash_set('All fields are required','error'); redirect('admin_users.php?action=edit&id='.$id);
    }

    // Check email daca e unic
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?");
    $chk->execute([$email, $id]);
    if ($chk->fetch()) {
        flash_set('Another user already uses that email','error'); redirect('admin_users.php?action=edit&id='.$id);
    }

    $cur = $pdo->prepare("SELECT is_active,email_verified_at FROM users WHERE id=?");
    $cur->execute([$id]);
    $prev = $cur->fetch();

    if ($is_active && (empty($prev['email_verified_at']))) {
        $upd = $pdo->prepare("UPDATE users SET name=?, email=?, is_active=1, email_verified_at=NOW() WHERE id=?");
        $upd->execute([$name,$email,$id]);
    } else {
        $upd = $pdo->prepare("UPDATE users SET name=?, email=?, is_active=? WHERE id=?");
        $upd->execute([$name,$email,$is_active,$id]);
    }

    flash_set('User updated');
    redirect('admin_users.php');
}

$rows = $pdo->query("SELECT id,name,email,role,is_active,created_at FROM users ORDER BY created_at DESC")->fetchAll();
require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Users</h2>
  <table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Since</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['name']) ?></td>
        <td><?= h($r['email']) ?></td>
        <td><?= role_badge($r['role']) ?></td>
        <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
        <td class="small"><?= h($r['created_at']) ?></td>
        <td class="actions">
          <?php if ($r['role'] === 'patient'): ?>
            <a class="btn" href="export_pdf.php?patient_id=<?= $r['id'] ?>" target="_blank">Export PDF</a>
          <?php endif; ?>

          <a class="btn" href="admin_users.php?action=edit&id=<?= $r['id'] ?>">Edit</a>

          <form method="post" action="admin_users.php?action=role" style="display:inline-block">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <select name="role">
              <?php foreach (['patient','doctor','admin'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $r['role']===$opt?'selected':'' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn">Save</button>
          </form>

          <?php if ($r['id'] !== $current_user['id']): ?>
            <a href="admin_users.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6"><em>No users.</em></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
