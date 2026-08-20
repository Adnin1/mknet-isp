# -*- coding: utf-8 -*-
"""
MK Net International Standard Presentation Generator (.pptx)
"""

import sys
import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE

def create_deck():
    prs = Presentation()
    # Set 16:9 Widescreen aspect ratio
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)

    blank_layout = prs.slide_layouts[6]

    # Color Palette
    BG_DARK = RGBColor(11, 15, 25)        # #0b0f19
    CARD_BG = RGBColor(30, 41, 59)        # #1e293b
    ACCENT_INDIGO = RGBColor(99, 102, 241) # #6366f1
    ACCENT_EMERALD = RGBColor(16, 185, 129)# #10b981
    ACCENT_CYAN = RGBColor(56, 189, 248)  # #38bdf8
    TEXT_WHITE = RGBColor(248, 250, 252)  # #f8fafc
    TEXT_MUTED = RGBColor(148, 163, 184) # #94a3b8

    def add_bg(slide):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, Inches(13.333), Inches(7.5))
        bg.fill.solid()
        bg.fill.fore_color.rgb = BG_DARK
        bg.line.fill.background()

    def add_header(slide, title_text, category_text="MK NET ISP SYSTEM ARCHITECTURE"):
        # Category Tag
        cat_box = slide.shapes.add_textbox(Inches(0.8), Inches(0.4), Inches(11.5), Inches(0.4))
        tf_cat = cat_box.text_frame
        p_cat = tf_cat.paragraphs[0]
        p_cat.text = category_text.upper()
        p_cat.font.size = Pt(10)
        p_cat.font.bold = True
        p_cat.font.color.rgb = ACCENT_CYAN

        # Title
        title_box = slide.shapes.add_textbox(Inches(0.8), Inches(0.7), Inches(11.5), Inches(0.8))
        tf_title = title_box.text_frame
        p_title = tf_title.paragraphs[0]
        p_title.text = title_text
        p_title.font.size = Pt(24)
        p_title.font.bold = True
        p_title.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 1: Title Cover (Executive Hero Slide)
    # -------------------------------------------------------------
    slide1 = prs.slides.add_slide(blank_layout)
    add_bg(slide1)

    # Decorative Card Box
    card1 = slide1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(1.0), Inches(1.2), Inches(11.333), Inches(5.1))
    card1.fill.solid()
    card1.fill.fore_color.rgb = CARD_BG
    card1.line.color.rgb = ACCENT_INDIGO
    card1.line.width = Pt(2)

    tb = slide1.shapes.add_textbox(Inches(1.5), Inches(1.8), Inches(10.333), Inches(3.8))
    tf = tb.text_frame
    tf.word_wrap = True

    p0 = tf.paragraphs[0]
    p0.text = "MK NET ISP BILLING & CUSTOMER SELF CARE PORTAL"
    p0.font.size = Pt(12)
    p0.font.bold = True
    p0.font.color.rgb = ACCENT_CYAN

    p1 = tf.add_paragraph()
    p1.text = "Commercial-Grade ISP Portal & Proactive AI Voice Platform"
    p1.font.size = Pt(32)
    p1.font.bold = True
    p1.font.color.rgb = TEXT_WHITE

    p2 = tf.add_paragraph()
    p2.text = "An international-standard, end-to-end ISP billing software featuring automated 1-30 day flexi-plan renewals, instant bKash/Nagad payment settlement, role-based admin control center, and a bilingual AI Voice Assistant."
    p2.font.size = Pt(14)
    p2.font.color.rgb = TEXT_MUTED

    p3 = tf.add_paragraph()
    p3.text = "Developed with PHP 8.2 • MySQL Self-Healing Schema • Web Speech API • Glassmorphism UI/UX"
    p3.font.size = Pt(11)
    p3.font.bold = True
    p3.font.color.rgb = ACCENT_EMERALD

    # -------------------------------------------------------------
    # SLIDE 2: Executive Overview & Problem-Solution
    # -------------------------------------------------------------
    slide2 = prs.slides.add_slide(blank_layout)
    add_bg(slide2)
    add_header(slide2, "Executive Overview: Solving Legacy ISP Challenges", "Strategic Platform Vision")

    # Left Box - Problem
    c_prob = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(5.6), Inches(5.2))
    c_prob.fill.solid()
    c_prob.fill.fore_color.rgb = CARD_BG
    c_prob.line.color.rgb = RGBColor(239, 68, 68) # Red

    tb_p = slide2.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(5.0), Inches(4.6))
    tf_p = tb_p.text_frame
    tf_p.word_wrap = True

    p = tf_p.paragraphs[0]
    p.text = "❌ Legacy ISP Operational Barriers"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = RGBColor(252, 165, 165)

    items_p = [
        "Rigid Fixed Monthly Billing: Customers locked into 30-day cycles with no short-term or custom day options.",
        "Manual Payment Bottlenecks: Delayed bKash/Nagad manual matching causing customer line suspensions.",
        "High NOC Support Expenses: Support call centers overwhelmed by repetitive queries like router passwords or line status.",
        "Static Outdated Portals: Cluttered, non-responsive user interfaces with poor mobile usability."
    ]
    for item in items_p:
        pi = tf_p.add_paragraph()
        pi.text = "• " + item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_MUTED

    # Right Box - Solution
    c_sol = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.9), Inches(1.6), Inches(5.6), Inches(5.2))
    c_sol.fill.solid()
    c_sol.fill.fore_color.rgb = CARD_BG
    c_sol.line.color.rgb = ACCENT_EMERALD

    tb_s = slide2.shapes.add_textbox(Inches(7.2), Inches(1.9), Inches(5.0), Inches(4.6))
    tf_s = tb_s.text_frame
    tf_s.word_wrap = True

    p = tf_s.paragraphs[0]
    p.text = "✅ MK Net Platform Solutions"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    items_s = [
        "Automated Flexi-Plan Duration Engine: Customers freely choose 1, 3, 7, 15, or 30-day packages.",
        "Instant Automated Payment Matching: Automated bKash, Nagad, Rocket & Upay transaction verification.",
        "Proactive 24/7 AI Voice Assistant: Hands-free Bengali & English speech recognition with auto-ticket creation.",
        "State-of-the-Art Glassmorphic UI: Next-gen dark mode aesthetic optimized for mobile, tablet, & desktop."
    ]
    for item in items_s:
        pi = tf_s.add_paragraph()
        pi.text = "• " + item
        pi.font.size = Pt(12)
        pi.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 3: System Architecture & Core Stack
    # -------------------------------------------------------------
    slide3 = prs.slides.add_slide(blank_layout)
    add_bg(slide3)
    add_header(slide3, "Enterprise System Architecture & Tech Stack", "Technical Foundation")

    cols = [
        ("🖥️ Core Backend", "PHP 8.2+ Architecture\n• Strict typing & modular OOP\n• CSRF token security layer\n• Prepared Statement PDO SQL\n• Centralized config bootstrapper"),
        ("🗄️ Database Layer", "MySQL 8 Self-Healing Schema\n• 24 Auto-bootstrapping tables\n• Dynamic column auto-migration\n• Zero data loss schema sync\n• Indexed query optimization"),
        ("🎨 UI/UX & Design", "Modern Glassmorphic Engine\n• Vanilla CSS custom tokens\n• Responsive grid & flex layout\n• Plus Jakarta Sans typography\n• Dark Cyber aesthetic"),
        ("🤖 AI & Voice Engine", "Web Speech & NLP Engine\n• Dual bn-BD & en-US recognition\n• Natural Speech Synthesis (TTS)\n• ISP intent pattern matching\n• Auto-ticket dispatcher")
    ]

    lefts = [0.8, 3.8, 6.8, 9.8]
    for idx, (title, content) in enumerate(cols):
        box = slide3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(lefts[idx]), Inches(1.6), Inches(2.7), Inches(5.2))
        box.fill.solid()
        box.fill.fore_color.rgb = CARD_BG
        box.line.color.rgb = ACCENT_INDIGO

        tb_col = slide3.shapes.add_textbox(Inches(lefts[idx] + 0.15), Inches(1.8), Inches(2.4), Inches(4.8))
        tf_col = tb_col.text_frame
        tf_col.word_wrap = True

        p_t = tf_col.paragraphs[0]
        p_t.text = title
        p_t.font.size = Pt(15)
        p_t.font.bold = True
        p_t.font.color.rgb = ACCENT_CYAN

        p_c = tf_col.add_paragraph()
        p_c.text = content
        p_c.font.size = Pt(11)
        p_c.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 4: Admin Control Center
    # -------------------------------------------------------------
    slide4 = prs.slides.add_slide(blank_layout)
    add_bg(slide4)
    add_header(slide4, "Executive Admin Control Center Modules", "Administrative Governance")

    admin_features = [
        ("👥 User & Role Manager", "Invite & manage admins via email tokens, suspend/resume customer lines, assign regular user status."),
        ("📦 Package & Price Engine", "Manage residential, business, and enterprise packages, custom day discount rates, and speed limits."),
        ("💰 Verification & Billing", "Inspect pending bKash/Nagad payment submissions, verify transaction IDs, and issue receipts."),
        ("⚙️ System Settings & Themes", "Configure hotline numbers, merchant numbers, upload site logos, and switch visual themes."),
        ("📣 Ads & Announcements", "Publish top announcement bars, hero cards, promo popups, and community news stories."),
        ("🎫 Ticket & NOC Operations", "Manage technical support tickets, SLA priorities, staff assignments, and customer responses.")
    ]

    positions = [
        (0.8, 1.6), (4.8, 1.6), (8.8, 1.6),
        (0.8, 4.3), (4.8, 4.3), (8.8, 4.3)
    ]

    for idx, (title, desc) in enumerate(admin_features):
        pos_x, pos_y = positions[idx]
        card = slide4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(pos_x), Inches(pos_y), Inches(3.7), Inches(2.4))
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = ACCENT_EMERALD

        tb_af = slide4.shapes.add_textbox(Inches(pos_x + 0.15), Inches(pos_y + 0.15), Inches(3.4), Inches(2.1))
        tf_af = tb_af.text_frame
        tf_af.word_wrap = True

        p_af = tf_af.paragraphs[0]
        p_af.text = title
        p_af.font.size = Pt(14)
        p_af.font.bold = True
        p_af.font.color.rgb = ACCENT_EMERALD

        p_d = tf_af.add_paragraph()
        p_d.text = desc
        p_d.font.size = Pt(11)
        p_d.font.color.rgb = TEXT_MUTED

    # -------------------------------------------------------------
    # SLIDE 5: Customer Self Care Portal
    # -------------------------------------------------------------
    slide5 = prs.slides.add_slide(blank_layout)
    add_bg(slide5)
    add_header(slide5, "Customer Self Care Portal & Flexi-Billing", "End-User Experience")

    # Feature 1: Flexi Plan
    b1 = slide5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(3.7), Inches(5.2))
    b1.fill.solid()
    b1.fill.fore_color.rgb = CARD_BG
    b1.line.color.rgb = ACCENT_INDIGO

    tb1 = slide5.shapes.add_textbox(Inches(0.95), Inches(1.8), Inches(3.4), Inches(4.8))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    p = tf1.paragraphs[0]
    p.text = "⚡ 1-30 Day Flexi Plan"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    p = tf1.add_paragraph()
    p.text = "• Regular Users: Select 1, 3, 7, 15, or 30 days.\n• Instant Price Calculation: Dynamic per-day rate computation.\n• Flexible Top-ups: Ideal for short-term travelers & regular subscribers."
    p.font.size = Pt(11)
    p.font.color.rgb = TEXT_WHITE

    # Feature 2: Automated Payments
    b2 = slide5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(4.8), Inches(1.6), Inches(3.7), Inches(5.2))
    b2.fill.solid()
    b2.fill.fore_color.rgb = CARD_BG
    b2.line.color.rgb = ACCENT_INDIGO

    tb2 = slide5.shapes.add_textbox(Inches(4.95), Inches(1.8), Inches(3.4), Inches(4.8))
    tf2 = tb2.text_frame
    tf2.word_wrap = True
    p = tf2.paragraphs[0]
    p.text = "💳 Automated Payments"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    p = tf2.add_paragraph()
    p.text = "• MFS Gateways: bKash, Nagad, Rocket, Upay & Cards.\n• Instant Verification: Manual TrxID verification module.\n• PDF Receipts: Automated invoice history & payment logs."
    p.font.size = Pt(11)
    p.font.color.rgb = TEXT_WHITE

    # Feature 3: Hardware Store
    b3 = slide5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(8.8), Inches(1.6), Inches(3.7), Inches(5.2))
    b3.fill.solid()
    b3.fill.fore_color.rgb = CARD_BG
    b3.line.color.rgb = ACCENT_INDIGO

    tb3 = slide5.shapes.add_textbox(Inches(8.95), Inches(1.8), Inches(3.4), Inches(4.8))
    tf3 = tb3.text_frame
    tf3.word_wrap = True
    p = tf3.paragraphs[0]
    p.text = "🛒 Hardware & Addon Store"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    p = tf3.add_paragraph()
    p.text = "• WiFi 6 Routers: High-speed dual-band devices.\n• Gigabit ONUs & Cables: Fiber optic accessories.\n• Real IP Addons: Dedicated IP purchasing for gaming & hosting."
    p.font.size = Pt(11)
    p.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 6: Proactive AI Voice Assistant & Chatbot
    # -------------------------------------------------------------
    slide6 = prs.slides.add_slide(blank_layout)
    add_bg(slide6)
    add_header(slide6, "Proactive AI Voice Assistant & Diagnostic Engine", "Artificial Intelligence & Voice")

    c_voice = slide6.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.6), Inches(11.733), Inches(5.2))
    c_voice.fill.solid()
    c_voice.fill.fore_color.rgb = CARD_BG
    c_voice.line.color.rgb = ACCENT_INDIGO

    tb_v = slide6.shapes.add_textbox(Inches(1.1), Inches(1.9), Inches(11.1), Inches(4.6))
    tf_v = tb_v.text_frame
    tf_v.word_wrap = True

    p = tf_v.paragraphs[0]
    p.text = "🤖 Bilingual Speech Recognition & Automated Support Ticket Dispatcher"
    p.font.size = Pt(18)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    voice_points = [
        "Dual Language Web Speech API: Native Bengali (bn-BD), English (en-US), and Romanized Banglish speech recognition.",
        "Interactive Hands-Free Auto-Listen Mode: Continuous voice conversation mode that automatically re-arms the microphone after text-to-speech audio completes.",
        "Proactive Quick-Action Chips: 1-tap quick actions for bill check, package speed, router restart, and NOC helpline.",
        "Automated Diagnostic Support Tickets: Automatically detects connection outages or LOS light issues and logs high-priority support tickets instantly."
    ]
    for vp in voice_points:
        p_vp = tf_v.add_paragraph()
        p_vp.text = "• " + vp
        p_vp.font.size = Pt(13)
        p_vp.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 7: Security & Reliability Audit
    # -------------------------------------------------------------
    slide7 = prs.slides.add_slide(blank_layout)
    add_bg(slide7)
    add_header(slide7, "Security Engineering & Code Quality Audit", "System Integrity")

    sec_cards = [
        ("🔒 CSRF Protection", "Every state-changing POST and GET request is validated with cryptographic CSRF tokens."),
        ("🛡️ SQL Injection Guard", "100% prepared statements via PDO. Zero raw unescaped SQL execution."),
        ("🧹 Input Sanitization", "All rendered data escaped using HTML special characters to prevent XSS attacks."),
        ("🔑 Password Hashing", "Bcrypt hashing algorithm with automatic legacy password hash upgrades.")
    ]

    for idx, (s_title, s_desc) in enumerate(sec_cards):
        row = idx // 2
        col = idx % 2
        px = 0.8 + col * 6.0
        py = 1.6 + row * 2.6

        card = slide7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(px), Inches(py), Inches(5.6), Inches(2.2))
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = ACCENT_CYAN

        tb_sc = slide7.shapes.add_textbox(Inches(px + 0.2), Inches(py + 0.2), Inches(5.2), Inches(1.8))
        tf_sc = tb_sc.text_frame
        tf_sc.word_wrap = True

        p_sc = tf_sc.paragraphs[0]
        p_sc.text = s_title
        p_sc.font.size = Pt(16)
        p_sc.font.bold = True
        p_sc.font.color.rgb = ACCENT_CYAN

        p_d = tf_sc.add_paragraph()
        p_d.text = s_desc
        p_d.font.size = Pt(12)
        p_d.font.color.rgb = TEXT_WHITE

    # -------------------------------------------------------------
    # SLIDE 8: Commercial Impact & Conclusion
    # -------------------------------------------------------------
    slide8 = prs.slides.add_slide(blank_layout)
    add_bg(slide8)

    card_fin = slide8.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(1.0), Inches(1.2), Inches(11.333), Inches(5.1))
    card_fin.fill.solid()
    card_fin.fill.fore_color.rgb = CARD_BG
    card_fin.line.color.rgb = ACCENT_EMERALD
    card_fin.line.width = Pt(2)

    tb_f = slide8.shapes.add_textbox(Inches(1.5), Inches(1.8), Inches(10.333), Inches(3.8))
    tf_f = tb_f.text_frame
    tf_f.word_wrap = True

    p = tf_f.paragraphs[0]
    p.text = "COMMERCIAL IMPACT & CONCLUSION"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = ACCENT_EMERALD

    p = tf_f.add_paragraph()
    p.text = "Production-Ready Commercial ISP Billing Software"
    p.font.size = Pt(28)
    p.font.bold = True
    p.font.color.rgb = TEXT_WHITE

    p = tf_f.add_paragraph()
    p.text = "MK Net is fully built, tested, and optimized for high-volume commercial ISP operations. Reduces call center load by over 60%, accelerates payment verification, and empowers customers with complete self-care control."
    p.font.size = Pt(13)
    p.font.color.rgb = TEXT_MUTED

    p = tf_f.add_paragraph()
    p.text = "📍 MK Net ISP • Hotline: 16335 / 09678-123123 • Email: support@mknet.com"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = ACCENT_CYAN

    # Save presentation
    output_path = os.path.join(os.getcwd(), "MK_Net_ISP_International_Presentation.pptx")
    prs.save(output_path)
    print(f"Presentation successfully created at: {output_path}")

if __name__ == "__main__":
    create_deck()
