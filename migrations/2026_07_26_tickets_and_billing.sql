-- =====================================================================
-- MK Net — Support Ticket System + Recurring Billing Automation
-- Purely additive, idempotent (safe to re-run).
-- =====================================================================

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(200) NOT NULL,
  department ENUM('technical','billing','sales','general') NOT NULL DEFAULT 'general',
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status ENUM('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_admin_id INT NULL,
  sla_due_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  KEY idx_tickets_user (user_id),
  KEY idx_tickets_status (status),
  CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  sender_type ENUM('customer','admin') NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  is_internal_note TINYINT(1) NOT NULL DEFAULT 0,
  attachment_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tm_ticket (ticket_id),
  CONSTRAINT fk_tm_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recurring billing: tracks which users are due for their next
-- auto-generated invoice, and prevents the cron from double-billing.
CREATE TABLE IF NOT EXISTS billing_cycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NULL,
  last_billed_at DATE NULL,
  next_billing_at DATE NOT NULL,
  auto_renew TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_bc_user (user_id),
  CONSTRAINT fk_bc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Tickets + recurring billing migration applied successfully.' AS result;
