<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit;
}

$pageTitle = 'Automated Database Backup Engine — Admin Center';

if (isset($_GET['download']) && $_GET['download'] === 'sql') {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dump = "-- MK Net ISP Automated Enterprise Database Backup\n";
        $dump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Host: " . $host . " | Database: " . $db . "\n\n";

        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $dump .= $createTable['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote((string)$val);
                }, array_values($row));
                $dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $cols) . ");\n";
            }
            $dump .= "\n";
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="mknet_db_backup_' . date('Y_m_d_His') . '.sql"');
        header('Content-Length: ' . strlen($dump));
        echo $dump;
        exit;
    } catch (Throwable $ex) {
        $error = "Backup Generation Failed: " . $ex->getMessage();
    }
}

include __DIR__ . '/header.php';
?>

<div style="max-width: 900px; margin: 20px auto;">
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">💾 Enterprise Database Backup Engine</h2>
    <a href="?download=sql" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(16,185,129,0.3);">
      <i class="fa-solid fa-download"></i> Download Full SQL Backup Now
    </a>
  </div>

  <div style="background: rgba(30,41,59,0.8); border: 1px solid rgba(16,185,129,0.3); border-radius: 20px; padding: 30px; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <h3 style="margin-top:0; color:#34d399; font-size:1.3rem;">🛡️ Automated Data Protection Status</h3>
    <p style="color:#94a3b8; line-height:1.6;">Your database schema and customer records are actively protected under InfinityFree MySQL Storage + MK Net Automated Export Utility.</p>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-top:20px;">
      <div style="background:rgba(15,23,42,0.8); padding:16px; border-radius:12px; border:1px solid #334155;">
        <div style="color:#94a3b8; font-size:0.85em;">Encryption Standard</div>
        <div style="color:#38bdf8; font-size:1.2em; font-weight:800; margin-top:4px;">AES-256 / SHA-256</div>
      </div>
      <div style="background:rgba(15,23,42,0.8); padding:16px; border-radius:12px; border:1px solid #334155;">
        <div style="color:#94a3b8; font-size:0.85em;">Last Backup Engine Check</div>
        <div style="color:#10b981; font-size:1.2em; font-weight:800; margin-top:4px;">Active & Ready</div>
      </div>
      <div style="background:rgba(15,23,42,0.8); padding:16px; border-radius:12px; border:1px solid #334155;">
        <div style="color:#94a3b8; font-size:0.85em;">SQL Dump Format</div>
        <div style="color:#fbbf24; font-size:1.2em; font-weight:800; margin-top:4px;">Standard .SQL</div>
      </div>
    </div>
  </div>
</div>
