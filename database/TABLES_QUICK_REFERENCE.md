# Database Tables - Quick Reference

## Core Tables (14 tables)

| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| **users** | Staff/Admin accounts | username, email, role, password |
| **lead_sources** | Lead source types | name, type, color |
| **leads** | Incoming leads | source_id, status, email, phone, assigned_to |
| **units** | Accommodation units | unit_number, unit_type, max_guests |
| **guests** | Guest database | first_name, last_name, email, phone, loyalty_points |
| **bookings** | Reservations | booking_reference, guest_id, unit_id, check_in, check_out, status |
| **unit_availability** | Blocked dates | unit_id, date, reason |
| **payment_transactions** | Payment history | booking_id, amount, payment_method, status |
| **pricing_rates** | Seasonal pricing | unit_type, season, rate_per_night, start_date, end_date |
| **communications** | Communication log | type, direction, guest_id, lead_id, booking_id, status |
| **campaigns** | Email campaigns | name, subject, type, status, scheduled_for |
| **campaign_recipients** | Campaign recipients | campaign_id, email, status |
| **review_requests** | Review requests | booking_id, guest_id, method, status |
| **reviews** | Collected reviews | guest_id, platform, rating, review_text |

## Supporting Tables (5 tables)

| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| **website_content** | Editable website content | page, section, content_key, content |
| **activity_log** | Audit trail | user_id, action, entity_type, entity_id |
| **settings** | System configuration | setting_key, setting_value, category |
| **email_templates** | Email templates | name, subject, template_type, variables |
| **guest_preferences** | Guest preferences | guest_id, preference_type, preference_value |

## Total: 19 Tables

## Key Relationships

```
users
  ├── leads (assigned_to)
  ├── bookings (created_by)
  ├── communications (sent_by)
  ├── campaigns (created_by)
  ├── review_requests (sent_by)
  └── activity_log (user_id)

lead_sources
  └── leads (source_id)

leads
  ├── bookings (lead_id)
  ├── campaign_recipients (lead_id)
  └── communications (lead_id)

units
  ├── bookings (unit_id)
  └── unit_availability (unit_id)

guests
  ├── bookings (guest_id)
  ├── communications (guest_id)
  ├── campaign_recipients (guest_id)
  ├── review_requests (guest_id)
  ├── reviews (guest_id)
  └── guest_preferences (guest_id)

bookings
  ├── payment_transactions (booking_id)
  ├── review_requests (booking_id)
  ├── reviews (booking_id)
  └── communications (booking_id)

campaigns
  └── campaign_recipients (campaign_id)

review_requests
  └── reviews (review_request_id)
```

## Common Queries

### Get all leads with source info
```sql
SELECT l.*, ls.name as source_name, ls.type as source_type
FROM leads l
JOIN lead_sources ls ON l.source_id = ls.id;
```

### Get booking with guest and unit info
```sql
SELECT b.*, g.first_name, g.last_name, g.email, u.unit_number, u.unit_type
FROM bookings b
JOIN guests g ON b.guest_id = g.id
JOIN units u ON b.unit_id = u.id;
```

### Get all payments for a booking
```sql
SELECT * FROM payment_transactions
WHERE booking_id = ?
ORDER BY created_at DESC;
```

### Get current pricing for a unit type
```sql
SELECT * FROM pricing_rates
WHERE unit_type = ? 
  AND season = ?
  AND CURDATE() BETWEEN start_date AND end_date
  AND is_active = 1;
```

### Get guest booking history
```sql
SELECT b.*, u.unit_number, u.unit_type
FROM bookings b
JOIN units u ON b.unit_id = u.id
WHERE b.guest_id = ?
ORDER BY b.check_in DESC;
```

### Get all communications for a guest
```sql
SELECT * FROM communications
WHERE guest_id = ?
ORDER BY created_at DESC;
```

### Get campaign statistics
```sql
SELECT 
  name,
  total_recipients,
  total_sent,
  total_opened,
  total_clicked,
  (total_opened / NULLIF(total_sent, 0) * 100) as open_rate,
  (total_clicked / NULLIF(total_sent, 0) * 100) as click_rate
FROM campaigns
WHERE status = 'sent';
```



