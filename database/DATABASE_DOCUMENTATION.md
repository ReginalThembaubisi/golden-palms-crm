# Golden Palms Beach Resort CRM - Database Documentation

## Overview
This document describes the complete database schema for the Golden Palms Beach Resort CRM system. The database uses MySQL 8.0+ with InnoDB engine and UTF8MB4 character set.

## Database Tables

### 1. **users** - Staff/Admin Users
Stores all staff members and administrators who can access the CRM system.

**Columns:**
- `id` - Primary key
- `username` - Unique username for login
- `email` - Unique email address
- `password` - Hashed password (bcrypt)
- `first_name`, `last_name` - User's full name
- `role` - User role: 'admin', 'manager', or 'staff'
- `is_active` - Whether the user account is active
- `last_login` - Timestamp of last login
- `created_at`, `updated_at` - Timestamps

**Indexes:** email, role

---

### 2. **lead_sources** - Lead Source Types
Defines the different sources from which leads can originate.

**Columns:**
- `id` - Primary key
- `name` - Source name (e.g., "Meta Ads", "Website Contact Form")
- `type` - Source type: 'meta_ads', 'website', 'manual', 'phone', 'email', 'other'
- `color` - Color code for UI display
- `is_active` - Whether this source is active
- `created_at` - Timestamp

