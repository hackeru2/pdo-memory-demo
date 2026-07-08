<?php
require __DIR__ . '/../src/db.php';

set_time_limit(300);
pageHeader('fixed.php — unbuffered query, row-by-row fetch');

echo '<pre>$pdo = new PDO($dsn, $user, $pass, [
  PDO::MYSQL_ATTR_USE_BUFFERED_QUERY =&gt; false, // stream, don\'t buffer
]);

$stmt = $pdo->prepare(\'SELECT * FROM largeTable\');
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
  // manipulate the data here
}</pre>';
echo '<p>Running it now over the same table&hellip;</p>';
flush_now();

// ---- the refactor ----
// 1. Unbuffered query: mysqlnd no longer copies the whole result set into
//    PHP memory on execute(); rows stream from the server as we ask for them.
//    Passed as a constructor option — setAttribute() after connect is not
//    honored for this attribute on all driver builds.
$pdo = db([PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
$start = microtime(true);
$memBefore = memory_get_usage();

echo '<p>Buffered-query attribute is now: '
    . var_export((bool) $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY), true) . '</p>';
flush_now();

// 2. fetch() one row at a time instead of fetchAll(), so only a single row
//    is ever held in PHP memory.
$stmt = $pdo->prepare('SELECT * FROM largeTable');
$stmt->execute();

$rows = 0;
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // manipulate the data here
    $rows++;
}
// ----------------------

$elapsed = number_format(microtime(true) - $start, 1);
echo "<p class='ok'>Processed " . number_format($rows) . " rows in {$elapsed}s. Peak memory: <strong>"
    . fmtBytes(memory_get_peak_usage()) . "</strong> of " . ini_get('memory_limit')
    . " (grew by " . fmtBytes(max(0, memory_get_usage() - $memBefore))
    . " during the loop) — the same table that killed <a href='/broken.php'>broken.php</a>.</p>";
