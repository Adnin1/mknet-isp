<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

$message = '';
$messageType = '';

// Ensure settings table has all columns
try {
    $sCols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('hotline', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN hotline VARCHAR(50) DEFAULT '16335 / 09678-123123'");
    if (!in_array('bkash_number', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN bkash_number VARCHAR(30) DEFAULT '01700000000'");
    if (!in_array('nagad_number', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN nagad_number VARCHAR(30) DEFAULT '01700000000'");
    if (!in_array('active_theme', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN active_theme VARCHAR(50) DEFAULT 'cyber_dark'");
    if (!in_array('custom_css', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN custom_css TEXT NULL");
    if (!in_array('logo_path', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN logo_path VARCHAR(255) NULL");
    if (!in_array('favicon_path', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN favicon_path VARCHAR(255) NULL");
    if (!in_array('address', $sCols)) $pdo->exec("ALTER TABLE settings ADD COLUMN address VARCHAR(255) DEFAULT 'Police Plaza Concord, Gulshan 1, Dhaka'");
} catch (Throwable $t) {}

// Fetch settings row
$settings = $pdo->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $pdo->exec("INSERT INTO settings (site_name, hotline, email, address, bkash_number, nagad_number) VALUES ('MK Net ISP', '16335 / 09678-123123', 'support@mknet.com', 'Police Plaza Concord, Gulshan 1, Dhaka', '01700000000', '01700000000')");
    $settings = $pdo->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!csrf_verify()) {
        $message = "CSRF verification failed.";
        $messageType = "error";
    } else {
        $siteName = trim($_POST['site_name'] ?? 'MK Net ISP');
        $hotline = trim($_POST['hotline'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bkash = trim($_POST['bkash_number'] ?? '');
        $nagad = trim($_POST['nagad_number'] ?? '');
        $theme = $_POST['active_theme'] ?? 'cyber_dark';
        $customCss = $_POST['custom_css'] ?? '';

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Logo Upload
        $logoPath = $settings['logo_path'] ?? '';
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                $targetFile = 'uploads/logo_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../' . $targetFile)) {
                    $logoPath = $targetFile;
                }
            }
        }

        // Favicon Upload
        $faviconPath = $settings['favicon_path'] ?? '';
        if (!empty($_FILES['favicon']['name'])) {
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'ico', 'svg'])) {
                $targetFile = 'uploads/favicon_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], __DIR__ . '/../' . $targetFile)) {
                    $faviconPath = $targetFile;
                }
            }
        }

        // Custom Theme CSS File Upload
        if (!empty($_FILES['theme_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['theme_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'css') {
                $cssContent = file_get_contents($_FILES['theme_file']['tmp_name']);
                if ($cssContent !== false) {
                    $customCss .= "\n/* Uploaded Custom Theme */\n" . $cssContent;
                }
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE settings SET site_name=?, hotline=?, email=?, address=?, bkash_number=?, nagad_number=?, active_theme=?, custom_css=?, logo_path=?, favicon_path=? WHERE id=?"
        );
        $stmt->execute([$siteName, $hotline, $email, $address, $bkash, $nagad, $theme, $customCss, $logoPath, $faviconPath, (int)$settings['id']]);

        $message = "Site Settings & Theme configuration saved successfully!";
        $messageType = "success";

        // Refresh settings
        $settings = $pdo->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
}

include __DIR__ . '/header.php';
?>

<div class="main-content" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">⚙️ Site Settings & Theme Engine</h2>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="save_settings" value="1">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px;">
            
            <!-- Company & Hotline Settings -->
            <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
                <h3 style="margin-top: 0; color: #38bdf8;">🏢 General & Contact Info</h3>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Site Name</label>
                    <input type="text" name="site_name" value="<?= h($settings['site_name'] ?? 'MK Net ISP') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">24/7 Hotline Number(s)</label>
                    <input type="text" name="hotline" value="<?= h($settings['hotline'] ?? '16335 / 09678-123123') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Support Email Address</label>
                    <input type="email" name="email" value="<?= h($settings['email'] ?? 'support@mknet.com') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Headquarter Office Address</label>
                    <input type="text" name="address" value="<?= h($settings['address'] ?? 'Police Plaza Concord, Gulshan 1, Dhaka') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">bKash Merchant #</label>
                        <input type="text" name="bkash_number" value="<?= h($settings['bkash_number'] ?? '01700000000') ?>" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Nagad Merchant #</label>
                        <input type="text" name="nagad_number" value="<?= h($settings['nagad_number'] ?? '01700000000') ?>" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <!-- Theme Preset & Assets Uploader -->
            <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
                <h3 style="margin-top: 0; color: #a5b4fc;">🎨 Theme Preset & Brand Assets</h3>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Active Color Theme Preset</label>
                    <select name="active_theme" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                        <option value="cyber_dark" <?= ($settings['active_theme']??'')==='cyber_dark'?'selected':'' ?>>🌌 Cyber Dark (Navy & Violet Glass - Default)</option>
                        <option value="neon_fiber" <?= ($settings['active_theme']??'')==='neon_fiber'?'selected':'' ?>>⚡ Neon Fiber (Cyan & Emerald Laser)</option>
                        <option value="midnight_emerald" <?= ($settings['active_theme']??'')==='midnight_emerald'?'selected':'' ?>>🌲 Midnight Emerald (Dark Mint Glass)</option>
                        <option value="royal_purple" <?= ($settings['active_theme']??'')==='royal_purple'?'selected':'' ?>>👑 Royal Purple (Deep Amethyst & Gold Glow)</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Upload Custom Brand Logo (PNG/SVG)</label>
                    <input type="file" name="logo" accept="image/*" style="width: 100%; color: #94a3b8;">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="/<?= h($settings['logo_path']) ?>" alt="Logo" style="max-height: 40px; border-radius: 6px; background: rgba(0,0,0,0.4); padding: 4px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Upload Favicon (ICO/PNG)</label>
                    <input type="file" name="favicon" accept="image/*" style="width: 100%; color: #94a3b8;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Upload Custom Theme (.CSS file)</label>
                    <input type="file" name="theme_file" accept=".css" style="width: 100%; color: #94a3b8;">
                    <span style="color: #94a3b8; font-size: 0.8rem;">Upload any `.css` theme file to inject directly into the website stylesheet.</span>
                </div>
            </div>

        </div>

        <!-- Custom CSS Live Code Editor -->
        <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 24px;">
            <h3 style="margin-top: 0; color: #34d399;">💻 Custom CSS Live Style Overrides</h3>
            <p style="color: #94a3b8; font-size: 0.9rem;">Write or edit raw CSS rules here to customize font sizes, background colors, button gradients, and glassmorphism cards in real-time.</p>
            <textarea name="custom_css" rows="8" style="width: 100%; padding: 14px; border-radius: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.15); color: #38bdf8; font-family: monospace; font-size: 0.9rem; line-height: 1.5; box-sizing: border-box;"><?= h($settings['custom_css'] ?? '') ?></textarea>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 14px 28px; border-radius: 12px; font-weight: 800; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(99,102,241,0.4);">
            💾 Save All Site Settings & Apply Theme Changes →
        </button>

    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
