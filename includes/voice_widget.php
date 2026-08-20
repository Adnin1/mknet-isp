<?php
/**
 * MK Net Ultra-Fast Proactive AI Voice Assistant — floating widget with Unlimited Chat & Hands-Free Mode
 */
?>
<div id="mknetAssistant">
  <button id="mknetAssistantToggle" type="button" aria-label="Open MK Net AI Assistant" title="MK Net Voice Assistant">
    <span id="mknetAssistantIcon"><i class="fa-solid fa-headset"></i></span>
    <span class="mknet-badge-pulse"></span>
  </button>

  <div id="mknetAssistantPanel" role="dialog" aria-label="MK Net Assistant" aria-hidden="true">
    <div id="mknetAssistantHead">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
          🤖
        </div>
        <div>
          <strong style="font-size:1.05rem;">MK Net Voice Assistant</strong>
          <div class="mknet-assistant-sub">🟢 Unlimited AI Chat • Hands-Free Mode</div>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <button id="mknetClearChat" type="button" title="Clear conversation history" style="background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:6px; padding:4px 8px; font-size:11px; cursor:pointer; font-weight:600;">🧹 Clear</button>
        <button id="mknetAssistantClose" type="button" aria-label="Close">×</button>
      </div>
    </div>

    <!-- Mode Selector & Quick Actions -->
    <div id="mknetQuickActions">
      <button class="mknet-chip" id="mknetAutoListenToggle" style="background:rgba(16,185,129,0.2); color:#34d399; border-color:#10b981;">🔄 Auto-Listen: Off</button>
      <button class="mknet-chip" data-ask="Check my bill">💳 Check Bill</button>
      <button class="mknet-chip" data-ask="Packages and price">🚀 Packages</button>
      <button class="mknet-chip" data-ask="My internet is slow">🛠️ Fix Internet</button>
      <button class="mknet-chip" data-ask="Hotline number">📞 Helpline</button>
      <button class="mknet-chip" data-ask="Open support ticket">🎫 Open Ticket</button>
    </div>

    <div id="mknetAssistantLog" aria-live="polite">
      <div class="mknet-msg mknet-msg-bot">
        Hello! 👋 Welcome to MK Net Unlimited AI Voice Assistant! 
        <br><br>
        💡 <em>Ask as many questions as you like in Bengali or English. Turn on "Auto-Listen" for continuous hands-free voice chat!</em>
      </div>
    </div>

    <form id="mknetAssistantForm">
      <button type="button" id="mknetAssistantMic" title="Speak in Bengali or English" aria-label="Speak">🎤</button>
      <input type="text" id="mknetAssistantInput" placeholder="Ask any question or tap mic..." autocomplete="off">
      <button type="submit" id="mknetAssistantSend" aria-label="Send">➤</button>
    </form>
  </div>
</div>

<style>
#mknetAssistant { position:fixed; bottom:20px; right:20px; z-index:99999; font-family:'Plus Jakarta Sans', sans-serif; }
#mknetAssistantToggle {
  width:62px; height:62px; border-radius:50%; border:none; cursor:pointer;
  background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; font-size:24px;
  box-shadow:0 10px 30px rgba(99,102,241,0.5);
  display:flex; align-items:center; justify-content:center; position:relative;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
#mknetAssistantToggle:hover { transform:scale(1.08); box-shadow:0 15px 35px rgba(99,102,241,0.7); }
.mknet-badge-pulse {
  position:absolute; top:2px; right:2px; width:14px; height:14px; background:#10b981; border:2px solid #0f172a; border-radius:50%;
  animation: mknetBadgePulse 2s infinite;
}
@keyframes mknetBadgePulse { 0%,100%{ transform:scale(1); opacity:1; } 50%{ transform:scale(1.4); opacity:0.6; } }

#mknetAssistantPanel {
  display:none; position:absolute; bottom:76px; right:0; width:390px; max-width:92vw;
  max-height:82vh; background:#0f172a; color:#f8fafc; border-radius:20px; overflow:hidden;
  box-shadow:0 20px 60px rgba(0,0,0,0.7); border:1px solid rgba(255,255,255,0.15);
  flex-direction:column; transform:translateY(20px); opacity:0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
#mknetAssistantPanel.open { display:flex; transform:translateY(0); opacity:1; }

