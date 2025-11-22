# Custom CRM System Specification
## Golden Palms Beach Resort

**Version:** 1.0  
**Date:** 2024  
**Status:** Development Ready

---

## Executive Summary

This document outlines the complete specification for a custom CRM (Customer Relationship Management) system designed specifically for Golden Palms Beach Resort. The system will centralize all lead management, booking operations, guest communications, and marketing activities in one unified platform.

---

## 1. System Overview

### 1.1 Purpose
The CRM system will serve as the central hub for managing all customer interactions, from initial lead capture through booking completion and post-stay follow-up.

### 1.2 Core Objectives
- Centralize all leads from multiple sources (Meta Ads, website forms)
- Streamline booking management and tracking
- Maintain comprehensive guest database
- Automate lead nurturing and marketing campaigns
- Enable easy review request management
- Provide website content editing capabilities

### 1.3 Target Users
- **Primary**: Resort management staff
- **Secondary**: Marketing team
- **Tertiary**: Customer service representatives

---

## 2. Feature Specifications

### 2.1 Centralized Lead Dashboard

**Purpose**: Single view of all leads from all sources

**Features**:
- **Unified Lead List**
  - All leads displayed in chronological order
  - Color-coded by source (Meta Ads = Blue, Website = Green, Manual = Gray)
  - Status indicators (New, Contacted, Qualified, Converted, Lost)
  - Quick filters (Source, Status, Date Range, Assigned To)

- **Lead Summary Cards**
  - Total leads (today, week, month)
  - Conversion rate
  - Average response time
  - Top lead sources

- **Lead Details View**
  - Full contact information
  - Lead source and timestamp
  - Interaction history (calls, emails, WhatsApp)
  - Notes and tags
  - Booking history (if converted)
  - Related documents/attachments

- **Quick Actions**
  - Mark as contacted
  - Assign to team member
  - Convert to booking
  - Send email/WhatsApp
  - Add note
  - Schedule follow-up

**Technical Requirements**:
- Real-time updates
- Search functionality
- Export to CSV/Excel
- Bulk actions (assign, tag, delete)

---

### 2.2 Meta Ads Lead Integration

**Purpose**: Automatically capture and manage leads from Facebook/Instagram ads

**Features**:
- **Webhook Integration**
  - Connect to Meta Lead Ads
  - Automatic lead capture when form submitted
  - Real-time notifications

- **Lead Data Mapping**
  - Name, Email, Phone
  - Ad campaign name
  - Ad set name
  - Form submission timestamp
  - Custom questions/answers

- **Auto-Assignment Rules**
  - Assign leads based on campaign
  - Tag leads with campaign name
  - Set priority based on ad type

- **Lead Quality Scoring**
  - Score based on:
    - Completeness of information
    - Ad campaign performance
    - Time of submission
  - Flag high-quality leads

**Technical Requirements**:
- Meta Lead Ads API integration
- Webhook endpoint: `/api/webhooks/meta-leads`
- Secure token validation
- Duplicate detection

---

### 2.3 Website Lead Integration

**Purpose**: Capture leads from website contact/enquiry forms

**Features**:
- **Form Integration**
  - Contact form submissions
  - Booking enquiry forms
  - Newsletter signups
  - Special offer requests

- **Lead Capture Points**
  - Homepage contact form
  - Accommodation enquiry forms
  - "Book Now" form submissions
  - "Request Quote" forms

- **Auto-Processing**
  - Immediate email notification to staff
  - Auto-reply to lead
  - Lead assignment
  - Tagging based on form type

- **Form Analytics**
  - Conversion rate per form
  - Most popular forms
  - Abandonment tracking

**Technical Requirements**:
- REST API endpoint: `/api/leads/website`
- Form validation
- Spam protection (reCAPTCHA)
- Email notifications

---

### 2.4 Booking Management Panel

**Purpose**: Complete booking lifecycle management

**Features**:
- **Booking Dashboard**
  - Calendar view (monthly, weekly, daily)
  - List view with filters
  - Upcoming check-ins (next 7 days)
  - Current guests
  - Upcoming check-outs

