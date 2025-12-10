-- Workflows table for automation
-- This migration is safe to run multiple times (uses IF NOT EXISTS)

CREATE TABLE IF NOT EXISTS `workflows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `trigger_type` ENUM('lead_created', 'lead_status_changed', 'booking_created', 'booking_status_changed', 'date_based', 'custom') NOT NULL,
  `trigger_conditions` JSON NULL COMMENT 'Conditions that must be met',
  `actions` JSON NOT NULL COMMENT 'Actions to execute',
  `is_active` TINYINT(1) DEFAULT 1,
  `execution_count` INT DEFAULT 0,
  `last_executed_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_trigger` (`trigger_type`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow execution log
CREATE TABLE IF NOT EXISTS `workflow_executions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_id` INT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
  `error_message` TEXT NULL,
  `executed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_workflow` (`workflow_id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`workflow_id`) REFERENCES `workflows`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default workflows (using INSERT IGNORE to prevent duplicates)
INSERT IGNORE INTO `workflows` (`name`, `description`, `trigger_type`, `trigger_conditions`, `actions`, `is_active`) VALUES
(
  'New Lead Welcome Email',
  'Automatically send welcome email to new leads',
  'lead_created',
  NULL,
  '[
    {
      "type": "send_email",
      "template": "welcome_lead",
      "to": "lead_email"
    },
    {
      "type": "add_note",
      "note": "Welcome email sent automatically"
    }
  ]',
  1
),
(
  'High Priority Lead Alert',
  'Alert manager when high priority lead is created',
  'lead_created',
  '[{"field": "priority", "operator": "equals", "value": "high"}]',
  '[
    {
      "type": "add_note",
      "note": "High priority lead - requires immediate attention"
    }
  ]',
  1
),
(
  'Uncontacted Lead Reminder',
  'Remind if lead not contacted within 2 hours',
  'date_based',
  '[{"field": "status", "operator": "equals", "value": "new"}, {"field": "hours_since_created", "operator": "greater_than", "value": "2"}]',
  '[
    {
      "type": "add_note",
      "note": "Reminder: Lead not contacted within 2 hours"
    }
  ]',
  0
),
(
  'Booking Confirmation Email',
  'Send confirmation email when booking is confirmed',
  'booking_status_changed',
  '[{"field": "status", "operator": "equals", "value": "confirmed"}]',
  '[
    {
      "type": "send_email",
      "template": "booking_confirmation",
      "to": "guest_email"
    }
  ]',
  1
)
ON DUPLICATE KEY UPDATE `name`=`name`;

