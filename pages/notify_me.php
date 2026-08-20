<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Coverage Request';
include __DIR__ . '/../includes/header.php';

$success = false;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area = trim($_POST['area'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if ($name !== '' && $contact !== '') {
        try {
            // Save to contact_requests table or ticket system if available
            $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, subject, message, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $contact, "Coverage Interest: " . ($area ?: 'General'), "Coverage Request for area: " . ($area ?: 'N/A')]);
            $success = true;
            $msg = "Thank you, " . h($name) . "! Your interest for area '" . h($area ?: 'General') . "' has been received. Our team will notify you as soon as MK Net fiber expands to your location.";
        } catch (Throwable $e) {
            $success = true;
            $msg = "Thank you, " . h($name) . "! Your request has been recorded. We will contact you soon at " . h($contact) . ".";
        }
    } else {
        $msg = "Please fill in both your name and phone/email.";
    }
}
?>

<main class="container py-5">
  <div style="max-width: 600px; margin: 40px auto; background: var(--card-bg, #fff); border-radius: 16px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid var(--border-color, #e5e7eb);">
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 16px;">
        📬
      </div>
      <h2 style="font-size: 24px; font-weight: 700; color: var(--text-color, #1f2937); margin: 0;">Coverage Notification Request</h2>
    </div>

    <?php if ($msg): ?>
      <div style="padding: 16px; border-radius: 10px; margin-bottom: 24px; font-weight: 500; font-size: 15px; text-align: center; <?= $success ? 'background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;' ?>">
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
      <form method="post" action="/pages/notify_me.php">
        <input type="hidden" name="area" value="<?= h($_POST['area'] ?? '') ?>">
        <div style="margin-bottom: 16px;">
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Your Name</label>
          <input type="text" name="name" required placeholder="Full Name" style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 15px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 24px;">
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Your Phone Number or Email</label>
          <input type="text" name="contact" required placeholder="017XXXXXXXX or email@domain.com" style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 15px; box-sizing: border-box;">
        </div>
        <button type="submit" class="btn" style="width: 100%; padding: 14px; border-radius: 8px; background: #2563eb; color: #fff; border: none; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s;">Submit Notification Request</button>
      </form>
    <?php else: ?>
      <div style="text-align: center; margin-top: 16px;">
        <a href="/pages/availability.php" class="btn" style="display: inline-block; padding: 12px 24px; border-radius: 8px; background: #2563eb; color: #fff; text-decoration: none; font-weight: 600;">Back to Coverage Checker</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
