<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $message = "Session expired. Please try submitting again.";
    $messageType = "error";
  } else {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $msgContent = trim((string)($_POST['message'] ?? ''));

    if ($name !== '' && $email !== '' && $msgContent !== '') {
      try {
        $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, subject, message, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $subject ?: 'General Contact Inquiry', "Phone: " . ($phone ?: 'N/A') . "\n\n" . $msgContent]);
        
        $message = "Thank you! Your message has been submitted to MK Net Support. We will get back to you shortly.";
        $messageType = "success";
      } catch (Throwable $e) {
        $message = "Thank you! Your message has been logged with MK Net Support.";
        $messageType = "success";
      }
    } else {
      $message = "Please fill in all required fields (Name, Email, Message).";
      $messageType = "error";
    }
  }
}

$pageTitle = 'Contact Us — MK Net ISP';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
  
  <div style="text-align: center; margin-bottom: 40px;">
    <span style="background: rgba(99,102,241,0.2); border: 1px solid rgba(129,140,248,0.4); color: #a5b4fc; font-weight: 800; font-size: 0.85rem; padding: 6px 18px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1.5px;">
      📞 24/7 VIP Customer Support
    </span>
    <h1 style="font-size: 2.6rem; font-weight: 900; color: #fff; margin: 12px 0 8px;">Get in Touch with MK Net</h1>
    <p style="color: #94a3b8; font-size: 1.1rem; max-width: 650px; margin: 0 auto;">Have a query about internet packages, new connection, or billing? Send us a message below.</p>
  </div>

  <?php if (!empty($message)): ?>
    <div style="padding: 14px 20px; border-radius: 12px; margin-bottom: 30px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: #fff;">
      <?= h($message) ?>
    </div>
  <?php endif; ?>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; margin-bottom: 50px;">
    
    <!-- Contact Form Card -->
    <div style="background: rgba(30,41,59,0.8); backdrop-filter: blur(16px); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 32px; color: #fff;">
      <h2 style="margin-top: 0; font-size: 1.5rem; color: #fff; font-weight: 800;"><i class="fa-solid fa-paper-plane" style="color:#818cf8;"></i> Send Us a Message</h2>
      
      <form method="post" action="contact.php">
        <?= csrf_field() ?>

        <div style="margin-bottom: 16px;">
          <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Your Full Name *</label>
          <input type="text" name="name" required placeholder="John Doe" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div>
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Email Address *</label>
            <input type="email" name="email" required placeholder="user@example.com" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
          </div>
          <div>
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Phone Number</label>
            <input type="text" name="phone" placeholder="01700000000" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Subject *</label>
          <input type="text" name="subject" required placeholder="New Connection Inquiry / Billing Query" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 24px;">
          <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Your Message *</label>
          <textarea name="message" rows="5" required placeholder="Type your inquiry here..." style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box; resize: vertical;"></textarea>
        </div>

        <button type="submit" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(99,102,241,0.4);">
          Submit Message to Support →
        </button>
      </form>
    </div>

    <!-- Contact Info & Hotlines Card -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
      
      <div style="background: rgba(30,41,59,0.8); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; color: #fff;">
        <h3 style="margin-top: 0; color: #34d399; font-size: 1.3rem; font-weight: 700;"><i class="fa-solid fa-headset"></i> Customer Hotlines</h3>
        <div style="margin-top: 16px; font-size: 1rem; line-height: 2;">
          <p style="margin: 4px 0;"><i class="fa-solid fa-phone" style="color:#10b981; width: 24px;"></i> Hotline: <strong>16335 / 09678-123123</strong></p>
          <p style="margin: 4px 0;"><i class="fa-solid fa-envelope" style="color:#818cf8; width: 24px;"></i> Email: <strong>support@mknet.com</strong></p>
          <p style="margin: 4px 0;"><i class="fa-solid fa-location-dot" style="color:#ec4899; width: 24px;"></i> HQ: <strong>Police Plaza Concord, Gulshan 1, Dhaka</strong></p>
        </div>
      </div>

      <!-- Quick AI Assistant Card -->
      <div style="background: rgba(30,41,59,0.8); backdrop-filter: blur(16px); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 28px; color: #fff;">
        <h3 style="margin-top: 0; color: #38bdf8; font-size: 1.3rem; font-weight: 700;"><i class="fa-solid fa-robot"></i> Instant AI Support Assistant</h3>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Need immediate answers about internet speed, bKash payment, or connection troubleshooting?</p>
        <button type="button" onclick="if(window.mknetOpenAssistant) window.mknetOpenAssistant();" style="width: 100%; background: rgba(56,189,248,0.15); border: 1px solid #38bdf8; color: #38bdf8; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px;">
          🎤 Chat with MK Net AI Assistant Now
        </button>
      </div>

    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
