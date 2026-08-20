<?php
// The old standalone "Voice Assistant API Demo" page has been replaced
// by a real, site-wide voice + chat assistant (the floating 💬 button in
// the bottom-left corner of every page, powered by pages/chatbot.php and
// the browser's free built-in Web Speech API). Redirect here so any old
// bookmarks/links still land somewhere useful instead of a dead demo.
require_once __DIR__ . '/../config.php';
header('Location: /index.php#assistant');
exit;
