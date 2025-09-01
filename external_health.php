<?php
require_once __DIR__ . '/lib/auth.php';

$items = [];
$err = null;

function fetch_url($url) {
    $ctx = stream_context_create(['http'=>['timeout'=>4], 'https'=>['timeout'=>4]]);
    return @file_get_contents($url, false, $ctx);
}

$rss_url = 'https://www.who.int/rss-feeds/news-english.xml';
$data = fetch_url($rss_url);
if ($data) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($data);
    if ($xml && isset($xml->channel->item)) {
        foreach ($xml->channel->item as $it) {
            $items[] = [
                'title' => (string)$it->title,
                'link'  => (string)$it->link,
                'date'  => (string)$it->pubDate
            ];
            if (count($items) >= 5) break;
        }
    } else {
        $err = 'Failed to parse RSS.';
    }
} else {
    $err = 'Could not fetch external feed (host may block outbound HTTP).';
}

require_once __DIR__ . '/_header.php';
?>
<div class="card">
  <h2>Health news (WHO)</h2>
  <?php if ($err): ?>
    <p class="small">Note: <?= h($err) ?></p>
  <?php else: ?>
    <ul>
    <?php foreach ($items as $x): ?>
      <li>
        <a href="<?= h($x['link']) ?>" target="_blank"><?= h($x['title']) ?></a>
        <div class="small"><?= h($x['date']) ?></div>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
