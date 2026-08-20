<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

$message = '';
$messageType = '';

// Handle AI Control Settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = "Session expired. Please try again.";
        $messageType = "error";
    } else {
        // Save Master AI Settings
        if (isset($_POST['save_ai_settings'])) {
            $aiEnabled = isset($_POST['ai_enabled']) ? '1' : '0';
            $autoListen = isset($_POST['auto_listen_default']) ? '1' : '0';
            $autoRedirect = isset($_POST['auto_redirect_enabled']) ? '1' : '0';
            $fallbackBn = trim($_POST['fallback_message_bn'] ?? '');
            $fallbackEn = trim($_POST['fallback_message_en'] ?? '');

            $pdo->prepare("INSERT INTO site_content (content_key, text_value) VALUES ('ai_enabled', ?) ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)")->execute([$aiEnabled]);
            $pdo->prepare("INSERT INTO site_content (content_key, text_value) VALUES ('auto_listen_default', ?) ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)")->execute([$autoListen]);
            $pdo->prepare("INSERT INTO site_content (content_key, text_value) VALUES ('auto_redirect_enabled', ?) ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)")->execute([$autoRedirect]);
            $pdo->prepare("INSERT INTO site_content (content_key, text_value) VALUES ('fallback_message_bn', ?) ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)")->execute([$fallbackBn]);
            $pdo->prepare("INSERT INTO site_content (content_key, text_value) VALUES ('fallback_message_en', ?) ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)")->execute([$fallbackEn]);

            $message = "AI Master Control Settings saved successfully!";
            $messageType = "success";
        }

        // Add Rule
        if (isset($_POST['add_rule'])) {
            $kw = trim($_POST['trigger_keywords'] ?? '');
            $reply = trim($_POST['bot_response'] ?? '');
            if ($kw !== '' && $reply !== '') {
                $pdo->prepare("INSERT INTO chatbot_rules (trigger_keywords, bot_response) VALUES (?, ?)")->execute([$kw, $reply]);
                $message = "New AI Knowledge Rule added!";
                $messageType = "success";
            }
        }

        // Delete Rule
        if (isset($_POST['delete_rule'])) {
            $ruleId = (int)$_POST['rule_id'];
            $pdo->prepare("DELETE FROM chatbot_rules WHERE id = ?")->execute([$ruleId]);
            $message = "Rule removed.";
            $messageType = "success";
        }
    }
}

