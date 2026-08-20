<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_item'])) {
    if (!csrf_verify()) {
        $message = "Session expired. Please try again.";
        $messageType = "error";
    } else {
        $itemName = trim($_POST['item_name'] ?? 'Store Item');
        $itemPrice = (float)($_POST['item_price'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($userId > 0 && $itemPrice > 0) {
            // Generate invoice for store item
            $stmt = $pdo->prepare("INSERT INTO invoices (user_id, amount, status, meta, due_date) VALUES (?, ?, 'pending', ?, DATE_ADD(NOW(), INTERVAL 3 DAY))");
            $stmt->execute([$userId, $itemPrice, json_encode(['item' => $itemName, 'type' => 'store_order'])]);
            $invId = (int)$pdo->lastInsertId();

            header("Location: /pages/payment_redirect.php?invoice_id=" . $invId);
            exit;
        } else {
            $message = "Please log in to your Self Care account to place an order.";
            $messageType = "error";
        }
    }
}

$pageTitle = 'Hardware & Add-on Store — MK Net ISP';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <span style="background: rgba(99,102,241,0.2); border: 1px solid rgba(129,140,248,0.4); color: #a5b4fc; font-weight: 800; font-size: 0.85rem; padding: 6px 18px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1.5px;">
            🛒 MK Net Official Store
        </span>
        <h1 style="font-size: 2.6rem; font-weight: 900; color: #fff; margin: 12px 0 8px;">Hardware, Routers & Digital Add-ons</h1>
        <p style="color: #94a3b8; font-size: 1.1rem; max-width: 650px; margin: 0 auto;">High-performance Wi-Fi 6 routers, optical ONUs, Static IP addresses, and low-latency gaming boosters.</p>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 12px; margin-bottom: 30px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: #fff;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px;">
        
        <!-- Item 1: Wi-Fi 6 Router -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; margin-bottom: 12px;">📡</div>
                <h3 style="color: #fff; font-size: 1.4rem; font-weight: 800; margin: 0 0 8px;">Archer AX12 Wi-Fi 6 Dual-Band Router</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">Gigabit ports, OFDMA technology, 1500 Mbps dual-band wireless speed for smooth 4K streaming.</p>
                <div style="font-size: 2rem; font-weight: 900; color: #10b981; margin-bottom: 20px;">3,200 <span style="font-size: 1rem; color: #94a3b8;">BDT</span></div>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="buy_item" value="1">
                <input type="hidden" name="item_name" value="Archer AX12 Wi-Fi 6 Router">
                <input type="hidden" name="item_price" value="3200">
                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">Buy Now →</button>
            </form>
        </div>

        <!-- Item 2: Optical ONU -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; margin-bottom: 12px;">⚡</div>
                <h3 style="color: #fff; font-size: 1.4rem; font-weight: 800; margin: 0 0 8px;">XPON Optical Network Unit (ONU)</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">High-speed fiber terminal, EPON/GPON compatible, ultra-durable heat dissipation chassis.</p>
                <div style="font-size: 2rem; font-weight: 900; color: #10b981; margin-bottom: 20px;">1,350 <span style="font-size: 1rem; color: #94a3b8;">BDT</span></div>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="buy_item" value="1">
                <input type="hidden" name="item_name" value="XPON Optical Fiber ONU">
                <input type="hidden" name="item_price" value="1350">
                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">Buy Now →</button>
            </form>
        </div>

        <!-- Item 3: Static IP Address -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; margin-bottom: 12px;">🌐</div>
                <h3 style="color: #fff; font-size: 1.4rem; font-weight: 800; margin: 0 0 8px;">Dedicated Real Static IP</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">Public static IP for CC Camera remote viewing, self-hosted web servers, and port forwarding.</p>
                <div style="font-size: 2rem; font-weight: 900; color: #10b981; margin-bottom: 20px;">500 <span style="font-size: 1rem; color: #94a3b8;">BDT / mo</span></div>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="buy_item" value="1">
                <input type="hidden" name="item_name" value="Dedicated Real Static IP">
                <input type="hidden" name="item_price" value="500">
                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">Subscribe Static IP →</button>
            </form>
        </div>

        <!-- Item 4: Gaming Priority Booster -->
        <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; margin-bottom: 12px;">🎮</div>
                <h3 style="color: #fff; font-size: 1.4rem; font-weight: 800; margin: 0 0 8px;">Low-Latency Gaming Route Pass</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">Direct routing to Singapore & Frankfurt game servers for PUBG, Valorant, and CS2.</p>
                <div style="font-size: 2rem; font-weight: 900; color: #10b981; margin-bottom: 20px;">400 <span style="font-size: 1rem; color: #94a3b8;">BDT / mo</span></div>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="buy_item" value="1">
                <input type="hidden" name="item_name" value="Low Latency Gaming Route Pass">
                <input type="hidden" name="item_price" value="400">
                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #fff; padding: 12px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">Activate Pass →</button>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