- **Booking Details**
  - Guest information
  - Accommodation type and unit number
  - Check-in/check-out dates
  - Number of guests
  - Special requests
  - Payment status
  - Booking source (direct, Meta Ads, website, etc.)

- **Booking Statuses**
  - Pending (awaiting payment)
  - Confirmed
  - Checked In
  - Checked Out
  - Cancelled
  - No Show

- **Booking Actions**
  - Create new booking
  - Edit booking details
  - Cancel booking
  - Process payment
  - Send confirmation email
  - Generate invoice
  - Check-in/Check-out
  - Add notes

- **Availability Management**
  - Unit availability calendar
  - Block dates (maintenance, etc.)
  - Overbooking prevention
  - Waitlist management

- **Reports**
  - Occupancy rate
  - Revenue by period
  - Booking sources
  - Cancellation rate
  - Average stay duration

**Technical Requirements**:
- Database: Bookings, Units, Availability
- Calendar component
- Payment integration (optional)
- Email automation
- PDF generation for invoices

---

### 2.5 Guest Information Database

**Purpose**: Comprehensive guest profile management

**Features**:
- **Guest Profiles**
  - Personal information (name, email, phone, address)
  - Contact preferences
  - Special occasions (birthdays, anniversaries)
  - Dietary restrictions/allergies
  - Accessibility requirements

- **Booking History**
  - All past bookings
  - Total nights stayed
  - Total revenue generated
  - Favorite accommodation type
  - Average booking value

- **Communication History**
  - All emails sent/received
  - WhatsApp messages
  - Phone call logs
  - Notes from staff

- **Guest Segmentation**
  - Tags (VIP, Repeat Guest, Family, Corporate, etc.)
  - Custom fields
  - Segments for marketing

- **Guest Preferences**
  - Preferred unit type
  - Preferred dates/season
  - Activities of interest
  - Special requests history

- **Loyalty Tracking**
  - Number of visits
  - Loyalty points (if applicable)
  - Referral tracking
  - Special offers sent

**Technical Requirements**:
- Database: Guests, Guest_Bookings, Guest_Communications
- Search and filter functionality
- Merge duplicate profiles
- Data import/export
- GDPR compliance (data export/deletion)

---

### 2.6 Lead Nurturing / Monthly Campaign Access

**Purpose**: Automated and manual email marketing campaigns

**Features**:
- **Campaign Management**
  - Create email campaigns
  - Template library
  - A/B testing
  - Schedule campaigns
  - Campaign performance tracking

- **Automated Sequences**
  - Welcome series (new leads)
  - Abandoned booking reminders
  - Post-stay follow-up
  - Seasonal promotions
  - Birthday/anniversary emails

- **Monthly Campaigns**
  - Newsletter
  - Special offers
  - Seasonal promotions
  - Activity highlights
  - Local area updates

- **Segmentation**
  - Target by:
    - Lead source
    - Booking history
    - Guest type
    - Interests
    - Location
    - Custom tags

- **Campaign Analytics**
  - Open rate
  - Click-through rate
  - Conversion rate
  - Unsubscribe rate
  - Revenue generated

- **Email Templates**
  - Pre-designed templates
  - Custom HTML editor
  - Personalization tokens
  - Mobile-responsive design

**Technical Requirements**:
- Email service integration (SendGrid, Mailgun, etc.)
- Template engine
- Email queue system
- Unsubscribe management
- Bounce handling

---

### 2.7 Website Editing Access

**Purpose**: Allow staff to update website content without technical knowledge

**Features**:
- **Content Management**
  - Edit page content (text, images)
  - Update rates/pricing
  - Manage special offers
  - Update availability calendar
  - Edit accommodation descriptions

- **Page Editor**
  - WYSIWYG editor (rich text)
  - Image upload and management
  - Link management
  - Preview before publishing
  - Version history

- **Content Types**
  - Homepage sections
  - Accommodation pages
  - Activities pages
  - Blog posts
  - Special offers
  - FAQ entries

