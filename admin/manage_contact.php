<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

$message = '';

if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $pdo->prepare("UPDATE contact_submissions SET status = 'read' WHERE id = ?")->execute([$id]);
    $message = "Marked as read.";
}

if (isset($_GET['delete'])) {
    if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM contact_submissions WHERE id = ?")->execute([$id]);
    $message = "Submission deleted.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes'])) {
    if (csrf_verify()) {
        $id = (int)$_POST['id'];
        $notes = trim($_POST['admin_notes'] ?? '');
        $status = $_POST['status'] ?? 'read';

        $pdo->prepare("UPDATE contact_submissions SET admin_notes = ?, status = ? WHERE id = ?")->execute([$notes, $status, $id]);
        $message = "Saved notes and status.";
    }
}

$submissions = $pdo->query("SELECT * FROM contact_submissions ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">✉️ Contact Submissions Manager</h2>
    </div>

    <?php if ($message): ?>
        <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #6ee7b7;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
        <h3 style="margin-top: 0; color: #38bdf8;">Messages from Contact Us Form</h3>

        <?php if (empty($submissions)): ?>
            <p style="color: #94a3b8;">No contact submissions received yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px; margin-top: 16px;">
                <?php foreach ($submissions as $sub): ?>
                    <div style="background: rgba(15,23,42,0.8); border: 1px solid <?= $sub['status']==='unread' ? 'rgba(99,102,241,0.5)' : 'rgba(255,255,255,0.08)' ?>; border-radius: 14px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <h4 style="margin: 0; font-size: 1.1rem; color: #fff;"><?= h($sub['subject']) ?></h4>
                                <div style="color: #94a3b8; font-size: 0.85rem; margin-top: 4px;">
                                    From: <strong style="color:#818cf8;"><?= h($sub['name']) ?></strong> (<?= h($sub['email']) ?> | <?= h($sub['phone'] ?: 'N/A') ?>) &nbsp;·&nbsp; <?= h($sub['created_at']) ?>
                                </div>
                            </div>
                            <div>
                                <span style="background: <?= $sub['status']==='unread'?'rgba(245,158,11,0.2)':'rgba(16,185,129,0.2)' ?>; color: <?= $sub['status']==='unread'?'#fbbf24':'#34d399' ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                    <?= h($sub['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div style="background: rgba(30,41,59,0.5); padding: 14px; border-radius: 8px; color: #e2e8f0; font-size: 0.95rem; line-height: 1.6; margin-bottom: 14px;">
                            <?= nl2br(h($sub['message'])) ?>
                        </div>

                        <form method="post" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
                            <input type="hidden" name="update_notes" value="1">
                            <input type="text" name="admin_notes" placeholder="Add admin internal note..." value="<?= h($sub['admin_notes'] ?? '') ?>" style="flex: 1; padding: 8px 12px; border-radius: 6px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.85rem;">
                            <select name="status" style="padding: 8px; border-radius: 6px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.85rem;">
                                <option value="unread" <?= $sub['status']==='unread'?'selected':'' ?>>Unread</option>
                                <option value="read" <?= $sub['status']==='read'?'selected':'' ?>>Read</option>
                                <option value="replied" <?= $sub['status']==='replied'?'selected':'' ?>>Replied</option>
                            </select>
                            <button type="submit" style="background: #6366f1; color: #fff; padding: 8px 16px; border-radius: 6px; border: none; font-weight: 700; cursor: pointer;">Save</button>
                            <a href="?delete=<?= (int)$sub['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete message?')" style="color: #fca5a5; font-size: 0.85rem; text-decoration: none; margin-left: 8px;">Delete</a>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
