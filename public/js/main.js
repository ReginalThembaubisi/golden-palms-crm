// API Configuration
// Use relative URL for production, fallback to localhost for development
const API_BASE_URL = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') 
    ? 'http://localhost:8000/api' 
    : '/api';

// Initialize on page load (optimized - only run when DOM is ready)
(function() {
    'use strict';
    
    // Check if DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded
        init();
    }
    
    function init() {
        // Clear any live edit mode flags
        localStorage.removeItem('liveEditMode');
        
        // Remove edit mode banner if it exists
        const editBanner = document.querySelector('.edit-mode-banner');
        if (editBanner) {
            editBanner.remove();
            document.body.style.paddingTop = '';
        }
        
        // Remove edit overlays if they exist
        document.querySelectorAll('.edit-overlay').forEach(overlay => overlay.remove());
        document.querySelectorAll('.editable-section').forEach(section => {
            section.classList.remove('editable-section');
            if (section.style.position === 'relative' && !section.hasAttribute('data-original-position')) {
                section.style.position = '';
            }
        });
        
        initNavigation();
        initBookingForm();
        initQuickBooking();
        initSmoothScroll();
        checkURLParams();
        setMinDates(); // Set minimum dates for booking forms
        
        // Load API data asynchronously without blocking page load
        // Use setTimeout to ensure page renders first
        setTimeout(() => {
            // Always load pricing - it will update rates page if on that page
            loadPricing().catch(err => console.warn('Pricing load failed:', err));
            // Load contact information dynamically
            loadContactInfo().catch(err => console.warn('Contact info load failed:', err));
        }, 100);
    }
})();

// Navigation
function initNavigation() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    const navbar = document.querySelector('.navbar');

    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Close mobile menu when clicking a link
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });
    });
}

// Smooth scroll
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offsetTop = target.offsetTop - 70;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}

// Check URL parameters for pre-filled booking form
function checkURLParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type');
    
    if (type) {
        const bookingUnitType = document.getElementById('bookingUnitType');
        if (bookingUnitType) {
            bookingUnitType.value = type;
        }
        
        // Scroll to booking section
        setTimeout(() => {
            const bookingSection = document.getElementById('book-now');
            if (bookingSection) {
                const offsetTop = bookingSection.offsetTop - 70;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        }, 500);
    }
}

// Load pricing from API or set defaults
// Load contact information from API
async function loadContactInfo() {
    try {
        // Add timeout to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        const response = await fetch(`${API_BASE_URL}/website/content?page=homepage`, {
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        
        if (response.ok) {
            const content = await response.json();
            
            // Find contact fields
            const phoneContent = content.find(item => item.section === 'contact_phone' && item.is_published);
            const emailContent = content.find(item => item.section === 'contact_email' && item.is_published);
            const whatsappContent = content.find(item => item.section === 'contact_whatsapp' && item.is_published);
            const addressContent = content.find(item => item.section === 'contact_address' && item.is_published);
            const extraContent = content.find(item => item.section === 'contact_extra' && item.is_published);
            
            // Update phone
            const phoneLink = document.getElementById('contact-phone-link');
            if (phoneLink && phoneContent && phoneContent.content) {
                const phone = phoneContent.content.trim();
                phoneLink.textContent = phone;
                phoneLink.href = `tel:${phone.replace(/\s+/g, '')}`;
            }
            
            // Update email
            const emailLink = document.getElementById('contact-email-link');
            if (emailLink && emailContent && emailContent.content) {
                const email = emailContent.content.trim();
                emailLink.textContent = email;
                emailLink.href = `mailto:${email}`;
            }
            
            // Update WhatsApp
            const whatsappLink = document.getElementById('contact-whatsapp-link');
            if (whatsappLink && whatsappContent && whatsappContent.content) {
                const whatsapp = whatsappContent.content.trim();
                whatsappLink.textContent = whatsapp;
                const whatsappNumber = whatsapp.replace(/\s+/g, '').replace(/\+/g, '');
                whatsappLink.href = `https://wa.me/${whatsappNumber}`;
            }
            
            // Update address (if displayed somewhere)
            // This could be used in a map or address section if needed
            
            // Update extra info
            const extraEl = document.getElementById('contact-extra');
            if (extraEl && extraContent && extraContent.content) {
                extraEl.textContent = extraContent.content.trim();
                extraEl.style.display = 'block';
            }
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            console.warn('Contact info request timed out');
        } else {
            console.error('Error loading contact info:', error);
        }
        // Continue without contact info - page should still work
    }
}