**Default Data:**
- Meta Ads (#1877f2)
- Website Contact Form (#28a745)
- Website Booking Form (#28a745)
- Manual Entry (#6c757d)
- Phone Call (#17a2b8)
- Email Direct (#ffc107)
- Other (#6c757d)

---

### 3. **leads** - Lead Management
Stores all incoming leads from various sources.

**Columns:**
- `id` - Primary key
- `source_id` - Foreign key to `lead_sources`
- `first_name`, `last_name` - Lead's name
- `email`, `phone` - Contact information
- `status` - Lead status: 'new', 'contacted', 'qualified', 'converted', 'lost'
- `priority` - Priority level: 'low', 'medium', 'high'
- `assigned_to` - Foreign key to `users` (assigned staff member)
- `campaign_name`, `ad_set_name` - Meta Ads tracking fields
- `form_type` - Website form type
- `message` - Lead's message/inquiry
- `notes` - Internal notes
- `tags` - JSON array of tags
- `quality_score` - Lead quality score (0-100)
- `converted_to_booking_id` - Foreign key to `bookings` if converted
- `created_at`, `updated_at`, `contacted_at` - Timestamps

**Indexes:** source_id, status, assigned_to, email, phone, created_at

**Foreign Keys:**
- `source_id` → `lead_sources.id`
- `assigned_to` → `users.id`
- `converted_to_booking_id` → `bookings.id`

---

### 4. **units** - Accommodation Units
Stores information about each accommodation unit.

**Columns:**
- `id` - Primary key
- `unit_number` - Unique unit identifier (e.g., "Unit 1")
- `unit_type` - Type: '2_bedroom', '3_bedroom', '5_bedroom'
- `max_guests` - Maximum occupancy
- `description` - Unit description
- `amenities` - JSON array of amenities
- `is_active` - Whether unit is available for booking
- `created_at`, `updated_at` - Timestamps

**Indexes:** unit_type

**Sample Data:**
- Unit 1-2: 2 Bedroom (6 guests)
- Unit 3: 3 Bedroom (8 guests)
- Unit 4: 5 Bedroom (10 guests)

---

### 5. **guests** - Guest Database
Comprehensive guest information and history.

**Columns:**
- `id` - Primary key
- `first_name`, `last_name` - Guest name
- `email`, `phone`, `phone_alt` - Contact information
- `address`, `city`, `country` - Address details
- `date_of_birth` - Date of birth
- `preferred_contact` - Preferred method: 'email', 'phone', 'whatsapp'
- `dietary_restrictions` - Dietary requirements
- `accessibility_needs` - Accessibility requirements
- `special_occasions` - JSON array (birthdays, anniversaries)
- `preferences` - JSON object (room preferences, activities)
- `tags` - JSON array of tags
- `loyalty_points` - Loyalty program points
- `total_nights` - Total nights stayed
- `total_revenue` - Total revenue from this guest
- `last_visit` - Date of last visit
- `notes` - Internal notes
- `created_at`, `updated_at` - Timestamps

**Indexes:** email, phone, (last_name, first_name)

---

### 6. **bookings** - Booking Management
Stores all bookings and reservations.

**Columns:**
- `id` - Primary key
- `booking_reference` - Unique booking reference code
- `guest_id` - Foreign key to `guests`
- `lead_id` - Foreign key to `leads` (if converted from lead)
- `unit_id` - Foreign key to `units`
- `check_in`, `check_out` - Booking dates
- `number_of_guests` - Number of guests
- `unit_type` - Unit type booked
- `status` - Booking status: 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'
- `total_amount` - Total booking amount
- `deposit_amount` - Deposit paid
- `balance_amount` - Remaining balance
- `payment_status` - Payment status: 'pending', 'partial', 'paid', 'refunded'
- `payment_method` - Payment method used
- `special_requests` - Guest special requests
- `notes` - Internal notes
- `cancellation_reason` - Reason for cancellation
- `cancelled_at`, `checked_in_at`, `checked_out_at` - Timestamps
- `created_by` - Foreign key to `users` (who created the booking)
- `created_at`, `updated_at` - Timestamps

**Indexes:** guest_id, unit_id, lead_id, (check_in, check_out), status, booking_reference

**Foreign Keys:**
- `guest_id` → `guests.id`
- `lead_id` → `leads.id`
- `unit_id` → `units.id`
- `created_by` → `users.id`

---

### 7. **unit_availability** - Unit Availability Management
Tracks blocked dates for units (maintenance, closures, etc.).

**Columns:**
- `id` - Primary key
- `unit_id` - Foreign key to `units`
- `date` - Blocked date
- `reason` - Reason for blocking (e.g., "maintenance", "closed")
- `created_at` - Timestamp

**Unique Constraint:** (unit_id, date) - Prevents duplicate blocks

**Foreign Keys:**
- `unit_id` → `units.id` (CASCADE delete)

---

### 8. **payment_transactions** - Payment History
Detailed transaction history for all payments.

**Columns:**
- `id` - Primary key
- `booking_id` - Foreign key to `bookings`
- `transaction_type` - Type: 'deposit', 'payment', 'refund', 'adjustment'
- `amount` - Transaction amount
- `payment_method` - Method: 'cash', 'card', 'bank_transfer', 'eft', 'paypal', 'other'
- `transaction_reference` - External transaction reference
- `status` - Status: 'pending', 'completed', 'failed', 'cancelled', 'refunded'
- `processed_by` - Foreign key to `users` (who processed)
- `processed_at` - Processing timestamp
- `notes` - Transaction notes
- `created_at`, `updated_at` - Timestamps

**Indexes:** booking_id, status, transaction_type, transaction_reference

**Foreign Keys:**
- `booking_id` → `bookings.id` (CASCADE delete)
- `processed_by` → `users.id`

---

### 9. **pricing_rates** - Seasonal Pricing
Defines pricing rates by unit type and season.

**Columns:**
- `id` - Primary key
- `unit_type` - Unit type: '2_bedroom', '3_bedroom', '5_bedroom'
- `season` - Season: 'low', 'mid', 'high', 'peak'
- `start_date`, `end_date` - Date range for this rate
- `rate_per_night` - Price per night
- `min_nights` - Minimum nights required
- `max_nights` - Maximum nights allowed (NULL = unlimited)
- `is_active` - Whether rate is active
- `created_at`, `updated_at` - Timestamps

**Indexes:** unit_type, season, (start_date, end_date), is_active

**Sample Data:**
- Low season: Jan-Mar
- Mid season: Apr-May
- High season: Jun-Aug
- Peak season: Dec 15 - Jan 15

---

### 10. **communications** - Communication Log
Logs all communications (emails, WhatsApp, phone, SMS).

**Columns:**
- `id` - Primary key
- `type` - Communication type: 'email', 'whatsapp', 'phone', 'sms'
- `direction` - Direction: 'inbound', 'outbound'
- `guest_id` - Foreign key to `guests` (optional)
- `lead_id` - Foreign key to `leads` (optional)
- `booking_id` - Foreign key to `bookings` (optional)
- `subject` - Message subject
- `message` - Message content
- `to_email`, `to_phone` - Recipient contact
- `from_email`, `from_phone` - Sender contact
- `status` - Status: 'sent', 'delivered', 'read', 'failed', 'pending'
- `sent_by` - Foreign key to `users` (who sent)
- `sent_at` - Send timestamp
- `created_at` - Timestamp

**Indexes:** guest_id, lead_id, booking_id, type, sent_at

**Foreign Keys:**
- `guest_id` → `guests.id`
- `lead_id` → `leads.id`
- `booking_id` → `bookings.id`
- `sent_by` → `users.id`

---

### 11. **campaigns** - Email Campaigns
Stores email marketing campaigns.

**Columns:**
- `id` - Primary key
- `name` - Campaign name
- `subject` - Email subject line
- `template_id` - Foreign key to `email_templates` (optional)
- `content` - Campaign content (HTML)
- `type` - Campaign type: 'newsletter', 'promotion', 'automated', 'custom'
- `status` - Status: 'draft', 'scheduled', 'sending', 'sent', 'cancelled'
- `segment` - JSON object with target segment criteria
- `scheduled_for` - Scheduled send time
- `sent_at` - Actual send time
- `total_recipients` - Total number of recipients
- `total_sent` - Successfully sent count
- `total_opened` - Open count
- `total_clicked` - Click count
- `total_bounced` - Bounce count
- `total_unsubscribed` - Unsubscribe count
- `created_by` - Foreign key to `users`
- `created_at`, `updated_at` - Timestamps

**Indexes:** status, type, scheduled_for

**Foreign Keys:**
- `created_by` → `users.id`

---

### 12. **campaign_recipients** - Campaign Recipients
Tracks individual recipients for each campaign.

**Columns:**
- `id` - Primary key
- `campaign_id` - Foreign key to `campaigns`
- `guest_id` - Foreign key to `guests` (optional)
- `lead_id` - Foreign key to `leads` (optional)
- `email` - Recipient email address
- `status` - Status: 'pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed'
- `opened_at`, `clicked_at`, `bounced_at`, `sent_at` - Timestamps
- `created_at` - Timestamp

**Indexes:** campaign_id, guest_id, lead_id, status

**Foreign Keys:**
- `campaign_id` → `campaigns.id` (CASCADE delete)
- `guest_id` → `guests.id`
- `lead_id` → `leads.id`

---

### 13. **review_requests** - Review Request Management
Tracks review requests sent to guests.

**Columns:**
- `id` - Primary key
- `booking_id` - Foreign key to `bookings`
- `guest_id` - Foreign key to `guests`
- `method` - Send method: 'email', 'whatsapp'
- `status` - Status: 'pending', 'sent', 'delivered', 'reviewed', 'failed'
- `message` - Custom message sent
- `review_links` - JSON array of review platform links
- `sent_at` - Send timestamp
- `reviewed_at` - When guest left a review
- `sent_by` - Foreign key to `users` (who sent)
- `created_at` - Timestamp

**Indexes:** booking_id, guest_id, status

**Foreign Keys:**
- `booking_id` → `bookings.id` (CASCADE delete)
- `guest_id` → `guests.id` (CASCADE delete)
- `sent_by` → `users.id`

---

### 14. **reviews** - Collected Reviews
Stores reviews collected from various platforms.

**Columns:**
- `id` - Primary key
- `review_request_id` - Foreign key to `review_requests` (optional)
- `booking_id` - Foreign key to `bookings` (optional)
- `guest_id` - Foreign key to `guests`
- `platform` - Platform: 'google', 'tripadvisor', 'facebook', 'website', 'other'
- `rating` - Rating (1-5)
- `title` - Review title
- `review_text` - Review content
- `reviewer_name` - Reviewer's name
- `review_url` - URL to the review
- `is_visible` - Whether to display on website
- `response` - Management response
- `responded_at` - Response timestamp
- `reviewed_at` - Review date
- `created_at` - Timestamp

**Indexes:** guest_id, booking_id, platform, rating

**Foreign Keys:**
- `review_request_id` → `review_requests.id`
- `booking_id` → `bookings.id`
- `guest_id` → `guests.id` (CASCADE delete)

---

### 15. **website_content** - Website Content Management
Stores editable website content.

**Columns:**
- `id` - Primary key
- `page` - Page identifier (e.g., "home", "about")
- `section` - Section identifier (e.g., "hero", "about")
- `content_key` - Content key (e.g., "title", "description")
- `content_type` - Type: 'text', 'html', 'image', 'json'
- `content` - Content value
- `metadata` - JSON metadata
- `version` - Content version number
- `is_published` - Whether content is published
- `published_at` - Publication timestamp
- `created_by` - Foreign key to `users`
- `created_at`, `updated_at` - Timestamps

**Unique Constraint:** (page, section, content_key, version)

**Indexes:** page, is_published

**Foreign Keys:**
- `created_by` → `users.id`

---

### 16. **activity_log** - Audit Trail
Logs all system activities for audit purposes.

**Columns:**
- `id` - Primary key
- `user_id` - Foreign key to `users` (who performed action)
- `action` - Action type (e.g., "create", "update", "delete")
- `entity_type` - Entity type (e.g., "booking", "lead", "guest")
- `entity_id` - Entity ID
- `description` - Action description
- `changes` - JSON object with before/after changes
- `ip_address` - User's IP address
- `user_agent` - User's browser/agent
- `created_at` - Timestamp

**Indexes:** user_id, (entity_type, entity_id), action, created_at

**Foreign Keys:**
- `user_id` → `users.id`

---

### 17. **settings** - System Settings
Stores system configuration settings.

**Columns:**
- `id` - Primary key
- `setting_key` - Unique setting key
- `setting_value` - Setting value
- `setting_type` - Value type: 'string', 'number', 'boolean', 'json'
- `category` - Setting category (e.g., "general", "email", "booking")
- `description` - Setting description
- `updated_by` - Foreign key to `users` (who last updated)
- `updated_at`, `created_at` - Timestamps

**Indexes:** setting_key, category

**Foreign Keys:**
- `updated_by` → `users.id`

**Default Settings:**
- Resort name, email, phone, address
- WhatsApp number
- Deposit percentage
- Cancellation policy
- Review request delay
- Email configuration
- Timezone and currency

---

### 18. **email_templates** - Email Templates
Stores reusable email templates.

**Columns:**
- `id` - Primary key
- `name` - Template name
- `subject` - Email subject (with variables)
- `body_html` - HTML email body
- `body_text` - Plain text version (optional)
- `template_type` - Type: 'booking_confirmation', 'booking_reminder', 'review_request', 'campaign', 'custom'
- `variables` - JSON array of available template variables
- `is_active` - Whether template is active
- `created_by` - Foreign key to `users`
- `created_at`, `updated_at` - Timestamps

**Indexes:** template_type, is_active

**Foreign Keys:**
- `created_by` → `users.id`

**Default Templates:**
- Booking Confirmation
- Review Request

---

### 19. **guest_preferences** - Guest Preferences
Stores detailed guest preferences for better segmentation.

**Columns:**
- `id` - Primary key
- `guest_id` - Foreign key to `guests`
- `preference_type` - Type of preference (e.g., "room_view", "activities")
- `preference_value` - Preference value
- `created_at`, `updated_at` - Timestamps

**Indexes:** guest_id, preference_type

**Foreign Keys:**
- `guest_id` → `guests.id` (CASCADE delete)

---

## Database Relationships

### Core Relationships:
1. **Leads → Bookings**: A lead can be converted to a booking
2. **Guests → Bookings**: A guest can have multiple bookings
3. **Units → Bookings**: A unit can have multiple bookings (over time)
4. **Bookings → Payments**: A booking can have multiple payment transactions
5. **Bookings → Reviews**: A booking can generate review requests and reviews
6. **Guests → Campaigns**: Guests can receive campaign emails
7. **Leads → Campaigns**: Leads can receive campaign emails

### User Relationships:
- Users can be assigned to leads
- Users can create bookings
- Users can send communications
- Users can create campaigns
- Users can send review requests
- Users can update website content
- Users can update settings

## Indexes and Performance

All tables include appropriate indexes for:
- Foreign keys
- Frequently queried columns (status, dates, emails, phones)
- Composite indexes for common query patterns
- Unique constraints where needed

## Data Integrity

- Foreign keys enforce referential integrity
- Unique constraints prevent duplicates
- Check constraints validate data ranges (e.g., ratings 1-5)
- Default values ensure data consistency

## Security Considerations

- Passwords are hashed using bcrypt
- User roles control access levels
- Activity log tracks all changes
- Soft deletes via `is_active` flags where appropriate

## Backup and Maintenance

- Regular backups recommended
- Consider archiving old activity logs
- Monitor index performance
- Review and optimize slow queries



