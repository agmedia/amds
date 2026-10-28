# Luceed inventory sync

Run the offline regressions with `php tests/luceed/run.php`. The suite replaces
database models and the upstream HTTP transport with fixtures; it never loads
real credentials, changes the shop database, or calls Luceed. It covers rejected
upstream responses, first-match lookup semantics, staging cleanup, transactional
failure handling, all-zero snapshots, overlapping inventory routes, and CLI exit
statuses/logs (including premature PHP exits).

The server cron command is:

```sh
/opt/cpanel/ea-php74/root/usr/bin/php /home/amds/public_html/bin/luceed-sync.php
```

First run the same command with `--check` to verify PHP extensions, configuration,
database access and log permissions without changing stock. The runner requires
PHP 7.3+, cURL, mysqli and PDO MySQL. It finds `upload/admin/config.php` relative to
the checkout; use `--admin-dir=/absolute/path/to/admin` for another layout.

Completion is recorded in `storage/logs/luceed_sync_cli.log`. A successful
completed sync exits 0 and prints the JSON result. An upstream/database failure,
an overlapping job, or an invalid result exits 1. Keep cron error notification
enabled. The existing HTTP endpoint remains available, but this server CLI
command avoids the web server's approximately 30-second response timeout.

Migration `32_convert_luceed_sync_tables_to_innodb.sql` must already be applied
for stock and option updates to roll back together. Migration 31 adds the option
SKU index used by the synchronization join.
