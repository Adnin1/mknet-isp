<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'AI Experience Layer — MK Net 2.0';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1250px; margin: 40px auto; padding: 0 20px;">
  
  <!-- Hero Banner -->
  <div style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #0f172a 100%); border: 1px solid rgba(99,102,241,0.3); border-radius: 24px; padding: 50px 30px; text-align: center; color: white; margin-bottom: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); position: relative; overflow: hidden;">
    <span style="background: rgba(99,102,241,0.25); border: 1px solid rgba(129,140,248,0.5); color: #a5b4fc; font-weight: 800; font-size: 0.85rem; padding: 8px 22px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1.5px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;">
      <i class="fa-solid fa-brain" style="color: #ec4899;"></i> MK Net AI Experience Layer 2.0
    </span>
    <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0 0 16px; background: linear-gradient(135deg, #ffffff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
      Experience 10 Specialized AI Assistants in Action
    </h1>
    <p style="font-size: 1.15rem; color: #94a3b8; max-width: 750px; margin: 0 auto 30px; line-height: 1.6;">
      Interact with our next-generation AI Chat, Voice, Sales, Billing, Diagnostic, and Technical Support Assistants. Click any feature below to test live!
    </p>

    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
      <button onclick="if(window.mknetOpenAssistant) window.mknetOpenAssistant();" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 20px rgba(99,102,241,0.5); display: inline-flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-microphone"></i> Launch Voice Assistant Mic
      </button>
    </div>
  </div>

  <!-- 10 AI Assistant Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; margin-bottom: 50px;">
    
    <!-- 1. AI Chat Assistant -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(99,102,241,0.2); padding: 10px; border-radius: 14px;">🤖</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">1. AI Chat Assistant</h3>
            <span style="color: #a5b4fc; font-size: 0.8rem; font-weight: 700;">24/7 Conversational Engine</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Understands Bengali, English, and Romanized Banglish queries instantly.</p>
      </div>
      <button onclick="testAiPrompt('হ্যালো, কেমন আছেন?');" style="margin-top: 16px; background: rgba(99,102,241,0.2); border: 1px solid #6366f1; color: #a5b4fc; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        💬 Test: "হ্যালো, কেমন আছেন?" →
      </button>
    </div>

    <!-- 2. AI Voice Assistant -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(236,72,153,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(236,72,153,0.2); padding: 10px; border-radius: 14px;">🎙️</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">2. AI Voice Assistant</h3>
            <span style="color: #f472b6; font-size: 0.8rem; font-weight: 700;">Bilingual STT & TTS</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Hands-free voice recognition & text-to-speech auto playback.</p>
      </div>
      <button onclick="if(window.mknetOpenAssistant) window.mknetOpenAssistant();" style="margin-top: 16px; background: rgba(236,72,153,0.2); border: 1px solid #ec4899; color: #f472b6; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        🎤 Test Voice Microphone →
      </button>
    </div>

    <!-- 3. AI Sales Assistant -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(52,211,153,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(52,211,153,0.2); padding: 10px; border-radius: 14px;">📊</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">3. AI Sales Assistant</h3>
            <span style="color: #34d399; font-size: 0.8rem; font-weight: 700;">Package Explorer & Action Redirect</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Queries package prices and auto-navigates to packages page.</p>
      </div>
      <button onclick="testAiPrompt('প্যাকেজের দাম কত');" style="margin-top: 16px; background: rgba(52,211,153,0.2); border: 1px solid #10b981; color: #34d399; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        💰 Test: "প্যাকেজের দাম কত" →
      </button>
    </div>

    <!-- 4. AI Technical Support -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(239,68,68,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(239,68,68,0.2); padding: 10px; border-radius: 14px;">🛠️</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">4. AI Technical Support</h3>
            <span style="color: #f87171; font-size: 0.8rem; font-weight: 700;">Fiber Line Diagnostics</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Guides ONU PON red light checks and optical power loss.</p>
      </div>
      <button onclick="testAiPrompt('net slow choltese');" style="margin-top: 16px; background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #f87171; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        🔧 Test: "net slow choltese" →
      </button>
    </div>

    <!-- 5. AI Billing Assistant -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(251,191,36,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(251,191,36,0.2); padding: 10px; border-radius: 14px;">💳</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">5. AI Billing Assistant</h3>
            <span style="color: #fbbf24; font-size: 0.8rem; font-weight: 700;">bKash & Nagad Payments</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Reads due invoice amount & redirects to Pay Bill page.</p>
      </div>
      <button onclick="testAiPrompt('amar bill koto');" style="margin-top: 16px; background: rgba(251,191,36,0.2); border: 1px solid #f59e0b; color: #fbbf24; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        💵 Test: "amar bill koto" →
      </button>
    </div>

    <!-- 6. AI Package Advisor -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(167,139,250,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(167,139,250,0.2); padding: 10px; border-radius: 14px;">🎯</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">6. AI Package Advisor</h3>
            <span style="color: #c084fc; font-size: 0.8rem; font-weight: 700;">Gaming & 4K Streaming</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Recommends ideal bandwidth for home, gaming, or corporate use.</p>
      </div>
      <button onclick="testAiPrompt('kon package ta bhalo hobe');" style="margin-top: 16px; background: rgba(167,139,250,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        🎮 Test: "kon package ta bhalo hobe" →
      </button>
    </div>

    <!-- 7. AI Router Setup Guide -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(56,189,248,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(56,189,248,0.2); padding: 10px; border-radius: 14px;">📶</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">7. AI Router Setup Guide</h3>
            <span style="color: #38bdf8; font-size: 0.8rem; font-weight: 700;">Wi-Fi Password & IP Config Tool</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Step-by-step 192.168.0.1 Wi-Fi password change & router configuration assistant.</p>
      </div>
      <button onclick="openRouterSetupModal();" style="margin-top: 16px; background: rgba(56,189,248,0.2); border: 1px solid #0284c7; color: #38bdf8; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        ⚙️ Launch Interactive Router Setup Tool →
      </button>
    </div>

    <!-- 8. AI Complaint Assistant -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(244,63,94,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(244,63,94,0.2); padding: 10px; border-radius: 14px;">🎫</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">8. AI Complaint Assistant</h3>
            <span style="color: #fb7185; font-size: 0.8rem; font-weight: 700;">Auto Ticket Generation</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Auto-opens support tickets and redirects to engineering portal.</p>
      </div>
      <button onclick="window.location.href="/pages/tickets.php";" style="margin-top: 16px; background: rgba(244,63,94,0.2); border: 1px solid #f43f5e; color: #fb7185; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        📝 Launch Complaint Escalation Portal →
      </button>
    </div>

    <!-- 9. AI Internet Diagnosis -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(16,185,129,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(16,185,129,0.2); padding: 10px; border-radius: 14px;">⚡</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">9. AI Internet Diagnosis</h3>
            <span style="color: #34d399; font-size: 0.8rem; font-weight: 700;">Speed & BDIX Routing Test</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Launches fast.com speed test and checks BDIX 100 Mbps routing.</p>
      </div>
      <button onclick="window.location.href="/pages/speedtest.php";" style="margin-top: 16px; background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #34d399; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        🚀 Launch Speed & BDIX Diagnostic Tool →
      </button>
    </div>

    <!-- 10. AI Knowledge Base -->
    <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(96,165,250,0.3); border-radius: 20px; padding: 28px; color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <span style="font-size: 2rem; background: rgba(96,165,250,0.2); padding: 10px; border-radius: 14px;">📚</span>
          <div>
            <h3 style="margin: 0; font-size: 1.3rem; color: #fff; font-weight: 800;">10. AI Knowledge Base</h3>
            <span style="color: #60a5fa; font-size: 0.8rem; font-weight: 700;">Hotline & HQ Office Info</span>
          </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">24/7 hotline numbers, Gulshan HQ address, & NOC support hours.</p>
      </div>
      <button onclick="testAiPrompt('contact hotline number');" style="margin-top: 16px; background: rgba(96,165,250,0.2); border: 1px solid #3b82f6; color: #60a5fa; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; text-align: left; width: 100%;">
        📞 Ask Knowledge Base Hotline →
      </button>
    </div>

  </div>

</div>

<div id="routerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:999999; justify-content:center; align-items:center; backdrop-filter:blur(10px);">
  <div style="background:#0f172a; border:1px solid #38bdf8; border-radius:20px; padding:30px; width:90%; max-width:600px; color:#fff; position:relative; box-shadow:0 20px 50px rgba(56,189,248,0.3);">
    <button onclick="closeRouterModal();" style="position:absolute; top:15px; right:20px; background:none; border:none; color:#94a3b8; font-size:24px; cursor:pointer;">×</button>
    <h3 style="margin-top:0; color:#38bdf8; font-size:1.5rem;"><i class="fa-solid fa-wifi"></i> Interactive Router & Gateway Setup Assistant</h3>
    <p style="color:#cbd5e1; font-size:0.9rem;">Select your Wi-Fi Router Brand to get step-by-step IP login & Wi-Fi password change guidance:</p>
    
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
      <button onclick="showBrandGuide('tplink');" style="background:rgba(56,189,248,0.2); border:1px solid #0284c7; color:#38bdf8; padding:8px 16px; border-radius:8px; font-weight:700; cursor:pointer;">TP-Link</button>
      <button onclick="showBrandGuide('tenda');" style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:8px 16px; border-radius:8px; font-weight:700; cursor:pointer;">Tenda</button>
      <button onclick="showBrandGuide('dlink');" style="background:rgba(245,158,11,0.2); border:1px solid #f59e0b; color:#fbbf24; padding:8px 16px; border-radius:8px; font-weight:700; cursor:pointer;">D-Link</button>
      <button onclick="showBrandGuide('netgear');" style="background:rgba(167,139,250,0.2); border:1px solid #a855f7; color:#c084fc; padding:8px 16px; border-radius:8px; font-weight:700; cursor:pointer;">Netgear / Totolink</button>
    </div>

    <div id="routerGuideContent" style="background:rgba(30,41,59,0.9); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.1); font-size:0.9rem; line-height:1.6; color:#f1f5f9; min-height:140px;">
      👉 Select a router brand above or click below to launch router gateway address directly:
      <br><br>
      <a href="http://192.168.0.1" target="_blank" style="color:#38bdf8; font-weight:700; text-decoration:underline; margin-right:15px;">🌐 Open http://192.168.0.1</a>
      <a href="http://192.168.1.1" target="_blank" style="color:#34d399; font-weight:700; text-decoration:underline;">🌐 Open http://192.168.1.1</a>
    </div>
  </div>
</div>

<script>
function testAiPrompt(promptText) {
  if (window.mknetOpenAssistant) {
    window.mknetOpenAssistant();
  }
  setTimeout(() => {
    const input = document.getElementById('mknetAssistantInput');
    if (input) {
      input.value = promptText;
      const form = document.getElementById('mknetAssistantForm');
      if (form) form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
  }, 300);
}

function openRouterSetupModal() {
  document.getElementById('routerModal').style.display = 'flex';
}
function closeRouterModal() {
  document.getElementById('routerModal').style.display = 'none';
}

function showBrandGuide(brand) {
  const box = document.getElementById('routerGuideContent');
  if (brand === 'tplink') {
    box.innerHTML = '🔑 <strong>TP-Link Router Setup:</strong><br>1. Open <a href="http://192.168.0.1" target="_blank" style="color:#38bdf8;">192.168.0.1</a> or tplinkwifi.net<br>2. Log in with admin/admin<br>3. Go to <strong>Wireless → Wireless Security</strong><br>4. Change <strong>WPA-PSK/WPA2-PSK Password</strong> & click Save!';
  } else if (brand === 'tenda') {
    box.innerHTML = '🔑 <strong>Tenda Router Setup:</strong><br>1. Open <a href="http://192.168.0.1" target="_blank" style="color:#34d399;">192.168.0.1</a> or tendawifi.com<br>2. Go to <strong>Wireless Settings</strong><br>3. Change <strong>Wi-Fi Name (SSID)</strong> & <strong>Wi-Fi Password</strong><br>4. Click OK and reboot!';
  } else if (brand === 'dlink') {
    box.innerHTML = '🔑 <strong>D-Link Router Setup:</strong><br>1. Open <a href="http://192.168.1.1" target="_blank" style="color:#fbbf24;">192.168.1.1</a><br>2. Log in with admin/admin<br>3. Go to <strong>Setup → Wireless Settings</strong><br>4. Click Save Settings!';
  } else {
    box.innerHTML = '🔑 <strong>Netgear / Totolink Setup:</strong><br>1. Open <a href="http://192.168.1.1" target="_blank" style="color:#c084fc;">192.168.1.1</a><br>2. Log in with admin/password<br>3. Navigate to <strong>Wireless Setup</strong> & update WPA2 passphrase!';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
