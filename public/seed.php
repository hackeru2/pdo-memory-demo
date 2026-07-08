<?php
require __DIR__ . '/../src/db.php';

set_time_limit(300);
pageHeader('Seeding largeTable');

$rows = min(max((int) ($_GET['rows'] ?? 200000), 1000), 1000000);

$pdo = db();
echo "<p>Rebuilding <code>largeTable</code> with " . number_format($rows) . " rows (generated server-side, nothing streamed over the wire)&hellip;</p>";
flush_now();

$start = microtime(true);

$pdo->exec('DROP TABLE IF EXISTS largeTable');
$pdo->exec('CREATE TABLE largeTable (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    payload VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB');

$pdo->exec('SET SESSION cte_max_recursion_depth = ' . ($rows + 1));
$pdo->exec("INSERT INTO largeTable (uuid, payload, amount, created_at)
    WITH RECURSIVE seq (n) AS (
        SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < {$rows}
    )
    SELECT UUID(),
           RPAD(CONCAT('row-', n, '-'), 250, 'x'),
           ROUND(RAND() * 10000, 2),
           NOW() - INTERVAL (n % 3650) DAY
    FROM seq");

$elapsed = number_format(microtime(true) - $start, 1);
echo "<p class='ok'>Done in {$elapsed}s. <a href='/broken.php'>Now trigger the memory error &rarr;</a></p>";