async function loadPricing() {
    try {
        // Add timeout to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout (reduced)
        
        // Load rates page content from API
        const response = await fetch(`${API_BASE_URL}/website/content?page=rates`, {
            signal: controller.signal,
            cache: 'no-cache'
        });
        clearTimeout(timeoutId);
        
        if (response.ok) {
            const content = await response.json();
            console.log('Loaded content from API:', content);
            
            // Create a map of section -> content
            const contentMap = {};
            content.forEach(item => {
                if (item.is_published) {
                    contentMap[item.section] = item.content;
                }
            });
            
            console.log('Content map:', contentMap);
            
            // Update intro text
            if (contentMap['intro']) {
                const introEl = document.getElementById('rates-intro');
                if (introEl) {
                    introEl.textContent = contentMap['intro'];
                    console.log('Updated intro text');
                }
            }
            
            // Update all rate prices
            const rateElements = document.querySelectorAll('[data-rate]');
            console.log(`Found ${rateElements.length} rate elements to update`);
            console.log('Available content keys:', Object.keys(contentMap));
            
            rateElements.forEach(el => {
                const rateKey = el.getAttribute('data-rate');
                const oldText = el.textContent;
                
                if (contentMap[rateKey]) {
                    const price = contentMap[rateKey];
                    // Format: R850 or R1,200 -> R850/night or R1,200/night
                    const formattedPrice = price.includes('/night') ? price : `${price}/night`;
                    el.textContent = formattedPrice;
                    console.log(`✓ Updated ${rateKey}: "${oldText}" → "${formattedPrice}"`);
                } else {
                    console.log(`✗ No content found for ${rateKey} (current: "${oldText}")`);
                }
            });
            
            // Update homepage prices if on homepage
            if (document.getElementById('price-2bed')) {
                updateHomepagePrices(contentMap);
            }
        } else {
            // Fallback to defaults if API fails
            console.warn('Could not load pricing from API, status:', response.status);
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            console.warn('Pricing request timed out - using default prices');
        } else {
            console.error('Error loading pricing:', error);
        }
        // Fallback to defaults - page should still work
    }
}

// Update homepage prices
function updateHomepagePrices(contentMap) {
    const priceMap = {
        'rate_2bed_low': 'price-2bed',
        'rate_3bed_low': 'price-3bed',
        'rate_5bed_low': 'price-5bed'
    };
    
    Object.keys(priceMap).forEach(rateKey => {
        if (contentMap[rateKey]) {
            const price = contentMap[rateKey].replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
            const element = document.getElementById(priceMap[rateKey]);
            if (element) {
                element.textContent = price;
            }
        }
    });
}

// Quick Booking Form
function initQuickBooking() {
    const quickBookingForm = document.getElementById('quickBookingForm');
    
    if (quickBookingForm) {
        quickBookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(quickBookingForm);
            const data = {
                check_in: formData.get('check_in'),
                check_out: formData.get('check_out'),
                unit_type: formData.get('unit_type') || null
            };

            try {
                // Check availability via API with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch(`${API_BASE_URL}/bookings/availability?` + new URLSearchParams({
                    check_in: data.check_in,
                    check_out: data.check_out,
                    unit_type: data.unit_type || ''
                }), {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                if (response.ok) {
                    const result = await response.json();
                    if (result.available > 0) {
                        // Redirect to booking form with dates pre-filled
                        const params = new URLSearchParams({
                            check_in: data.check_in,
                            check_out: data.check_out,
                            unit_type: data.unit_type || ''
                        });
                        window.location.href = `#book-now?${params.toString()}`;
                    } else {
                        alert('Sorry, no units available for the selected dates. Please try different dates.');
                    }
                } else {
                    // If API not available, just redirect to booking form
                    const params = new URLSearchParams({
                        check_in: data.check_in,
                        check_out: data.check_out,
                        unit_type: data.unit_type || ''
                    });
                    window.location.href = `#book-now?${params.toString()}`;
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.warn('Availability check timed out - redirecting to booking form');
                } else {
                    console.error('Error checking availability:', error);
                }
                // Fallback: redirect to booking form
                const params = new URLSearchParams({
                    check_in: data.check_in,
                    check_out: data.check_out,
                    unit_type: data.unit_type || ''
                });
                window.location.href = `#book-now?${params.toString()}`;
            }
        });
    }
}

