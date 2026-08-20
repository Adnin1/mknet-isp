<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$log = [];

// Only ever executes .sql files that already exist inside this codebase's
// migrations/ folder - never anything uploaded through this form. That's
// the entire safety model: the "update package" is the vetted release you
// deployed via FTP, not arbitrary input from a web request.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_migrations'])) {
    if (!csrf_verify()) {
        $log[] = ['error', 'Session expired, please try again.'];
    } else {
        $migrationsDir = __DIR__ . '/../migrations';
        $applied = $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) continue;

            $sql = file_get_contents($file);
            try {
                $pdo->exec($sql);
                $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)")->execute([$name]);
                $log[] = ['ok', "Applied: $name"];
                app_log('system_updates', "Migration applied: $name");
            } catch (Throwable $ex) {
                $log[] = ['error', "$name failed: " . $ex->getMessage()];
                app_log('system_updates', "Migration FAILED: $name — " . $ex->getMessage());
                break; // stop on first failure rather than risk running things out of order
            }
        }
        if (empty($log)) $log[] = ['ok', 'Everything is already up to date.'];
    }
}

$appliedRows = $pdo->query("SELECT filename, applied_at FROM schema_migrations ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
$allFiles = glob(__DIR__ . '/../migrations/*.sql');
sort($allFiles);
$pending = array_filter($allFiles, fn($f) => !isset($appliedRows[basename($f)]));

$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
?>
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>System Updates</h2>
  <p class="small-note">This only ever runs SQL files that already ship inside <code>migrations/</code> in your deployed codebase — it never accepts a file upload or arbitrary code, by design, since that would be a serious security risk.</p>

  <?php foreach ($log as [$type, $msg]): ?>
    <p style="color:<?= $type === 'ok' ? '#0a6' : '#b00020' ?>;"><?= $type === 'ok' ? '✅' : '❌' ?> <?= h($msg) ?></p>
  <?php endforeach; ?>

  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>System Info</h3>
    <p>PHP: <?= h(PHP_VERSION) ?> &nbsp; | &nbsp; MySQL/MariaDB: <?= h($mysqlVersion) ?> &nbsp; | &nbsp; APP_DEBUG: <?= APP_DEBUG ? '⚠️ ON (turn off before launch)' : '✅ OFF' ?> &nbsp; | &nbsp; Dev mode: <?= Services::isDevMode() ? '⚠️ ON' : '✅ OFF' ?></p>
  </div>

  <div class="card" style="padding:16px;">
    <h3>Database Migrations</h3>
    <p><?= count($appliedRows) ?> applied, <?= count($pending) ?> pending.</p>

    <?php if (!empty($pending)): ?>
      <form method="post">
        <?= csrf_field() ?>
        <button type="submit" name="apply_migrations" class="btn primary" onclick="return confirm('Apply all pending migrations now? Make sure you have a fresh database backup first.')">⬇️ Apply <?= count($pending) ?> Pending Update(s)</button>
      </form>
    <?php else: ?>
      <p style="color:#0a6;">✅ Database is fully up to date.</p>
    <?php endif; ?>

    <table style="margin-top:14px;">
      <tr><th>File</th><th>Status</th><th>Applied At</th></tr>
      <?php foreach ($allFiles as $f): $name = basename($f); ?>
      <tr>
        <td><?= h($name) ?></td>
        <td><?= isset($appliedRows[$name]) ? '✅ Applied' : '⏳ Pending' ?></td>
        <td><?= h($appliedRows[$name] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card" style="padding:16px;margin-top:16px;">
    <h3>How future updates work</h3>
    <ol>
      <li>You'll receive a new release zip (like this one) with new/changed PHP files and, if needed, a new dated file in <code>migrations/</code>.</li>
      <li>Upload the files via FTP/SSH as usual (see <code>docs/DEPLOYMENT_GUIDE.md</code>) — always after a fresh backup.</li>
      <li>Come back to this page and click "Apply Pending Updates" — the database side is now one click, tracked, and safe to re-run.</li>
    </ol>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
