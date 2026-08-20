# -*- coding: utf-8 -*-
"""
MK Net Mega Executive Presentation Deck Generator (18 Widescreen Slides with Screenshots)
"""

import sys
import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE

def create_mega_deck():
    prs = Presentation()
    # Set 16:9 Widescreen aspect ratio
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)

    blank_layout = prs.slide_layouts[6]

    # Color Palette
    BG_DARK = RGBColor(9, 13, 22)         # #090d16
    CARD_BG = RGBColor(30, 41, 59)        # #1e293b
    CARD_BG_ALT = RGBColor(15, 23, 42)    # #0f172a
    ACCENT_INDIGO = RGBColor(99, 102, 241) # #6366f1
    ACCENT_EMERALD = RGBColor(16, 185, 129)# #10b981
    ACCENT_CYAN = RGBColor(56, 189, 248)  # #38bdf8
    ACCENT_AMBER = RGBColor(251, 191, 36) # #fbbf24
    TEXT_WHITE = RGBColor(248, 250, 252)  # #f8fafc
    TEXT_MUTED = RGBColor(148, 163, 184) # #94a3b8

    # Uploaded image paths
    user_img_dir = r"C:\Users\mahin khan\.gemini\antigravity\brain\afae190f-5e51-4ebd-a26a-116387c34786\.user_uploaded"
    
    img_hero = os.path.join(user_img_dir, "media__1785448240113.png")
    img_voice_hero = os.path.join(user_img_dir, "media__1785448240121.png")
    img_plans = os.path.join(user_img_dir, "media__1785448240104.png")
    img_voice_plans = os.path.join(user_img_dir, "media__1785448240107.png")
    img_custom_builder = os.path.join(user_img_dir, "media__1785448240110.png")
    
    # Older admin screenshots for admin slides
    img_admin1 = os.path.join(user_img_dir, "media__1785445037668.png")
    img_admin2 = os.path.join(user_img_dir, "media__1785445037670.png")
    img_admin3 = os.path.join(user_img_dir, "media__1785445037678.png")

    def add_bg(slide):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, Inches(13.333), Inches(7.5))
        bg.fill.solid()
        bg.fill.fore_color.rgb = BG_DARK
        bg.line.fill.background()

    def add_header(slide, title_text, category_text="MK NET ISP SYSTEM ARCHITECTURE"):
        cat_box = slide.shapes.add_textbox(Inches(0.8), Inches(0.4), Inches(11.5), Inches(0.4))
        tf_cat = cat_box.text_frame
        p_cat = tf_cat.paragraphs[0]
        p_cat.text = category_text.upper()
        p_cat.font.size = Pt(10)
        p_cat.font.bold = True
        p_cat.font.color.rgb = ACCENT_CYAN

        title_box = slide.shapes.add_textbox(Inches(0.8), Inches(0.7), Inches(11.5), Inches(0.8))
        tf_title = title_box.text_frame
        p_title = tf_title.paragraphs[0]
        p_title.text = title_text
        p_title.font.size = Pt(24)
        p_title.font.bold = True
        p_title.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 1: Title Cover (World-Class Executive Cover)
    # -------------------------------------------------------------
    slide1 = prs.slides.add_slide(blank_layout)
    add_bg(slide1)

    card1 = slide1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.0), Inches(11.733), Inches(5.5))
    card1.fill.solid()
    card1.fill.fore_color.rgb = CARD_BG
    card1.line.color.rgb = ACCENT_INDIGO
    card1.line.width = Pt(2)

    tb = slide1.shapes.add_textbox(Inches(1.2), Inches(1.5), Inches(10.9), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True

    p0 = tf.paragraphs[0]
    p0.text = "MK NET ISP BILLING & CUSTOMER SELF CARE PORTAL"
    p0.font.size = Pt(12)
    p0.font.bold = True
    p0.font.color.rgb = ACCENT_CYAN

    p1 = tf.add_paragraph()
    p1.text = "Commercial-Grade ISP Portal & Bilingual AI Voice Platform"
    p1.font.size = Pt(32)
    p1.font.bold = True
    p1.font.color.rgb = TEXT_WHITE

    p2 = tf.add_paragraph()
    p2.text = "An international-standard, commercial-ready ISP platform featuring 1-30 day flexi-plan renewals, automated bKash/Nagad payment settlement, glassmorphic dark theme, role-based admin governance, and a proactive AI Voice Assistant."
    p2.font.size = Pt(14)
    p2.font.color.rgb = TEXT_MUTED

    p3 = tf.add_paragraph()
    p3.text = "Comprehensive 18-Slide Master Product Specification Deck"
    p3.font.size = Pt(12)
    p3.font.bold = True
    p3.font.color.rgb = ACCENT_EMERALD

    # -------------------------------------------------------------
    # SLIDE 2: Executive Summary & System Vision
    # -------------------------------------------------------------
    slide2 = prs.slides.add_slide(blank_layout)
    add_bg(slide2)
    add_header(slide2, "Executive Summary: Next-Gen ISP Platform", "System Vision")

    col_w = Inches(3.7)
    lefts = [0.8, 4.8, 8.8]
    summaries = [
        ("⚡ 1-30 Day Flexi Plans", "Eliminates monthly lock-in by providing dynamic per-day rates for 1, 3, 7, 15, and 30 days."),
        ("💳 MFS Automated Payments", "Seamless integration with bKash, Nagad, Rocket, Upay & Cards with instant payment verification."),
        ("🤖 Bilingual AI Assistant", "Proactive hands-free voice chat supporting Bengali (bn-BD), English, and Banglish script.")
    ]

    for idx, (title, desc) in enumerate(summaries):
        box = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(lefts[idx]), Inches(1.6), col_w, Inches(5.2))
        box.fill.solid()
        box.fill.fore_color.rgb = CARD_BG
        box.line.color.rgb = ACCENT_INDIGO

        tb_s = slide2.shapes.add_textbox(Inches(lefts[idx] + 0.2), Inches(1.9), Inches(3.3), Inches(4.6))
        tf_s = tb_s.text_frame
        tf_s.word_wrap = True

        p_t = tf_s.paragraphs[0]
        p_t.text = title
        p_t.font.size = Pt(18)
        p_t.font.bold = True
        p_t.font.color.rgb = ACCENT_CYAN

        p_d = tf_s.add_paragraph()
        p_d.text = desc
        p_d.font.size = Pt(13)
        p_d.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 3: Problem vs Solution Matrix
    # -------------------------------------------------------------
    slide3 = prs.slides.add_slide(blank_layout)
    add_bg(slide3)
    add_header(slide3, "Problem vs. Solution Analysis", "Strategic Impact")

    # Left: Problems
    c_prob = slide3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(5.6), Inches(5.2))
    c_prob.fill.solid()
    c_prob.fill.fore_color.rgb = CARD_BG
    c_prob.line.color.rgb = RGBColor(239, 68, 68)

    tb_p = slide3.shapes.add_textbox(Inches(1.0), Inches(1.8), Inches(5.2), Inches(4.8))
    tf_p = tb_p.text_frame
    tf_p.word_wrap = True
    p = tf_p.paragraphs[0]
    p.text = "❌ Legacy ISP Barriers"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = RGBColor(252, 165, 165)

    probs = [
        "Rigid Fixed Monthly Cycles: Customer disconnects due to unbending 30-day billing.",
        "Manual MFS Reconciliations: Hour-long delays verifying bKash/Nagad transactions.",
        "NOC Overload: 60%+ support calls for basic tasks like router passwords and line status.",
        "Cluttered Non-Responsive UI: Poor mobile experience leading to customer frustration."
    ]
    for pb in probs:
        pi = tf_p.add_paragraph()
        pi.text = "• " + pb
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_MUTED

    # Right: Solution
    c_sol = slide3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.9), Inches(1.6), Inches(5.6), Inches(5.2))
    c_sol.fill.solid()
    c_sol.fill.fore_color.rgb = CARD_BG
    c_sol.line.color.rgb = ACCENT_EMERALD

    tb_sol = slide3.shapes.add_textbox(Inches(7.1), Inches(1.8), Inches(5.2), Inches(4.8))
    tf_sol = tb_sol.text_frame
    tf_sol.word_wrap = True
    p = tf_sol.paragraphs[0]
    p.text = "✅ MK Net Innovation"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    sols = [
        "1-30 Day Flexi Billing: Dynamic price calculation for short-term passes & renewals.",
        "Instant Automated Payment Verification: Multi-channel MFS receipt generation.",
        "24/7 Proactive AI Assistant: Web Speech Bengali/English engine with auto-ticket creation.",
        "Modern Glassmorphic Dark UI: 100% responsive interface across desktop & mobile."
    ]
    for sl in sols:
        pi = tf_sol.add_paragraph()
        pi.text = "• " + sl
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 4: Homepage & Portal Architecture (SHOWCASE SCREENSHOT)
    # -------------------------------------------------------------
    slide4 = prs.slides.add_slide(blank_layout)
    add_bg(slide4)
    add_header(slide4, "MK Net Homepage & Core Portal Layout", "Interface Showcase")

    # Left: Explanation
    tb_l = slide4.shapes.add_textbox(Inches(0.8), Inches(1.6), Inches(5.0), Inches(5.2))
    tf_l = tb_l.text_frame
    tf_l.word_wrap = True
    p = tf_l.paragraphs[0]
    p.text = "🌐 High-Impact Landing Page"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    features_hp = [
        "Sleek Glassmorphic Cyber Aesthetic: Opaque dark containers with glow borders.",
        "Real-Time Network Statistics: Live subscriber counts (50,000+), uptime SLA (99.99%), & BDIX bandwidth (200+ Gbps).",
        "Clear Navigation Bar: One-click links for Packages, Pay Bill, Self Care Portal, Coverage, & Contact.",
        "Promotional Call-to-Action: Direct engagement for new fiber optic connections."
    ]
    for f in features_hp:
        pi = tf_l.add_paragraph()
        pi.text = "• " + f
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # Right: Image
    if os.path.exists(img_hero):
        slide4.shapes.add_picture(img_hero, Inches(6.0), Inches(1.6), Inches(6.5))

    # -------------------------------------------------------------
    # SLIDE 5: Popular Packages Showcase (SHOWCASE SCREENSHOT)
    # -------------------------------------------------------------
    slide5 = prs.slides.add_slide(blank_layout)
    add_bg(slide5)
    add_header(slide5, "Popular Internet Plans & Quick Tools", "Residential & Business Plans")

    # Left: Image
    if os.path.exists(img_plans):
        slide5.shapes.add_picture(img_plans, Inches(0.8), Inches(1.6), Inches(6.5))

    # Right: Explanation
    tb_r = slide5.shapes.add_textbox(Inches(7.6), Inches(1.6), Inches(4.9), Inches(5.2))
    tf_r = tb_r.text_frame
    tf_r.word_wrap = True
    p = tf_r.paragraphs[0]
    p.text = "🚀 Fiber Packages & Tools"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_AMBER

    points_plans = [
        "Tiered Residential Plans: Starter (20 Mbps / 999 BDT) and Standard (50 Mbps / 1499 BDT).",
        "SME & Business Line: Business Bronze (50 Mbps / 3999 BDT) with dedicated priority SLA.",
        "Interactive Quick Package Finder: Instant speed & price range filtering.",
        "Live Coverage & Speed Test Widgets: District availability check & fast.com speed test launcher."
    ]
    for pt_item in points_plans:
        pi = tf_r.add_paragraph()
        pi.text = "• " + pt_item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 6: Interactive Custom Duration Builder (SHOWCASE SCREENSHOT)
    # -------------------------------------------------------------
    slide6 = prs.slides.add_slide(blank_layout)
    add_bg(slide6)
    add_header(slide6, "Flexi-Plan Interactive Custom Duration Builder", "Custom Billing Engine")

    # Left: Explanation
    tb_l6 = slide6.shapes.add_textbox(Inches(0.8), Inches(1.6), Inches(5.0), Inches(5.2))
    tf_l6 = tb_l6.text_frame
    tf_l6.word_wrap = True
    p = tf_l6.paragraphs[0]
    p.text = "⚡ Flexible Day Top-Up Engine"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    points_builder = [
        "Freedom of Duration: Choose 1, 3, 7, 15, or 30-day passes.",
        "Dynamic Rate Calculation: Automatic per-day pricing based on selected bandwidth.",
        "Regular User Unlocks: Short-term passes unlock automatically for verified regular subscribers.",
        "Seamless Checkout: Direct transition to MFS bill payment and instant activation."
    ]
    for pt_item in points_builder:
        pi = tf_l6.add_paragraph()
        pi.text = "• " + pt_item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # Right: Image
    if os.path.exists(img_custom_builder):
        slide6.shapes.add_picture(img_custom_builder, Inches(6.0), Inches(1.6), Inches(6.5))

    # -------------------------------------------------------------
    # SLIDE 7: Proactive AI Voice Assistant (SHOWCASE SCREENSHOT)
    # -------------------------------------------------------------
    slide7 = prs.slides.add_slide(blank_layout)
    add_bg(slide7)
    add_header(slide7, "Proactive AI Voice Assistant & Widget UI", "Artificial Intelligence")

    # Left: Image
    if os.path.exists(img_voice_hero):
        slide7.shapes.add_picture(img_voice_hero, Inches(0.8), Inches(1.6), Inches(6.5))

    # Right: Explanation
    tb_r7 = slide7.shapes.add_textbox(Inches(7.6), Inches(1.6), Inches(4.9), Inches(5.2))
    tf_r7 = tb_r7.text_frame
    tf_r7.word_wrap = True
    p = tf_r7.paragraphs[0]
    p.text = "🤖 Hands-Free AI Voice Assistant"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_INDIGO

    points_voice = [
        "Bilingual Speech Engine: Native Bengali (bn-BD), English (en-US), & Romanized Banglish.",
        "Continuous Auto-Listen Mode: Hands-free conversation mode that automatically re-arms mic after speaking.",
        "Proactive Action Chips: 1-tap quick actions for bill check, packages, line repair, & hotline.",
        "Bulletproof Query Fallback: Guarantees helpful guided answers for any query."
    ]
    for pt_item in points_voice:
        pi = tf_r7.add_paragraph()
        pi.text = "• " + pt_item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 8: AI Conversational Intelligence & Banglish Support
    # -------------------------------------------------------------
    slide8 = prs.slides.add_slide(blank_layout)
    add_bg(slide8)
    add_header(slide8, "Bilingual NLP & Conversational Intelligence", "Natural Language Processing")

    box_nlp = slide8.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_nlp.fill.solid()
    box_nlp.fill.fore_color.rgb = CARD_BG
    box_nlp.line.color.rgb = ACCENT_CYAN

    tb_nlp = slide8.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_nlp = tb_nlp.text_frame
    tf_nlp.word_wrap = True

    p = tf_nlp.paragraphs[0]
    p.text = "🧠 Multi-Language Intent Parser & Diagnostic Engine"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    nlp_capabilities = [
        "Banglish (Romanized Bengali) Parsing: Understands natural Bengali written in English script ('wifi er password change korbo kivabe', 'bill koto dibo').",
        "Wi-Fi Password & Router Setup Guide: 5-step router login (192.168.0.1) and security key change instructions.",
        "Outage Diagnostics & Auto-Tickets: Recognizes line issue terms ('slow net', 'los light', 'red light') and automatically creates high-priority NOC support tickets.",
        "Merchant Payment Info: Dynamically pulls live bKash and Nagad merchant numbers directly from database settings."
    ]
    for cap in nlp_capabilities:
        p_cap = tf_nlp.add_paragraph()
        p_cap.text = "• " + cap
        p_cap.font.size = Pt(13)
        p_cap.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 9: Automated MFS Payment & Billing Settlement
    # -------------------------------------------------------------
    slide9 = prs.slides.add_slide(blank_layout)
    add_bg(slide9)
    add_header(slide9, "Automated MFS Payment & Billing Settlement", "Financial Operations")

    col_w9 = Inches(3.7)
    lefts9 = [0.8, 4.8, 8.8]
    mfs_cards = [
        ("📱 Multi-Channel MFS", "Supports bKash, Nagad, Rocket, Upay, Debit/Credit Cards & Online Banking."),
        ("⚡ Manual & Auto Verification", "Instant transaction ID input verification by customers and admin payment audit panel."),
        ("📄 Automated PDF Invoices", "Generates itemized digital receipts with due date tracking and payment logs.")
    ]
    for idx, (title, desc) in enumerate(mfs_cards):
        box = slide9.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(lefts9[idx]), Inches(1.6), col_w9, Inches(5.2))
        box.fill.solid()
        box.fill.fore_color.rgb = CARD_BG
        box.line.color.rgb = ACCENT_EMERALD

        tb_m = slide9.shapes.add_textbox(Inches(lefts9[idx] + 0.2), Inches(1.9), Inches(3.3), Inches(4.6))
        tf_m = tb_m.text_frame
        tf_m.word_wrap = True

        p_t = tf_m.paragraphs[0]
        p_t.text = title
        p_t.font.size = Pt(18)
        p_t.font.bold = True
        p_t.font.color.rgb = ACCENT_EMERALD

        p_d = tf_m.add_paragraph()
        p_d.text = desc
        p_d.font.size = Pt(13)
        p_d.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 10: Executive Admin Panel Overview (SHOWCASE SCREENSHOT)
    # -------------------------------------------------------------
    slide10 = prs.slides.add_slide(blank_layout)
    add_bg(slide10)
    add_header(slide10, "Executive Admin Control Center", "Administrative Governance")

    # Left: Explanation
    tb_l10 = slide10.shapes.add_textbox(Inches(0.8), Inches(1.6), Inches(5.0), Inches(5.2))
    tf_l10 = tb_l10.text_frame
    tf_l10.word_wrap = True
    p = tf_l10.paragraphs[0]
    p.text = "⚙️ Centralized Administration"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_INDIGO

    admin_overview = [
        "Executive Dashboard Widgets: Real-time overview of active subscribers, monthly revenue, and open tickets.",
        "Role-Based Admin Governance: Superadmin, Admin, and Staff access permissions.",
        "Admin Invitation System: Secure email-token invitation workflow for adding new administrators.",
        "Dark Cyber Theme: Unified executive styling across all management pages."
    ]
    for ao in admin_overview:
        pi = tf_l10.add_paragraph()
        pi.text = "• " + ao
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # Right: Image
    if os.path.exists(img_admin1):
        slide10.shapes.add_picture(img_admin1, Inches(6.0), Inches(1.6), Inches(6.5))

    # -------------------------------------------------------------
    # SLIDE 11: Admin User & Invitation Governance
    # -------------------------------------------------------------
    slide11 = prs.slides.add_slide(blank_layout)
    add_bg(slide11)
    add_header(slide11, "User Management & Admin Invitation Engine", "User Governance")

    box_u = slide11.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_u.fill.solid()
    box_u.fill.fore_color.rgb = CARD_BG
    box_u.line.color.rgb = ACCENT_CYAN

    tb_u = slide11.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_u = tb_u.text_frame
    tf_u.word_wrap = True

    p = tf_u.paragraphs[0]
    p.text = "👥 Customer & Admin Control Workflow"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    u_points = [
        "Account Status Management: One-click active, paused, or suspended status toggling.",
        "Regular User Flagging: Assign regular subscriber status to unlock custom 1-15 day top-up passes.",
        "Admin Invitation System: Generate secure 64-character token invite links for new administrators.",
        "Password Self-Service: Admin password reset and security management."
    ]
    for up in u_points:
        p_up = tf_u.add_paragraph()
        p_up.text = "• " + up
        p_up.font.size = Pt(13)
        p_up.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 12: Admin Package & Duration Plan Manager
    # -------------------------------------------------------------
    slide12 = prs.slides.add_slide(blank_layout)
    add_bg(slide12)
    add_header(slide12, "Package & Duration Plan Management", "Product Management")

    col_w12 = Inches(5.6)
    box_pkg = slide12.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), col_w12, Inches(5.2))
    box_pkg.fill.solid()
    box_pkg.fill.fore_color.rgb = CARD_BG
    box_pkg.line.color.rgb = ACCENT_INDIGO

    tb_pkg = slide12.shapes.add_textbox(Inches(1.0), Inches(1.8), Inches(5.2), Inches(4.8))
    tf_pkg = tb_pkg.text_frame
    tf_pkg.word_wrap = True
    p = tf_pkg.paragraphs[0]
    p.text = "📦 Package Management"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    pkg_items = [
        "Bandwidth & Speed Limits (Mbps)",
        "Residential, Business & Enterprise Categorization",
        "Monthly & Promo Pricing Control",
        "Feature Tagging & Popular Badges"
    ]
    for pi_item in pkg_items:
        pi = tf_pkg.add_paragraph()
        pi.text = "• " + pi_item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    box_dur = slide12.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.9), Inches(1.6), col_w12, Inches(5.2))
    box_dur.fill.solid()
    box_dur.fill.fore_color.rgb = CARD_BG
    box_dur.line.color.rgb = ACCENT_EMERALD

    tb_dur = slide12.shapes.add_textbox(Inches(7.1), Inches(1.8), Inches(5.2), Inches(4.8))
    tf_dur = tb_dur.text_frame
    tf_dur.word_wrap = True
    p = tf_dur.paragraphs[0]
    p.text = "⌛ Duration Plan Management"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    dur_items = [
        "1, 3, 7, 15, 30, 60, 90 Day Duration Presets",
        "Custom Duration Min/Max Limits",
        "Percentage Discount Configuration per Duration",
        "Active / Inactive Plan Visibility Toggles"
    ]
    for di_item in dur_items:
        pi = tf_dur.add_paragraph()
        pi.text = "• " + di_item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 13: Admin Content, Ads & Banner Engine
    # -------------------------------------------------------------
    slide13 = prs.slides.add_slide(blank_layout)
    add_bg(slide13)
    add_header(slide13, "Content Engine, Banners & Ads Manager", "Marketing Control")

    box_ads = slide13.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_ads.fill.solid()
    box_ads.fill.fore_color.rgb = CARD_BG
    box_ads.line.color.rgb = ACCENT_AMBER

    tb_ads = slide13.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_ads = tb_ads.text_frame
    tf_ads.word_wrap = True

    p = tf_ads.paragraphs[0]
    p.text = "📢 Promotional Ads & Marquee Announcements"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_AMBER

    ad_points = [
        "Multi-Placement Ad Support: Top Marquee Announcement Bar, Hero Slider Cards, and Selfcare Portal Modal Popups.",
        "Image & Banner Upload Manager: File upload handling with automatic filename timestamping and directory management.",
        "Global Broadcast Announcements: Publish network outage warnings or holiday notices across the entire website.",
        "News & Community Events: Publish blog articles, expansion news, and community stories."
    ]
    for ap in ad_points:
        p_ap = tf_ads.add_paragraph()
        p_ap.text = "• " + ap
        p_ap.font.size = Pt(13)
        p_ap.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 14: Coverage Area & Network Infrastructure Manager
    # -------------------------------------------------------------
    slide14 = prs.slides.add_slide(blank_layout)
    add_bg(slide14)
    add_header(slide14, "Coverage Areas & Infrastructure Governance", "Network Infrastructure")

    box_cov = slide14.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_cov.fill.solid()
    box_cov.fill.fore_color.rgb = CARD_BG
    box_cov.line.color.rgb = ACCENT_INDIGO

    tb_cov = slide14.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_cov = tb_cov.text_frame
    tf_cov.word_wrap = True

    p = tf_cov.paragraphs[0]
    p.text = "📍 Network Coverage Directory"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    cov_points = [
        "District & Upazila Management: Configure active covered regions and planned expansion zones.",
        "Coverage Status Badges: Covered (Active), Planned (Upcoming), and Unavailable status indicators.",
        "Customer Coverage Checker: Allows website visitors to search their district and upazila for immediate availability.",
        "Expansion Roadmap: Showcase planned fiber expansion zones to potential subscribers."
    ]
    for cp in cov_points:
        p_cp = tf_cov.add_paragraph()
        p_cp.text = "• " + cp
        p_cp.font.size = Pt(13)
        p_cp.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 15: Support Ticket System & NOC Operations
    # -------------------------------------------------------------
    slide15 = prs.slides.add_slide(blank_layout)
    add_bg(slide15)
    add_header(slide15, "Support Ticket System & NOC Operations", "Customer Support")

    box_tkt = slide15.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_tkt.fill.solid()
    box_tkt.fill.fore_color.rgb = CARD_BG
    box_tkt.line.color.rgb = ACCENT_EMERALD

    tb_tkt = slide15.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_tkt = tb_tkt.text_frame
    tf_tkt.word_wrap = True

    p = tf_tkt.paragraphs[0]
    p.text = "🎫 Helpdesk & NOC Technical Support Workflow"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    tkt_points = [
        "Automated & Manual Ticket Logging: Tickets generated automatically by AI Voice Assistant or manually by users.",
        "SLA & Priority Management: Technical, Billing, Sales, & General departments with Low, Medium, High, & Urgent priorities.",
        "Staff Assignment: Assign tickets to specific support engineers for resolution tracking.",
        "Threaded Customer Messaging: Real-time message exchange between customers and support staff."
    ]
    for tp in tkt_points:
        p_tp = tf_tkt.add_paragraph()
        p_tp.text = "• " + tp
        p_tp.font.size = Pt(13)
        p_tp.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 16: Security Architecture & Vulnerability Audits
    # -------------------------------------------------------------
    slide16 = prs.slides.add_slide(blank_layout)
    add_bg(slide16)
    add_header(slide16, "Security Architecture & Vulnerability Audits", "System Security")

    sec_items = [
        ("🔒 CSRF Defense", "Every state-changing POST and GET action is validated with cryptographic CSRF tokens."),
        ("🛡️ SQL Injection Prevention", "100% prepared statement execution via PDO. Zero raw string concatenation."),
        ("🧹 XSS & Data Escaping", "All output sanitized using HTML special characters to prevent cross-site scripting."),
        ("🔑 Password Hashing", "Bcrypt hashing algorithm with automatic legacy password hash upgrade bootstrapper.")
    ]

    for idx, (title, desc) in enumerate(sec_items):
        row = idx // 2
        col = idx % 2
        px = 0.8 + col * 6.0
        py = 1.6 + row * 2.6

        card = slide16.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(px), Inches(py), Inches(5.6), Inches(2.2))
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = ACCENT_CYAN

        tb_s = slide16.shapes.add_textbox(Inches(px + 0.2), Inches(py + 0.2), Inches(5.2), Inches(1.8))
        tf_s = tb_s.text_frame
        tf_s.word_wrap = True

        p_s = tf_s.paragraphs[0]
        p_s.text = title
        p_s.font.size = Pt(16)
        p_s.font.bold = True
        p_s.font.color.rgb = ACCENT_CYAN

        p_d = tf_s.add_paragraph()
        p_d.text = desc
        p_d.font.size = Pt(12)
        p_d.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 17: Database Self-Healing Architecture
    # -------------------------------------------------------------
    slide17 = prs.slides.add_slide(blank_layout)
    add_bg(slide17)
    add_header(slide17, "Database Self-Healing & Auto-Bootstrapper", "Database Resilience")

    box_db = slide17.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    box_db.fill.solid()
    box_db.fill.fore_color.rgb = CARD_BG
    box_db.line.color.rgb = ACCENT_INDIGO

    tb_db = slide17.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_db = tb_db.text_frame
    tf_db.word_wrap = True

    p = tf_db.paragraphs[0]
    p.text = "🗄️ Automated MySQL Schema Migration & Verification"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    db_points = [
        "24 Auto-Bootstrapping Tables: Automatic CREATE TABLE IF NOT EXISTS routines for all core system tables.",
        "Dynamic Column Verification: Dynamic SHOW COLUMNS checks automatically append missing columns (e.g. duration_days, account_status).",
        "Zero Downtime Migrations: Prevents database errors during deployment without manual SQL script execution.",
        "Master SQL Import Backup: Full mknet.sql master schema file for fresh server deployments."
    ]
    for db_p in db_points:
        p_db = tf_db.add_paragraph()
        p_db.text = "• " + db_p
        p_db.font.size = Pt(13)
        p_db.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 18: Commercial Impact & NOC Conclusion (Hero Finale)
    # -------------------------------------------------------------
    slide18 = prs.slides.add_slide(blank_layout)
    add_bg(slide18)

    card_fin = slide18.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.0), Inches(11.733), Inches(5.5))
    card_fin.fill.solid()
    card_fin.fill.fore_color.rgb = CARD_BG
    card_fin.line.color.rgb = ACCENT_EMERALD
    card_fin.line.width = Pt(2)

    tb_f = slide18.shapes.add_textbox(Inches(1.2), Inches(1.4), Inches(10.9), Inches(4.7))
    tf_f = tb_f.text_frame
    tf_f.word_wrap = True

    p = tf_f.paragraphs[0]
    p.text = "COMMERCIAL IMPACT & CONCLUSION"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    p = tf_f.add_paragraph()
    p.text = "Production-Ready Commercial ISP Portal & AI Platform"
    p.font.size = Pt(28)
    p.font.bold = True
    p.font.color.rgb = TEXT_WHITE

    p = tf_f.add_paragraph()
    p.text = "MK Net is fully engineered, audited, and optimized for high-volume commercial ISP operations. Reduces support call volume by over 60%, accelerates MFS payment settlement, and provides subscribers with 100% self-care freedom."
    p.font.size = Pt(14)
    p.font.color.rgb = TEXT_MUTED

    p = tf_f.add_paragraph()
    p.text = "📍 MK Net ISP • NOC Hotline: 16335 / 09678-123123 • Email: support@mknet.com"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    # Save presentation
    output_path = os.path.join(os.getcwd(), "MK_Net_ISP_Master_Presentation_18Slides.pptx")
    prs.save(output_path)
    print(f"Master 18-Slide Presentation successfully created at: {output_path}")

if __name__ == "__main__":
    create_mega_deck()
