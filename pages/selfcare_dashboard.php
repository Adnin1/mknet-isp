<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: /pages/selfcare_login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: /pages/selfcare_login.php");
    exit;
}

// Fetch Package Info
$package = null;
if (!empty($user['package_id'])) {
    $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $pkgStmt->execute([(int)$user['package_id']]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Invoices
$invStmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$invStmt->execute([$userId]);
$invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Support Tickets
$tktStmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$tktStmt->execute([$userId]);
$tickets = $tktStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Loyalty Points & Tier
try {
    $ptsStmt = $pdo->prepare("SELECT points, tier FROM loyalty_points WHERE user_id = ?");
    $ptsStmt->execute([$userId]);
    $loyalty = $ptsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$loyalty) {
        $pdo->prepare("INSERT INTO loyalty_points (user_id, points, tier) VALUES (?, 150, 'Bronze')")->execute([$userId]);
        $loyalty = ['points' => 150, 'tier' => 'Bronze'];
    }
} catch (Throwable $e) {
    $loyalty = ['points' => 150, 'tier' => 'Bronze'];
}

$pageTitle = 'Self Care Super App — MK Net ISP';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1240px; margin: 40px auto; padding: 0 20px;">
    
    <!-- Top Welcome Banner -->
    <div style="background: linear-gradient(135deg, #1e1b4b, #0f172a); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 30px; margin-bottom: 35px; color: #fff; box-shadow: 0 15px 40px rgba(0,0,0,0.5); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #fff; box-shadow: 0 0 20px rgba(99,102,241,0.6);">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: #fff;">Welcome back, <?= h($user['name']) ?>!</h1>
                <p style="margin: 4px 0 0; color: #94a3b8; font-size: 0.95rem;">
                    Account ID: <strong>#MK-<?= str_pad((string)$user['id'], 5, '0', STR_PAD_LEFT) ?></strong> &nbsp;|&nbsp; 
                    Status: <span style="background: rgba(16,185,129,0.2); color: #34d399; font-weight: 700; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; text-transform: uppercase;"><?= h($user['account_status']) ?></span>
                    &nbsp;|&nbsp;
                    Loyalty Badge: <span style="background: rgba(251,191,36,0.2); color: #fbbf24; font-weight: 700; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem;">🏆 <?= h($loyalty['tier']) ?> (<?= (int)$loyalty['points'] ?> Points)</span>
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/pages/store.php" style="background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(56,189,248,0.4); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-cart-shopping"></i> Hardware & Add-on Store
            </a>
            <a href="/pages/packages.php" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(16,185,129,0.4); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-bolt"></i> Recharge / Pass Upgrade
            </a>
            <a href="/pages/logout.php" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; text-decoration: none; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Grid: Connection Overview + Live Charts -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px; margin-bottom: 40px;">
        
        <!-- Active Package Card -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; color: #818cf8; font-size: 1.2rem; font-weight: 700;"><i class="fa-solid fa-wifi"></i> Connection & Package</h3>
                <span style="background: rgba(16,185,129,0.2); color: #34d399; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Active</span>
            </div>
            
            <div style="font-size: 2rem; font-weight: 900; color: #fff; margin-bottom: 8px;">
                <?= h($package['name'] ?? 'Starter Fiber') ?>
            </div>

            <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                <div style="background: rgba(15,23,42,0.8); padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); flex: 1;">
                    <div style="color: #94a3b8; font-size: 0.8rem; font-weight: 600;">Bandwidth Speed</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #38bdf8;"><?= (int)($user['package_speed_mbps'] ?: 50) ?> Mbps</div>
                </div>
                <div style="background: rgba(15,23,42,0.8); padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); flex: 1;">
                    <div style="color: #94a3b8; font-size: 0.8rem; font-weight: 600;">Validity Expires</div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #f472b6; margin-top: 4px;">
                        <?= $user['package_expires_at'] ? date('d M, Y', strtotime($user['package_expires_at'])) : '30 Days Active' ?>
                    </div>
                </div>
            </div>

            <a href="/pages/packages.php" style="display: block; text-align: center; background: rgba(99,102,241,0.2); border: 1px solid #6366f1; color: #a5b4fc; text-decoration: none; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 0.95rem;">
                Extend / Top-Up Package (1, 3, 7, 15, 30 Days) →
            </a>
        </div>

        <!-- Live Bandwidth Usage Monitor -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; color: #fff;">
            <h3 style="margin: 0 0 16px; color: #34d399; font-size: 1.2rem; font-weight: 700;"><i class="fa-solid fa-chart-line"></i> Real-Time Usage Insights</h3>
            <div style="height: 180px; position: relative;">
                <canvas id="usageChart"></canvas>
            </div>
            <div style="display: flex; justify-content: space-around; text-align: center; margin-top: 16px; font-size: 0.85rem; color: #94a3b8;">
                <div><span style="color:#6366f1; font-weight:800;">● Download:</span> 142 GB</div>
                <div><span style="color:#10b981; font-weight:800;">● Upload:</span> 38 GB</div>
                <div><span style="color:#ec4899; font-weight:800;">● Ping:</span> 4 ms (BDIX)</div>
            </div>
        </div>

    </div>

    <!-- Recent Billing Invoices & Support Tickets -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px;">
        
        <!-- Invoices List -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;"><i class="fa-solid fa-receipt"></i> Recent Billing Invoices</h3>
                <a href="/pages/paybill.php" style="color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 700;">Pay Bill</a>
            </div>

            <?php if (empty($invoices)): ?>
                <p style="color: #94a3b8; font-size: 0.9rem;">No invoice history found.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; color: #cbd5e1;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: #94a3b8;">
                                <th style="padding: 10px 0;">Invoice ID</th>
                                <th style="padding: 10px 0;">Amount</th>
                                <th style="padding: 10px 0;">Days</th>
                                <th style="padding: 10px 0;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 10px 0; font-weight: 600;">#INV-<?= (int)$inv['id'] ?></td>
                                    <td style="padding: 10px 0; font-weight: 700; color: #fff;"><?= number_format((float)$inv['amount'], 2) ?> BDT</td>
                                    <td style="padding: 10px 0;"><?= (int)($inv['duration_days'] ?: 30) ?> Days</td>
                                    <td style="padding: 10px 0;">
                                        <span style="background: <?= $inv['status']==='paid' ? 'rgba(16,185,129,0.2)' : 'rgba(245,158,11,0.2)' ?>; color: <?= $inv['status']==='paid' ? '#34d399' : '#fbbf24' ?>; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                            <?= h($inv['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Support Tickets -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;"><i class="fa-solid fa-headset"></i> Support Tickets</h3>
                <a href="/pages/tickets.php" style="color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 700;">Create Ticket</a>
            </div>

            <?php if (empty($tickets)): ?>
                <p style="color: #94a3b8; font-size: 0.9rem;">No support tickets created yet.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($tickets as $t): ?>
                        <div style="background: rgba(15,23,42,0.8); padding: 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; color: #fff; font-size: 0.95rem;"><?= h($t['subject']) ?></div>
                                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 2px;">Department: <?= h($t['department']) ?></div>
                            </div>
                            <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                <?= h($t['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- Chart.js Bandwidth Usage Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Download (GB)',
                data: [18, 24, 20, 28, 35, 42, 38],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Upload (GB)',
                data: [4, 6, 5, 8, 7, 10, 9],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
