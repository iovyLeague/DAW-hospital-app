<?php
require_once __DIR__ . '/lib/auth.php';
require_role('admin');

$action = $_GET['action'] ?? 'list';

if ($action === 'mark' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $status = $_GET['status'] ?? 'handled';
  if (!in_array($status, ['new', 'spam', 'handled']))
    $status = 'handled';
  $pdo->prepare("UPDATE contact_messages SET status=? WHERE id=?")->execute([$status, $id]);
  flash_set('Message updated');
  header("Location: admin_contact.php");
  exit;
}

$rows = $pdo->query("SELECT c.*, u.name AS user_name FROM contact_messages c LEFT JOIN users u ON u.id=c.user_id ORDER BY priority DESC, created_at DESC")->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Contact messages</h2>
  <table class="table">
    <thead>
      <tr>
        <th>When</th>
        <th>Name</th>
        <th>Email</th>
        <th>Priority</th>
        <th>Recaptcha</th>
        <th>Status</th>
        <th>Message</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small"><?= h($r['created_at']) ?></td>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td><?= $r['priority'] ? 'High' : 'Normal' ?></td>
          <td><?= $r['recaptcha_ok'] ? 'OK' : 'Fail' ?></td>
          <td><?= h($r['status']) ?></td>
          <td>
            <div class="message-cell" data-expanded="false">
              <div class="message-text"><?= h($r['message']) ?></div>
              <button type="button" class="message-toggle btn btn-sm btn-outline" aria-expanded="false">
                Show more
              </button>
            </div>
          </td>
          <td class="actions">
            <a href="admin_contact.php?action=mark&id=<?= $r['id'] ?>&status=handled">Handled</a> |
            <a href="admin_contact.php?action=mark&id=<?= $r['id'] ?>&status=spam">Spam</a> |
            <a href="admin_contact.php?action=mark&id=<?= $r['id'] ?>&status=new">New</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="8"><em>No messages.</em></td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>