#mknetAssistantHead {
  display:flex; justify-content:space-between; align-items:center; padding:14px 16px;
  background:linear-gradient(135deg,#1e1b4b,#312e81); color:#fff; border-bottom:1px solid rgba(255,255,255,0.1);
}
.mknet-assistant-sub { font-size:11px; color:#a5b4fc; margin-top:2px; }
#mknetAssistantClose { background:none; border:none; color:#94a3b8; font-size:24px; cursor:pointer; line-height:1; }
#mknetAssistantClose:hover { color:#fff; }

#mknetQuickActions {
  display:flex; gap:6px; overflow-x:auto; padding:10px 12px; background:rgba(30,41,59,0.5); border-bottom:1px solid rgba(255,255,255,0.05);
}
#mknetQuickActions::-webkit-scrollbar { height:3px; }
#mknetQuickActions::-webkit-scrollbar-thumb { background:rgba(99,102,241,0.5); border-radius:4px; }

.mknet-chip {
  white-space:nowrap; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); color:#a5b4fc;
  padding:6px 12px; border-radius:16px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s ease;
}
.mknet-chip:hover { background:rgba(99,102,241,0.35); color:#fff; transform:translateY(-1px); }

#mknetAssistantLog { padding:14px; overflow-y:auto; flex:1; min-height:240px; max-height:400px; }
.mknet-msg { padding:10px 14px; border-radius:14px; margin-bottom:10px; font-size:13.5px; line-height:1.5; white-space:pre-line; max-width:88%; box-shadow:0 2px 8px rgba(0,0,0,0.2); }
.mknet-msg-bot { background:#1e293b; color:#f1f5f9; border-top-left-radius:2px; border:1px solid rgba(255,255,255,0.05); }
.mknet-msg-user { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; margin-left:auto; border-top-right-radius:2px; }

#mknetAssistantForm { display:flex; gap:8px; padding:12px; background:#0f172a; border-top:1px solid rgba(255,255,255,0.08); }
#mknetAssistantInput { flex:1; padding:10px 14px; border-radius:12px; border:1px solid rgba(255,255,255,0.15); background:#1e293b; color:#fff; font-size:0.9rem; }
#mknetAssistantInput:focus { outline:none; border-color:#6366f1; }
#mknetAssistantMic, #mknetAssistantSend { background:#6366f1; border:none; color:#fff; border-radius:12px; width:42px; height:42px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease; }
#mknetAssistantMic:hover, #mknetAssistantSend:hover { background:#4f46e5; }
#mknetAssistantMic.listening { background:#ef4444; animation: mknetBadgePulse 1s infinite; }
</style>

<script>
(function () {
  var toggleBtn = document.getElementById('mknetAssistantToggle');
  var panel = document.getElementById('mknetAssistantPanel');
  var closeBtn = document.getElementById('mknetAssistantClose');
  var clearBtn = document.getElementById('mknetClearChat');
  var form = document.getElementById('mknetAssistantForm');
  var input = document.getElementById('mknetAssistantInput');
  var log = document.getElementById('mknetAssistantLog');
  var micBtn = document.getElementById('mknetAssistantMic');
  var quickActions = document.getElementById('mknetQuickActions');
  var autoListenBtn = document.getElementById('mknetAutoListenToggle');

  var autoListenMode = false;

  function openPanel() { panel.classList.add('open'); panel.setAttribute('aria-hidden', 'false'); input.focus(); }
  function closePanel() { panel.classList.remove('open'); panel.setAttribute('aria-hidden', 'true'); }
  
  toggleBtn.addEventListener('click', function () {
    panel.classList.contains('open') ? closePanel() : openPanel();
  });
  closeBtn.addEventListener('click', closePanel);
  window.mknetOpenAssistant = openPanel;

  clearBtn.addEventListener('click', function() {
    log.innerHTML = '<div class="mknet-msg mknet-msg-bot">Conversation cleared! How can I assist you now?</div>';
  });

  autoListenBtn.addEventListener('click', function() {
    autoListenMode = !autoListenMode;
    if (autoListenMode) {
      autoListenBtn.textContent = "🔄 Auto-Listen: ON";
      autoListenBtn.style.background = "rgba(16,185,129,0.4)";
      autoListenBtn.style.color = "#fff";
      startListening();
    } else {
      autoListenBtn.textContent = "🔄 Auto-Listen: OFF";
      autoListenBtn.style.background = "rgba(16,185,129,0.2)";
      autoListenBtn.style.color = "#34d399";
    }
  });

  // Quick Action Chips
  quickActions.addEventListener('click', function(e) {
    if (e.target.classList.contains('mknet-chip') && e.target !== autoListenBtn) {
      var question = e.target.getAttribute('data-ask');
      if (question) ask(question);
    }
  });

  function addMsg(text, who) {
    var div = document.createElement('div');
    div.className = 'mknet-msg mknet-msg-' + who;
    div.innerHTML = text.replace(/\n/g, '<br>');
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  // Text-to-speech with auto-listen trigger on completion
  function speak(text) {
    if (!('speechSynthesis' in window)) return;
    try {
      var utter = new SpeechSynthesisUtterance(text);
      if (/[\u0980-\u09FF]/.test(text)) {
        utter.lang = 'bn-BD';
      } else {
        utter.lang = 'en-US';
      }
      utter.rate = 1.0;

      utter.onend = function() {
        if (autoListenMode && SpeechRecognition) {
          setTimeout(startListening, 500);
        }
      };

      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utter);
    } catch (e) {}
  }

  async function ask(message) {
    if (!message || message.trim() === '') return;
    var cleanMsg = message.trim();
    addMsg(cleanMsg, 'user');
    input.value = '';
    setTimeout(function() { input.focus(); }, 50);

    try {
      var res = await fetch('/pages/chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(cleanMsg)
      });
      var reply = await res.text();
      
      // Parse & execute automated page actions
      var cleanReply = reply.replace(/\[ACTION:[A-Z_]+\]/g, '').trim();
      addMsg(cleanReply, 'bot');
      speak(cleanReply);

      if (reply.includes('[ACTION:PAY_BILL]')) {
        setTimeout(function() { window.location.href = '/pages/paybill.php'; }, 2500);
      } else if (reply.includes('[ACTION:PACKAGES]')) {
        setTimeout(function() { window.location.href = '/pages/packages.php'; }, 2500);
      } else if (reply.includes('[ACTION:OPEN_TICKET]')) {
        setTimeout(function() { window.location.href = '/pages/tickets.php'; }, 2500);
      } else if (reply.includes('[ACTION:SPEEDTEST]')) {
        setTimeout(function() { window.location.href = '/pages/speedtest.php'; }, 2500);
      }
    } catch (e) {
      addMsg('Sorry, I could not reach the server just now. Please try again.', 'bot');
    }
  }

  function handleSend(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var val = input.value.trim();
    if (!val) return false;
    ask(val);
    return false;
  }

  form.addEventListener('submit', handleSend);
  document.getElementById('mknetAssistantSend').addEventListener('click', handleSend);
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      handleSend(e);
    }
  });

  // Web Speech API
  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  var listening = false;
  var activeRecog = null;

  function startListening() {
    if (!SpeechRecognition) return;
    if (listening) {
      if (activeRecog) try { activeRecog.stop(); } catch(e){}
      listening = false;
      micBtn.classList.remove('listening');
      input.placeholder = "Ask any question or tap mic...";
      return;
    }

    try {
      activeRecog = new SpeechRecognition();
      activeRecog.lang = 'bn-BD';
      activeRecog.interimResults = false;
      activeRecog.maxAlternatives = 1;

      activeRecog.onstart = function() {
        listening = true;
        micBtn.classList.add('listening');
        input.placeholder = "🎙️ Listening... Speak your question now...";
      };

      activeRecog.onresult = function(e) {
        var said = e.results[0][0].transcript;
        input.value = said;
        ask(said);
      };

      activeRecog.onerror = function() {
        listening = false;
        micBtn.classList.remove('listening');
        input.placeholder = "Ask any question or tap mic...";
      };

      activeRecog.onend = function() {
        listening = false;
        micBtn.classList.remove('listening');
        input.placeholder = "Ask any question or tap mic...";
      };

      activeRecog.start();
    } catch (e) {
      listening = false;
      micBtn.classList.remove('listening');
    }
  }

  if (!SpeechRecognition) {
    micBtn.title = "Voice recognition not supported in this browser. Please type your message.";
  } else {
    micBtn.addEventListener('click', startListening);
  }
})();
</script>
