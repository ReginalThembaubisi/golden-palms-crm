-- Golden Palms Beach Resort CRM Database Schema
-- MySQL 8.0+

-- Users table (Staff/Admin)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `role` ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lead sources
CREATE TABLE IF NOT EXISTS `lead_sources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('meta_ads', 'website', 'manual', 'phone', 'email', 'other') NOT NULL,
  `color` VARCHAR(7) DEFAULT '#007bff',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leads table
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `status` ENUM('new', 'contacted', 'qualified', 'converted', 'lost') DEFAULT 'new',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `assigned_to` INT UNSIGNED NULL,
  `campaign_name` VARCHAR(100) NULL COMMENT 'Meta Ads campaign name',
  `ad_set_name` VARCHAR(100) NULL COMMENT 'Meta Ads ad set name',
  `form_type` VARCHAR(50) NULL COMMENT 'Website form type',
  `message` TEXT NULL,
  `notes` TEXT NULL,
  `tags` JSON NULL,
  `quality_score` INT DEFAULT 0,
  `converted_to_booking_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `contacted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_source` (`source_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_assigned` (`assigned_to`),
  INDEX `idx_email` (`email`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`source_id`) REFERENCES `lead_sources`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accommodation units
CREATE TABLE IF NOT EXISTS `units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_number` VARCHAR(20) NOT NULL UNIQUE,
  `unit_type` ENUM('2_bedroom', '3_bedroom', '5_bedroom') NOT NULL,
  `max_guests` INT NOT NULL,
  `description` TEXT NULL,
  `amenities` JSON NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`unit_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guests table
CREATE TABLE IF NOT EXISTS `guests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `phone_alt` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(50) NULL,
  `country` VARCHAR(50) DEFAULT 'South Africa',
  `date_of_birth` DATE NULL,
  `preferred_contact` ENUM('email', 'phone', 'whatsapp') DEFAULT 'email',
  `dietary_restrictions` TEXT NULL,
  `accessibility_needs` TEXT NULL,
  `special_occasions` JSON NULL COMMENT 'Birthdays, anniversaries, etc.',
  `preferences` JSON NULL COMMENT 'Room preferences, activities, etc.',
  `tags` JSON NULL,
  `loyalty_points` INT DEFAULT 0,
  `total_nights` INT DEFAULT 0,
  `total_revenue` DECIMAL(10,2) DEFAULT 0.00,
  `last_visit` DATE NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_reference` VARCHAR(20) NOT NULL UNIQUE,
  `guest_id` INT UNSIGNED NOT NULL,
  `lead_id` INT UNSIGNED NULL COMMENT 'Original lead if converted',
  `unit_id` INT UNSIGNED NOT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `number_of_guests` INT NOT NULL,
  `unit_type` ENUM('2_bedroom', '3_bedroom', '5_bedroom') NOT NULL,
  `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `deposit_amount` DECIMAL(10,2) DEFAULT 0.00,
  `balance_amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `special_requests` TEXT NULL,
  `notes` TEXT NULL,
  `cancellation_reason` TEXT NULL,
  `cancelled_at` DATETIME NULL,
  `checked_in_at` DATETIME NULL,
  `checked_out_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_unit` (`unit_id`),
  INDEX `idx_lead` (`lead_id`),
  INDEX `idx_dates` (`check_in`, `check_out`),
  INDEX `idx_status` (`status`),
  INDEX `idx_reference` (`booking_reference`),
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unit availability (blocked dates for maintenance, etc.)
CREATE TABLE IF NOT EXISTS `unit_availability` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `reason` VARCHAR(100) NULL COMMENT 'maintenance, closed, etc.',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_unit_date` (`unit_id`, `date`),
  INDEX `idx_date` (`date`),
  FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Communications log
CREATE TABLE IF NOT EXISTS `communications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('email', 'whatsapp', 'phone', 'sms') NOT NULL,
  `direction` ENUM('inbound', 'outbound') NOT NULL,
  `guest_id` INT UNSIGNED NULL,
  `lead_id` INT UNSIGNED NULL,
  `booking_id` INT UNSIGNED NULL,
  `subject` VARCHAR(200) NULL,
  `message` TEXT NOT NULL,
  `to_email` VARCHAR(100) NULL,
  `to_phone` VARCHAR(20) NULL,
  `from_email` VARCHAR(100) NULL,
  `from_phone` VARCHAR(20) NULL,
  `status` ENUM('sent', 'delivered', 'read', 'failed', 'pending') DEFAULT 'pending',
  `sent_by` INT UNSIGNED NULL,
  `sent_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_lead` (`lead_id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_sent_at` (`sent_at`),
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email campaigns
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `template_id` INT UNSIGNED NULL,
  `content` TEXT NOT NULL,
  `type` ENUM('newsletter', 'promotion', 'automated', 'custom') NOT NULL,
  `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft',
  `segment` JSON NULL COMMENT 'Target segment criteria',
  `scheduled_for` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `total_recipients` INT DEFAULT 0,
  `total_sent` INT DEFAULT 0,
  `total_opened` INT DEFAULT 0,
  `total_clicked` INT DEFAULT 0,
  `total_bounced` INT DEFAULT 0,
  `total_unsubscribed` INT DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`type`),
  INDEX `idx_scheduled` (`scheduled_for`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaign recipients
CREATE TABLE IF NOT EXISTS `campaign_recipients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` INT UNSIGNED NOT NULL,
  `guest_id` INT UNSIGNED NULL,
  `lead_id` INT UNSIGNED NULL,
  `email` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed') DEFAULT 'pending',
  `opened_at` DATETIME NULL,
  `clicked_at` DATETIME NULL,
  `bounced_at` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_campaign` (`campaign_id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_lead` (`lead_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Review requests
CREATE TABLE IF NOT EXISTS `review_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `guest_id` INT UNSIGNED NOT NULL,
  `method` ENUM('email', 'whatsapp') NOT NULL,
  `status` ENUM('pending', 'sent', 'delivered', 'reviewed', 'failed') DEFAULT 'pending',
  `message` TEXT NULL,
  `review_links` JSON NULL COMMENT 'Links to Google, TripAdvisor, etc.',
  `sent_at` DATETIME NULL,
  `reviewed_at` DATETIME NULL,
  `sent_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews (collected reviews)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_request_id` INT UNSIGNED NULL,
  `booking_id` INT UNSIGNED NULL,
  `guest_id` INT UNSIGNED NOT NULL,
  `platform` ENUM('google', 'tripadvisor', 'facebook', 'website', 'other') NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `title` VARCHAR(200) NULL,
  `review_text` TEXT NOT NULL,
  `reviewer_name` VARCHAR(100) NULL,
  `review_url` VARCHAR(500) NULL,
  `is_visible` TINYINT(1) DEFAULT 1,
  `response` TEXT NULL,
  `responded_at` DATETIME NULL,
  `reviewed_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_platform` (`platform`),
  INDEX `idx_rating` (`rating`),
  FOREIGN KEY (`review_request_id`) REFERENCES `review_requests`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Website content
CREATE TABLE IF NOT EXISTS `website_content` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page` VARCHAR(100) NOT NULL,
  `section` VARCHAR(100) NOT NULL,
  `content_key` VARCHAR(100) NOT NULL,
  `content_type` ENUM('text', 'html', 'image', 'json') DEFAULT 'text',
  `content` TEXT NULL,
  `metadata` JSON NULL,
  `version` INT DEFAULT 1,
  `is_published` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content` (`page`, `section`, `content_key`, `version`),
  INDEX `idx_page` (`page`),
  INDEX `idx_published` (`is_published`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log (audit trail)
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `changes` JSON NULL COMMENT 'Before/after changes',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default lead sources
INSERT INTO `lead_sources` (`name`, `type`, `color`) VALUES
('Meta Ads', 'meta_ads', '#1877f2'),
('Website Contact Form', 'website', '#28a745'),
('Website Booking Form', 'website', '#28a745'),
('Manual Entry', 'manual', '#6c757d'),
('Phone Call', 'phone', '#17a2b8'),
('Email Direct', 'email', '#ffc107'),
('Other', 'other', '#6c757d')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `is_active`) VALUES
('admin', 'admin@goldenpalmsbeachresort.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Admin', 'User', 'admin', 1)
ON DUPLICATE KEY UPDATE 
    `password` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    `is_active` = 1,
    `role` = 'admin';

-- Payment transactions (detailed payment history)
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `transaction_type` ENUM('deposit', 'payment', 'refund', 'adjustment') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash', 'card', 'bank_transfer', 'eft', 'paypal', 'other') NOT NULL,
  `transaction_reference` VARCHAR(100) NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`transaction_type`),
  INDEX `idx_reference` (`transaction_reference`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pricing/rates table (seasonal pricing)
CREATE TABLE IF NOT EXISTS `pricing_rates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_type` ENUM('2_bedroom', '3_bedroom', '5_bedroom') NOT NULL,
  `season` ENUM('low', 'mid', 'high', 'peak') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `rate_per_night` DECIMAL(10,2) NOT NULL,
  `min_nights` INT DEFAULT 1,
  `max_nights` INT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_unit_type` (`unit_type`),
  INDEX `idx_season` (`season`),
  INDEX `idx_dates` (`start_date`, `end_date`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `category` VARCHAR(50) DEFAULT 'general',
  `description` TEXT NULL,
  `updated_by` INT UNSIGNED NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_key` (`setting_key`),
  INDEX `idx_category` (`category`),
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email templates
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `body_html` TEXT NOT NULL,
  `body_text` TEXT NULL,
  `template_type` ENUM('booking_confirmation', 'booking_reminder', 'review_request', 'campaign', 'custom') NOT NULL,
  `variables` JSON NULL COMMENT 'Available template variables',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`template_type`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guest preferences (for better segmentation)
CREATE TABLE IF NOT EXISTS `guest_preferences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guest_id` INT UNSIGNED NOT NULL,
  `preference_type` VARCHAR(50) NOT NULL,
  `preference_value` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_type` (`preference_type`),
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default lead sources
INSERT INTO `lead_sources` (`name`, `type`, `color`) VALUES
('Meta Ads', 'meta_ads', '#1877f2'),
('Website Contact Form', 'website', '#28a745'),
('Website Booking Form', 'website', '#28a745'),
('Manual Entry', 'manual', '#6c757d'),
('Phone Call', 'phone', '#17a2b8'),
('Email Direct', 'email', '#ffc107'),
('Other', 'other', '#6c757d')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `is_active`) VALUES
('admin', 'admin@goldenpalmsbeachresort.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Admin', 'User', 'admin', 1)
ON DUPLICATE KEY UPDATE 
    `password` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    `is_active` = 1,
    `role` = 'admin';

-- Insert sample units
INSERT INTO `units` (`unit_number`, `unit_type`, `max_guests`, `description`, `amenities`) VALUES
('Unit 1', '2_bedroom', 6, '2 Bedroom Unit - Beach View', '["WiFi", "Air Conditioning", "Kitchen", "TV", "Beach Access"]'),
('Unit 2', '2_bedroom', 6, '2 Bedroom Unit - Garden View', '["WiFi", "Air Conditioning", "Kitchen", "TV", "Garden View"]'),
('Unit 3', '3_bedroom', 8, '3 Bedroom Unit - Beach View', '["WiFi", "Air Conditioning", "Kitchen", "TV", "Beach Access", "Balcony"]'),
('Unit 4', '5_bedroom', 10, '5 Bedroom Unit - Beach View', '["WiFi", "Air Conditioning", "Kitchen", "TV", "Beach Access", "Balcony", "Pool Access"]')
ON DUPLICATE KEY UPDATE `unit_number`=`unit_number`;

-- Insert default pricing rates
INSERT INTO `pricing_rates` (`unit_type`, `season`, `start_date`, `end_date`, `rate_per_night`, `min_nights`) VALUES
-- 2 Bedroom rates
('2_bedroom', 'low', '2024-01-01', '2024-03-31', 1200.00, 2),
('2_bedroom', 'mid', '2024-04-01', '2024-05-31', 1500.00, 2),
('2_bedroom', 'high', '2024-06-01', '2024-08-31', 1800.00, 3),
('2_bedroom', 'peak', '2024-12-15', '2025-01-15', 2200.00, 5),
-- 3 Bedroom rates
('3_bedroom', 'low', '2024-01-01', '2024-03-31', 1800.00, 2),
('3_bedroom', 'mid', '2024-04-01', '2024-05-31', 2200.00, 2),
('3_bedroom', 'high', '2024-06-01', '2024-08-31', 2600.00, 3),
('3_bedroom', 'peak', '2024-12-15', '2025-01-15', 3200.00, 5),
-- 5 Bedroom rates
('5_bedroom', 'low', '2024-01-01', '2024-03-31', 2800.00, 2),
('5_bedroom', 'mid', '2024-04-01', '2024-05-31', 3500.00, 2),
('5_bedroom', 'high', '2024-06-01', '2024-08-31', 4200.00, 3),
('5_bedroom', 'peak', '2024-12-15', '2025-01-15', 5200.00, 5)
ON DUPLICATE KEY UPDATE `rate_per_night`=VALUES(`rate_per_night`);

-- Insert default system settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
('resort_name', 'Golden Palms Beach Resort', 'string', 'general', 'Name of the resort'),
('resort_email', 'info@goldenpalmsbeachresort.com', 'string', 'contact', 'Main contact email'),
('resort_phone', '+27 42 294 1234', 'string', 'contact', 'Main contact phone'),
('resort_address', 'Beach Road, Port Edward, KwaZulu-Natal, South Africa', 'string', 'contact', 'Physical address'),
('whatsapp_number', '+27 82 123 4567', 'string', 'contact', 'WhatsApp Business number'),
('deposit_percentage', '50', 'number', 'booking', 'Default deposit percentage required'),
('cancellation_days', '7', 'number', 'booking', 'Days before check-in for free cancellation'),
('review_request_delay_days', '1', 'number', 'reviews', 'Days after checkout to send review request'),
('email_from_name', 'Golden Palms Beach Resort', 'string', 'email', 'Email sender name'),
('email_from_address', 'noreply@goldenpalmsbeachresort.com', 'string', 'email', 'Email sender address'),
('timezone', 'Africa/Johannesburg', 'string', 'general', 'System timezone'),
('currency', 'ZAR', 'string', 'general', 'Currency code'),
('currency_symbol', 'R', 'string', 'general', 'Currency symbol')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- Insert default email templates
INSERT INTO `email_templates` (`name`, `subject`, `body_html`, `template_type`, `variables`) VALUES
('Booking Confirmation', 'Booking Confirmation - {{booking_reference}}', 
'<h2>Thank you for your booking!</h2><p>Dear {{guest_name}},</p><p>Your booking has been confirmed.</p><p><strong>Booking Reference:</strong> {{booking_reference}}</p><p><strong>Check-in:</strong> {{check_in}}</p><p><strong>Check-out:</strong> {{check_out}}</p>', 
'booking_confirmation', 
'["booking_reference", "guest_name", "check_in", "check_out", "unit_type", "total_amount"]'),

('Review Request', 'How was your stay at Golden Palms?', 
'<h2>We hope you enjoyed your stay!</h2><p>Dear {{guest_name}},</p><p>Thank you for staying with us. We would love to hear about your experience.</p><p><a href="{{review_link}}">Leave a Review</a></p>', 
'review_request', 
'["guest_name", "booking_reference", "review_link", "check_in", "check_out"]')
ON DUPLICATE KEY UPDATE `subject`=VALUES(`subject`);

