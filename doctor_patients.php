<?php
require_once __DIR__ . '/lib/auth.php';
require_role('doctor');

$q = trim($_GET['q'] ?? '');
$rows = [];
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE role='patient' AND (name LIKE ? OR email LIKE ?) ORDER BY name");
    $like = '%' . $q . '%';
    $stmt->execute([$like,$like]);
    $rows = $stmt->fetchAll();
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Find patient</h2>
  <form method="get">
    <label>Search by name or email</label>
    <input name="q" value="<?= h($q) ?>" placeholder="e.g. John or john@example.com">
    <button class="btn btn-primary">Search</button>
  </form>

  <?php if ($q !== ''): ?>
    <h3>Results</h3>
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Export</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td><?= h($r['email']) ?></td>
            <td><a class="btn" href="export_pdf.php?patient_id=<?= $r['id'] ?>" target="_blank">Export PDF</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="3"><em>No matches.</em></td></tr><?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