// Main Booking Form
function initBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    const formMessage = document.getElementById('formMessage');
    
    if (bookingForm) {
        // Pre-fill form from URL params
        const urlParams = new URLSearchParams(window.location.search);
        const checkIn = urlParams.get('check_in');
        const checkOut = urlParams.get('check_out');
        const unitType = urlParams.get('unit_type');
        
        if (checkIn) {
            const checkInInput = bookingForm.querySelector('input[name="check_in"]');
            if (checkInInput) checkInInput.value = checkIn;
        }
        
        if (checkOut) {
            const checkOutInput = bookingForm.querySelector('input[name="check_out"]');
            if (checkOutInput) checkOutInput.value = checkOut;
        }
        
        if (unitType) {
            const unitTypeSelect = bookingForm.querySelector('select[name="unit_type"]');
            if (unitTypeSelect) unitTypeSelect.value = unitType;
        }

        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Clear previous messages
            if (formMessage) {
                formMessage.className = 'form-message';
                formMessage.textContent = '';
                formMessage.style.display = 'none';
            }
            
            const formData = new FormData(bookingForm);
            const data = {
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                check_in: formData.get('check_in'),
                check_out: formData.get('check_out'),
                guests: formData.get('guests'),
                unit_type: formData.get('unit_type'),
                message: formData.get('message'),
                form_type: 'booking'
            };

            // Show loading state
            const submitButton = bookingForm.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                // Submit to CRM API with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
                
                const response = await fetch(`${API_BASE_URL}/leads/website`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                const result = await response.json();

                if (response.ok) {
                    // Success
                    formMessage.className = 'form-message success';
                    formMessage.innerHTML = '<i class="fas fa-check-circle"></i> Thank you! Your enquiry has been sent successfully. We will contact you within 24 hours.';
                    formMessage.style.display = 'block';
                    bookingForm.reset();
                    
                    // Scroll to message
                    setTimeout(() => {
                        formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                } else {
                    // Error from API
                    formMessage.className = 'form-message error';
                    formMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'An error occurred. Please try again or contact us directly.');
                    formMessage.style.display = 'block';
                    setTimeout(() => {
                        formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.warn('Form submission timed out - showing fallback message');
                } else {
                    console.error('Error submitting form:', error);
                }
                // Fallback: show success message anyway (form might work via email)
                formMessage.className = 'form-message success';
                formMessage.innerHTML = '<i class="fas fa-check-circle"></i> Thank you! Your enquiry has been received. If you don\'t hear from us within 24 hours, please call us at +27 72 565 7091.';
                formMessage.style.display = 'block';
                setTimeout(() => {
                    formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }
}

// Gallery Lightbox (optional enhancement)
function initGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            if (img) {
                // Simple lightbox - can be enhanced with a library
                const lightbox = document.createElement('div');
                lightbox.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    cursor: pointer;
                `;
                
                const imgClone = img.cloneNode();
                imgClone.style.cssText = 'max-width: 90%; max-height: 90%; object-fit: contain;';
                lightbox.appendChild(imgClone);
                
                lightbox.addEventListener('click', () => {
                    document.body.removeChild(lightbox);
                });
                
                document.body.appendChild(lightbox);
            }
        });
    });
}

// Initialize gallery if it exists
if (document.querySelector('.gallery-item')) {
    initGallery();
}

// Set minimum date for booking forms (today)
// Use the same init pattern to avoid duplicate listeners
function setMinDates() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.setAttribute('min', today);
    });
}

// Call it in init function instead of separate listener
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setMinDates);
} else {
    setMinDates();
}

// WhatsApp Integration
function openWhatsApp(message = '') {
    const phone = '27725657091';
    const encodedMessage = encodeURIComponent(message || 'Hello, I\'m interested in booking at Golden Palms Beach Resort.');
    window.open(`https://wa.me/${phone}?text=${encodedMessage}`, '_blank');
}

// Add WhatsApp button click handlers
document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const phone = this.getAttribute('href').match(/wa\.me\/(\d+)/)?.[1];
        if (phone) {
            openWhatsApp();
        }
    });
});

