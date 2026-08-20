<?php
declare(strict_types=1);

// Prevent PHP notices/warnings from breaking AJAX response text
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/plain; charset=utf-8');

$userMessage = trim((string)($_POST['message'] ?? ''));
$loggedInUserId = $_SESSION['user_id'] ?? null;

if (!function_exists('chatbot_money')) {
    function chatbot_money($v): string { return number_format((float)$v, 0); }
}

if (!function_exists('isBengaliInput')) {
    function isBengaliInput(string $text): bool {
        return (bool)preg_match('/[\u0980-\u09FF]/u', $text) || (bool)preg_match('/(kemon|aso|achen|koto|taka|dam|line|bikas|bikash|nagad|amar|wifi|pasword|pabe|nai|slow|thik|dhonnobad|khobor|bhalo|sathe|jevabe|chat|bujhte|bujhe|kortechi|kori|bhabe|bujhbe)/i', $text);
    }
}

if (!function_exists('tryLiveAnswer')) {
    function tryLiveAnswer(PDO $pdo, string $msg, ?int $userId): ?string {
    $m = mb_strtolower($msg);
    $bn = isBengaliInput($msg);

    if ($m === '') {
        return $bn ? "অনুগ্রহ করে আপনার প্রশ্নটি লিখুন বা মুখে বলুন!" : "Please type or speak your question!";
    }

    // --- Multi-Intent Fusion Engine (Handles compound queries like "slow and bill") ---
    $hasSlowTech = (bool)preg_match('/(slow|down|problem|red light|ping|lag|স্লো|সমস্যা|লাল বাতি|লাইন নাই)/i', $m);
    $hasBilling  = (bool)preg_match('/(bill|pay|due|invoice|bkash|nagad|বিল|পেমেন্ট|বিকাশ|নগদ)/i', $m);
    if ($hasSlowTech && $hasBilling) {
        return $bn
            ? "🛠️💳 **এমকে নেট মাল্টি-অ্যাসিস্ট্যান্ট রেসপন্স:**\n" .
              "১. **কারিগরি সাপোর্ট:** লাইন স্লো/সমস্যা হলে রাউটার ও ONU পাওয়ার প্লাগ ৩০ সেকেন্ড বন্ধ করে চালু করুন। সমস্যা থাকলে সাপোর্ট টিকেট তৈরি করা হয়েছে। [ACTION:OPEN_TICKET]\n" .
              "২. **বিলিং তথ্য:** বিকাশ/নগদে বিল পেমেন্ট করে সার্ভিস অ্যাক্টিভ রাখুন। [ACTION:PAY_BILL]"
            : "🛠️💳 **MK Net Multi-Assistant Response:**\n" .
              "1. **Technical Support:** For line issues, power cycle your ONU & Wi-Fi router for 30 seconds. [ACTION:OPEN_TICKET]\n" .
              "2. **Billing Info:** Verify your outstanding invoices via bKash or Nagad. [ACTION:PAY_BILL]";
    }

    // --- 👤 Pro-Level Personalized Subscriber Status & Account Profile Lookup ---
    if (preg_match('/(amar info|my status|my plan|account info|subscriber status|amar line|আামার ইনফো|আামার লাইন|আামার প্যাকেজ)/i', $m)) {
        if ($userId) {
            try {
                $uStmt = $pdo->prepare("SELECT u.name, u.email, u.phone, p.name AS pkg_name, p.speed_mbps FROM users u LEFT JOIN packages p ON u.package_id = p.id WHERE u.id = ?");
                $uStmt->execute([$userId]);
                $userProf = $uStmt->fetch(PDO::FETCH_ASSOC);

                $invStmt = $pdo->prepare("SELECT amount FROM invoices WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
                $invStmt->execute([$userId]);
                $dueAmt = $invStmt->fetchColumn();

                $tktStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'");
                $tktStmt->execute([$userId]);
                $openTkts = (int)$tktStmt->fetchColumn();

                if ($userProf) {
                    $pkgName = $userProf['pkg_name'] ?: 'Standard Fiber';
                    $spd = $userProf['speed_mbps'] ? $userProf['speed_mbps'] . ' Mbps' : 'Active Broadband';
                    $dueText = ($dueAmt !== false) ? chatbot_money($dueAmt) . " BDT (Pending ⚠️)" : "0 BDT (Paid ✅)";
                    
                    return $bn
                        ? "👤 **গ্রাহক প্রোফাইল ও লাইন স্ট্যাটাস:**\n" .
                          "• **গ্রাহকের নাম:** {$userProf['name']}\n" .
                          "• **বর্তমান প্যাকেজ:** {$pkgName} ({$spd})\n" .
                          "• **বকেয়া বিল:** {$dueText}\n" .
                          "• **চলতি সাপোর্ট টিকেট:** {$openTkts} টি উন্মুক্ত টিকেট\n" .
                          "কোনো তথ্যের পরিবর্তন করতে ড্যাশবোর্ডে প্রবেশ করুন।"
                        : "👤 **Subscriber Profile & Line Status:**\n" .
                          "• **Subscriber:** {$userProf['name']}\n" .
                          "• **Active Package:** {$pkgName} ({$spd})\n" .
                          "• **Due Balance:** {$dueText}\n" .
                          "• **Active Tickets:** {$openTkts} Open Ticket(s)\n" .
                          "Visit your Selfcare Dashboard for full management options.";
                }
            } catch (Throwable $e) {}
        }
        return $bn
            ? "👤 আপনার সুনির্দিষ্ট লাইন স্ট্যাটাস ও প্যাকেজের মেয়াদ দেখতে অনুগ্রহ করে অ্যাকাউন্টে লগইন করুন!"
            : "👤 Please log in to inspect your personalized line status, active package speed, and bill details!";
    }

    // --- Banglish Chat / Language Capability -------------------------
    if (preg_match('/(bujhte|bujhe|bujhi|bujho|bujhbe|language|bhasha|bangla|banglish|sathe|jevabe|kortechi|kori)/i', $m)) {
        return "হ্যাঁ! আমি আপনার বাংলা, ইংরেজি এবং বাংলিশ (Romanized Bengali) ভাষা পুরোপুরি বুঝতে পারি! 😊 আপনার ইন্টারনেট লাইন, প্যাকেজের দাম, বিকাশ/নগদ পেমেন্ট অথবা যেকোনো কারিগরি সমস্যা নিয়ে প্রশ্ন করুন, আমি এখনই সমাধান করে দিচ্ছি।";
    }

    // --- Chit-Chat / Identity / Creator ---------------------------
    if (preg_match('/(hi|hello|hey|assalamu|salam|হাই|হ্যালো|কেমন আছেন|ভালো আছেন|কেমন আছো|kemon aso|kemon achen)/i', $m)) {
        return $bn 
            ? "হ্যালো! 👋 এমকে নেট এআই এসিস্ট্যান্টে আপনাকে স্বাগতম। আপনি প্যাকেজ, ১-৩০ দিনের কাস্টম রিচার্জ, বিকাশ/নগদ পেমেন্ট, রাউটার ও লাইন সাপোর্ট নিয়ে যেকোনো প্রশ্ন করতে পারেন!"
            : "Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!";
    }

    if (preg_match('/(who are you|your name|tumi ke|apnar nam|ke baniyeche|কে তুমি|আপনার নাম|তোমার নাম)/i', $m)) {
        return $bn
            ? "আমি এমকে নেটের ভার্চুয়াল এআই অ্যাসিস্ট্যান্ট! 🤖 ইন্টারনেট প্যাকেজ, বিল পরিশোধ, রাউটার পাসওয়ার্ড ও লাইন সাপোর্টের জন্য ২৪ ঘণ্টা আপনার সেবায় নিয়োজিত।"
            : "I am MK Net's Virtual AI Assistant! 🤖 Dedicated 24/7 to helping you with internet lines, packages, bills, and technical support.";
    }

    if (preg_match('/\b(thanks|thank you|dhonnobad|ধন্যবাদ|থ্যাংকস)\b/u', $m)) {
        return $bn ? "আপনাকে অসংখ্য ধন্যবাদ! 😊 যেকোনো প্রয়োজনে আমাকে প্রশ্ন করুন।" : "You're very welcome! 😊 Always happy to assist. Let me know if you need anything else regarding your MK Net internet connection!";
    }

    // --- Loyalty Points & Referral Earnings -----------------------
    if (preg_match('/(loyalty|points|point|referral|refer|bonus|point koto|লয়ালটি|পয়েন্ট|রেফার)/i', $m)) {
        return $bn
            ? "🎁 এমকে নেট লয়ালটি ও রেফারেল প্রোগ্রাম:\n• প্রতিটি বিল পরিশোধে পাবেন লয়ালটি পয়েন্ট (Bronze, Silver, Gold, Platinum ব্যাজ)!\n• বন্ধুদের রেফার করলে পাবেন ব্যাজ পয়েন্ট এবং ফ্রি ব্যালেন্স বোনাস। সেলফকেয়ার ড্যাশবোর্ডে গিয়ে আপনার রেফারেল লিংক কপি করুন।"
            : "🎁 MK Net Loyalty & Referral Program:\n• Earn loyalty points with every bill payment to unlock Bronze, Silver, Gold, and Platinum badges!\n• Share your unique referral link from your Selfcare Dashboard to earn cash bonus points.";
    }

    // --- New Connection & Survey Appointment ----------------------
    if (preg_match('/(new connection|survey|appointment|install|setup|নতুন লাইন|কানেকশন|কানেকশন কিভাবে নিব)/i', $m)) {
        return $bn
            ? "🔌 নতুন ফাইবার সংযোগ নেওয়ার জন্য:\n১. আমাদের বুকিং পেজ অথবা কভারেজ পেজ থেকে আপনার এলাকা বা জেলা ও থানা নির্বাচন করুন।\n২. আমাদের ফাইবার সার্ভে টিম ২৪ ঘণ্টার মধ্যে আপনার সাথে যোগাযোগ করবে।"
            : "🔌 How to get a New Fiber Connection:\n1. Select your area on our Coverage or Appointment Booking page.\n2. Our optical fiber survey team will inspect your line within 24 hours!";
    }

    // --- Network Outage & POP Status -------------------------------
    if (preg_match('/(outage|incident|status|pop|sla|maintenance|নেটওয়ার্ক|আপটাইম|রক্ষণাবেক্ষণ)/i', $m)) {
        return $bn
            ? "📡 আমাদের নেটওয়ার্ক আপটাইম ৯৯.৯৯% গ্যারান্টিযুক্ত। কোন এলাকায় রক্ষণাবেক্ষণ কাজ চলছে কিনা তা সরাসরি Network Status পেজ থেকে রিয়েল-টাইম চেক করতে পারেন।"
            : "📡 Our fiber network operates at 99.99% SLA uptime. You can inspect live POP hub statuses and optical maintenance logs on our Network Status page.";
    }

    // --- Custom Duration & Package Renewal ------------------------
    if (preg_match('/(custom|day|days|short|renew|recharge|মেয়াদ|কাস্টম|রিচার্জ|মেয়ার|দিন)/i', $m)) {
        return $bn
            ? "⚡ এমকে নেট ফ্লেক্সি-প্ল্যান: রেগুলার গ্রাহকরা ১, ৩, ৭, ১৫ অথবা ৩০ দিনের জন্য কাস্টম প্যাকেজ নিতে পারেন! নতুন গ্রাহকদের জন্য রয়েছে ৩০ দিনের স্ট্যান্ডার্ড প্যাকেজ। সেলফকেয়ার পোর্টাল অথবা প্যাকেজ পেজ থেকে আপনার ইচ্ছামতো মেয়াদ নির্বাচন করুন।"
            : "⚡ MK Net Flexi-Plan: Regular users can choose custom package durations for 1, 3, 7, 15, or 30 days! New users get standard 30-day packages. You can select custom days from the Selfcare Portal or Packages page.";
    }

    // --- Payment methods & Bkash/Nagad -----------------------------
    if (preg_match('/\b(bkash|nagad|rocket|upay|payment|pay|card|বিকাশ|নগদ|পেমেন্ট|টাকা|বিল)\b/u', $m)) {
        try {
            $s = $pdo->query("SELECT bkash_number, nagad_number FROM settings WHERE id=1")->fetch();
            $b = $s['bkash_number'] ?? '01700000000';
            $n = $s['nagad_number'] ?? '01700000000';
        } catch (Throwable $e) { $b = '01700000000'; $n = '01700000000'; }
        return $bn
            ? "💳 আপনি বিকাশ, নগদ, রকেট অথবা কার্ডের মাধ্যমে বিল পরিশোধ করতে পারেন!\n• বিকাশ মার্চেন্ট: {$b}\n• নগদ মার্চেন্ট: {$n}\nবিল পে পেজ অথবা সেলফকেয়ার ড্যাশবোর্ড থেকে সহজেই ইনস্ট্যান্ট বিল পে করুন!"
            : "💳 You can pay your bill via bKash, Nagad, Rocket, Upay, or Cards!\n• bKash Merchant: {$b}\n• Nagad Merchant: {$n}\nVisit the Pay Bill page or your Selfcare Dashboard to verify manual or auto payments instant!";
    }

    // --- Wi-Fi Password & Router Settings ------------------------
    if (preg_match('/\b(wifi password|pasword|pass change|router reset|ssid|192\.168|পাসওয়ার্ড|ওয়াইফাই)\b/u', $m)) {
        return $bn
            ? "🔑 যেভাবে ওয়াইফাই পাসওয়ার্ড পরিবর্তন করবেন:\n১. আপনার রাউটারের সাথে কানেক্ট থেকে ব্রাউজারে ১৯২.১৬৮.০.১ অথবা ১৯২.১৬৮.১.১ লিখুন।\n২. রাউটারের ইউজার ও পাসওয়ার্ড দিয়ে লগইন করুন (সাধারণত admin/admin)।\n৩. Wireless -> Wireless Security অপশনে গিয়ে নতুন পাসওয়ার্ড দিন।\n৪. সেভ করে রাউটার রিস্টার্ট দিন!"
            : "🔑 How to Change your Wi-Fi Password:\n1. Connect to your Wi-Fi network and open a browser.\n2. Go to 192.168.0.1 or 192.168.1.1.\n3. Log in using your router credentials (usually admin/admin).\n4. Navigate to Wireless -> Wireless Security -> Pre-Shared Key / Password.\n5. Save & restart your router!";
    }

    // --- Hardware & Addon Store ------------------------------------
    if (preg_match('/\b(store|hardware|router|onu|patch cord|cable|onu price|স্টোর|রাউটার|ওএনইউ)\b/u', $m)) {
        return $bn
            ? "🛒 এমকে নেট হার্ডওয়্যার স্টোরে পাবেন হাই-স্পিড ডুয়েল ব্যান্ড WiFi 6 রাউটার, গিগাবিট ONU এবং ফাইবার প্যাচ কর্ড! সেলফকেয়ার পোর্টালের স্টোর অপশন থেকে সরাসরি অর্ডার করতে পারেন।"
            : "🛒 MK Net Hardware Store offers high-speed Dual Band WiFi 6 Routers, Gigabit ONUs, and Fiber Patch Cords! You can order directly from the Store page inside your Selfcare Portal.";
    }

    // --- Speedtest / BDIX ------------------------------------------
    if (preg_match('/(speed|test|fast|bdix|ftp|movie|স্পিড|এফটিপি|মুভি)/i', $m)) {
        return ($bn
            ? "🚀 fast.com অথবা speedtest.net-এ আপনার লাইনের স্পিড টেস্ট করুন! এমকে নেটে রয়েছে ১০০ এমবিপিএস বিডিআইএক্স রউটিং, যা দিয়ে বাফারিং ছাড়াই ইউটিউব ৪কে স্ট্রিমিং ও বিডিআইএক্স এফটিপি সার্ভার ব্রাউজ করা যায়।"
            : "🚀 Test your line speed on fast.com or speedtest.net! MK Net features 100 Mbps BDIX ultra-fast routing for zero buffer YouTube 4K streaming and high-speed BDIX FTP servers.") . " [ACTION:SPEEDTEST]";
    }

    // --- 1. AI Package Advisor (Smart Bandwidth Consultant) -------------
    if (preg_match('/(advisor|recommend|suggestion|best package|which package|gaming|student|family|stream|4k|পরামর্শ|প্যাকেজ নিব|কোনটা ভালো|প্যাকেজ সাজেস্ট)/i', $m)) {
        return $bn
            ? "🎯 **এমকে নেট এআই প্যাকেজ এডভাইজর পরামর্শ:**\n" .
              "• 🎓 **স্টুডেন্ট / সিংগেল ইউজার:** 20 Mbps (৫০০ টাকা/মাস) - ব্রাউজিং ও সোশ্যাল মিডিয়া\n" .
              "• 🏠 **বাসাবাড়ি / ৩-৪ জন ইউজার:** 50 Mbps (১,০০০ টাকা/মাস) - বাফারিং ছাড়া স্মার্ট টিভি ৪কে ইউটিউব/নেটফ্লিক্স\n" .
              "• 🎮 **গেমিং / অনলাইন স্ট্রিমার:** 100 Mbps BDIX Low Ping (১,৮০০ টাকা/মাস) - জিরো ল্যাগ ও হাই স্পিড এফটিপি\n" .
              "• 🏢 **অফিস / করপোরেট:** 200 Mbps 1:1 Dedicated Fiber (১৪,৯৯৯ টাকা/মাস) - ৯৯.৯৯% SLA সাপোর্ট [ACTION:PACKAGES]"
            : "🎯 **MK Net AI Package Advisor Recommendation:**\n" .
              "• 🎓 **Student / Single User:** 20 Mbps (500 BDT/mo) - Perfect for HD browsing\n" .
              "• 🏠 **Family / 3-4 Users:** 50 Mbps (1,000 BDT/mo) - Zero buffer 4K YouTube & Smart TV\n" .
              "• 🎮 **Gaming & Streaming:** 100 Mbps BDIX (1,800 BDT/mo) - Ultra low ping gaming\n" .
              "• 🏢 **Corporate / Office:** 200 Mbps Dedicated (14,999 BDT/mo) - 99.99% SLA Uptime [ACTION:PACKAGES]";
    }

    // --- 2. AI Sales Assistant (ISP Sales & Lead Conversion) ------------
    if (preg_match('/(sales|buy|order|installation|charge|new line|offer|discount|কেনাবেচা|ইনস্টলেশন|অফার|নতুন লাইন)/i', $m)) {
        return $bn
            ? "🏷️ **এমকে নেট এআই সেলস অ্যাসিস্ট্যান্ট সেবাসমূহ:**\n" .
              "• **নতুন সংযোগ অফার:** কভারেজ এলাকায় ১০০% অপটিক্যাল ফাইবার লাইন বিনামূল্যে টিউনিং করা হয়!\n" .
              "• **ইনস্টলেশন ফি:** এলাকা ভেদে ফাইবার ক্যাবল ও গিগাবিট ওএনইউ সংযোগ মাত্র ৫০০-১,০০০ টাকা।\n" .
              "• **ফ্লেক্সি মেয়াদী প্ল্যান:** ১ দিন, ৩ দিন, ৭ দিন বা ৩০ দিনের জন্য রিচার্জ করার সুবিধা।" . " [ACTION:PACKAGES]"
            : "🏷️ **MK Net AI Sales Assistant Services:**\n" .
              "• **New Connection Offer:** 100% Optical Fiber line with free splicing tuning in active coverage zones!\n" .
              "• **Installation Charge:** Only 500 - 1,000 BDT for Fiber drop cable & ONU setup.\n" .
              "• **Flexi Recharge:** 1-day, 7-day, 15-day, or full month top-up passes available!" . " [ACTION:PACKAGES]";
    }

    // --- 3. AI Technical Support Specialist (Fiber & Hardware Expert) ----
    if (preg_match('/(technical|support|troubleshoot|onu|los|red light|pon|router|cable|ethernet|patch cord|slow|lag|ping|down|disconnect|কারিগরি|সমস্যা|লাইন নাই|লাল বাতি|স্লো)/i', $m)) {
        if ($userId) {
            try {
                $insTkt = $pdo->prepare("INSERT INTO tickets (user_id, subject, department, priority, status) VALUES (?, ?, 'technical', 'high', 'open')");
                $insTkt->execute([$userId, "Auto Technical Support Ticket: " . mb_substr($msg, 0, 80)]);
                $tktId = (int)$pdo->lastInsertId();
                return $bn
                    ? "🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n" .
                      "১. **ONU LOS লাল বাতি জ্বললে:** বাহ্যিক ফাইবার তার কেটে যাওয়া বোঝা যায়। আমাদের NOC ইঞ্জিনিয়ারদের কাছে টিকেট পাঠানো হয়েছে (#TKT-{$tktId})।\n" .
                      "২. **ইন্টারনেট স্লো হলে:** রাউটার ও ONU পাওয়ার প্লাগ ৩০ সেকেন্ড খুলে পুনরায় অন করুন।\n" .
                      "৩. **প্যাচ কর্ড চেক:** হলুদ ফাইবার তারটি ONU PON পোর্টে শক্তভাবে লাগানো আছে কিনা দেখুন। [ACTION:OPEN_TICKET]"
                    : "🛠️ **MK Net AI Technical Support Specialist:**\n" .
                      "1. **ONU LOS Red Light Blinking:** Indicates external optical fiber line cut. Support Ticket #TKT-{$tktId} has been logged for our NOC engineers!\n" .
                      "2. **Slow Speed Troubleshooting:** Unplug power for both ONU & Wi-Fi Router for 30 seconds, then reboot.\n" .
                      "3. **Optical Patch Cord Check:** Ensure the yellow SC fiber cable is firmly clicked into the ONU PON port. [ACTION:OPEN_TICKET]";
            } catch (Throwable $t) {}
        }
        return $bn
            ? "🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n" .
              "১. **ONU LOS লাল বাতি:** ফাইবার কেবল বিচ্ছিন্ন নির্দেশ করে।\n" .
              "২. **পাওয়ার সাইকেল:** রাউটার ও ওএনইউ ডিভাইস ৩০ সেকেন্ড বন্ধ রেখে চালু করুন।\n" .
              "৩. **সাপোর্ট টিকেট:** আপনার অ্যাকাউন্টে লগইন করে ১-ক্লিকে সাপোর্ট টিকেট ওপেন করুন। [ACTION:OPEN_TICKET]"
            : "🛠️ **MK Net AI Technical Support Specialist:**\n" .
              "1. **ONU LOS Red Blinking Light:** Signal loss due to optical fiber cable cut.\n" .
              "2. **Power Cycle Reset:** Turn off Wi-Fi router & ONU for 30 seconds, then power back on.\n" .
              "3. **Auto Ticket:** Log in to automatically dispatch our technical NOC team. [ACTION:OPEN_TICKET]";
    }

    // --- 4. AI Billing Assistant (Payment Gateway & Invoice Expert) -------
    if (preg_match('/(bill|pay|due|invoice|outstanding|bkash|nagad|merchant|trx|trxid|পেমেন্ট|বিল|বিকাশ|নগদ|রসিদ)/i', $m)) {
        try {
            $s = $pdo->query("SELECT bkash_number, nagad_number FROM settings WHERE id=1")->fetch();
            $b = $s['bkash_number'] ?? '01700000000';
            $n = $s['nagad_number'] ?? '01700000000';
        } catch (Throwable $e) { $b = '01700000000'; $n = '01700000000'; }

        if ($userId) {
            try {
                $stmt = $pdo->prepare("SELECT amount FROM invoices WHERE user_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
                $stmt->execute([$userId]);
                $amt = $stmt->fetchColumn();
                if ($amt !== false) {
                    return $bn
                        ? "💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n" .
                          "আপনার বর্তমান বকেয়া বিল: " . chatbot_money($amt) . " টাকা।\n" .
                          "• **বিকাশ মার্চেন্ট মেক পেমেন্ট:** {$b}\n" .
                          "• **নগদ মার্চেন্ট পেমেন্ট:** {$n}\n" .
                          "পেমেন্ট শেষ করে TrxID লিখে সাবমিট করলে অ্যাকাউন্ট রিনিউ হয়ে যাবে! [ACTION:PAY_BILL]"
                        : "💳 **MK Net AI Billing Assistant:**\n" .
                          "Your outstanding bill amount is: " . chatbot_money($amt) . " BDT.\n" .
                          "• **bKash Merchant Pay:** {$b}\n" .
                          "• **Nagad Merchant Pay:** {$n}\n" .
                          "Submit your Transaction ID (TrxID) to verify instantly! [ACTION:PAY_BILL]";
                }
            } catch (Throwable $e) {}
        }
        return $bn
            ? "💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n" .
              "• বিকাশ মার্চেন্ট নম্বর: {$b}\n" .
              "• নগদ মার্চেন্ট নম্বর: {$n}\n" .
              "লগইন করে আপনার নির্দিষ্ট ইনভয়েস ডাউনলোড ও বিকাশ/নগদে ইনস্ট্যান্ট বিল পরিশোধ করুন। [ACTION:PAY_BILL]"
            : "💳 **MK Net AI Billing Assistant:**\n" .
              "• bKash Merchant Number: {$b}\n" .
              "• Nagad Merchant Number: {$n}\n" .
              "Log in to inspect your exact invoice and execute instant bKash/Nagad payments. [ACTION:PAY_BILL]";
    }

    // --- Contact / hours / address ------------------------------------
    if (preg_match('/\b(contact|phone|number|address|location|hours|open|call|ফোন|ঠিকানা|হটলাইন)\b/u', $m)) {
        try {
            $s = $pdo->query("SELECT hotline, email, address FROM settings WHERE id=1")->fetch();
            $phone = $s['hotline'] ?? '16335 / 09678-123123';
            $addr = $s['address'] ?? 'Police Plaza Concord, Gulshan 1, Dhaka';
        } catch (Throwable $e) { 
            $phone = '16335 / 09678-123123'; 
            $addr = 'Police Plaza Concord, Gulshan 1, Dhaka';
        }
        return "📞 You can reach MK Net 24/7 Support Hotline at {$phone}, or email support@mknet.com.\n📍 HQ Office: {$addr}. Our NOC team monitors lines 24/7!";
    }

    return null;
}
}

// Master execution with fail-safe error wrapping
try {
    $botReply = null;
    if ($userMessage !== '') {
        $botReply = tryLiveAnswer($pdo, $userMessage, $loggedInUserId ? (int)$loggedInUserId : null);

        // Fall back to admin-configured keyword rules
        if ($botReply === null) {
            try {
                $rules = $pdo->query("SELECT keyword, reply FROM chatbot_rules ORDER BY LENGTH(keyword) DESC")->fetchAll();
                foreach ($rules as $rule) {
                    if (!empty($rule['keyword']) && stripos($userMessage, $rule['keyword']) !== false) {
                        $botReply = $rule['reply'];
                        break;
                    }
                }
            } catch (Throwable $e) {}
        }
    }

    // Intelligent Failsafe Response for ANY Unrecognized / Wrong Query
    if ($botReply === null || $botReply === '') {
        $cleanMsg = htmlspecialchars(mb_substr($userMessage, 0, 50));
        $isBn = isBengaliInput($userMessage);
        $botReply = $isBn
            ? "আমি আপনার প্রশ্নটি পেয়েছি: \"{$cleanMsg}\"। আমি এমকে নেটের ভার্চুয়াল এআই অ্যাসিস্ট্যান্ট! 🤖\n\nআপনাকে কীভাবে সাহায্য করতে পারি? আপনি জিজ্ঞেস করতে পারেন:\n• ইন্টারনেট প্যাকেজের দাম ও স্পিড\n• ১-৩০ দিনের কাস্টম রিচার্জ ফ্লেক্সি প্ল্যান\n• বিকাশ / নগদ / রকেট বিল পরিশোধ\n• ওয়াইফাই পাসওয়ার্ড পরিবর্তন নির্দেশনা\n• কারিগরি সমস্যা বা লাইন সাপোর্ট টিকেট"
            : "I received your message: \"{$cleanMsg}\". As MK Net's AI Assistant, I'm specialized in fiber broadband, packages, bill payments, and line support! 🤖\n\nHow can I help you today? Feel free to ask about:\n• Internet Packages & Speeds\n• Custom Flexi Top-ups (1-30 Days)\n• bKash / Nagad Bill Payments\n• Wi-Fi Password Reset Setup\n• Helpline & Support Ticket";
    }

    // Log the conversation (non-critical)
    try {
        $pdo->prepare("INSERT INTO chatbot_messages (user_message, bot_reply, created_at) VALUES (?,?,NOW())")
            ->execute([$userMessage, $botReply]);
    } catch (Throwable $e) {}

    echo $botReply;

} catch (Throwable $fatalError) {
    // Guaranteed emergency response (NEVER fails)
    echo "I'm MK Net AI Assistant! How can I assist you with your internet packages, bill payment, or line support today?";
}
