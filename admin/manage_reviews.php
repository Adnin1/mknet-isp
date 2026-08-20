<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = '';
$messageType = '';

// Approve / Reject
if (isset($_GET['approve']) && ctype_digit((string)$_GET['approve'])) {
    if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    $message = 'Review approved & published!';
    $messageType = 'success';
}

if (isset($_GET['reject']) && ctype_digit((string)$_GET['reject'])) {
    if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject']]);
    $message = 'Review rejected.';
    $messageType = 'success';
}

// Fetch reviews using LEFT JOIN to safely handle general reviews
$pending = $pdo->query(
    "SELECT r.*, COALESCE(p.name, 'General ISP Service') as package_name 
     FROM reviews r 
     LEFT JOIN packages p ON p.id=r.package_id 
     WHERE r.status='pending' 
     ORDER BY r.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$approved = $pdo->query(
    "SELECT r.*, COALESCE(p.name, 'General ISP Service') as package_name 
     FROM reviews r 
     LEFT JOIN packages p ON p.id=r.package_id 
     WHERE r.status='approved' 
     ORDER BY r.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">⭐ Manage Customer Testimonials & Reviews</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Pending Reviews -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #fbbf24;">⏳ Pending Customer Reviews (<?= count($pending) ?>)</h3>
    
    <?php if (empty($pending)): ?>
        <p style="color: #94a3b8; margin: 0;">No pending reviews awaiting approval.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Customer</th>
                        <th style="padding: 12px;">Rating</th>
                        <th style="padding: 12px;">Package / Subject</th>
                        <th style="padding: 12px;">Comment</th>
                        <th style="padding: 12px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $r): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px; font-weight: 600;">#<?= (int)$r['id'] ?></td>
                            <td style="padding: 12px; font-weight: 700; color: #fff;"><?= h($r['user_name']) ?></td>
                            <td style="padding: 12px; color: #fbbf24; font-weight: 800;"><?= str_repeat('⭐', (int)$r['rating']) ?></td>
                            <td style="padding: 12px; color: #a5b4fc;"><?= h($r['package_name']) ?></td>
                            <td style="padding: 12px; color: #cbd5e1;"><?= h($r['comment']) ?></td>
                            <td style="padding: 12px; text-align: right;">
                                <a href="?approve=<?= (int)$r['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" style="background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #6ee7b7; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none; margin-right: 6px;">Approve</a>
                                <a href="?reject=<?= (int)$r['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none;">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Approved Reviews -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">✅ Published Customer Testimonials</h3>
    
    <?php if (empty($approved)): ?>
        <p style="color: #94a3b8; margin: 0;">No approved reviews yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Customer</th>
                        <th style="padding: 12px;">Rating</th>
                        <th style="padding: 12px;">Package / Subject</th>
                        <th style="padding: 12px;">Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved as $r): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px; font-weight: 600;">#<?= (int)$r['id'] ?></td>
                            <td style="padding: 12px; font-weight: 700; color: #fff;"><?= h($r['user_name']) ?></td>
                            <td style="padding: 12px; color: #fbbf24; font-weight: 800;"><?= str_repeat('⭐', (int)$r['rating']) ?></td>
                            <td style="padding: 12px; color: #a5b4fc;"><?= h($r['package_name']) ?></td>
                            <td style="padding: 12px; color: #cbd5e1;"><?= h($r['comment']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
