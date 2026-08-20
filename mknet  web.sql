-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2026 at 05:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mknet`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--

CREATE TABLE `about` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`id`, `title`, `content`, `image`, `updated_at`) VALUES
(1, 'best team ever ', 'Content fixed in code.\r\n\r\nTo change text/images → must edit file manually.\r\n\r\nSuper admin cannot control from panel.', 'https://www.shutterstock.com/search/internet', '2026-02-23 19:28:38'),
(2, 'Our Vision', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', '/mknet/assets/images/about/mission.jpg', '2026-02-23 19:33:22'),
(3, 'Our Vision', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', '/mknet/assets/images/about/mission.jpg', '2026-02-23 19:33:25'),
(4, 'Our Vision', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', '/mknet/assets/images/about/mission.jpg', '2026-02-23 19:33:32'),
(5, 'content', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', 'https://www.shutterstock.com/search/internet', '2026-02-23 19:36:55'),
(6, 'Our Vision', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', '/mknet/assets/images/about/mission.jpg', '2026-02-23 19:40:11'),
(7, 'Our Vision', 'MK Net facilitates seamless information flow through innovative ICT infrastructure and services.', '/mknet/assets/images/about/vision.jpg', '2026-02-23 19:51:12'),
(8, 'Security Commitment', 'We are committed to information security and continual improvement, aligning with industry standards.', '/mknet/assets/images/about/security.jpg', '2026-02-23 19:51:12'),
(9, 'Customer Support', 'Quality service, simple pricing, and 24/7 support for our customers.', '/mknet/assets/images/about/support.jpg', '2026-02-23 19:51:12');

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `type` enum('recurring','one_time') NOT NULL DEFAULT 'recurring',
  `taxable` tinyint(1) NOT NULL DEFAULT 0,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `name`, `price`, `type`, `taxable`, `visible`, `created_at`) VALUES
(1, 'abir samar', 500.00, 'one_time', 0, 1, '2026-06-16 23:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT 'admin',
  `email` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`, `role`, `email`) VALUES
