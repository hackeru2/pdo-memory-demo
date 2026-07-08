<?php
require __DIR__ . '/../src/db.php';

set_time_limit(300);
pageHeader('broken.php — fetchAll() on largeTable');

// Report peak memory even when the request dies with a fatal error.
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null && str_contains($err['message'], 'Allowed memory size')) {
        echo "<p class='bad'>&uarr; That is the exact fatal error from the question. Peak usage: "
            . fmtBytes(memory_get_peak_usage()) . " of " . ini_get('memory_limit') . "</p>";
    }
});

echo '<pre>$stmt = $pdo->prepare(\'SELECT * FROM largeTable\');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $result) {
  // manipulate the data here
}</pre>';
echo '<p>Running it now&hellip; memory before query: ' . fmtBytes(memory_get_usage()) . '</p>';
flush_now();

$pdo = db();

// ---- the snippet from the question, verbatim ----
$stmt = $pdo->prepare('SELECT * FROM largeTable');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $result) {
  // manipulate the data here
}
// -------------------------------------------------

// Only reached if the table is too small to exhaust memory.
echo "<p class='ok'>No fatal error this time — peak usage was " . fmtBytes(memory_get_peak_usage())
    . ". Re-seed with more rows: <a href='/seed.php?rows=400000'>seed.php?rows=400000</a></p>";