// Fetch settings values
function getAiContent(PDO $pdo, string $key, string $default): string {
    try {
        $stmt = $pdo->prepare("SELECT text_value FROM site_content WHERE content_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn() ?: $default;
    } catch (Throwable $e) { return $default; }
}

$aiEnabled = getAiContent($pdo, 'ai_enabled', '1');
$autoListen = getAiContent($pdo, 'auto_listen_default', '0');
$autoRedirect = getAiContent($pdo, 'auto_redirect_enabled', '1');
$fallbackBn = getAiContent($pdo, 'fallback_message_bn', 'আমি আপনার প্রশ্নটি পেয়েছি। আমি এমকে নেটের ভার্চুয়াল এআই অ্যাসিস্ট্যান্ট! 🤖 আপনাকে কীভাবে সাহায্য করতে পারি?');
$fallbackEn = getAiContent($pdo, 'fallback_message_en', 'I received your message. As MK Net\'s AI Assistant, I\'m specialized in fiber broadband, packages, bill payments, and line support! 🤖');

// Fetch rules
$rules = $pdo->query("SELECT * FROM chatbot_rules ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="padding: 24px; max-width: 1200px; margin: 0 auto; color: #fff;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">🧠 Master AI Experience Layer Control Center</h2>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <!-- Master Controls Form -->
    <div class="card" style="background: rgba(30,41,59,0.8); backdrop-filter: blur(16px); border-radius: 20px; border: 1px solid rgba(99,102,241,0.3); padding: 32px; margin-bottom: 32px;">
        <h3 style="margin-top: 0; color: #818cf8; font-size: 1.3rem; font-weight: 800;"><i class="fa-solid fa-sliders"></i> Global AI Engine & Voice Controls</h3>
        
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="save_ai_settings" value="1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <label style="background: rgba(15,23,42,0.8); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="ai_enabled" value="1" <?= $aiEnabled==='1'?'checked':'' ?> style="width: 20px; height: 20px; accent-color: #6366f1;">
                    <div>
                        <strong style="color: #fff; display: block;">Enable Global AI Assistant</strong>
                        <span style="color: #94a3b8; font-size: 0.8rem;">Master switch for chatbot & voice widget</span>
                    </div>
                </label>

                <label style="background: rgba(15,23,42,0.8); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="auto_listen_default" value="1" <?= $autoListen==='1'?'checked':'' ?> style="width: 20px; height: 20px; accent-color: #10b981;">
                    <div>
                        <strong style="color: #fff; display: block;">Auto-Listen Hands-Free Mode</strong>
                        <span style="color: #94a3b8; font-size: 0.8rem;">Automatically listens after TTS speech</span>
                    </div>
                </label>

                <label style="background: rgba(15,23,42,0.8); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="auto_redirect_enabled" value="1" <?= $autoRedirect==='1'?'checked':'' ?> style="width: 20px; height: 20px; accent-color: #ec4899;">
                    <div>
                        <strong style="color: #fff; display: block;">Automated Action Redirection</strong>
                        <span style="color: #94a3b8; font-size: 0.8rem;">Auto-redirects to Pay Bill / Packages / Tickets</span>
                    </div>
                </label>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Bengali Fallback Response Prompt</label>
                <textarea name="fallback_message_bn" rows="2" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; font-family: inherit;"><?= h($fallbackBn) ?></textarea>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">English Fallback Response Prompt</label>
                <textarea name="fallback_message_en" rows="2" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; font-family: inherit;"><?= h($fallbackEn) ?></textarea>
            </div>

            <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 12px 30px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(99,102,241,0.4);">
                Save Master AI Controls
            </button>
        </form>
    </div>

    <!-- AI Knowledge Rules Management Table -->
    <div class="card" style="background: rgba(30,41,59,0.8); backdrop-filter: blur(16px); border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); padding: 32px;">
        <h3 style="margin-top: 0; color: #34d399; font-size: 1.3rem; font-weight: 800;"><i class="fa-solid fa-graduation-cap"></i> AI Knowledge Base Training Rules</h3>
        
        <form method="post" style="margin-bottom: 24px; background: rgba(15,23,42,0.6); padding: 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.1);">
            <?= csrf_field() ?>
            <input type="hidden" name="add_rule" value="1">
            <h4 style="margin-top: 0; color: #a5b4fc;">Add New Custom Intent Rule</h4>
            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 12px; align-items: center;">
                <input type="text" name="trigger_keywords" placeholder="Trigger Keywords (e.g. ftp, movie)" required style="padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                <input type="text" name="bot_response" placeholder="Bot Response text" required style="padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                <button type="submit" style="background: #10b981; color: #fff; padding: 12px 20px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">+ Add Rule</button>
            </div>
        </form>

        <table class="table" style="width: 100%; border-collapse: collapse; color: #fff;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                    <th style="padding: 12px;">Trigger Keyword</th>
                    <th style="padding: 12px;">AI Bot Response</th>
                    <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="3" style="padding: 16px; color: #94a3b8;">No custom training rules found. Add one above!</td></tr>
                <?php else: foreach ($rules as $r): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 12px; font-weight: 700; color: #38bdf8;"><?= h($r['trigger_keywords']) ?></td>
                        <td style="padding: 12px; color: #cbd5e1;"><?= h($r['bot_response']) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this AI rule?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="delete_rule" value="1">
                                <input type="hidden" name="rule_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #f87171; padding: 6px 12px; border-radius: 8px; font-weight: 700; cursor: pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
