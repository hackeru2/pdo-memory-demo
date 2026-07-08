<?php

/**
 * Builds a PDO connection from the DATABASE_URL env var, e.g. the
 * Aiven service URI: mysql://avnadmin:pass@host:port/defaultdb?ssl-mode=REQUIRED
 */
function db(array $extraOptions = []): PDO
{
    $url = getenv('DATABASE_URL');
    if ($url === false || $url === '') {
        http_response_code(500);
        exit("DATABASE_URL is not set. Add it in the Render dashboard (Environment tab).\n");
    }

    $parts = parse_url($url);
    if ($parts === false || !isset($parts['host'], $parts['user'], $parts['pass'])) {
        http_response_code(500);
        exit("DATABASE_URL could not be parsed. Expected mysql://user:pass@host:port/dbname\n");
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $parts['host'],
        $parts['port'] ?? 3306,
        ltrim($parts['path'] ?? '/defaultdb', '/')
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Aiven requires TLS. If the project CA is provided in MYSQL_CA_PEM we
    // verify against it; otherwise fall back to TLS without verification,
    // which mysqlnd enables when any SSL attribute is set.
    $caPem = getenv('MYSQL_CA_PEM');
    if ($caPem !== false && trim($caPem) !== '') {
        $caFile = sys_get_temp_dir() . '/aiven-ca.pem';
        if (!is_file($caFile)) {
            file_put_contents($caFile, $caPem);
        }
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
    } else {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    return new PDO($dsn, urldecode($parts['user']), urldecode($parts['pass']), $extraOptions + $options);
}

function pageHeader(string $title): void
{
    echo "<!doctype html><meta charset='utf-8'><title>{$title}</title>";
    echo "<style>body{font-family:ui-monospace,monospace;max-width:820px;margin:2rem auto;padding:0 1rem;line-height:1.5}
        pre{background:#f4f4f4;padding:1rem;overflow-x:auto}
        .ok{color:#0a7d38}.bad{color:#c0392b}a{color:#2456d6}</style>";
    echo "<h1>{$title}</h1><p><a href='/'>&larr; back</a></p>";
    flush_now();
}

function flush_now(): void
{
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

function fmtBytes(int $bytes): string
{
    return number_format($bytes / 1048576, 1) . ' MB';
}
