<?php
require __DIR__ . '/../src/db.php';

pageHeader('PDO memory-exhaustion demo');

echo '<p>PHP memory_limit: <strong>' . ini_get('memory_limit') . '</strong> (matches the 134217728-byte limit in the question)</p>';

try {
    $pdo = db();
    $count = 0;
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM largeTable')->fetchColumn();
    } catch (PDOException $e) {
        echo "<p class='bad'>largeTable does not exist yet — run the seeder first.</p>";
    }
    if ($count > 0) {
        echo '<p>largeTable currently holds <strong>' . number_format($count) . '</strong> rows.</p>';
    }
    echo "<ol>
        <li><a href='/seed.php'>seed.php</a> — (re)create largeTable with 200,000 rows, generated inside MySQL</li>
        <li><a href='/broken.php'>broken.php</a> — the code from the question: <code>fetchAll()</code> &rarr; fatal memory error</li>
        <li><a href='/fixed.php'>fixed.php</a> — the refactor: unbuffered query + row-by-row <code>fetch()</code></li>
    </ol>";
} catch (PDOException $e) {
    echo "<p class='bad'>Could not connect to the database: " . htmlspecialchars($e->getMessage()) . '</p>';
}
