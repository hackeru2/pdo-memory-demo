<?php
require __DIR__ . '/../src/db.php';

set_time_limit(300);
pageHeader('submitted.php — the answer sent to the company, verbatim');

echo '<pre>$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
$stmt = $pdo->prepare(\'SELECT * FROM largeTable\');
$stmt->execute();
foreach ($stmt as $result) {
  // manipulate the data here
}</pre>';
echo "<p>Reviewer said: <em>&quot;Sets buffered query to false, but misses to use fetch&quot;</em>.
    PDOStatement is iterable — foreach calls fetch() internally. Running it now&hellip;</p>";
flush_now();

$pdo = db();
$start = microtime(true);
$memBefore = memory_get_usage();

// ---- the gist answer, verbatim ----
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
$stmt = $pdo->prepare('SELECT * FROM largeTable');
$stmt->execute();
$rows = 0;
foreach ($stmt as $result) {
  // manipulate the data here
  $rows++;
}
// -----------------------------------

$elapsed = number_format(microtime(true) - $start, 1);
echo "<p class='ok'>Processed " . number_format($rows) . " rows in {$elapsed}s. Peak memory: <strong>"
    . fmtBytes(memory_get_peak_usage()) . "</strong> of " . ini_get('memory_limit')
    . " (grew by " . fmtBytes(max(0, memory_get_usage() - $memBefore))
    . " during the loop). Same streaming behavior as the explicit fetch() loop in <a href='/fixed.php'>fixed.php</a>.</p>";