- **Media Library**
  - Upload images
  - Organize by category
  - Image optimization
  - Alt text management

- **Access Control**
  - Role-based permissions
  - Approval workflow (optional)
  - Audit log (who changed what, when)

**Technical Requirements**:
- CMS integration or custom editor
- File upload handling
- Image optimization
- Content versioning
- Backup before changes

---

### 2.8 Review Request Trigger (1-Click)

**Purpose**: Streamline review collection process

**Features**:
- **One-Click Review Request**
  - Single button to send review request
  - Choose delivery method (Email or WhatsApp)
  - Pre-filled templates
  - Personalization (guest name, stay dates)

- **Review Request Templates**
  - Email template
  - WhatsApp message template
  - Customizable message
  - Multiple review platform links (Google, TripAdvisor, Facebook)

- **Automated Triggers**
  - Auto-send after check-out (configurable delay)
  - Based on booking status
  - Scheduled batch sending

- **Review Tracking**
  - Track sent requests
  - Track review submissions
  - Link reviews to guest profiles
  - Review response management

- **Review Analytics**
  - Request send rate
  - Review submission rate
  - Average rating
  - Review platform distribution

- **Review Management**
  - View all reviews
  - Respond to reviews
  - Flag reviews for attention
  - Export reviews

**Technical Requirements**:
- Email integration
- WhatsApp Business API integration
- Review platform APIs (Google, TripAdvisor)
- Template system
- Tracking system

---

## 3. Technical Architecture

### 3.1 Technology Stack

**Backend**:
- **Framework**: Slim PHP 4.x (as per user preference)
- **Database**: MySQL 8.0+
- **API**: RESTful API
- **Authentication**: JWT tokens

**Frontend**:
- **Framework**: React.js or Vue.js (modern SPA)
- **UI Library**: Bootstrap 5 or Tailwind CSS
- **Charts**: Chart.js or similar
- **Calendar**: FullCalendar.js

**Integrations**:
- **Email**: SendGrid / Mailgun / SMTP
- **WhatsApp**: WhatsApp Business API
- **Meta Ads**: Meta Lead Ads API
- **Payment**: PayFast / PayPal (optional)

**Hosting**:
- **Server**: Linux (Ubuntu/CentOS)
- **Web Server**: Nginx or Apache
- **PHP**: 8.1+
- **SSL**: Required

### 3.2 Database Schema

**Core Tables**:
- `users` - System users (staff)
- `leads` - All leads from all sources
- `lead_sources` - Source types (Meta Ads, Website, Manual)
- `bookings` - Booking records
- `guests` - Guest profiles
- `units` - Accommodation units
- `availability` - Unit availability calendar
- `communications` - Email/WhatsApp logs
- `campaigns` - Email campaigns
- `campaign_recipients` - Campaign send list
- `reviews` - Review records
- `review_requests` - Review request logs
- `website_content` - Website content versions
- `activity_log` - System activity audit

### 3.3 API Endpoints

