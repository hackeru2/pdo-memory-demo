<?php
require __DIR__ . '/../src/db.php';

set_time_limit(300);
pageHeader('debug — where does the memory go?');

echo '<p>Driver: ' . (extension_loaded('mysqlnd') ? 'mysqlnd' : 'libmysqlclient') . '</p>';

function variant(string $label, array $options, bool $useQuery): void
{
    gc_collect_cycles();
    memory_reset_peak_usage();
    $pdo = db($options);

    $before = memory_get_usage(true);
    if ($useQuery) {
        $stmt = $pdo->query('SELECT * FROM largeTable');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM largeTable');
        $stmt->execute();
    }
    $afterExec = memory_get_usage(true);

    $rows = 0;
    $afterFirst = 0;
    while ($stmt->fetch(PDO::FETCH_ASSOC)) {
        if (++$rows === 1) {
            $afterFirst = memory_get_usage(true);
        }
    }
    printf(
        '<p><strong>%s</strong>: before=%s, after execute=%s, after first fetch=%s, rows=%s, peak=%s</p>',
        htmlspecialchars($label),
        fmtBytes($before),
        fmtBytes($afterExec),
        fmtBytes($afterFirst),
        number_format($rows),
        fmtBytes(memory_get_peak_usage(true))
    );
    flush_now();
    $stmt = null;
    $pdo = null;
}

variant('A: unbuffered + emulated prepare (default)', [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false], false);
variant('B: unbuffered + native prepare', [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, PDO::ATTR_EMULATE_PREPARES => false], false);
variant('C: unbuffered + query()', [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false], true);
