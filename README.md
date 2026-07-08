# PDO memory-exhaustion demo (cloud-only)

Reproduces — and fixes — the interview question:

> `Fatal error: Allowed memory size of 134217728 bytes exhausted` while looping
> over `$stmt->fetchAll(PDO::FETCH_ASSOC)` on `largeTable`.

Everything runs in the cloud: the PHP app on **Render** (free plan, built from
this repo on Render's servers) and MySQL on **Aiven** (free plan). Nothing runs
locally, no local Docker.

## The answer to the question

`fetchAll()` materializes every row of `largeTable` as a PHP array at once. On
top of that, PDO's MySQL driver uses **buffered queries by default**, so with
mysqlnd the entire result set is *also* copied into PHP-managed memory the
moment `execute()` returns — and that buffer counts against `memory_limit`.
Two full copies of a huge table never fit in 128M.

The refactor streams rows instead of materializing them:

```php
// 1. Turn off client-side buffering: rows now stream from the server.
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

// 2. Fetch one row at a time instead of fetchAll().
$stmt = $pdo->prepare('SELECT * FROM largeTable');
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // manipulate the data here
}
```

Memory use is now O(1 row) instead of O(table). Caveat of unbuffered mode: you
can't run other queries on the same connection until the result set is fully
read — open a second connection if the loop needs to write. An alternative
that keeps buffering is chunked pagination (`WHERE id > :last ORDER BY id
LIMIT 10000` in a loop), which also works across drivers.

## Deploy

1. **Aiven**: create a free MySQL service, copy its *Service URI*
   (`mysql://avnadmin:...@...aivencloud.com:PORT/defaultdb?ssl-mode=REQUIRED`).
2. **Render**: New → Blueprint → point at this repo. When prompted, set
   `DATABASE_URL` to the Aiven URI (and optionally `MYSQL_CA_PEM` to the
   contents of the Aiven project CA for verified TLS). Pick the Render region
   closest to the Aiven region so streaming 200k rows stays fast.
3. Open the service URL:
   - `/seed.php` — creates `largeTable` with 200,000 rows (generated inside
     MySQL via a recursive CTE, nothing shipped over the wire)
   - `/broken.php` — the verbatim snippet; dies with the exact fatal error
   - `/fixed.php` — same table, streams through in a few MB of peak memory