**Authentication**:
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/refresh`

**Leads**:
- `GET /api/leads` - List all leads
- `GET /api/leads/{id}` - Get lead details
- `POST /api/leads` - Create manual lead
- `PUT /api/leads/{id}` - Update lead
- `POST /api/leads/{id}/convert` - Convert to booking
- `POST /api/webhooks/meta-leads` - Meta Ads webhook
- `POST /api/leads/website` - Website form submission

**Bookings**:
- `GET /api/bookings` - List bookings
- `GET /api/bookings/{id}` - Get booking details
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Cancel booking
- `GET /api/bookings/calendar` - Calendar view
- `GET /api/bookings/availability` - Check availability

**Guests**:
- `GET /api/guests` - List guests
- `GET /api/guests/{id}` - Get guest profile
- `POST /api/guests` - Create guest
- `PUT /api/guests/{id}` - Update guest
- `GET /api/guests/{id}/bookings` - Guest booking history
- `GET /api/guests/{id}/communications` - Communication history

**Campaigns**:
- `GET /api/campaigns` - List campaigns
- `POST /api/campaigns` - Create campaign
- `POST /api/campaigns/{id}/send` - Send campaign
- `GET /api/campaigns/{id}/analytics` - Campaign analytics

**Reviews**:
- `POST /api/reviews/request` - Send review request
- `GET /api/reviews` - List reviews
- `GET /api/reviews/analytics` - Review analytics

**Website**:
- `GET /api/website/content` - Get content
- `PUT /api/website/content/{id}` - Update content
- `POST /api/website/media` - Upload media

---

## 4. User Interface Design

### 4.1 Dashboard Layout

**Main Navigation**:
- Dashboard (home)
- Leads
- Bookings
- Guests
- Campaigns
- Reviews
- Website Editor
- Settings

**Dashboard Widgets**:
- Today's leads
- Upcoming check-ins
- Current occupancy
- Revenue (today, week, month)
- Pending tasks
- Recent activity feed

### 4.2 Key Pages

**Leads Page**:
- Filterable table/list
- Quick view sidebar
- Bulk actions toolbar
- Lead source indicators

**Bookings Page**:
- Calendar view (default)
- List view toggle
- Color-coded by status
- Quick booking form

**Guests Page**:
- Searchable guest list
- Guest profile cards
- Quick actions menu

**Campaigns Page**:
- Campaign list with stats
- Create campaign button
- Template gallery

---

## 5. Security & Compliance

### 5.1 Security Measures
- User authentication (JWT)
- Role-based access control (RBAC)
- Password hashing (bcrypt)
- API rate limiting
- SQL injection prevention
- XSS protection
- CSRF tokens
- Secure file uploads

### 5.2 Data Protection
- GDPR compliance
- Data encryption at rest
- Secure data transmission (HTTPS)
- Regular backups
- Data retention policies
- Right to deletion

### 5.3 Access Control
- Admin role (full access)
- Manager role (leads, bookings, guests)
- Staff role (view only, limited edit)
- Custom permissions per feature

---

## 6. Integration Points

### 6.1 Meta Lead Ads
- Webhook URL registration
- Lead form field mapping
- Duplicate detection
- Auto-tagging

### 6.2 Website Forms
- JavaScript SDK for form submission
- AJAX form handlers
- reCAPTCHA integration
- Success/error handling

### 6.3 Email Service
- SMTP configuration
- Template management
- Bounce handling
- Unsubscribe management

### 6.4 WhatsApp Business API
- API credentials setup
- Message templates
- Media support
- Delivery status tracking

---

## 7. Implementation Phases

### Phase 1: Core CRM (Weeks 1-4)
- Database setup
- User authentication
- Lead dashboard
- Basic lead management
- Guest database

### Phase 2: Integrations (Weeks 5-6)
- Meta Ads integration
- Website form integration
- Email service setup

### Phase 3: Booking System (Weeks 7-8)
- Booking management panel
- Calendar view
- Availability management

### Phase 4: Marketing Tools (Weeks 9-10)
- Campaign management
- Email templates
- Automation sequences

### Phase 5: Advanced Features (Weeks 11-12)
- Review request system
- Website editor
- Analytics and reporting

### Phase 6: Testing & Launch (Weeks 13-14)
- User acceptance testing
- Bug fixes
- Documentation
- Training
- Launch

---

## 8. Success Metrics

### 8.1 Lead Management
- Lead response time (target: < 2 hours)
- Lead conversion rate
- Lead source performance

### 8.2 Booking Management
- Booking processing time
- Occupancy rate
- Revenue tracking

### 8.3 Marketing
- Email open rates
- Campaign conversion rates
- Review request success rate

---

## 9. Maintenance & Support

### 9.1 Regular Updates
- Security patches
- Feature enhancements
- Bug fixes
- Performance optimization

### 9.2 Backup Strategy
- Daily database backups
- Weekly full system backups
- Off-site backup storage

### 9.3 Monitoring
- System uptime monitoring
- Error logging
- Performance metrics
- User activity tracking

---

## 10. Documentation Requirements

- User manual
- Admin guide
- API documentation
- Integration guides
- Training materials
- Video tutorials

---

*End of Specification*