(1, 'admin', '$2y$10$EZcXlK.hHYxcPVEQjyhgme48S5bj/WcuXiX.yuolpDaVZ8JHlNkji', '2026-02-09 20:55:08', 'admin', NULL),
(3, 'superadmin', '$2y$10$zOZC/YbB23Mm./I6sALCmO8kJD/h0LlTeOByXsWD6FZ7c6vGa7EU6', '2026-02-20 10:17:57', 'super', NULL),
(8, 'admin2', '$2y$10$G1WdCAQILjakuesFl4CkLekuOMuCZBU4pZqgJH27XcM3H8bOd47Rm', '2026-02-20 10:25:00', 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_banners`
--

CREATE TABLE `admin_banners` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `banner_type` enum('top_banner','sidebar_ad','popup_ad') NOT NULL DEFAULT 'top_banner',
  `image_path` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT 'top_banner'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_invitations`
--

CREATE TABLE `admin_invitations` (
  `id` int(11) NOT NULL,
  `email` varchar(120) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'admin',
  `token` varchar(64) NOT NULL,
  `status` enum('pending','accepted','expired') NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `title`, `image`) VALUES
(1, 'Super Offer', 'images/offer.png'),
(2, 'Discount', 'images/discount.png'),
(3, 'Super Offer', 'images/offer.png'),
(4, 'Discount', 'images/discount.png');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'General',
  `priority` tinyint(1) DEFAULT 0,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`, `category`, `priority`, `expiry_date`) VALUES
(1, NULL, 'New Turbo Package launched!', '2026-02-04 13:25:12', 'General', 0, NULL),
(2, NULL, 'Coverage expanded to Bogra Sadar.', '2026-02-04 13:25:12', 'General', 0, NULL),
(3, NULL, 'Emergency Mode active in Dhaka.', '2026-02-04 13:25:12', 'General', 0, NULL),
(4, 'Welcome', 'Welcome to our ISP service!', '2026-02-09 21:50:48', 'General', 0, NULL),
(5, 'Outage', 'We will conduct maintenance on Friday.', '2026-02-09 21:50:48', 'General', 0, NULL),
(10, 'Maintenance', 'Scheduled maintenance on Friday.', '2026-02-09 22:21:59', 'General', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`id`, `user_id`, `amount`, `status`, `created_at`, `due_date`, `paid_date`, `transaction_id`, `auto_renew`) VALUES
(1, 7, 748.00, 'paid', '2026-06-24 03:13:11', '2026-02-02', '2026-06-16', NULL, 0),
(2, 8, 1200.00, 'paid', '2026-06-24 03:13:11', '2026-02-05', '2026-06-24', NULL, 0),
(3, 33, 748.00, 'pending', '2026-07-28 02:55:27', '0005-03-04', NULL, NULL, 0),
(4, 4, 700.00, 'pending', '2026-07-28 02:55:46', '2026-02-02', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `blockchain_payments`
--

CREATE TABLE `blockchain_payments` (
  `id` int(11) NOT NULL,
  `user_email` varchar(120) DEFAULT NULL,
  `package` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `tx_hash` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blockchain_payments`
--

INSERT INTO `blockchain_payments` (`id`, `user_email`, `package`, `amount`, `tx_hash`, `status`, `created_at`) VALUES
(1, 'rahman@mknet.com', 'Premium 50 Mbps', 1200.00, '0xABC123XYZ789', 'Verified', '2026-02-03 23:24:02');

-- --------------------------------------------------------

--
-- Table structure for table `bundles`
--

CREATE TABLE `bundles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `price` decimal(10,2) DEFAULT NULL,
  `discount_percent` int(11) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bundles`
--

INSERT INTO `bundles` (`id`, `name`, `description`, `items`, `price`, `discount_percent`, `visible`, `created_at`) VALUES
(1, 'abir samar', 'new user', '[{\"type\":\"package\",\"id\":5}]', 1800.00, 10, 1, '2026-06-16 23:46:03');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_slides`
--

CREATE TABLE `carousel_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_messages`
--

CREATE TABLE `chatbot_messages` (
  `id` int(11) NOT NULL,
  `user_message` text DEFAULT NULL,
  `bot_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_messages`
--

INSERT INTO `chatbot_messages` (`id`, `user_message`, `bot_reply`, `created_at`) VALUES
(1, 'hi', 'Hello! 👋 I\'m the MK Net assistant. Ask me about packages, prices, coverage, your bill, your usage, or say \"ticket\" to get help from our support team.', '2026-07-30 20:12:28'),
(2, 'hi . amar sathe kotha bolo', 'Hello! 👋 I\'m the MK Net assistant. Ask me about packages, prices, coverage, your bill, your usage, or say \"ticket\" to get help from our support team.', '2026-07-30 20:13:06'),
(3, 'hi', 'Hello! 👋 I\'m the MK Net assistant. Ask me about packages, prices, coverage, your bill, your usage, or say \"ticket\" to get help from our support team.', '2026-07-30 20:46:36'),
(4, 'thik', 'I\'m not fully sure about that one yet. Try asking about \"packages\", \"coverage\", \"my bill\", \"my usage\", or say \"ticket\" to reach our human support team.', '2026-07-30 21:03:25'),
(5, 'hi\'', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 21:19:01'),
(6, 'hey', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 21:23:33'),
(7, 'jo', 'I\'m specialized as MK Net\'s 24/7 ISP AI Assistant! 🤖\n\nI can help you with:\n• Packages & Custom Day Top-ups (1-30 days)\n• Bill Payment & bKash/Nagad Numbers\n• Wi-Fi Password Change & Router Restart\n• Connection Outage & Technical Support\n• Coverage Check & Hotline Contacts\n\nTry asking: \"How to change wifi password?\", \"What is my bill?\", or \"Show packages\"!', '2026-07-30 21:25:17'),
(8, 'jo', 'I\'m specialized as MK Net\'s 24/7 ISP AI Assistant! 🤖\n\nI can help you with:\n• Packages & Custom Day Top-ups (1-30 days)\n• Bill Payment & bKash/Nagad Numbers\n• Wi-Fi Password Change & Router Restart\n• Connection Outage & Technical Support\n• Coverage Check & Hotline Contacts\n\nTry asking: \"How to change wifi password?\", \"What is my bill?\", or \"Show packages\"!', '2026-07-30 21:26:42'),
(9, 'hui', 'I noticed your question: \"hui\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:28:29'),
(10, 'Check my bill', 'Good news — you have no outstanding bills right now. ✅', '2026-07-30 21:29:21'),
(11, 'Packages and price', 'Here are our current packages:\n• Starter (residential) — 20 Mbps — 999 BDT/month\n• Standard (residential) — 50 Mbps — 1,499 BDT/month\n• Business Bronze (residential) — 50 Mbps — 3,999 BDT/month\n• Basic (residential) — 50 Mbps — 500 BDT/month\n• Turbo (residential) — 100 Mbps — 1,999 BDT/month\n• Business Silver (residential) — 100 Mbps — 6,999 BDT/month\n• Basic (residential) — 100 Mbps — 100 BDT/month\n• Basic (residential) — 100 Mbps — 30 BDT/month\n\nWant a custom duration (1 day / 7 days / custom) instead of a full month? Visit Recharge / Renew after logging in — I can calculate the exact price for any number of days.', '2026-07-30 21:29:24'),
(12, 'My internet is slow', '🛠️ I have logged a Support Ticket (#TKT-1) for our technical engineering team! An engineer is reviewing your fiber connection now.\n\nQuick Troubleshooting Steps while you wait:\n1. Turn off your Wi-Fi router & ONU device for 30 seconds, then restart.\n2. Ensure the optical fiber cable is firmly plugged into the ONU PON port.\n3. Check if the LOS light on your ONU is blinking RED (indicates external fiber cut).', '2026-07-30 21:29:26'),
(13, 'Check my bill', 'Good news — you have no outstanding bills right now. ✅', '2026-07-30 21:29:37'),
(14, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 21:29:42'),
(15, 'fe', 'I noticed your question: \"fe\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:30:22'),
(16, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 21:30:23'),
(17, 'ki koro', 'I noticed your question: \"ki koro\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:30:26'),
(18, 'ki koro', 'I noticed your question: \"ki koro\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:32:41'),
(19, 'tumi kemon acho', 'আমি এমকে নেটের ভার্চুয়াল এআই অ্যাসিস্ট্যান্ট! 🤖 ইন্টারনেট প্যাকেজ, বিল পরিশোধ, রাউটার পাসওয়ার্ড ও লাইন সাপোর্টের জন্য ২৪ ঘণ্টা আপনার সেবায় নিয়োজিত।', '2026-07-30 21:33:48'),
(20, 'tell me about service', 'I noticed your question: \"tell me about service\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:34:03'),
(21, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 21:34:07'),
(22, 'how are you', 'I noticed your question: \"how are you\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:34:14'),
(23, 'are you sick', 'I noticed your question: \"are you sick\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 21:34:20'),
(24, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 22:07:55'),
(25, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-30 22:27:24'),
(26, 'kemon', 'I noticed your question: \"kemon\". As your MK Net AI Assistant, I\'m specialized in internet lines, packages, bill payments, and line support! 🤖\n\nHow can I assist you today? Feel free to ask about:\n• Internet Packages & Prices\n• Custom Day Top-ups (1-30 Days)\n• bKash / Nagad Bill Payment\n• Wi-Fi Password Change Setup\n• Helpline & Support Ticket', '2026-07-30 22:27:28'),
(27, 'package', 'Here are our current packages:\n• Starter (residential) — 20 Mbps — 999 টাকা/মাস\n• Standard (residential) — 50 Mbps — 1,499 টাকা/মাস\n• Business Bronze (residential) — 50 Mbps — 3,999 টাকা/মাস\n• Basic (residential) — 50 Mbps — 500 টাকা/মাস\n• Turbo (residential) — 100 Mbps — 1,999 টাকা/মাস\n• Business Silver (residential) — 100 Mbps — 6,999 টাকা/মাস\n• Basic (residential) — 100 Mbps — 100 টাকা/মাস\n• Basic (residential) — 100 Mbps — 30 টাকা/মাস\n\nWant a custom duration (1 day / 7 days / custom) instead of a full month? Visit Recharge / Renew after logging in — I can calculate the exact price for any number of days. [ACTION:PACKAGES]', '2026-07-30 22:27:35'),
(28, 'amar net off', 'আমি আপনার প্রশ্নটি পেয়েছি: \"amar net off\"। আমি এমকে নেটের ভার্চুয়াল এআই অ্যাসিস্ট্যান্ট! 🤖\n\nআপনাকে কীভাবে সাহায্য করতে পারি? আপনি জিজ্ঞেস করতে পারেন:\n• ইন্টারনেট প্যাকেজের দাম ও স্পিড\n• ১-৩০ দিনের কাস্টম রিচার্জ ফ্লেক্সি প্ল্যান\n• বিকাশ / নগদ / রকেট বিল পরিশোধ\n• ওয়াইফাই পাসওয়ার্ড পরিবর্তন নির্দেশনা\n• কারিগরি সমস্যা বা লাইন সাপোর্ট টিকেট', '2026-07-30 23:56:50'),
(29, 'contact hotline number', '📞 You can reach MK Net 24/7 Support Hotline at 16335 / 09678-123123, or email support@mknet.com.\n📍 HQ Office: Police Plaza Concord, Gulshan 1, Dhaka. Our NOC team monitors lines 24/7!', '2026-07-31 00:02:49'),
(30, 'প্যাকেজের দাম কত', 'I received your message: \"প্যাকেজের দাম কত\". As MK Net\'s AI Assistant, I\'m specialized in fiber broadband, packages, bill payments, and line support! 🤖\n\nHow can I help you today? Feel free to ask about:\n• Internet Packages & Speeds\n• Custom Flexi Top-ups (1-30 Days)\n• bKash / Nagad Bill Payments\n• Wi-Fi Password Reset Setup\n• Helpline & Support Ticket', '2026-07-31 00:07:49'),
(31, 'হ্যালো, কেমন আছেন?', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 00:07:51'),
(32, 'হ্যালো, কেমন আছেন?', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 00:07:51'),
(33, 'হ্যালো, কেমন আছেন?', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 00:07:52'),
(34, 'amar bill koto', '💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n• বিকাশ মার্চেন্ট নম্বর: 01700000000\n• নগদ মার্চেন্ট নম্বর: 01700000000\nলগইন করে আপনার নির্দিষ্ট ইনভয়েস ডাউনলোড ও বিকাশ/নগদে ইনস্ট্যান্ট বিল পরিশোধ করুন। [ACTION:PAY_BILL]', '2026-07-31 00:07:53'),
(35, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 14:32:49'),
(36, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 14:32:53'),
(37, 'hi', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 14:33:04'),
(38, 'net slow choltese', '🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n১. **ONU LOS লাল বাতি:** ফাইবার কেবল বিচ্ছিন্ন নির্দেশ করে।\n২. **পাওয়ার সাইকেল:** রাউটার ও ওএনইউ ডিভাইস ৩০ সেকেন্ড বন্ধ রেখে চালু করুন।\n৩. **সাপোর্ট টিকেট:** আপনার অ্যাকাউন্টে লগইন করে ১-ক্লিকে সাপোর্ট টিকেট ওপেন করুন। [ACTION:OPEN_TICKET]', '2026-07-31 14:33:04'),
(39, 'bill koto', '💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n• বিকাশ মার্চেন্ট নম্বর: 01700000000\n• নগদ মার্চেন্ট নম্বর: 01700000000\nলগইন করে আপনার নির্দিষ্ট ইনভয়েস ডাউনলোড ও বিকাশ/নগদে ইনস্ট্যান্ট বিল পরিশোধ করুন। [ACTION:PAY_BILL]', '2026-07-31 14:33:04'),
(40, 'kon package ta bhalo hobe', 'You can view our packages on the Packages tab.', '2026-07-31 14:33:04'),
(41, 'wifi password change korbo kivabe', '🔑 যেভাবে ওয়াইফাই পাসওয়ার্ড পরিবর্তন করবেন:\n১. আপনার রাউটারের সাথে কানেক্ট থেকে ব্রাউজারে ১৯২.১৬৮.০.১ অথবা ১৯২.১৬৮.১.১ লিখুন।\n২. রাউটারের ইউজার ও পাসওয়ার্ড দিয়ে লগইন করুন (সাধারণত admin/admin)।\n৩. Wireless -> Wireless Security অপশনে গিয়ে নতুন পাসওয়ার্ড দিন।\n৪. সেভ করে রাউটার রিস্টার্ট দিন!', '2026-07-31 14:33:04'),
(42, 'speed test', '🚀 Test your line speed on fast.com or speedtest.net! MK Net features 100 Mbps BDIX ultra-fast routing for zero buffer YouTube 4K streaming and high-speed BDIX FTP servers. [ACTION:SPEEDTEST]', '2026-07-31 14:33:04'),
(43, 'open ticket for line issue', '📞 You can reach MK Net 24/7 Support Hotline at 16335 / 09678-123123, or email support@mknet.com.\n📍 HQ Office: Police Plaza Concord, Gulshan 1, Dhaka. Our NOC team monitors lines 24/7!', '2026-07-31 14:33:04'),
(44, 'contact hotline number', '📞 You can reach MK Net 24/7 Support Hotline at 16335 / 09678-123123, or email support@mknet.com.\n📍 HQ Office: Police Plaza Concord, Gulshan 1, Dhaka. Our NOC team monitors lines 24/7!', '2026-07-31 14:33:04'),
(45, 'bikashe bill dibo kivabe', '💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n• বিকাশ মার্চেন্ট নম্বর: 01700000000\n• নগদ মার্চেন্ট নম্বর: 01700000000\nলগইন করে আপনার নির্দিষ্ট ইনভয়েস ডাউনলোড ও বিকাশ/নগদে ইনস্ট্যান্ট বিল পরিশোধ করুন। [ACTION:PAY_BILL]', '2026-07-31 14:33:04'),
(46, 'amar info', '👤 আপনার সুনির্দিষ্ট লাইন স্ট্যাটাস ও প্যাকেজের মেয়াদ দেখতে অনুগ্রহ করে অ্যাকাউন্টে লগইন করুন!', '2026-07-31 14:33:04'),
(47, 'contact hotline number', '📞 You can reach MK Net 24/7 Support Hotline at 16335 / 09678-123123, or email support@mknet.com.\n📍 HQ Office: Police Plaza Concord, Gulshan 1, Dhaka. Our NOC team monitors lines 24/7!', '2026-07-31 15:41:51'),
(48, 'হ্যালো, কেমন আছেন?', 'Hello! 👋 Welcome to MK Net AI Assistant. I can help you with packages, custom day recharge (1-30 days), bKash/Nagad payments, hardware store, coverage check, or log a support ticket!', '2026-07-31 15:46:37'),
(49, 'প্যাকেজের দাম কত', 'I received your message: \"প্যাকেজের দাম কত\". As MK Net\'s AI Assistant, I\'m specialized in fiber broadband, packages, bill payments, and line support! 🤖\n\nHow can I help you today? Feel free to ask about:\n• Internet Packages & Speeds\n• Custom Flexi Top-ups (1-30 Days)\n• bKash / Nagad Bill Payments\n• Wi-Fi Password Reset Setup\n• Helpline & Support Ticket', '2026-07-31 15:46:51'),
(50, 'net slow choltese', '🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n১. **ONU LOS লাল বাতি:** ফাইবার কেবল বিচ্ছিন্ন নির্দেশ করে।\n২. **পাওয়ার সাইকেল:** রাউটার ও ওএনইউ ডিভাইস ৩০ সেকেন্ড বন্ধ রেখে চালু করুন।\n৩. **সাপোর্ট টিকেট:** আপনার অ্যাকাউন্টে লগইন করে ১-ক্লিকে সাপোর্ট টিকেট ওপেন করুন। [ACTION:OPEN_TICKET]', '2026-07-31 15:47:02'),
(51, 'net slow choltese', '🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n১. **ONU LOS লাল বাতি:** ফাইবার কেবল বিচ্ছিন্ন নির্দেশ করে।\n২. **পাওয়ার সাইকেল:** রাউটার ও ওএনইউ ডিভাইস ৩০ সেকেন্ড বন্ধ রেখে চালু করুন।\n৩. **সাপোর্ট টিকেট:** আপনার অ্যাকাউন্টে লগইন করে ১-ক্লিকে সাপোর্ট টিকেট ওপেন করুন। [ACTION:OPEN_TICKET]', '2026-07-31 15:47:13'),
(52, 'net slow choltese', '🛠️ **এমকে নেট এআই টেকনিক্যাল সাপোর্ট গাইড:**\n১. **ONU LOS লাল বাতি:** ফাইবার কেবল বিচ্ছিন্ন নির্দেশ করে।\n২. **পাওয়ার সাইকেল:** রাউটার ও ওএনইউ ডিভাইস ৩০ সেকেন্ড বন্ধ রেখে চালু করুন।\n৩. **সাপোর্ট টিকেট:** আপনার অ্যাকাউন্টে লগইন করে ১-ক্লিকে সাপোর্ট টিকেট ওপেন করুন। [ACTION:OPEN_TICKET]', '2026-07-31 15:47:20'),
(53, 'amar bill koto', '💳 **এমকে নেট এআই বিলিং অ্যাসিস্ট্যান্ট:**\n• বিকাশ মার্চেন্ট নম্বর: 01700000000\n• নগদ মার্চেন্ট নম্বর: 01700000000\nলগইন করে আপনার নির্দিষ্ট ইনভয়েস ডাউনলোড ও বিকাশ/নগদে ইনস্ট্যান্ট বিল পরিশোধ করুন। [ACTION:PAY_BILL]', '2026-07-31 15:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_rules`
--

CREATE TABLE `chatbot_rules` (
  `id` int(11) NOT NULL,
  `keyword` varchar(100) DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_rules`
--

INSERT INTO `chatbot_rules` (`id`, `keyword`, `reply`, `created_at`) VALUES
(1, 'package', 'You can view our packages on the Packages tab.', '2026-02-23 20:08:00'),
(2, 'pay bill', 'To pay your bill, please go to the Pay Bill section.', '2026-02-23 20:08:00'),
(3, 'contact', 'You can reach us at info@mknet.com or call 16335.', '2026-02-23 20:08:00'),
(4, 'coverage', 'Check our Coverage Map tab to see if your area is supported.', '2026-02-23 20:08:00'),
(5, 'support', 'Our support team is available 24/7. Please submit your query via the Contact tab.', '2026-02-23 20:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `community_comments`
--

CREATE TABLE `community_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `badge` varchar(50) DEFAULT 'Member',
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `user_name`, `badge`, `title`, `content`, `created_at`) VALUES
(1, 'anonymous', 'Member', 'super fast internet', 'i am satisfied after using this internet service. their service and internet speed is best', '2026-02-23 20:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coverage`
--

CREATE TABLE `coverage` (
  `id` int(11) NOT NULL,
  `area` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coverage`
--

INSERT INTO `coverage` (`id`, `area`) VALUES
(1, 'Dhaka'),
(2, 'Bogura'),
(3, 'Rajshahi');

-- --------------------------------------------------------

--
-- Table structure for table `coverage_areas`
--

CREATE TABLE `coverage_areas` (
  `id` int(11) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `upazila` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `customers` int(11) DEFAULT 0,
  `upcoming_targets` text DEFAULT NULL,
  `sectors` int(11) DEFAULT 0,
  `homes` int(11) DEFAULT 0,
  `offices` int(11) DEFAULT 0,
  `present_status` text DEFAULT NULL,
  `updates` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coverage_areas`
--

INSERT INTO `coverage_areas` (`id`, `district`, `upazila`, `status`, `latitude`, `longitude`, `customers`, `upcoming_targets`, `sectors`, `homes`, `offices`, `present_status`, `updates`, `updated_at`) VALUES
(1, 'Bogra', 'Sadar', 'Covered', 24.846565, 89.377755, 0, NULL, 0, 0, 0, NULL, NULL, '2026-02-20 10:08:17'),
(2, 'Rajshahi', 'Boalia', 'Covered', 24.374500, 88.604200, 0, NULL, 0, 0, 0, NULL, NULL, '2026-02-20 10:08:17'),
(3, 'Dhaka', 'Gulshan', 'Covered', 23.780800, 90.416800, 0, NULL, 0, 0, 0, NULL, NULL, '2026-02-20 10:08:17'),
(4, 'Chattogram', 'Pahartali', 'Planned', 22.356900, 91.783200, 0, NULL, 0, 0, 0, NULL, NULL, '2026-02-20 10:08:17'),
(5, 'Bogra', 'Sherpur', '', NULL, NULL, 600, 'Target 2 unions', 6, 400, 200, 'Growing', 'New connections planned', '2026-02-20 10:08:26'),
(6, 'Chattogram', 'Anwara', '', NULL, NULL, 900, 'Expand to coastal belt', 10, 500, 400, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:08:26'),
(7, 'Chattogram', 'Sitakunda', '', NULL, NULL, 950, 'Target 3 industrial zones', 9, 450, 300, 'High demand', 'Industrial coverage expanded', '2026-02-20 10:08:26'),
(8, 'Rajshahi', 'Sadar', '', NULL, NULL, 850, 'Expand to suburbs', 11, 500, 350, 'Stable', 'New Turbo package launched', '2026-02-20 10:08:26'),
(9, 'Khulna', 'Sadar', '', NULL, NULL, 700, 'Target 2 wards', 7, 400, 300, 'Stable', 'Coverage expanded', '2026-02-20 10:08:26'),
(10, 'Sylhet', 'Sadar', '', NULL, NULL, 650, 'Expand to rural areas', 6, 350, 320, 'Stable', 'Fiber rollout ongoing', '2026-02-20 10:08:26'),
(11, 'Mymensingh', 'Sadar', '', NULL, NULL, 500, 'Target 3 unions', 5, 300, 200, 'Stable', 'New tower installation', '2026-02-20 10:08:26'),
(12, 'Cumilla', 'Sadar', '', NULL, NULL, 720, 'Target 4 unions', 8, 400, 320, 'Stable', 'Fiber expansion ongoing', '2026-02-20 10:08:26'),
(13, 'Noakhali', 'Sadar', '', NULL, NULL, 550, 'Expand to Subarnachar', 5, 280, 200, 'Stable', 'Coverage expanded', '2026-02-20 10:08:26'),
(14, 'Jessore', 'Sadar', '', NULL, NULL, 530, 'Target 2 wards', 6, 300, 230, 'Stable', 'New package launched', '2026-02-20 10:08:26'),
(15, 'Naogaon', 'Sadar', '', NULL, NULL, 510, 'Expand to Birampur', 7, 350, 250, 'Stable', 'Coverage expanded', '2026-02-20 10:08:26'),
(16, 'Pabna', 'Sadar', '', NULL, NULL, 450, 'Target 3 unions', 5, 270, 180, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:08:26'),
(17, 'Bhola', 'Sadar', '', NULL, NULL, 400, 'Expand to Bhola', 4, 250, 150, 'Stable', 'Coverage expanded', '2026-02-20 10:08:26'),
(18, 'Sunamganj', 'Sadar', '', NULL, NULL, 300, 'Target 2 unions', 3, 180, 120, 'Stable', 'New tower installation', '2026-02-20 10:08:26'),
(19, 'Bogra', 'Sherpur', 'Available', NULL, NULL, 600, 'Target 2 unions', 6, 400, 200, 'Growing', 'New connections planned', '2026-02-20 10:09:40'),
(20, 'Chattogram', 'Anwara', 'Available', NULL, NULL, 900, 'Expand to coastal belt', 10, 500, 400, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:09:40'),
(21, 'Chattogram', 'Sitakunda', 'Available', NULL, NULL, 950, 'Target 3 industrial zones', 9, 450, 300, 'High demand', 'Industrial coverage expanded', '2026-02-20 10:09:40'),
(22, 'Rajshahi', 'Sadar', 'Available', NULL, NULL, 850, 'Expand to suburbs', 11, 500, 350, 'Stable', 'New Turbo package launched', '2026-02-20 10:09:40'),
(23, 'Khulna', 'Sadar', 'Available', NULL, NULL, 700, 'Target 2 wards', 7, 400, 380, 'Stable', 'Coverage expanded', '2026-02-20 10:09:40'),
(24, 'Dhaka', 'Savar', 'Available', NULL, NULL, 1200, 'Expand to Ashulia', 15, 800, 400, 'Stable connectivity', 'Last mile fiber upgrade ongoing', '2026-02-20 10:10:27'),
(25, 'Dhaka', 'Keraniganj', 'Available', NULL, NULL, 950, 'Target 3 new unions', 10, 600, 350, 'Good coverage', 'New tower installation', '2026-02-20 10:10:27'),
(26, 'Gazipur', 'Tongi', 'Available', NULL, NULL, 1100, 'Expand to Konabari', 12, 700, 400, 'Stable', 'Fiber expansion in progress', '2026-02-20 10:10:27'),
(27, 'Narayanganj', 'Sadar', 'Available', NULL, NULL, 800, 'Target 5 wards', 8, 500, 300, 'Strong demand', 'Turbo package launched', '2026-02-20 10:10:27'),
(28, 'Bogra', 'Sadar', 'Available', NULL, NULL, 1000, 'Expand to Shajahanpur', 14, 650, 350, 'Stable', 'Coverage expanded to Bogra Sadar', '2026-02-20 10:10:27'),
(29, 'Bogra', 'Sherpur', 'Available', NULL, NULL, 600, 'Target 2 unions', 6, 400, 200, 'Growing', 'New connections planned', '2026-02-20 10:10:27'),
(30, 'Chattogram', 'Anwara', 'Available', NULL, NULL, 900, 'Expand to coastal belt', 10, 500, 400, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:10:27'),
(31, 'Chattogram', 'Sitakunda', 'Available', NULL, NULL, 750, 'Target 3 industrial zones', 9, 450, 300, 'High demand', 'Industrial coverage expanded', '2026-02-20 10:10:27'),
(32, 'Rajshahi', 'Sadar', 'Available', NULL, NULL, 850, 'Expand to suburbs', 11, 500, 350, 'Stable', 'New Turbo package launched', '2026-02-20 10:10:27'),
(33, 'Khulna', 'Sadar', 'Available', NULL, NULL, 700, 'Target 2 wards', 7, 400, 300, 'Stable', 'Coverage expanded', '2026-02-20 10:10:27'),
(34, 'Sylhet', 'Sadar', 'Available', NULL, NULL, 650, 'Expand to rural areas', 6, 350, 300, 'Stable', 'Fiber rollout ongoing', '2026-02-20 10:10:27'),
(35, 'Mymensingh', 'Sadar', 'Available', NULL, NULL, 500, 'Target 3 unions', 5, 300, 200, 'Stable', 'New tower installation', '2026-02-20 10:10:27'),
(36, 'Rangpur', 'Sadar', 'Available', NULL, NULL, 550, 'Expand to Pirgachha', 6, 320, 230, 'Stable', 'Coverage expanded', '2026-02-20 10:10:27'),
(37, 'Cumilla', 'Sadar', 'Available', NULL, NULL, 720, 'Target 4 unions', 8, 400, 320, 'Stable', 'Fiber expansion ongoing', '2026-02-20 10:10:27'),
(38, 'Noakhali', 'Sadar', 'Available', NULL, NULL, 480, 'Expand to Subarnachar', 5, 280, 200, 'Stable', 'Coverage expanded', '2026-02-20 10:10:27'),
(39, 'Jessore', 'Sadar', 'Available', NULL, NULL, 530, 'Target 2 wards', 6, 300, 230, 'Stable', 'New package launched', '2026-02-20 10:10:27'),
(40, 'Dinajpur', 'Sadar', 'Available', NULL, NULL, 600, 'Expand to Birampur', 7, 350, 250, 'Stable', 'Coverage expanded', '2026-02-20 10:10:27'),
(41, 'Pabna', 'Sadar', 'Available', NULL, NULL, 450, 'Target 3 unions', 5, 270, 180, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:10:27'),
(42, 'Barisal', 'Sadar', 'Available', NULL, NULL, 400, 'Expand to Bhola', 4, 250, 150, 'Stable', 'Coverage expanded', '2026-02-20 10:10:27'),
(43, 'Sunamganj', 'Sadar', 'Available', NULL, NULL, 300, 'Target 2 unions', 3, 180, 120, 'Stable', 'New tower installation', '2026-02-20 10:10:27'),
(44, 'Dhaka', 'Savar', 'Available', NULL, NULL, 1200, 'Expand to Ashulia', 15, 800, 400, 'Stable connectivity', 'Fiber upgrade ongoing', '2026-02-20 10:13:05'),
(45, 'Dhaka', 'Keraniganj', 'Available', NULL, NULL, 950, 'Target 3 new unions', 10, 600, 350, 'Good coverage', 'New tower installation', '2026-02-20 10:13:05'),
(46, 'Gazipur', 'Tongi', 'Available', NULL, NULL, 1100, 'Expand to Konabari', 12, 700, 400, 'Stable', 'Fiber expansion in progress', '2026-02-20 10:13:05'),
(47, 'Narayanganj', 'Sadar', 'Available', NULL, NULL, 800, 'Target 5 wards', 8, 500, 300, 'Strong demand', 'Turbo package launched', '2026-02-20 10:13:05'),
(48, 'Bogra', 'Sadar', 'Available', NULL, NULL, 1000, 'Expand to Shajahanpur', 14, 650, 350, 'Stable', 'Coverage expanded to Bogra Sadar', '2026-02-20 10:13:05'),
(49, 'Bogra', 'Sherpur', 'Available', NULL, NULL, 600, 'Target 2 unions', 6, 400, 200, 'Growing', 'New connections planned', '2026-02-20 10:13:05'),
(50, 'Chattogram', 'Anwara', 'Available', NULL, NULL, 900, 'Expand to coastal belt', 10, 500, 400, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:13:05'),
(51, 'Chattogram', 'Sitakunda', 'Available', NULL, NULL, 750, 'Target 3 industrial zones', 9, 450, 300, 'High demand', 'Industrial coverage expanded', '2026-02-20 10:13:05'),
(52, 'Rajshahi', 'Sadar', 'Available', NULL, NULL, 850, 'Expand to suburbs', 11, 500, 350, 'Stable', 'New Turbo package launched', '2026-02-20 10:13:05'),
(53, 'Khulna', 'Sadar', 'Available', NULL, NULL, 700, 'Target 2 wards', 7, 400, 300, 'Stable', 'Coverage expanded', '2026-02-20 10:13:05'),
(54, 'Sylhet', 'Sadar', 'Available', NULL, NULL, 650, 'Expand to rural areas', 6, 350, 300, 'Stable', 'Fiber rollout ongoing', '2026-02-20 10:13:05'),
(55, 'Mymensingh', 'Sadar', 'Available', NULL, NULL, 500, 'Target 3 unions', 5, 300, 200, 'Stable', 'New tower installation', '2026-02-20 10:13:05'),
(56, 'Rangpur', 'Sadar', 'Available', NULL, NULL, 550, 'Expand to Pirgachha', 6, 320, 230, 'Stable', 'Coverage expanded', '2026-02-20 10:13:05'),
(57, 'Cumilla', 'Sadar', 'Available', NULL, NULL, 720, 'Target 4 unions', 8, 400, 320, 'Stable', 'Fiber expansion ongoing', '2026-02-20 10:13:05'),
(58, 'Noakhali', 'Sadar', 'Available', NULL, NULL, 480, 'Expand to Subarnachar', 5, 280, 200, 'Stable', 'Coverage expanded', '2026-02-20 10:13:05'),
(59, 'Jessore', 'Sadar', 'Available', NULL, NULL, 530, 'Target 2 wards', 6, 300, 230, 'Stable', 'New package launched', '2026-02-20 10:13:05'),
(60, 'Dinajpur', 'Sadar', 'Available', NULL, NULL, 600, 'Expand to Birampur', 7, 350, 250, 'Stable', 'Coverage expanded', '2026-02-20 10:13:05'),
(61, 'Pabna', 'Sadar', 'Available', NULL, NULL, 450, 'Target 3 unions', 5, 270, 180, 'Stable', 'Fiber upgrade ongoing', '2026-02-20 10:13:05'),
(62, 'Barisal', 'Sadar', 'Available', NULL, NULL, 400, 'Expand to Bhola', 4, 250, 150, 'Stable', 'Coverage expanded', '2026-02-20 10:13:05'),
(63, 'Sunamganj', 'Sadar', 'Available', NULL, NULL, 300, 'Target 2 unions', 3, 180, 120, 'Stable', 'New tower installation', '2026-02-20 10:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `duration_plans`
--

CREATE TABLE `duration_plans` (
  `id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `days` int(11) DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `min_custom_days` int(11) NOT NULL DEFAULT 1,
  `max_custom_days` int(11) NOT NULL DEFAULT 365,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duration_plans`
--

INSERT INTO `duration_plans` (`id`, `label`, `days`, `is_custom`, `min_custom_days`, `max_custom_days`, `discount_percent`, `active`, `sort_order`, `created_at`) VALUES
(1, '1 Day', 1, 0, 1, 365, 28.00, 1, 1, '2026-07-30 19:05:56'),
(2, '3 Days', 3, 0, 1, 365, 65.00, 1, 2, '2026-07-30 19:05:56'),
(3, '7 Days', 7, 0, 1, 365, 140.00, 1, 3, '2026-07-30 19:05:56'),
(4, '15 Days', 15, 0, 1, 365, 270.00, 1, 4, '2026-07-30 19:05:56'),
(5, '30 Days', 30, 0, 1, 365, 500.00, 1, 5, '2026-07-30 19:05:56'),
(6, '60 Days', 60, 0, 1, 365, 920.00, 1, 6, '2026-07-30 19:05:56'),
(7, '90 Days', 90, 0, 1, 365, 999.99, 1, 7, '2026-07-30 19:05:56'),
(8, 'Custom', NULL, 1, 1, 365, 0.00, 1, 8, '2026-07-30 19:05:56');

-- --------------------------------------------------------

--
-- Table structure for table `eco_stats`
--

CREATE TABLE `eco_stats` (
  `id` int(10) UNSIGNED NOT NULL,
  `month` varchar(20) NOT NULL,
  `energy_saved_kwh` decimal(10,2) NOT NULL,
  `carbon_reduced_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eco_stats`
--

INSERT INTO `eco_stats` (`id`, `month`, `energy_saved_kwh`, `carbon_reduced_kg`) VALUES
(1, 'January 2026', 120.50, 45.20),
(2, 'February 2026', 98.75, 38.10);

-- --------------------------------------------------------

--
-- Table structure for table `emergency_status`
--

CREATE TABLE `emergency_status` (
  `id` int(11) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `speed` varchar(20) DEFAULT NULL,
  `services` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_status`
--

INSERT INTO `emergency_status` (`id`, `area`, `active`, `speed`, `services`) VALUES
(1, 'Bogra Sadar', 1, '256 kbps', 'Email, Messaging, Browsing'),
(2, 'Bogra Sadar', 1, '256 kbps', 'Email, Messaging, Browsing');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `photo` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `role`, `email`, `photo`) VALUES
(1, 'K. Hasan', 'Network Engineer', 'hasan@mknet.com', 'assets/images/team1.jpg'),
(2, 'R. Akter', 'Customer Success', 'akter@mknet.com', 'assets/images/team2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bundle_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `addons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`addons`)),
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `duration_days` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `user_id`, `bundle_id`, `package_id`, `addons`, `amount`, `currency`, `status`, `created_at`, `meta`, `duration_days`) VALUES
(1, NULL, 1, NULL, NULL, 999.00, 'BDT', 'pending', '2026-06-17 00:09:41', NULL, 30),
(2, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:02:45', '{}', 30),
(3, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:11:27', '{}', 30),
(4, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:12:01', '{}', 30),
(5, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:15:02', '{}', 30),
(6, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:23:47', '{}', 30),
(7, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:34:10', '{}', 30),
(8, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:39:17', '{}', 30),
(9, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:48:51', '{}', 30),
(10, 1, NULL, 3, NULL, 500.00, 'BDT', 'pending', '2026-06-23 19:59:21', '{}', 30),
(11, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:04:31', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(12, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:04:31', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(13, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:10:00', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(14, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:10:01', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(15, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:07', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(16, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:08', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(17, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:08', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(18, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:08', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(19, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:08', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(20, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(21, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(22, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(23, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(24, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:20:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(25, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:50', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(26, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:51', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(27, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:51', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(28, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:51', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(29, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:52', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(30, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:52', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(31, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:52', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(32, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:52', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(33, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:52', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(34, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(35, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(36, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(37, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(38, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(39, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:54', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(40, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:54', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(41, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:55', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(42, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:55', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(43, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:56', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(44, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:56', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(45, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:56', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(46, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:56', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(47, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:56', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(48, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:57', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(49, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:57', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(50, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:57', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(51, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:57', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(52, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:58', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(53, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:26:58', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(54, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'paid', '2026-06-23 20:26:58', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(55, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:33:35', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(56, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:33:36', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(57, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:38:02', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(58, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:38:03', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(59, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:38:03', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(60, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 20:38:03', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(61, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:05', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(62, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:05', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(63, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:06', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(64, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:14', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(65, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:14', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(66, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:01:15', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(67, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:37', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(68, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:38', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(69, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:38', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(70, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:38', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(71, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:38', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(72, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:39', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(73, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:39', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(74, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:03:39', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(75, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:09:48', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(76, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:09:49', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(77, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:09:49', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(78, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:09:53', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(79, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:09:54', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(80, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:10', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(81, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:10', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(82, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:11', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(83, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:11', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(84, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:11', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(85, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:11', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(86, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:12', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(87, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:12', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(88, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:10:12', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(89, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:12:04', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(90, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:14:23', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(91, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:14:24', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(92, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:19:09', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(93, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'paid', '2026-06-23 21:19:10', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(94, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:23:59', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(95, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:32:21', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(96, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:40:01', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(97, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-06-23 21:40:01', '{\"type\":\"package\",\"package_id\":3,\"addons\":[]}', 30),
(98, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:16:41', '{\"type\":\"package\",\"package_id\":3}', 30),
(99, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:19:12', '{\"type\":\"package\",\"package_id\":3}', 30),
(100, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:20:51', '{\"type\":\"package\",\"package_id\":3}', 30),
(101, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'paid', '2026-07-22 21:21:03', '{\"type\":\"package\",\"package_id\":3}', 30),
(102, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:23:31', '{\"type\":\"package\",\"package_id\":3}', 30),
(103, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:24:42', '{\"type\":\"package\",\"package_id\":3}', 30),
(104, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:30:55', '{\"type\":\"package\",\"package_id\":3}', 30),
(105, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 21:30:56', '{\"type\":\"package\",\"package_id\":3}', 30),
(106, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 22:24:12', '{\"type\":\"package\",\"package_id\":3}', 30),
(107, NULL, NULL, 3, NULL, 1999.00, 'BDT', 'pending', '2026-07-22 22:27:52', '{\"type\":\"package\",\"package_id\":3}', 30),
(108, 8, NULL, NULL, NULL, 1000.00, 'BDT', 'pending', '2026-07-30 20:11:37', '{\"gateway\":\"bkash\",\"phone\":\"01629286887\",\"type\":\"quick_pay\"}', 30);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 1,
  `first_attempt_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points`
--

CREATE TABLE `loyalty_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 100,
  `tier` enum('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_points`
--

INSERT INTO `loyalty_points` (`id`, `user_id`, `points`, `tier`, `updated_at`) VALUES
(1, 33, 150, 'Bronze', '2026-07-30 22:07:15');

-- --------------------------------------------------------

--
-- Table structure for table `network_incidents`
--

CREATE TABLE `network_incidents` (
  `id` int(11) NOT NULL,
  `pop_name` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `incident_type` enum('maintenance','fiber_cut','hardware','operational') NOT NULL DEFAULT 'operational',
  `status` enum('operational','degraded','outage','scheduled') NOT NULL DEFAULT 'operational',
  `estimated_recovery` varchar(100) DEFAULT 'Operational',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `network_incidents`
--

INSERT INTO `network_incidents` (`id`, `pop_name`, `region`, `incident_type`, `status`, `estimated_recovery`, `updated_at`) VALUES
(1, 'Dhaka Central POP', 'Gulshan / Banani / Uttara', 'operational', 'operational', 'All Systems Normal', '2026-07-30 22:06:59'),
(2, 'Chittagong Port POP', 'Agrabad / GEC / Halishahar', 'operational', 'operational', 'All Systems Normal', '2026-07-30 22:06:59'),
(3, 'Sylhet Metro POP', 'Zindabazar / Ambarkhana', 'operational', 'operational', 'All Systems Normal', '2026-07-30 22:06:59'),
(4, 'Rajshahi North POP', 'Shaheb Bazar / Boalia', 'operational', 'operational', 'All Systems Normal', '2026-07-30 22:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `network_status`
--

CREATE TABLE `network_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `area` varchar(100) NOT NULL,
  `uptime_percent` decimal(5,2) NOT NULL,
  `bandwidth_usage` int(10) UNSIGNED NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `network_status`
--

INSERT INTO `network_status` (`id`, `area`, `uptime_percent`, `bandwidth_usage`, `last_update`) VALUES
(1, 'Bogra Sadar', 99.80, 120, '2026-02-03 19:51:18'),
(2, 'Rajshahi Boalia', 99.50, 95, '2026-02-03 19:51:18'),
(3, 'Dhaka Gulshan', 98.90, 150, '2026-02-03 19:51:18'),
(4, 'Chattogram Pahartali', 97.20, 80, '2026-02-03 19:51:18'),
(5, 'Bogra Sadar', 99.80, 120, '2026-02-03 20:35:32'),
(6, 'Rajshahi Boalia', 99.50, 95, '2026-02-03 20:35:32'),
(7, 'Dhaka Gulshan', 98.90, 150, '2026-02-03 20:35:32'),
(8, 'Chattogram Pahartali', 97.20, 80, '2026-02-03 20:35:32'),
(9, 'Bogra Sadar', 99.80, 120, '2026-02-03 20:37:01'),
(10, 'Rajshahi Boalia', 99.50, 95, '2026-02-03 20:37:01'),
(11, 'Dhaka Gulshan', 98.90, 150, '2026-02-03 20:37:01'),
(12, 'Chattogram Pahartali', 97.20, 80, '2026-02-03 20:37:01'),
(13, 'Bogra Sadar', 99.80, 120, '2026-02-03 20:44:47'),
(14, 'Rajshahi Boalia', 99.50, 95, '2026-02-03 20:44:47'),
(15, 'Dhaka Gulshan', 98.90, 150, '2026-02-03 20:44:47'),
(16, 'Chattogram Pahartali', 97.20, 80, '2026-02-03 20:44:47'),
(17, 'Bogra Sadar', 99.80, 120, '2026-02-03 23:03:30'),
(18, 'Rajshahi Boalia', 99.50, 95, '2026-02-03 23:03:30'),
(19, 'Dhaka Gulshan', 98.90, 150, '2026-02-03 23:03:30'),
(20, 'Chattogram Pahartali', 97.20, 80, '2026-02-03 23:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_events`
--

CREATE TABLE `news_events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_events`
--

INSERT INTO `news_events` (`id`, `title`, `content`, `event_date`, `image`, `category`, `location`) VALUES
(1, 'MK Net Launch in Bogra', 'We are excited to expand coverage in Bogra Sadar.', '2025-12-20', NULL, 'General', NULL),
(2, 'Holiday Service Update', '24/7 support continues during holidays.', '2025-12-25', NULL, 'General', NULL),
(3, 'MK Net Launch in Bogra', 'We are excited to expand coverage in Bogra Sadar.', '2025-12-20', NULL, 'General', NULL),
(4, 'Holiday Service Update', '24/7 support continues during holidays.', '2025-12-25', NULL, 'General', NULL),
(5, 'MK Net Launch in Bogra', 'We are excited to expand coverage in Bogra Sadar.', '2025-12-20', NULL, 'General', NULL),
(6, 'Holiday Service Update', '24/7 support continues during holidays.', '2025-12-25', NULL, 'General', NULL),
(7, 'MK Net Launch in Bogra', 'We are excited to expand coverage in Bogra Sadar.', '2025-12-20', NULL, 'General', NULL),
(8, 'Holiday Service Update', '24/7 support continues during holidays.', '2025-12-25', NULL, 'General', NULL),
(9, 'Grand Opening of MK Net HQ', 'We are excited to announce the grand opening of our new headquarters in Bogra. Join us for the celebration and meet our team!', '2026-02-01', '/mknet/assets/images/news/1771879252_holiday-service-update.jpg', 'General', NULL),
(10, 'Internet Speed Upgrade', 'MK Net has upgraded its infrastructure to provide faster internet speeds across Rajshahi division. Experience super fast browsing and streaming!', '2026-01-20', '/mknet/assets/images/news/1771879270_holiday-service-update.jpg', 'General', NULL),
(11, 'Community Meetup', 'Our first community meetup was a huge success! Members shared feedback, ideas, and enjoyed networking with fellow MK Net users.', '2026-01-10', '/mknet/assets/images/news/1771879296_default.jpg', 'General', NULL),
(12, '24/7 Support Launch', 'We are proud to launch our 24/7 customer support service. Now you can reach us anytime for assistance with your internet connection.', '2025-12-15', '/mknet/assets/images/news/1771879330_mk-net-launch-in-bogra.jpg', 'General', NULL),
(13, 'Holiday Offer', 'Special holiday packages are now available! Enjoy discounted rates and bonus data during the festive season.', '2025-12-01', '/mknet/assets/images/news/1771879344_mk-net-launch-in-bogra.jpg', 'General', NULL),
(14, 'Grand Opening of MK Net HQ', 'We are excited to announce the grand opening of our new headquarters in Bogra. Join us for the celebration and meet our team!', '2026-02-01', '/mknet/assets/images/news/1771879262_mk-net-launch-in-bogra.jpg', 'Announcements', NULL),
(15, 'Internet Speed Upgrade', 'MK Net has upgraded its infrastructure to provide faster internet speeds across Rajshahi division. Experience super fast browsing and streaming!', '2026-01-20', '/mknet/assets/images/news/1771879288_1771879262_mk-net-launch-in-bogra.jpg', 'Announcements', NULL),
(16, 'Community Meetup', 'Our first community meetup was a huge success! Members shared feedback, ideas, and enjoyed networking with fellow MK Net users.', '2026-01-10', '/mknet/assets/images/news/1771879302_1771879262_mk-net-launch-in-bogra.jpg', 'Community', NULL),
(17, '24/7 Support Launch', 'We are proud to launch our 24/7 customer support service. Now you can reach us anytime for assistance with your internet connection.', '2025-12-15', '/mknet/assets/images/news/1771879338_1771879330_mk-net-launch-in-bogra.jpg', 'Announcements', NULL),
(18, 'Holiday Offer', 'Special holiday packages are now available! Enjoy discounted rates and bonus data during the festive season.', '2025-12-01', '/mknet/assets/images/news/1771879282_holiday-service-update.jpg', 'Offers', NULL),
(19, 'General Update', 'Routine maintenance will be carried out this weekend to improve service reliability.', '2025-11-20', '/mknet/assets/images/news/1771879351_1771879344_mk-net-launch-in-bogra.jpg', 'General', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `speed_mbps` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `price_bdt` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `data_cap` int(11) DEFAULT 0,
  `best_value` tinyint(1) DEFAULT 0,
  `slug` varchar(150) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `promo_price` decimal(10,2) DEFAULT NULL,
  `promo_start` datetime DEFAULT NULL,
  `promo_end` datetime DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `billing_cycle` varchar(50) DEFAULT NULL,
  `trial_days` int(11) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `category` enum('residential','business','enterprise') NOT NULL DEFAULT 'residential',
  `badge` varchar(30) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `speed_mbps`, `price`, `price_bdt`, `description`, `data_cap`, `best_value`, `slug`, `image`, `features`, `visible`, `featured`, `promo_price`, `promo_start`, `promo_end`, `region`, `billing_cycle`, `trial_days`, `order_index`, `category`, `badge`) VALUES
(1, 'Starter', 20, 999.00, 999, 'Reliable home internet for light use', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(2, 'Standard', 50, 1499.00, 1499, 'Balanced speed for families', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(3, 'Turbo', 100, 1999.00, 1999, 'Streaming and gaming ready', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(4, 'Business Bronze', 50, 3999.00, 3999, 'SME plan with priority support', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(5, 'Business Silver', 100, 6999.00, 6999, 'Faster bandwidth for offices', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(6, 'Basic', 100, 100.00, 100, NULL, 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(7, 'Faster', 200, 200.00, 200, NULL, 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(8, 'Basic', 100, 29.99, 30, 'Basic internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(9, 'Faster', 200, 49.99, 50, 'Faster internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(10, 'Classic', 100, 29.99, 30, 'Basic internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(11, 'Booster', 200, 39.99, 40, 'Faster internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(12, 'Classic', 100, 29.99, 30, 'Basic internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(13, 'Booster', 200, 39.99, 40, 'Faster internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(14, 'Basic', 50, 500.00, 500, 'Basic internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(15, 'Premium', 100, 1500.00, 1500, 'Premium internet package', 0, 0, NULL, NULL, '[]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'residential', 'none'),
(16, 'Enterprise Dedicated', 200, 14999.00, 14999, 'Dedicated fiber bandwidth with 99.9% SLA and 24/7 VIP support.', 0, 0, NULL, NULL, '[\"Dedicated 1:1 Bandwidth\",\"24/7 VIP Support\",\"99.9% Uptime SLA\",\"Static IP Included\"]', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'enterprise', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `package_addons`
--

CREATE TABLE `package_addons` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `username`, `token`, `expires_at`) VALUES
(1, 'superadmin', '84480d01b2f7fcca48f362814aee8f9a82840f3fae841ebb96debc2d98ea4f69', '2026-02-23 11:31:25'),
(2, 'superadmin', 'ec4a4c6a28c1129e9e0091d25046b220ef6c359b642ec43273bd0727ec2a8992', '2026-02-23 11:31:27'),
(3, 'superadmin', '2ed726bb4bca95caffb312102352b60b2295e8d222c191c43611305e1e37c98c', '2026-02-23 11:31:28'),
(4, 'admin', '78c493dcdfddb45f974e2bafd1d352c536f001c55ba2654f34f7e27b456740e9', '2026-07-27 23:05:42'),
(5, 'admin', '61093646c3d3c421cc4ab11c33358ac322673977d5e4b807856c798eb65b7b98', '2026-07-27 23:05:44'),
(6, 'admin', '8e23b9f3fdd75e0a2a39807add992a15041ab322f6ce22d0603655838af30952', '2026-07-27 23:05:45'),
(7, 'admin', '2640336b2a329e207db0f231abfbfa1871f8ae4226946c42c31d1640ae02883e', '2026-07-27 23:05:45'),
(8, 'admin', 'd499c569043bc0af6ee2d528096a0b7895f2dcc1f859252704856e15cd871254', '2026-07-27 23:05:46'),
(9, 'admin', '27484db3e7701e8ba65cbc1269542d8a4e2abc42c1ccccc4a5847d4597e7ea7f', '2026-07-27 23:05:46'),
(10, 'admin', 'a4dcfe1b6c6f5c7f8dee9d1d58c76e765090b5594e40ae4d340a917d5661b969', '2026-07-27 23:05:46');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount_bdt` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `txn_ref` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount_bdt`, `package_id`, `txn_ref`, `created_at`) VALUES
(1, 1, 999, 1, 'TXN12345', '2025-12-18 00:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `trx_id` varchar(100) DEFAULT NULL,
  `sender_number` varchar(30) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
  `raw_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_user_id` int(11) NOT NULL,
  `referred_email` varchar(150) NOT NULL,
  `referral_code` varchar(50) NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `reward_points` int(11) NOT NULL DEFAULT 500,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `package_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_name`, `rating`, `comment`, `created_at`, `package_id`, `status`) VALUES
(1, 'Rahim', 4, 'Good value for the price.', '2025-12-18 00:03:06', NULL, 'pending'),
(2, 'Mitu', 5, 'Service is stable and support is responsive.', '2025-12-18 00:03:06', NULL, 'pending'),
(3, 'Sadia', 5, 'Speed is consistent even in peak hours.', '2025-12-18 00:03:06', NULL, 'pending'),
(4, 'Rahim', 4, 'Good value for the price.', '2025-12-18 07:58:11', NULL, 'pending'),
(5, 'Mitu', 5, 'Service is stable and support is responsive.', '2025-12-18 07:58:11', NULL, 'pending'),
(6, 'Sadia', 5, 'Speed is consistent even in peak hours.', '2025-12-18 07:58:11', NULL, 'pending'),
(7, 'Test User', 5, 'Great package!', '2026-06-23 18:56:27', 3, 'approved'),
(8, 'Test User', 5, 'Great package!', '2026-06-23 18:59:07', 25, 'approved'),
(9, 'abir samar', 5, '', '2026-07-30 15:22:07', 6, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `sitename` varchar(255) DEFAULT NULL,
  `footer` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `theme` varchar(50) DEFAULT NULL,
  `support_phone` varchar(20) DEFAULT NULL,
  `hotline` varchar(50) DEFAULT '16335 / 09678-123123',
  `bkash_number` varchar(30) DEFAULT '01700000000',
  `nagad_number` varchar(30) DEFAULT '01700000000',
  `active_theme` varchar(50) DEFAULT 'cyber_dark',
  `custom_css` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT 'Police Plaza Concord, Gulshan 1, Dhaka'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `sitename`, `footer`, `contact`, `logo`, `theme`, `support_phone`, `hotline`, `bkash_number`, `nagad_number`, `active_theme`, `custom_css`, `logo_path`, `favicon_path`, `address`) VALUES
(1, 'MK Net ISP', '© 2026 MK Net ISP', 'support@mknet.com', 'images/logo.png', 'default', '+8801700000000', '16335 / 09678-123123', '01700000000', '01700000000', 'cyber_dark', NULL, NULL, NULL, 'Police Plaza Concord, Gulshan 1, Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `site_content`
--

CREATE TABLE `site_content` (
  `id` int(11) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_content`
--

INSERT INTO `site_content` (`id`, `content_key`, `image_path`, `updated_at`) VALUES
(1, 'homepage_background', 'assets/images/hero-bg.jpg', '2026-07-29 09:37:22'),
(2, 'dashboard_background', 'assets/images/dashboard-bg.jpg', '2026-07-29 09:37:22'),
(3, 'login_background', 'assets/images/login-bg.jpg', '2026-07-29 09:37:22'),
(4, 'register_background', 'assets/images/register-bg.jpg', '2026-07-29 09:37:22');

-- --------------------------------------------------------

--
-- Table structure for table `site_menu`
--

CREATE TABLE `site_menu` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `link` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_menu`
--

INSERT INTO `site_menu` (`id`, `title`, `link`) VALUES
(1, 'Home', '/mknet/index.php'),
(3, 'Coverage Map', '/mknet/pages/coverage_map.php'),
(23, 'Packages', '/mknet/pages/packages.php'),
(24, 'Coverage', '/mknet/pages/coverage.php'),
(25, 'Pay Bill', '/mknet/pages/packages.php'),
(26, 'Self Care', '/mknet/pages/selfcare_login.php'),
(30, 'About', '/mknet/pages/about.php'),
(31, 'Contact', '/mknet/pages/contact.php');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `trainer` varchar(100) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`id`, `name`, `role`, `photo`, `bio`, `designation`, `linkedin`, `facebook`, `email`, `phone`, `join_date`, `trainer`, `twitter`) VALUES
(1, 'Adnin Rahman', 'Founder & CEO', '/mknet/assets/images/team/ceo.jpg', 'Visionary leader driving MK Net ISP.', NULL, NULL, NULL, NULL, NULL, '2026-06-16 20:30:06', NULL, NULL),
(2, 'Sara Akter', 'CTO', '/mknet/assets/images/team/cto.jpg', 'Expert in ICT infrastructure and innovation.', NULL, NULL, NULL, NULL, NULL, '2026-06-16 20:30:06', NULL, NULL),
(3, 'Rafiq Hasan', 'Support Lead', '/mknet/assets/images/team/support.jpg', 'Ensures 24/7 customer support and satisfaction.', NULL, NULL, NULL, NULL, NULL, '2026-06-16 20:30:06', NULL, NULL),
(7, 'Adnin Sadat', NULL, 'uploads/team/1781645026_2fbf515c5f4c.png', '', 'Area manager', 'https:www.abirsam.linked.com', 'https:abirsamir.facebook.com', 'adnansadatmahin2@gmail.com', '01629286887', '2026-06-16 21:23:46', NULL, 'https://x.com/elonmusk'),
(8, 'Adnin Sadat', NULL, 'uploads/team/1781645124_76ebd5b0634b.png', 'best employee', 'Area manager', 'https:www.abirsam.linked.com', 'https:abirsamir.facebook.com', 'adnansadatmahinsdfsf@gmail.com', '01629286888', '2026-06-16 21:25:24', NULL, 'https://x.com/elonmusk'),
(9, 'Adnin Sadat', NULL, '/mknet/uploads/team/1781647066_c6301f84635c.png', 'best employee', 'Area manager', 'https:www.abirsam.linked.com', 'https:abirsamir.facebook.com', 'adnansadatmahinsdfsf@gmail.com', '01629286888', '2026-06-16 21:57:46', NULL, 'https://x.com/elonmusk'),
(10, 'abrar fahad', NULL, '/mknet/uploads/team/1781647721_f58e7ff3acdb.png', 'outstanding', 'Manager', 'https:www.abirsafffffm.linked.com', 'https:abirfsafffmir.facebook.com', 'adnansadafsadrfwertmahin2@gmail.com', '01629286778', '2026-06-16 22:08:41', NULL, 'https://x.com/elonmuskf'),
(11, 'abrar fahad', NULL, '/mknet/uploads/team/1781647867_a9cb8d1ac046.png', 'outstanding', 'Manager', 'https:www.abirsafffffm.linked.com', 'https:abirfsafffmir.facebook.com', 'adnansadafsadrfwertmahin2@gmail.com', '01629286778', '2026-06-16 22:11:07', NULL, 'https://x.com/elonmuskf'),
(12, 'abrar fahad', NULL, '/mknet/uploads/team/1781647910_470f6d6fc53c.png', 'outstanding', 'Manager', 'https:www.abirsafffffm.linked.com', 'https:abirfsafffmir.facebook.com', 'adnansadafsadrfwertmahin2@gmail.com', '01629286778', '2026-06-16 22:11:50', NULL, 'https://x.com/elonmuskf'),
(13, 'abrar fahad', NULL, '/mknet/uploads/team/1781650291_4b48e1be7641.png', 'outstanding', 'Manager', 'https:www.abirsafffffm.linked.com', 'https:abirfsafffmir.facebook.com', 'adnansadafsadrfwertmahin2@gmail.com', '01629286778', '2026-06-16 22:51:31', NULL, 'https://x.com/elonmuskf');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `department` enum('technical','billing','sales','general') NOT NULL DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_admin_id` int(11) DEFAULT NULL,
  `sla_due_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `department`, `priority`, `status`, `assigned_admin_id`, `sla_due_at`, `created_at`, `updated_at`, `closed_at`) VALUES
(1, 33, 'Auto Support Ticket: My internet is slow', 'technical', 'high', 'open', NULL, NULL, '2026-07-30 21:29:26', '2026-07-30 21:29:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_internal_note` tinyint(1) NOT NULL DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_history`
--

CREATE TABLE `usage_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `download_gb` float DEFAULT NULL,
  `upload_gb` float DEFAULT NULL,
  `min_speed_mbps` float DEFAULT NULL,
  `max_speed_mbps` float DEFAULT NULL,
  `downtime_seconds` int(11) DEFAULT NULL,
  `problems_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_stats`
--

CREATE TABLE `usage_stats` (
  `id` int(11) NOT NULL,
  `user_email` varchar(120) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `data_used_gb` decimal(10,2) DEFAULT NULL,
  `peak_hours` varchar(50) DEFAULT NULL,
  `devices` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usage_stats`
--

INSERT INTO `usage_stats` (`id`, `user_email`, `month`, `data_used_gb`, `peak_hours`, `devices`) VALUES
(1, 'rahman@mknet.com', 'January 2026', 120.50, '7-10 PM', 3),
(2, 'akter@mknet.com', 'January 2026', 95.20, '6-9 PM', 2),
(3, 'karim@mknet.com', 'January 2026', 60.80, '8-11 PM', 4),
(4, 'rahman@mknet.com', 'January 2026', 120.50, '7-10 PM', 3),
(5, 'akter@mknet.com', 'January 2026', 95.20, '6-9 PM', 2),
(6, 'karim@mknet.com', 'January 2026', 60.80, '8-11 PM', 4),
(7, 'rahman@mknet.com', 'January 2026', 120.50, '7-10 PM', 3),
(8, 'akter@mknet.com', 'January 2026', 95.20, '6-9 PM', 2),
(9, 'karim@mknet.com', 'January 2026', 60.80, '8-11 PM', 4),
(10, 'rehana@inkkast.com', 'January 2026', 120.50, '7-10 PM', 3),
(11, 'rahman@mknet.com', 'January 2026', 95.20, '6-9 PM', 2),
(12, 'rehan@inkkast.com', 'January 2024', 150.50, '7-10 PM', 3),
(13, 'rahman@mknet.com', 'January 2026', 95.20, '6-9 PM', 2),
(14, 'rehan@inkkast.com', 'January 2024', 150.50, '7-10 PM', 3),
(15, 'rahul@mknet.com', 'January 2024', 159.88, '6 PM', 2),
(16, 'rahul@mknet.com', 'January 2024', 159.88, '6 PM', 2),
(17, 'rahul@mknet.com', 'January 2026', 159.88, '6 PM', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `package_speed_mbps` int(11) DEFAULT NULL,
  `ftp_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_preference` varchar(10) NOT NULL DEFAULT 'light',
  `loyalty_points` int(11) DEFAULT 0,
  `is_regular` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(100) DEFAULT NULL,
  `account_status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `phone`, `email`, `password`, `package_speed_mbps`, `ftp_link`, `created_at`, `theme_preference`, `loyalty_points`, `is_regular`, `name`, `account_status`) VALUES
(4, NULL, NULL, 'testuser1@example.com', '$2y$10$pHNz8ZBpVdDTIxEFKb3OEa2zm4KyVhr3gHY8OdVBYEfGcO9DyG', 50, 'ftp://mknet.com/testuser1', '2026-02-04 23:00:18', 'light', 0, 0, NULL, 'active'),
(7, NULL, NULL, 'adnin@example.com', '$2y$10$pHNz8ZBpVdDTIxEFKb3OEa2zm4KyVhr3gHY8OdVBYEfGcO9DyG', 50, 'ftp://mknet.com/adnin', '2026-02-04 23:19:09', 'light', 0, 0, NULL, 'active'),
(8, 'Adnin Sadat', '01629286887', 'adnansadatmahin2@gmail.com', '$2y$10$MGEzjthW8tQQrHTvMwHxW.Do5xuwEIpYA16/ooo6uApdbS86Kwq3i', 20, 'ftp://mknet.com/adnansadatmahin2@gmail.com', '2026-02-04 23:27:21', 'light', 10, 0, 'Adnin Sadat', 'active'),
(9, NULL, NULL, 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9z7rEV4GqIo5f7kFq7c5eW', 50, 'ftp://user1', '2026-02-09 21:23:20', 'light', 0, 0, NULL, 'active'),
(10, NULL, NULL, 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9z7rEV4GqIo5f7kFq7c5eW', 100, 'ftp://user2', '2026-02-09 21:23:20', 'light', 0, 0, NULL, 'active'),
(33, 'Adnin Sadat', '01629286887', 'adnansadatmahin@gmail.com', '$2y$10$1fz3u5BvL8gsiMACSEjr4uhRdXAngUhhYgr9eWwjLp9.tgfXhuFSK', 20, 'ftp://mknet.com/adnansadatmahin@gmail.com', '2026-07-24 09:26:43', 'light', 0, 0, 'Adnin Sadat', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_addons`
--

CREATE TABLE `user_addons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_banners`
--
ALTER TABLE `admin_banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_invitations`
--
ALTER TABLE `admin_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD UNIQUE KEY `title_2` (`title`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blockchain_payments`
--
ALTER TABLE `blockchain_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bundles`
--
ALTER TABLE `bundles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bundles_visible` (`visible`);

--
-- Indexes for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chatbot_rules`
--
ALTER TABLE `chatbot_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coverage`
--
ALTER TABLE `coverage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coverage_areas`
--
ALTER TABLE `coverage_areas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `duration_plans`
--
ALTER TABLE `duration_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eco_stats`
--
ALTER TABLE `eco_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emergency_status`
--
ALTER TABLE `emergency_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoices_user_status` (`user_id`,`status`),
  ADD KEY `idx_invoices_package` (`package_id`),
  ADD KEY `idx_invoices_bundle` (`bundle_id`);

--
-- Indexes for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `network_incidents`
--
ALTER TABLE `network_incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `network_status`
--
ALTER TABLE `network_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_events`
--
ALTER TABLE `news_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_packages_visible` (`visible`);

--
-- Indexes for table `package_addons`
--
ALTER TABLE `package_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_content`
--
ALTER TABLE `site_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sc_key` (`content_key`);

--
-- Indexes for table `site_menu`
--
ALTER TABLE `site_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usage_history`
--
ALTER TABLE `usage_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `usage_stats`
--
ALTER TABLE `usage_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addons`
--
ALTER TABLE `user_addons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admin_banners`
--
ALTER TABLE `admin_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_invitations`
--
ALTER TABLE `admin_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blockchain_payments`
--
ALTER TABLE `blockchain_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bundles`
--
ALTER TABLE `bundles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `chatbot_rules`
--
ALTER TABLE `chatbot_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coverage`
--
ALTER TABLE `coverage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `coverage_areas`
--
ALTER TABLE `coverage_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `duration_plans`
--
ALTER TABLE `duration_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `eco_stats`
--
ALTER TABLE `eco_stats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emergency_status`
--
ALTER TABLE `emergency_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `network_incidents`
--
ALTER TABLE `network_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `network_status`
--
ALTER TABLE `network_status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_events`
--
ALTER TABLE `news_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `package_addons`
--
ALTER TABLE `package_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `site_content`
--
ALTER TABLE `site_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `site_menu`
--
ALTER TABLE `site_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_history`
--
ALTER TABLE `usage_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `usage_stats`
--
ALTER TABLE `usage_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `user_addons`
--
ALTER TABLE `user_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD CONSTRAINT `community_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_addons`
--
ALTER TABLE `package_addons`
  ADD CONSTRAINT `package_addons_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_history`
--
ALTER TABLE `usage_history`
  ADD CONSTRAINT `usage_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
