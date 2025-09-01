<?php
require_once __DIR__ . '/lib/auth.php';
require_role('admin');

$total = $pdo->query("SELECT COUNT(*) AS c FROM stats")->fetch()['c'] ?? 0;
$unique = $pdo->query("SELECT COUNT(DISTINCT ip) AS c FROM stats")->fetch()['c'] ?? 0;
$per_path = $pdo->query("SELECT path, COUNT(*) AS c FROM stats GROUP BY path ORDER BY c DESC LIMIT 20")->fetchAll();
$latest = $pdo->query("SELECT * FROM stats ORDER BY created_at DESC LIMIT 50")->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Site analytics</h2>
  <p>Total visits: <b><?= h($total) ?></b> • Unique IPs: <b><?= h($unique) ?></b></p>

  <h3>Top pages</h3>
  <table class="table">
    <thead><tr><th>Path</th><th>Views</th></tr></thead>
    <tbody>
    <?php foreach ($per_path as $r): ?>
      <tr><td><?= h($r['path']) ?></td><td><?= h($r['c']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Recent visits</h3>
  <table class="table">
    <thead><tr><th>Time</th><th>IP</th><th>Path</th><th>User-Agent</th></tr></thead>
    <tbody>
    <?php foreach ($latest as $r): ?>
      <tr><td class="small"><?= h($r['created_at']) ?></td><td><?= h($r['ip']) ?></td><td><?= h($r['path']) ?></td><td class="small"><?= h($r['ua']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
