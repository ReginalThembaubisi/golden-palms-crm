// Admin Dashboard JavaScript
// Use relative URL for production, fallback to localhost for development
const API_BASE_URL = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') 
    ? 'http://localhost:8000/api' 
    : '/api';

let authToken = localStorage.getItem('authToken');
let currentUser = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    initEventListeners();
    if (authToken) {
        loadDashboard();
    }
});

// Check authentication
function checkAuth() {
    if (authToken) {
        verifyToken();
    } else {
        showLogin();
    }
}

// Verify token
async function verifyToken() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/me`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (response.ok) {
            currentUser = await response.json();
            showDashboard();
        } else {
            localStorage.removeItem('authToken');
            showLogin();
        }
    } catch (error) {
        console.error('Auth error:', error);
        showLogin();
    }
}

// Show login screen
function showLogin() {
    document.getElementById('loginScreen').style.display = 'flex';
    document.getElementById('dashboard').style.display = 'none';
}

// Load dashboard (alias for showDashboard)
function loadDashboard() {
    showDashboard();
}

// Show dashboard
function showDashboard() {
    document.getElementById('loginScreen').style.display = 'none';
    document.getElementById('dashboard').style.display = 'flex';
    loadDashboardData();
}

// Initialize event listeners
function initEventListeners() {
    // Login form
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    
    // Navigation
    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            showPage(page);
        });
    });
    
    // Modal close
    document.getElementById('modalClose').addEventListener('click', closeModal);
    document.getElementById('modalOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Bookings view toggle
    document.querySelectorAll('.btn-toggle[data-view]').forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            const bookingsCalendar = document.getElementById('bookingsCalendar');
            const bookingsList = document.getElementById('bookingsList');
            
            // Update active button
            document.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide views
            if (view === 'calendar') {
                if (bookingsCalendar) bookingsCalendar.style.display = 'block';
                if (bookingsList) bookingsList.style.display = 'none';
                // Load calendar when switching to calendar view
                loadBookingsCalendar();
            } else if (view === 'list') {
                if (bookingsCalendar) bookingsCalendar.style.display = 'none';
                if (bookingsList) bookingsList.style.display = 'block';
                // Load bookings when switching to list view
                loadBookings();
            }
        });
    });
    
    // Website editor event listeners (delegated - will work when page loads)
    setTimeout(() => {
        // Page selection in sidebar
        document.querySelectorAll('.page-list a[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                selectWebsitePage(page);
            });
        });
        
        // Save button
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveWebsiteContent);
        }
        
        // Preview button
        const previewBtn = document.getElementById('previewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', previewWebsiteContent);
        }
    }, 100);
}

// Handle login
async function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('loginError');
    
    errorDiv.classList.remove('show');
    
    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            authToken = data.token;
            currentUser = data.user;
            localStorage.setItem('authToken', authToken);
            showDashboard();
        } else {
            errorDiv.textContent = data.message || 'Login failed';
            errorDiv.classList.add('show');
        }
    } catch (error) {
        errorDiv.textContent = 'Connection error. Please check if the server is running.';
        errorDiv.classList.add('show');
    }
}

// Handle logout
function handleLogout() {
    localStorage.removeItem('authToken');
    authToken = null;
    currentUser = null;
    showLogin();
}

// Show page
function showPage(pageId) {
    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.style.display = 'none';
        page.classList.remove('active');
    });
    
    // Show selected page
    const targetPage = document.getElementById(`page-${pageId}`);
    if (targetPage) {
        targetPage.style.display = 'block';
        targetPage.classList.add('active');
    }
    
    // Update nav
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-page="${pageId}"]`).classList.add('active');
    
    // Update title
    const titles = {
        'dashboard-home': 'Dashboard',
        'leads': 'Leads Management',
        'bookings': 'Bookings',
        'guests': 'Guests Database',
        'campaigns': 'Email Campaigns',
        'reviews': 'Review Management',
        'website': 'Website Editor'
    };
    document.getElementById('pageTitle').textContent = titles[pageId] || 'Dashboard';
    
    // Load page data
    loadPageData(pageId);
    
    // Check for URL parameters to pre-select section
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (pageId === 'website' && section) {
        // Wait for page to load, then select the section
        setTimeout(() => {
            const pageSelect = document.getElementById('websitePageSelect');
            const sectionSelect = document.getElementById('websiteSectionSelect');
            if (pageSelect && sectionSelect) {
                // Find which page this section belongs to
                for (const [page, sections] of Object.entries(pageSections)) {
                    const found = sections.find(s => s.value === section);
                    if (found) {
                        pageSelect.value = page;
                        pageSelect.dispatchEvent(new Event('change'));
                        setTimeout(() => {
                            sectionSelect.value = section;
                            sectionSelect.dispatchEvent(new Event('change'));
                        }, 100);
                        break;
                    }
                }
            }
        }, 300);
    }
}

// Load dashboard data
async function loadDashboardData() {
    loadStats();
    loadRecentLeads();
    loadUpcomingCheckins();
}

// Load stats
async function loadStats() {
    try {
        // Load leads
        const leadsResponse = await fetch(`${API_BASE_URL}/leads?per_page=100`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        if (leadsResponse.ok) {
            const leadsData = await leadsResponse.json();
            const today = new Date().toISOString().split('T')[0];
            const todayLeads = leadsData.data.filter(lead => 
                lead.created_at && lead.created_at.startsWith(today)
            );
            document.getElementById('statLeadsToday').textContent = todayLeads.length;
            document.getElementById('leadsBadge').textContent = leadsData.data.filter(l => l.status === 'new').length;
        }
        
        // Load bookings
        const bookingsResponse = await fetch(`${API_BASE_URL}/bookings?per_page=100`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        if (bookingsResponse.ok) {
            const bookingsData = await bookingsResponse.json();
            const today = new Date().toISOString().split('T')[0];
            const todayBookings = bookingsData.data.filter(booking => 
                booking.check_in === today
            );
            document.getElementById('statBookingsToday').textContent = todayBookings.length;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load recent leads
async function loadRecentLeads() {
    try {
        const response = await fetch(`${API_BASE_URL}/leads?per_page=5&page=1`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const container = document.getElementById('recentLeads');
            
            if (data.data && data.data.length > 0) {
                container.innerHTML = data.data.map(lead => `
                    <div class="list-item">
                        <strong>${lead.first_name} ${lead.last_name}</strong>
                        <span class="status-badge status-${lead.status}">${lead.status}</span>
                        <small>${new Date(lead.created_at).toLocaleDateString()}</small>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="empty-state">No leads yet</p>';
            }
        }
    } catch (error) {
        console.error('Error loading leads:', error);
    }
}

// Load upcoming check-ins
async function loadUpcomingCheckins() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        const nextWeekStr = nextWeek.toISOString().split('T')[0];
        
        const response = await fetch(`${API_BASE_URL}/bookings?date_from=${today}&date_to=${nextWeekStr}&status=confirmed`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const container = document.getElementById('upcomingCheckins');
            
            if (data.data && data.data.length > 0) {
                container.innerHTML = data.data.map(booking => `
                    <div class="list-item">
                        <strong>${booking.guest_first_name} ${booking.guest_last_name}</strong>
                        <small>${booking.unit_number} - ${booking.check_in}</small>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="empty-state">No upcoming check-ins</p>';
            }
        }
    } catch (error) {
        console.error('Error loading check-ins:', error);
    }
}

// Load page data
function loadPageData(pageId) {
    switch(pageId) {
        case 'leads':
            loadLeads();
            break;
        case 'bookings':
            // Ensure list view is visible and load bookings
            setTimeout(() => {
                const bookingsList = document.getElementById('bookingsList');
                const bookingsCalendar = document.getElementById('bookingsCalendar');
                if (bookingsList) bookingsList.style.display = 'block';
                if (bookingsCalendar) bookingsCalendar.style.display = 'none';
                // Update toggle buttons
                document.querySelectorAll('.btn-toggle').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('data-view') === 'list') {
                        btn.classList.add('active');
                    }
                });
                loadBookings();
                // Preload calendar data for faster switching
                loadBookingsCalendar();
            }, 50);
            break;
        case 'guests':
            loadGuests();
            break;
        case 'campaigns':
            loadCampaigns();
            break;
        case 'reviews':
            loadReviews();
            break;
        case 'website':
            loadWebsite();
            break;
    }
}

// Load leads
async function loadLeads() {
    try {
        const tbody = document.getElementById('leadsTableBody');
        if (!tbody) {
            console.error('Leads table body not found');
            return;
        }
        
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading leads...</td></tr>';
        
        const sourceFilter = document.getElementById('filterSource')?.value || '';
        const statusFilter = document.getElementById('filterStatus')?.value || '';
        
        let url = `${API_BASE_URL}/leads?per_page=100`; // Get more to filter on frontend
        if (sourceFilter) url += `&source_id=${sourceFilter}`;
        // Don't send status filter if it's "all" - we'll filter on frontend
        if (statusFilter && statusFilter !== 'all') {
            url += `&status=${statusFilter}`;
        }
        
        const response = await fetch(url, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Filter leads based on status filter
            let leads = data.data || [];
            
            // By default (empty filter), exclude converted and lost leads
            if (!statusFilter || statusFilter === '') {
                leads = leads.filter(lead => lead.status !== 'converted' && lead.status !== 'lost');
            } else if (statusFilter !== 'all') {
                // Filter by specific status
                leads = leads.filter(lead => lead.status === statusFilter);
            }
            // If statusFilter is 'all', show all leads including converted and lost
            
            if (leads.length > 0) {
                tbody.innerHTML = leads.map(lead => `
                    <tr>
                        <td><strong>${lead.first_name || ''} ${lead.last_name || ''}</strong></td>
                        <td>
                            ${lead.email ? `<div><i class="fas fa-envelope"></i> ${lead.email}</div>` : ''}
                            ${lead.phone ? `<div><i class="fas fa-phone"></i> ${lead.phone}</div>` : ''}
                            ${!lead.email && !lead.phone ? '<div style="color: #999;">No contact info</div>' : ''}
                        </td>
                        <td>
                            <span style="color: ${lead.source_color || '#007bff'}">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i> ${lead.source_name || 'Unknown'}
                            </span>
                            ${lead.form_type ? `<div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">${lead.form_type === 'booking' ? '📅 Booking Enquiry' : '📧 Contact'}</div>` : ''}
                        </td>
                        <td><span class="status-badge status-${lead.status || 'new'}">${lead.status || 'new'}</span></td>
                        <td>${lead.created_at ? new Date(lead.created_at).toLocaleDateString() : 'N/A'}</td>
                        <td>
                            <button class="btn-icon" onclick="viewLead(${lead.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon" onclick="editLead(${lead.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${lead.status !== 'converted' ? `
                                <button class="btn-icon btn-primary" onclick="convertToBooking(${lead.id})" title="Convert to Booking" style="background: #28a745; color: white;">
                                    <i class="fas fa-calendar-check"></i>
                                </button>
                                <button class="btn-icon" onclick="cancelLead(${lead.id})" title="Cancel Lead" style="background: #dc3545; color: white;">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : '<span class="status-badge status-converted" style="font-size: 0.75rem;">Converted</span>'}
                            ${lead.status !== 'converted' && lead.status !== 'lost' ? `
                                <button class="btn-icon" onclick="deleteLead(${lead.id})" title="Delete Lead" style="background: #6c757d; color: white;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No leads found</td></tr>';
            }
        } else {
            const errorData = await response.json().catch(() => ({}));
            console.error('Error loading leads:', response.status, errorData);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">Error loading leads: ${errorData.message || response.statusText}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading leads:', error);
        const tbody = document.getElementById('leadsTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading leads. Please check console.</td></tr>';
        }
    }
}

// Load bookings calendar
async function loadBookingsCalendar() {
    try {
        const container = document.getElementById('calendarContainer');
        if (!container) {
            console.error('Calendar container not found');
            return;
        }
        
        container.innerHTML = '<div style="text-align: center; padding: 2rem;">Loading calendar...</div>';
        
        // Get current month
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        const startStr = start.toISOString().split('T')[0];
        const endStr = end.toISOString().split('T')[0];
        
        const response = await fetch(`${API_BASE_URL}/bookings/calendar?start=${startStr}&end=${endStr}`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const events = await response.json();
            renderCalendar(now, events);
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;">Error loading calendar</div>';
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
        const container = document.getElementById('calendarContainer');
        if (container) {
            container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;">Error loading calendar. Please try again.</div>';
        }
    }
}

// Render calendar
function renderCalendar(date, events) {
    const container = document.getElementById('calendarContainer');
    if (!container) return;
    
    const year = date.getFullYear();
    const month = date.getMonth();
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    // Create events map for quick lookup
    const eventsMap = {};
    events.forEach(event => {
        const startDate = new Date(event.start);
        const endDate = new Date(event.end);
        const currentDate = new Date(startDate);
        
        while (currentDate <= endDate) {
            const dateKey = currentDate.toISOString().split('T')[0];
            if (!eventsMap[dateKey]) {
                eventsMap[dateKey] = [];
            }
            eventsMap[dateKey].push(event);
            currentDate.setDate(currentDate.getDate() + 1);
        }
    });
    
    let html = `
        <div style="background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <button id="prevMonth" style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <h2 style="margin: 0; color: #333;">${monthNames[month]} ${year}</h2>
                <button id="nextMonth" style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">
    `;
    
    // Day headers
    dayNames.forEach(day => {
        html += `<div style="padding: 0.75rem; text-align: center; font-weight: bold; background: #f8f9fa; border-radius: 4px;">${day}</div>`;
    });
    
    // Empty cells for days before month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
        html += `<div style="padding: 0.5rem; min-height: 80px; background: #f8f9fa; border-radius: 4px;"></div>`;
    }
    
    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const dateKey = currentDate.toISOString().split('T')[0];
        const isToday = dateKey === new Date().toISOString().split('T')[0];
        const dayEvents = eventsMap[dateKey] || [];
        
        html += `
            <div style="padding: 0.5rem; min-height: 80px; background: ${isToday ? '#e3f2fd' : 'white'}; border: ${isToday ? '2px solid #2196f3' : '1px solid #e0e0e0'}; border-radius: 4px; position: relative;">
                <div style="font-weight: ${isToday ? 'bold' : 'normal'}; margin-bottom: 0.25rem; color: ${isToday ? '#2196f3' : '#333'};">${day}</div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
        `;
        
        // Show up to 2 events, then "+X more"
        dayEvents.slice(0, 2).forEach(event => {
            // Use backgroundColor from API or fallback to status-based color
            const color = event.backgroundColor || event.color || '#667eea';
            const reference = event.extendedProps?.reference || '';
            const tooltip = `${event.title}${reference ? ' - ' + reference : ''}`;
            html += `
                <div onclick="viewBooking(${event.id})" style="background: ${color}; color: white; padding: 2px 4px; border-radius: 3px; font-size: 0.75rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${tooltip}">
                    ${event.title.length > 15 ? event.title.substring(0, 15) + '...' : event.title}
                </div>
            `;
        });
        
        if (dayEvents.length > 2) {
            html += `<div style="font-size: 0.7rem; color: #666; padding: 2px;">+${dayEvents.length - 2} more</div>`;
        }
        
        html += `
                </div>
            </div>
        `;
    }
    
    // Empty cells for days after month ends
    const totalCells = startingDayOfWeek + daysInMonth;
    const remainingCells = 7 - (totalCells % 7);
    if (remainingCells < 7) {
        for (let i = 0; i < remainingCells; i++) {
            html += `<div style="padding: 0.5rem; min-height: 80px; background: #f8f9fa; border-radius: 4px;"></div>`;
        }
    }
    
    html += `
            </div>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #ffc107; border-radius: 3px;"></div>
                    <span style="font-size: 0.85rem;">Pending</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #28a745; border-radius: 3px;"></div>
                    <span style="font-size: 0.85rem;">Confirmed</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #dc3545; border-radius: 3px;"></div>
                    <span style="font-size: 0.85rem;">Cancelled</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #17a2b8; border-radius: 3px;"></div>
                    <span style="font-size: 0.85rem;">Checked In</span>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners for month navigation
    document.getElementById('prevMonth')?.addEventListener('click', () => {
        const newDate = new Date(year, month - 1, 1);
        loadBookingsCalendarForMonth(newDate);
    });
    
    document.getElementById('nextMonth')?.addEventListener('click', () => {
        const newDate = new Date(year, month + 1, 1);
        loadBookingsCalendarForMonth(newDate);
    });
}

// Load calendar for specific month
async function loadBookingsCalendarForMonth(date) {
    try {
        const container = document.getElementById('calendarContainer');
        if (!container) return;
        
        const year = date.getFullYear();
        const month = date.getMonth();
        const start = new Date(year, month, 1);
        const end = new Date(year, month + 1, 0);
        
        const startStr = start.toISOString().split('T')[0];
        const endStr = end.toISOString().split('T')[0];
        
        const response = await fetch(`${API_BASE_URL}/bookings/calendar?start=${startStr}&end=${endStr}`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const events = await response.json();
            renderCalendar(date, events);
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
    }
}

// Load bookings
async function loadBookings() {
    try {
        const tbody = document.getElementById('bookingsTableBody');
        if (!tbody) {
            console.error('Bookings table body not found');
            return;
        }
        
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading bookings...</td></tr>';
        
        const response = await fetch(`${API_BASE_URL}/bookings?per_page=50`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(booking => `
                    <tr>
                        <td><strong>${booking.booking_reference || 'N/A'}</strong></td>
                        <td>${(booking.guest_first_name || '')} ${(booking.guest_last_name || '')}</td>
                        <td>${booking.unit_number || booking.unit_id || 'N/A'}</td>
                        <td>${booking.check_in ? new Date(booking.check_in).toLocaleDateString() : 'N/A'}</td>
                        <td>${booking.check_out ? new Date(booking.check_out).toLocaleDateString() : 'N/A'}</td>
                        <td><span class="status-badge status-${booking.status || 'pending'}">${booking.status || 'pending'}</span></td>
                        <td>
                            <button class="btn-icon" onclick="viewBooking(${booking.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No bookings found</td></tr>';
            }
        } else {
            const errorData = await response.json().catch(() => ({}));
            console.error('Error loading bookings:', response.status, errorData);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">Error loading bookings: ${errorData.message || response.statusText}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
        const tbody = document.getElementById('bookingsTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading bookings. Please check console.</td></tr>';
        }
    }
}

// Load guests
async function loadGuests() {
    try {
        const search = document.getElementById('guestSearch').value;
        let url = `${API_BASE_URL}/guests?per_page=50`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        
        const response = await fetch(url, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const tbody = document.getElementById('guestsTableBody');
            
            if (data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(guest => `
                    <tr>
                        <td><strong>${guest.first_name} ${guest.last_name}</strong></td>
                        <td>${guest.email || '-'}</td>
                        <td>${guest.phone || '-'}</td>
                        <td>${guest.total_nights || 0} nights</td>
                        <td>${guest.total_nights || 0}</td>
                        <td>${guest.last_visit || 'Never'}</td>
                        <td>
                            <button class="btn-icon" onclick="viewGuest(${guest.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No guests found</td></tr>';
            }
        }
    } catch (error) {
        console.error('Error loading guests:', error);
    }
}

// Load campaigns
async function loadCampaigns() {
    try {
        const response = await fetch(`${API_BASE_URL}/campaigns?per_page=20`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const container = document.getElementById('campaignsGrid');
            
            if (data.data && data.data.length > 0) {
                container.innerHTML = data.data.map(campaign => `
                    <div class="dashboard-card">
                        <h3>${campaign.name}</h3>
                        <p><strong>Status:</strong> <span class="status-badge status-${campaign.status}">${campaign.status}</span></p>
                        <p><strong>Type:</strong> ${campaign.type}</p>
                        <p><strong>Recipients:</strong> ${campaign.total_recipients || 0}</p>
                        <p><strong>Sent:</strong> ${campaign.total_sent || 0}</p>
                        <p><strong>Opened:</strong> ${campaign.total_opened || 0}</p>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="empty-state">No campaigns yet. Create your first campaign!</p>';
            }
        }
    } catch (error) {
        console.error('Error loading campaigns:', error);
    }
}

// Load reviews
async function loadReviews() {
    try {
        const response = await fetch(`${API_BASE_URL}/reviews?per_page=20`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const tbody = document.getElementById('reviewsTableBody');
            
            if (data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(review => `
                    <tr>
                        <td>${review.first_name} ${review.last_name}</td>
                        <td>${review.platform}</td>
                        <td>
                            ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                            <strong>${review.rating}/5</strong>
                        </td>
                        <td>${review.review_text.substring(0, 100)}...</td>
                        <td>${new Date(review.reviewed_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-icon" title="View Full Review">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No reviews yet</td></tr>';
            }
        }
        
        // Load analytics
        const analyticsResponse = await fetch(`${API_BASE_URL}/reviews/analytics`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (analyticsResponse.ok) {
            const analytics = await analyticsResponse.json();
            document.getElementById('totalReviews').textContent = analytics.total_reviews || 0;
            document.getElementById('avgRating').textContent = analytics.average_rating || '0.0';
            document.getElementById('requestsSent').textContent = analytics.total_requests || 0;
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

// Modal functions
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalOverlay').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

// Placeholder functions
function viewLead(id) {
    openModal('Lead Details', `<p>Loading lead #${id}...</p>`);
}

function editLead(id) {
    openModal('Edit Lead', `<p>Edit lead #${id}...</p>`);
}

// Cancel lead (mark as lost)
async function cancelLead(leadId) {
    const reason = prompt('Please provide a reason for cancelling this lead (optional):');
    if (reason === null) return; // User cancelled
    
    if (!confirm(`Are you sure you want to cancel this lead? This will mark it as "lost".`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/leads/${leadId}/cancel`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason: reason || 'No reason provided' })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert('✓ Lead cancelled successfully');
            loadLeads(); // Refresh leads list
        } else {
            alert('Error cancelling lead: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error cancelling lead:', error);
        alert('Error cancelling lead. Please try again.');
    }
}

// Delete lead (permanent removal)
async function deleteLead(leadId) {
    if (!confirm('⚠️ WARNING: This will permanently delete this lead. This action cannot be undone.\n\nAre you sure you want to delete this lead?')) {
        return;
    }
    
    // Double confirmation
    if (!confirm('Are you absolutely sure? This lead will be permanently removed from the system.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/leads/${leadId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert('✓ Lead deleted successfully');
            loadLeads(); // Refresh leads list
        } else {
            alert('Error deleting lead: ' + (result.message || result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting lead:', error);
        alert('Error deleting lead. Please try again.');
    }
}

// Convert lead to booking
async function convertToBooking(leadId) {
    try {
        // Fetch lead details
        const response = await fetch(`${API_BASE_URL}/leads/${leadId}`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (!response.ok) {
            alert('Error loading lead details');
            return;
        }
        
        const lead = await response.json();
        
        // Extract booking data from notes (stored as JSON) or message
        let checkIn = '';
        let checkOut = '';
        let unitType = '';
        let guests = '2';
        
        // First, try to get from notes (JSON)
        if (lead.notes) {
            try {
                const bookingData = JSON.parse(lead.notes);
                checkIn = bookingData.check_in || '';
                checkOut = bookingData.check_out || '';
                unitType = bookingData.unit_type || '';
                guests = bookingData.number_of_guests || bookingData.guests || '2';
            } catch (e) {
                // If notes is not JSON, continue to message parsing
            }
        }
        
        // If not found in notes, try to extract from message
        if (!checkIn || !checkOut) {
            const datePattern = /(\d{4}-\d{2}-\d{2})/g;
            const dates = lead.message?.match(datePattern) || [];
            if (dates.length >= 2) {
                checkIn = checkIn || dates[0];
                checkOut = checkOut || dates[1];
            }
        }
        
        // Extract unit type from message if not found
        if (!unitType && lead.message) {
            const unitMatch = lead.message.match(/unit[_\s]type[:\s]+(\w+)/i) || 
                             lead.message.match(/(\d+)[\s-]?bedroom/i);
            if (unitMatch) {
                const bedNum = unitMatch[1];
                if (bedNum.includes('2') || bedNum === '2') unitType = '2_bedroom';
                else if (bedNum.includes('3') || bedNum === '3') unitType = '3_bedroom';
                else if (bedNum.includes('5') || bedNum === '5') unitType = '5_bedroom';
            }
        }
        
        // Extract guests from message if not found
        if (guests === '2' && lead.message) {
            const guestMatch = lead.message.match(/guest[s]?[:\s]+(\d+)/i) || 
                              lead.message.match(/(\d+)[\s-]?guest/i);
            if (guestMatch) {
                guests = guestMatch[1];
            }
        }
        
        // Convert guests range (e.g., "3-4") to a number (take the max)
        if (guests.includes('-')) {
            const parts = guests.split('-');
            guests = parts[parts.length - 1].trim();
        }
        
        // Create booking form HTML
        const formHtml = `
            <div style="max-width: 600px;">
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #333;">Lead Information</h4>
                    <p style="margin: 0.25rem 0; color: #666;"><strong>Name:</strong> ${lead.first_name} ${lead.last_name}</p>
                    <p style="margin: 0.25rem 0; color: #666;"><strong>Email:</strong> ${lead.email || 'N/A'}</p>
                    <p style="margin: 0.25rem 0; color: #666;"><strong>Phone:</strong> ${lead.phone || 'N/A'}</p>
                    ${lead.message ? `<p style="margin: 0.5rem 0 0 0; color: #666;"><strong>Message:</strong> ${lead.message.substring(0, 100)}${lead.message.length > 100 ? '...' : ''}</p>` : ''}
                </div>
                
                <form id="convertBookingForm" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Check-in Date *</label>
                            <input type="date" name="check_in" value="${checkIn}" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Check-out Date *</label>
                            <input type="date" name="check_out" value="${checkOut}" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Unit Type *</label>
                            <select name="unit_type" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="2_bedroom" ${unitType === '2_bedroom' || unitType === '2' ? 'selected' : ''}>2 Bedroom</option>
                                <option value="3_bedroom" ${unitType === '3_bedroom' || unitType === '3' ? 'selected' : ''}>3 Bedroom</option>
                                <option value="5_bedroom" ${unitType === '5_bedroom' || unitType === '5' ? 'selected' : ''}>5 Bedroom</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Number of Guests *</label>
                            <input type="number" name="number_of_guests" value="${guests}" min="1" max="20" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Unit Number</label>
                        <input type="number" name="unit_id" value="1" min="1" placeholder="Enter unit number (e.g., 1, 2, 3...)" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 0.85rem;">Leave blank or enter 1 if unsure. You can update this later.</small>
                    </div>
                    
                    <div id="pricingInfo" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; margin-top: 0.5rem; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #2e7d32;">Calculated Price:</strong>
                            <span id="calculatedPrice" style="font-size: 1.25rem; font-weight: bold; color: #1b5e20;">R0.00</span>
                        </div>
                        <div id="priceBreakdown" style="font-size: 0.85rem; color: #555; margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Total Amount (R) *</label>
                            <input type="number" name="total_amount" id="totalAmountInput" value="0" min="0" step="0.01" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: #666; font-size: 0.85rem;">Click "Calculate Price" or it will auto-calculate when dates/unit type change</small>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Status</label>
                            <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="pending" selected>Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="button" id="calculatePriceBtn" style="padding: 0.75rem; background: #1976d2; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; margin-top: 0.5rem;">
                        <i class="fas fa-calculator"></i> Calculate Price
                    </button>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Special Requests / Notes</label>
                        <textarea name="special_requests" rows="3" placeholder="Any special requests or notes..." style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; resize: vertical;">${lead.message || ''}</textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-calendar-check"></i> Create Booking
                        </button>
                        <button type="button" onclick="closeModal()" class="btn" style="flex: 1; padding: 0.75rem; background: #6c757d; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        openModal('Convert Lead to Booking', formHtml);
        
        // Function to calculate price
        async function calculatePrice() {
            const checkIn = document.querySelector('input[name="check_in"]').value;
            const checkOut = document.querySelector('input[name="check_out"]').value;
            const unitType = document.querySelector('select[name="unit_type"]').value;
            const pricingInfo = document.getElementById('pricingInfo');
            const calculatedPrice = document.getElementById('calculatedPrice');
            const priceBreakdown = document.getElementById('priceBreakdown');
            const totalAmountInput = document.getElementById('totalAmountInput');
            const calculateBtn = document.getElementById('calculatePriceBtn');
            
            if (!checkIn || !checkOut || !unitType) {
                pricingInfo.style.display = 'none';
                return;
            }
            
            // Validate dates
            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            if (checkOutDate <= checkInDate) {
                pricingInfo.style.display = 'none';
                return;
            }
            
            calculateBtn.disabled = true;
            calculateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculating...';
            
            try {
                const response = await fetch(`${API_BASE_URL}/bookings/calculate-price?check_in=${checkIn}&check_out=${checkOut}&unit_type=${unitType}`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const total = parseFloat(data.total_amount);
                    const nights = data.nights;
                    
                    // Update display
                    calculatedPrice.textContent = `R${total.toLocaleString('en-ZA', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    totalAmountInput.value = total;
                    
                    // Show breakdown
                    if (data.breakdown && data.breakdown.length > 0) {
                        const breakdownText = `${nights} night${nights > 1 ? 's' : ''} × Average R${(total / nights).toFixed(2)}/night`;
                        priceBreakdown.innerHTML = breakdownText;
                    }
                    
                    pricingInfo.style.display = 'block';
                } else {
                    pricingInfo.style.display = 'none';
                }
            } catch (error) {
                console.error('Error calculating price:', error);
                pricingInfo.style.display = 'none';
            } finally {
                calculateBtn.disabled = false;
                calculateBtn.innerHTML = '<i class="fas fa-calculator"></i> Calculate Price';
            }
        }
        
        // Auto-calculate when dates or unit type change
        setTimeout(() => {
            const checkInInput = document.querySelector('input[name="check_in"]');
            const checkOutInput = document.querySelector('input[name="check_out"]');
            const unitTypeSelect = document.querySelector('select[name="unit_type"]');
            
            if (checkInInput && checkOutInput && unitTypeSelect) {
                checkInInput.addEventListener('change', calculatePrice);
                checkOutInput.addEventListener('change', calculatePrice);
                unitTypeSelect.addEventListener('change', calculatePrice);
                
                // Calculate immediately if all fields are filled
                if (checkInInput.value && checkOutInput.value && unitTypeSelect.value) {
                    calculatePrice();
                }
            }
            
            // Calculate button click
            const calculateBtn = document.getElementById('calculatePriceBtn');
            if (calculateBtn) {
                calculateBtn.addEventListener('click', calculatePrice);
            }
        }, 100);
        
        // Handle form submission
        document.getElementById('convertBookingForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                check_in: formData.get('check_in'),
                check_out: formData.get('check_out'),
                unit_type: formData.get('unit_type'),
                number_of_guests: parseInt(formData.get('number_of_guests')),
                unit_id: formData.get('unit_id') ? parseInt(formData.get('unit_id')) : 1,
                total_amount: parseFloat(formData.get('total_amount')) || 0,
                status: formData.get('status'),
                special_requests: formData.get('special_requests')
            };
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            try {
                const convertResponse = await fetch(`${API_BASE_URL}/leads/${leadId}/convert`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                let result;
                try {
                    result = await convertResponse.json();
                } catch (jsonError) {
                    // If response is not JSON, it's likely an HTML error page
                    const textResponse = await convertResponse.text();
                    console.error('Non-JSON response:', textResponse);
                    throw new Error('Server returned an error. Please check the server logs.');
                }
                
                if (convertResponse.ok) {
                    closeModal();
                    
                    // Show success message with email status
                    let message = `✓ Booking created successfully!\n\nBooking Reference: ${result.booking_reference}\n\nThe lead has been converted and removed from the leads list.\n\n`;
                    if (result.email_sent) {
                        message += `✓ Confirmation email sent successfully!`;
                        if (result.email_details) {
                            message += `\n${result.email_details}`;
                        }
                        message += `\n\n`;
                    } else {
                        message += `⚠ Confirmation email could not be sent.\n`;
                        if (result.email_error) {
                            message += `Error: ${result.email_error}\n`;
                        } else {
                            message += `Please check email configuration and server logs.\n`;
                        }
                        message += `\nNote: The booking was created successfully, but the email failed. Check your server's error log for details.\n\n`;
                    }
                    message += `Click OK to view the booking, or Cancel to stay here.`;
                    
                    const viewBooking = confirm(message);
                    
                    // Refresh leads list (converted lead will be removed)
                    loadLeads();
                    
                    // Refresh bookings list
                    loadBookings();
                    
                    // If user wants to view booking, navigate to bookings page
                    if (viewBooking) {
                    // Switch to bookings page
                    showPage('bookings');
                    
                    // Switch to list view and load bookings
                    setTimeout(() => {
                        const listBtn = document.querySelector('.btn-toggle[data-view="list"]');
                        if (listBtn) {
                            listBtn.click(); // This will trigger the view toggle and load bookings
                        } else {
                            // If toggle button not found, just load bookings directly
                            loadBookings();
                            const bookingsList = document.getElementById('bookingsList');
                            const bookingsCalendar = document.getElementById('bookingsCalendar');
                            if (bookingsList) bookingsList.style.display = 'block';
                            if (bookingsCalendar) bookingsCalendar.style.display = 'none';
                        }
                    }, 100);
                    }
                } else {
                    const errorMsg = result.message || result.error || 'Unknown error';
                    console.error('Booking creation error:', result);
                    alert('Error creating booking: ' + errorMsg);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error converting lead:', error);
                alert('Error creating booking: ' + (error.message || 'Please check the console for details.'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
    } catch (error) {
        console.error('Error loading lead:', error);
        alert('Error loading lead details. Please try again.');
    }
}

function viewBooking(id) {
    openModal('Booking Details', `<p>Loading booking #${id}...</p>`);
}

function viewGuest(id) {
    openModal('Guest Profile', `<p>Loading guest #${id}...</p>`);
}

// Filter event listeners
document.getElementById('filterSource')?.addEventListener('change', loadLeads);
document.getElementById('filterStatus')?.addEventListener('change', loadLeads);
document.getElementById('guestSearch')?.addEventListener('input', debounce(loadGuests, 300));

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Website Editor Functions
let currentWebsitePage = 'homepage';
let currentWebsiteSection = '';
let currentWebsiteContent = null;
let currentContentId = null;

// Define available sections for each page with location info
const pageSections = {
    'homepage': [
        { value: 'hero_title', label: 'Hero - Title', description: 'Main heading in the hero section', location: 'Top of homepage - Large banner area', previewClass: 'hero-preview' },
        { value: 'hero_subtitle', label: 'Hero - Subtitle', description: 'Subtitle text in the hero section', location: 'Top of homepage - Below main title', previewClass: 'hero-preview' },
        { value: 'hero_description', label: 'Hero - Description', description: 'Description text in the hero section', location: 'Top of homepage - Below subtitle', previewClass: 'hero-preview' },
        { value: 'hero_image', label: 'Hero - Background Image', description: 'Hero section background image', location: 'Top of homepage - Background', previewClass: 'media-image', contentType: 'media' },
        { value: 'hero_video', label: 'Hero - Video (Optional)', description: 'Hero section background video URL', location: 'Top of homepage - Background video', previewClass: 'media-video', contentType: 'media' },
        { value: 'logo', label: 'Logo', description: 'Website logo image', location: 'Top navigation - Logo area', previewClass: 'media-image', contentType: 'media' },
        { value: 'accommodation_intro', label: 'Accommodation - Introduction', description: 'Introduction text for accommodation section', location: 'Homepage - Accommodation section header', previewClass: 'section-intro' },
        { value: 'accommodation_2bed', label: 'Accommodation - 2 Bedroom', description: 'Content for 2 bedroom units', location: 'Homepage - First accommodation card', previewClass: 'accommodation-card' },
        { value: 'accommodation_2bed_image', label: 'Accommodation - 2 Bedroom Image', description: 'Image for 2 bedroom units', location: 'Homepage - First accommodation card image', previewClass: 'media-image', contentType: 'media' },
        { value: 'accommodation_3bed', label: 'Accommodation - 3 Bedroom', description: 'Content for 3 bedroom units', location: 'Homepage - Second accommodation card', previewClass: 'accommodation-card' },
        { value: 'accommodation_3bed_image', label: 'Accommodation - 3 Bedroom Image', description: 'Image for 3 bedroom units', location: 'Homepage - Second accommodation card image', previewClass: 'media-image', contentType: 'media' },
        { value: 'accommodation_5bed', label: 'Accommodation - 5 Bedroom', description: 'Content for 5 bedroom units', location: 'Homepage - Third accommodation card', previewClass: 'accommodation-card' },
        { value: 'accommodation_5bed_image', label: 'Accommodation - 5 Bedroom Image', description: 'Image for 5 bedroom units', location: 'Homepage - Third accommodation card image', previewClass: 'media-image', contentType: 'media' },
        { value: 'activities_intro', label: 'Activities - Introduction', description: 'Introduction text for activities section', location: 'Homepage - Activities section header', previewClass: 'section-intro' },
        { value: 'gallery_images', label: 'Gallery Images', description: 'Photo gallery images', location: 'Homepage - Gallery section', previewClass: 'media-gallery', contentType: 'media' },
        { value: 'testimonials_intro', label: 'Testimonials - Introduction', description: 'Introduction text for testimonials section', location: 'Homepage - Testimonials section header', previewClass: 'section-intro' },
        { value: 'about', label: 'About Section', description: 'About us content', location: 'Homepage - About section', previewClass: 'section-content' },
        { value: 'faq', label: 'FAQ Section', description: 'Frequently asked questions', location: 'Homepage - FAQ section', previewClass: 'section-content' },
        { value: 'contact_phone', label: 'Contact - Phone Number', description: 'Phone number for contact section', location: 'Homepage - Contact section, Phone card', previewClass: 'contact-field', order: 1 },
        { value: 'contact_email', label: 'Contact - Email Address', description: 'Email address for contact section', location: 'Homepage - Contact section, Email card', previewClass: 'contact-field', order: 2 },
        { value: 'contact_whatsapp', label: 'Contact - WhatsApp Number', description: 'WhatsApp number for contact section', location: 'Homepage - Contact section, WhatsApp card', previewClass: 'contact-field', order: 3 },
        { value: 'contact_address', label: 'Contact - Address/Location', description: 'Physical address or location text', location: 'Homepage - Contact section, Address info', previewClass: 'contact-field', order: 4 },
        { value: 'contact_extra', label: 'Contact - Additional Info', description: 'Additional contact information or notes', location: 'Homepage - Contact section, Below contact cards', previewClass: 'section-content', order: 5 }
    ],
    'rates': [
        { value: 'intro', label: '1. Page Header Subtitle', description: 'The subtitle text below "Rates & Pricing" title', location: 'Rates page - Top header area (below main title)', previewClass: 'page-intro', order: 1 },
        { value: 'important_info', label: '2. Important Information Box', description: 'The gray box with checkmarks (rates per unit, minimum stay, VAT, etc.)', location: 'Rates page - Above pricing cards', previewClass: 'section-content', order: 2 },
        { value: 'rate_2bed_low', label: '3. 2 Bedroom - Low Season Price', description: 'Price for 2 bedroom units during low season', location: 'Rates page - 2 Bedroom card, Low Season row', previewClass: 'rate-price', order: 3 },
        { value: 'rate_2bed_mid', label: '4. 2 Bedroom - Mid Season Price', description: 'Price for 2 bedroom units during mid season', location: 'Rates page - 2 Bedroom card, Mid Season row', previewClass: 'rate-price', order: 4 },
        { value: 'rate_2bed_peak', label: '5. 2 Bedroom - Peak Season Price', description: 'Price for 2 bedroom units during peak season', location: 'Rates page - 2 Bedroom card, Peak Season row', previewClass: 'rate-price', order: 5 },
        { value: 'rate_3bed_low', label: '6. 3 Bedroom - Low Season Price', description: 'Price for 3 bedroom units during low season', location: 'Rates page - 3 Bedroom card, Low Season row', previewClass: 'rate-price', order: 6 },
        { value: 'rate_3bed_mid', label: '7. 3 Bedroom - Mid Season Price', description: 'Price for 3 bedroom units during mid season', location: 'Rates page - 3 Bedroom card, Mid Season row', previewClass: 'rate-price', order: 7 },
        { value: 'rate_3bed_peak', label: '8. 3 Bedroom - Peak Season Price', description: 'Price for 3 bedroom units during peak season', location: 'Rates page - 3 Bedroom card, Peak Season row', previewClass: 'rate-price', order: 8 },
        { value: 'rate_5bed_low', label: '9. 5 Bedroom - Low Season Price', description: 'Price for 5 bedroom units during low season', location: 'Rates page - 5 Bedroom card, Low Season row', previewClass: 'rate-price', order: 9 },
        { value: 'rate_5bed_mid', label: '10. 5 Bedroom - Mid Season Price', description: 'Price for 5 bedroom units during mid season', location: 'Rates page - 5 Bedroom card, Mid Season row', previewClass: 'rate-price', order: 10 },
        { value: 'rate_5bed_peak', label: '11. 5 Bedroom - Peak Season Price', description: 'Price for 5 bedroom units during peak season', location: 'Rates page - 5 Bedroom card, Peak Season row', previewClass: 'rate-price', order: 11 },
        { value: 'season_dates', label: '12. Season Dates Section', description: 'The section showing Low/Mid/Peak season date ranges', location: 'Rates page - Below pricing cards', previewClass: 'section-content', order: 12 },
        { value: 'terms', label: '13. Terms & Conditions', description: 'Terms and conditions for rates', location: 'Rates page - Terms section', previewClass: 'section-content', order: 13 }
    ],
    'specials': [
        { value: 'intro', label: 'Page Header Subtitle', description: 'Subtitle below "Special Offers & Packages" title', location: 'Specials page - Top header area (below main title)', previewClass: 'page-intro', order: 1 },
        // Offer 1 - Early Bird Special (broken down)
        { value: 'offer_1_title', label: 'Offer 1 - Title', description: 'Title for first offer card', location: 'Specials page - First card header', previewClass: 'offer-field', order: 2 },
        { value: 'offer_1_subtitle', label: 'Offer 1 - Subtitle', description: 'Subtitle for first offer card', location: 'Specials page - First card header', previewClass: 'offer-field', order: 3 },
        { value: 'offer_1_discount', label: 'Offer 1 - Discount', description: 'Discount percentage for first offer', location: 'Specials page - First card discount', previewClass: 'offer-field', order: 4 },
        { value: 'offer_1_features', label: 'Offer 1 - Features', description: 'Feature list for first offer (one per line)', location: 'Specials page - First card features', previewClass: 'offer-field', order: 5 },
        // Offer 2 - Long Stay Discount (broken down)
        { value: 'offer_2_title', label: 'Offer 2 - Title', description: 'Title for second offer card', location: 'Specials page - Second card header', previewClass: 'offer-field', order: 6 },
        { value: 'offer_2_subtitle', label: 'Offer 2 - Subtitle', description: 'Subtitle for second offer card', location: 'Specials page - Second card header', previewClass: 'offer-field', order: 7 },
        { value: 'offer_2_discount', label: 'Offer 2 - Discount', description: 'Discount percentage for second offer', location: 'Specials page - Second card discount', previewClass: 'offer-field', order: 8 },
        { value: 'offer_2_features', label: 'Offer 2 - Features', description: 'Feature list for second offer (one per line)', location: 'Specials page - Second card features', previewClass: 'offer-field', order: 9 },
        // Offer 3 - Last Minute Deal (broken down)
        { value: 'offer_3_title', label: 'Offer 3 - Title', description: 'Title for third offer card', location: 'Specials page - Third card header', previewClass: 'offer-field', order: 10 },
        { value: 'offer_3_subtitle', label: 'Offer 3 - Subtitle', description: 'Subtitle for third offer card', location: 'Specials page - Third card header', previewClass: 'offer-field', order: 11 },
        { value: 'offer_3_discount', label: 'Offer 3 - Discount', description: 'Discount percentage for third offer', location: 'Specials page - Third card discount', previewClass: 'offer-field', order: 12 },
        { value: 'offer_3_features', label: 'Offer 3 - Features', description: 'Feature list for third offer (one per line)', location: 'Specials page - Third card features', previewClass: 'offer-field', order: 13 },
        { value: 'terms', label: 'Terms & Conditions', description: 'Terms and conditions for special offers', location: 'Specials page - Terms section below offers', previewClass: 'section-content', order: 14 }
    ]
};

// Load website editor
async function loadWebsite() {
    // Set default page
    selectWebsitePage('homepage');
    
    // Add event listener for section selector
    const sectionSelector = document.getElementById('sectionSelector');
    if (sectionSelector) {
        sectionSelector.addEventListener('change', function() {
            const section = this.value;
            if (section) {
                selectWebsiteSection(section);
            } else {
                clearEditor();
            }
        });
    }
    
    // Setup preview update on content change
    setupPreviewUpdate();
}

// Select website page
async function selectWebsitePage(page) {
    currentWebsitePage = page;
    currentWebsiteSection = '';
    
    // Update active state in sidebar
    document.querySelectorAll('.page-list a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-page') === page) {
            link.classList.add('active');
        }
    });
    
    // Update page name in toolbar
    const pageNames = {
        'homepage': 'Homepage',
        'rates': 'Rates',
        'specials': 'Specials'
    };
    const pageNameEl = document.getElementById('currentPageName');
    if (pageNameEl) {
        pageNameEl.textContent = pageNames[page] || page;
    }
    
    // Populate section selector
    populateSectionSelector(page);
    
    // Clear editor until section is selected
    clearEditor();
}

// Populate section selector based on current page
function populateSectionSelector(page) {
    const sectionSelector = document.getElementById('sectionSelector');
    if (!sectionSelector) return;
    
    const sections = pageSections[page] || [];
    // Sort by order if available
    const sortedSections = [...sections].sort((a, b) => (a.order || 999) - (b.order || 999));
    
    sectionSelector.innerHTML = '<option value="">Select Section...</option>';
    
    sortedSections.forEach(section => {
        const option = document.createElement('option');
        option.value = section.value;
        // Show order number and label
        const label = section.order ? `${section.order}. ${section.label}` : section.label;
        option.textContent = label;
        option.title = section.description || section.location || '';
        sectionSelector.appendChild(option);
    });
}

// Select website section
async function selectWebsiteSection(section) {
    currentWebsiteSection = section;
    
    // Update section info display
    const sections = pageSections[currentWebsitePage] || [];
    const sectionInfo = sections.find(s => s.value === section);
    
    const sectionInfoEl = document.getElementById('sectionInfo');
    const sectionNameEl = document.getElementById('currentSectionName');
    const sectionDescEl = document.getElementById('sectionDescription');
    const sectionOrderEl = document.getElementById('sectionOrder');
    const sectionLocationTextEl = document.getElementById('sectionLocationText');
    
    if (sectionInfoEl && sectionNameEl && sectionDescEl) {
        // Remove order number from label if present
        const label = sectionInfo ? sectionInfo.label.replace(/^\d+\.\s*/, '') : section;
        sectionNameEl.textContent = label;
        sectionDescEl.textContent = sectionInfo ? sectionInfo.description : '';
        
        // Show order number with better styling
        if (sectionOrderEl && sectionInfo && sectionInfo.order) {
            sectionOrderEl.textContent = sectionInfo.order;
            sectionOrderEl.style.display = 'inline-block';
        } else if (sectionOrderEl) {
            sectionOrderEl.style.display = 'none';
        }
        
        // Show location with better formatting
        if (sectionLocationTextEl && sectionInfo && sectionInfo.location) {
            sectionLocationTextEl.textContent = sectionInfo.location;
        } else if (sectionLocationTextEl) {
            sectionLocationTextEl.textContent = 'Location information not available';
        }
        
        // Show the section info panel with animation
        sectionInfoEl.style.display = 'block';
        sectionInfoEl.style.opacity = '0';
        sectionInfoEl.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            sectionInfoEl.style.transition = 'all 0.3s ease';
            sectionInfoEl.style.opacity = '1';
            sectionInfoEl.style.transform = 'translateY(0)';
        }, 10);
    }
    
    // Update the "Editing:" label in the editor
    const editingSectionNameEl = document.getElementById('editingSectionName');
    const editingSectionTypeEl = document.getElementById('editingSectionType');
    if (editingSectionNameEl && sectionInfo) {
        const label = sectionInfo.label.replace(/^\d+\.\s*/, '');
        editingSectionNameEl.textContent = label;
    }
    if (editingSectionTypeEl && sectionInfo) {
        let typeText = '';
        if (sectionInfo.contentType === 'media') {
            if (sectionInfo.value.includes('image')) typeText = 'Image Editor';
            else if (sectionInfo.value.includes('video')) typeText = 'Video Editor';
            else if (sectionInfo.value.includes('gallery')) typeText = 'Gallery Editor';
            else typeText = 'Media Editor';
        } else if (sectionInfo.previewClass === 'rate-price') {
            typeText = 'Price Editor';
        } else {
            typeText = 'Text Editor';
        }
        editingSectionTypeEl.textContent = typeText;
    }
    
    // Update location info (for the separate location box)
    updateSectionLocation(sectionInfo);
    
    // Show/hide appropriate editor based on content type
    if (sectionInfo && sectionInfo.contentType === 'media') {
        showMediaEditor(sectionInfo);
    } else {
        showTextEditor();
        // Setup rate price editor if needed
        if (sectionInfo && sectionInfo.previewClass === 'rate-price') {
            setupRatePriceEditor();
        }
        // Setup offer field editor if needed
        if (sectionInfo && sectionInfo.previewClass === 'offer-field') {
            const fieldType = sectionInfo.value.split('_').pop();
            const contentEditor = document.getElementById('contentEditor');
            if (contentEditor) {
                if (fieldType === 'features') {
                    // Features need multiple lines
                    contentEditor.rows = 8;
                    contentEditor.placeholder = 'Enter one feature per line:\nValid for all unit types\nMinimum 3 nights stay\nBook 60+ days before arrival';
                } else {
                    // Title, subtitle, discount are single line
                    contentEditor.rows = 3;
                    if (fieldType === 'title') {
                        contentEditor.placeholder = 'Enter the offer title (e.g., Early Bird Special)';
                    } else if (fieldType === 'subtitle') {
                        contentEditor.placeholder = 'Enter the offer subtitle (e.g., Book 60+ days in advance)';
                    } else if (fieldType === 'discount') {
                        contentEditor.placeholder = 'Enter the discount (e.g., 15% OFF or R200 OFF)';
                    }
                }
            }
        }
    }
    
    // Show placeholder preview while loading
    showPlaceholderPreview(sectionInfo);
    
    // Load content for this page and section
    await loadWebsiteContent(currentWebsitePage, section);
}

// Show text editor
function showTextEditor() {
    const textContainer = document.getElementById('textEditorContainer');
    const mediaContainer = document.getElementById('mediaEditorContainer');
    const ratePriceEditor = document.getElementById('ratePriceEditor');
    const contentEditor = document.getElementById('contentEditor');
    
    if (textContainer) textContainer.style.display = 'block';
    if (mediaContainer) mediaContainer.style.display = 'none';
    
    // Check if this is a rate price section
    const sections = pageSections[currentWebsitePage] || [];
    const sectionInfo = sections.find(s => s.value === currentWebsiteSection);
    const isRatePrice = sectionInfo && sectionInfo.previewClass === 'rate-price';
    
    if (ratePriceEditor) {
        ratePriceEditor.style.display = isRatePrice ? 'block' : 'none';
    }
    
    if (contentEditor) {
        // Hide textarea for rate prices, but keep it in DOM for value storage
        contentEditor.style.display = isRatePrice ? 'none' : 'block';
        // Clear placeholder text when hidden
        if (isRatePrice) {
            contentEditor.placeholder = '';
        }
    }
    
    // Setup rate price input handler
    if (isRatePrice) {
        // Small delay to ensure DOM is ready
        setTimeout(() => setupRatePriceEditor(), 50);
    }
}

// Setup rate price editor
function setupRatePriceEditor() {
    const ratePriceInput = document.getElementById('ratePriceInput');
    const contentEditor = document.getElementById('contentEditor');
    
    if (!ratePriceInput || !contentEditor) return;
    
    // Remove existing event listeners by cloning the input
    const newRatePriceInput = ratePriceInput.cloneNode(true);
    ratePriceInput.parentNode.replaceChild(newRatePriceInput, ratePriceInput);
    
    // Load current value into price input from contentEditor
    let currentValue = contentEditor.value.trim();
    
    // Check if contentEditor has placeholder text or is empty
    if (!currentValue || 
        currentValue.includes('Enteryourcontenthere') || 
        currentValue.includes('Enter your content here') ||
        currentValue === 'Select a page and section from above to edit...') {
        // Load placeholder if no valid content
        const sections = pageSections[currentWebsitePage] || [];
        const sectionInfo = sections.find(s => s.value === currentWebsiteSection);
        if (sectionInfo) {
            const placeholder = getPlaceholderContent(sectionInfo);
            currentValue = placeholder;
            contentEditor.value = placeholder; // Set it in the hidden textarea
            console.log('setupRatePriceEditor - using placeholder:', placeholder);
        }
    }
    
    // Extract number from "R850" or "R1,200" format
    if (currentValue) {
        const number = currentValue.replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
        if (number) {
            newRatePriceInput.value = number;
            console.log('setupRatePriceEditor - extracted number:', number, 'from:', currentValue);
        } else {
            newRatePriceInput.value = '';
        }
    } else {
        newRatePriceInput.value = '';
    }
    
    // Update content editor when price input changes
    newRatePriceInput.addEventListener('input', function() {
        // Remove all non-numeric characters
        const number = this.value.replace(/[^0-9]/g, '');
        if (number) {
            // Format with commas for thousands
            const formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            contentEditor.value = `R${formatted}`;
            // Update preview immediately
            updatePreviewForCurrentSection();
        } else {
            contentEditor.value = '';
            // Update preview even when empty
            updatePreviewForCurrentSection();
        }
    });
    
    // Also handle paste events
    newRatePriceInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text');
        const number = pasted.replace(/[^0-9]/g, '');
        if (number) {
            this.value = number;
            const formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            contentEditor.value = `R${formatted}`;
            updatePreviewForCurrentSection();
        }
    });
    
    // Also listen to content editor for manual edits
    contentEditor.addEventListener('input', function() {
        const value = this.value.trim();
        const number = value.replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
        if (newRatePriceInput && number !== newRatePriceInput.value.replace(/[^0-9]/g, '')) {
            newRatePriceInput.value = number;
        }
    });
}

// Show media editor
function showMediaEditor(sectionInfo) {
    const textContainer = document.getElementById('textEditorContainer');
    const mediaContainer = document.getElementById('mediaEditorContainer');
    const imageEditor = document.getElementById('imageEditor');
    const videoEditor = document.getElementById('videoEditor');
    const galleryEditor = document.getElementById('galleryEditor');
    
    if (textContainer) textContainer.style.display = 'none';
    if (mediaContainer) mediaContainer.style.display = 'block';
    
    // Show appropriate media editor based on section
    if (sectionInfo) {
        if (imageEditor) imageEditor.style.display = 'none';
        if (videoEditor) videoEditor.style.display = 'none';
        if (galleryEditor) galleryEditor.style.display = 'none';
        
        if (sectionInfo.value.includes('image') || sectionInfo.value === 'logo' || sectionInfo.value === 'hero_image') {
            if (imageEditor) imageEditor.style.display = 'block';
        } else if (sectionInfo.value.includes('video')) {
            if (videoEditor) videoEditor.style.display = 'block';
        } else if (sectionInfo.value.includes('gallery')) {
            if (galleryEditor) galleryEditor.style.display = 'block';
        }
    }
}

// Update section location display
function updateSectionLocation(sectionInfo) {
    const locationEl = document.getElementById('sectionLocation');
    const locationTextEl = document.getElementById('locationText');
    
    if (locationEl && locationTextEl && sectionInfo && sectionInfo.location) {
        locationTextEl.textContent = sectionInfo.location;
        locationEl.style.display = 'block';
    } else if (locationEl) {
        locationEl.style.display = 'none';
    }
}

// Show placeholder preview
function showPlaceholderPreview(sectionInfo) {
    const previewEl = document.getElementById('sectionPreview');
    if (!previewEl) return;
    
    if (!sectionInfo) {
        previewEl.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #6c757d;">
                <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="margin: 0;">Select a section to see a preview</p>
            </div>
        `;
        return;
    }
    
    // Show preview with placeholder content
    updateSectionPreview(sectionInfo, true);
}

// Update section preview
function updateSectionPreview(sectionInfo, isPlaceholder = false, overrideContent = null) {
    const previewEl = document.getElementById('sectionPreview');
    if (!previewEl) return;
    
    // Clear the click handler flag so it can be re-added for new content
    delete previewEl.dataset.clickHandlerAdded;
    
    if (!sectionInfo) {
        previewEl.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #6c757d;">
                <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="margin: 0;">Select a section to see a preview</p>
            </div>
        `;
        return;
    }
    
    // Get current content - use override if provided (from real-time typing), otherwise from saved content or editor
    let content = '';
    
    if (overrideContent !== null) {
        // Use the override content (from real-time typing)
        content = overrideContent;
    } else {
        // Get from saved content first
        if (currentWebsiteContent && currentWebsiteContent.content) {
            content = currentWebsiteContent.content;
        } else {
            // Fall back to editor content only if it matches the current section
            if (sectionInfo.contentType === 'media') {
                if (sectionInfo.value.includes('image') || sectionInfo.value === 'logo' || sectionInfo.value === 'hero_image') {
                    const imageUrlInput = document.getElementById('imageUrlInput');
                    content = imageUrlInput && imageUrlInput.offsetParent !== null ? imageUrlInput.value : '';
                } else if (sectionInfo.value.includes('video')) {
                    const videoUrlInput = document.getElementById('videoUrlInput');
                    content = videoUrlInput && videoUrlInput.offsetParent !== null ? videoUrlInput.value : '';
                } else if (sectionInfo.value.includes('gallery')) {
                    const images = getGalleryImages();
                    content = JSON.stringify(images);
                }
            } else if (sectionInfo.previewClass === 'rate-price') {
                const ratePriceInput = document.getElementById('ratePriceInput');
                if (ratePriceInput && ratePriceInput.offsetParent !== null) {
                    const priceValue = ratePriceInput.value.trim();
                    if (priceValue) {
                        const number = priceValue.replace(/[^\d,]/g, '').replace(/,/g, '');
                        content = number ? `R${number}` : '';
                    }
                }
            } else {
                const editor = document.getElementById('contentEditor');
                content = editor && editor.offsetParent !== null ? editor.value : '';
            }
        }
    }
    
    // Use placeholder if no content and isPlaceholder is true
    const displayContent = (isPlaceholder && !content) ? getPlaceholderContent(sectionInfo) : (content || getPlaceholderContent(sectionInfo));
    const isPlaceholderMode = isPlaceholder && !content;
    
    // Create preview based on section type
    let previewHTML = '';
    
    if (sectionInfo.previewClass === 'hero-preview') {
        if (sectionInfo.value === 'hero_title') {
            previewHTML = `
                <div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4a7fb8 100%); padding: 40px 20px; border-radius: 8px; color: white; text-align: center; min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                    <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                        ${displayContent}
                    </h1>
                    ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.7; font-style: italic;">This is where your hero title will appear</p>' : ''}
                </div>
            `;
        } else if (sectionInfo.value === 'hero_subtitle') {
            previewHTML = `
                <div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4a7fb8 100%); padding: 40px 20px; border-radius: 8px; color: white; text-align: center; min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                    <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 15px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">Golden Palms Beach Resort</h1>
                    <p style="font-size: 1.5rem; margin: 0; font-weight: 600;">
                        ${displayContent}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.7; font-style: italic;">This subtitle appears below the main title</p>' : ''}
                </div>
            `;
        } else if (sectionInfo.value === 'hero_description') {
            previewHTML = `
                <div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4a7fb8 100%); padding: 40px 20px; border-radius: 8px; color: white; text-align: center; min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                    <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 15px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">Golden Palms Beach Resort</h1>
                    <p style="font-size: 1.1rem; margin: 0; line-height: 1.6; opacity: 0.95;">
                        ${displayContent}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.7; font-style: italic;">This description appears below the subtitle</p>' : ''}
                </div>
            `;
        }
    } else if (sectionInfo.previewClass === 'section-intro') {
        previewHTML = `
            <div style="text-align: center; padding: 30px 20px; background: #f8f9fa; border-radius: 8px;">
                <h2 style="color: #1a5490; margin: 0 0 15px 0; font-size: 2rem;">Section Heading</h2>
                <p style="color: #666; font-size: 1.1rem; line-height: 1.6; margin: 0; max-width: 800px; margin-left: auto; margin-right: auto;">
                    ${displayContent}
                </p>
                ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.85rem; color: #999; font-style: italic;">This introduction text appears at the top of the section</p>' : ''}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'accommodation-card') {
        previewHTML = `
            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="background: #e8f4f8; height: 150px; display: flex; align-items: center; justify-content: center; color: #666;">
                    <i class="fas fa-image" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
                <div style="padding: 20px;">
                    <h3 style="color: #1a5490; margin: 0 0 10px 0; font-size: 1.5rem;">${sectionInfo.label.replace('Accommodation - ', '')}</h3>
                    <p style="color: #666; line-height: 1.6; margin: 0;">
                        ${displayContent}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.85rem; color: #999; font-style: italic;">This content appears in the accommodation card</p>' : ''}
                </div>
            </div>
        `;
    } else if (sectionInfo.previewClass === 'media-image') {
        const imageUrl = displayContent || (isPlaceholderMode ? 'https://via.placeholder.com/400x300?text=Image' : '');
        previewHTML = `
            <div style="text-align: center; padding: 20px; background: white; border-radius: 8px;">
                ${imageUrl ? `<img src="${imageUrl}" alt="Preview" style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">` : '<div style="padding: 40px; color: #999;"><i class="fas fa-image" style="font-size: 3rem; opacity: 0.3;"></i><p>No image set</p></div>'}
                ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic;">This is how your image will appear</p>' : ''}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'media-video') {
        const videoUrl = displayContent || '';
        let embedUrl = '';
        if (videoUrl) {
            if (videoUrl.includes('youtube.com/watch') || videoUrl.includes('youtu.be/')) {
                const videoId = videoUrl.includes('youtu.be/') ? videoUrl.split('youtu.be/')[1]?.split('?')[0] : videoUrl.split('v=')[1]?.split('&')[0];
                if (videoId) embedUrl = `https://www.youtube.com/embed/${videoId}`;
            } else if (videoUrl.includes('vimeo.com/')) {
                const videoId = videoUrl.split('vimeo.com/')[1]?.split('?')[0];
                if (videoId) embedUrl = `https://player.vimeo.com/video/${videoId}`;
            } else {
                embedUrl = videoUrl; // Direct video URL
            }
        }
        previewHTML = `
            <div style="background: #000; border-radius: 8px; padding: 10px; min-height: 300px; display: flex; align-items: center; justify-content: center;">
                ${embedUrl ? `<iframe width="100%" height="315" src="${embedUrl}" frameborder="0" allowfullscreen style="border-radius: 5px;"></iframe>` : '<div style="color: #999; text-align: center;"><i class="fas fa-video" style="font-size: 3rem; opacity: 0.3;"></i><p style="margin-top: 10px;">No video set</p></div>'}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'media-gallery') {
        let images = [];
        try {
            images = displayContent ? JSON.parse(displayContent) : [];
        } catch (e) {
            images = [];
        }
        previewHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
                ${images.length > 0 ? images.map(img => `
                    <img src="${img.url}" alt="${img.alt || 'Gallery'}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 5px;">
                `).join('') : '<div style="grid-column: 1/-1; text-align: center; padding: 20px; color: #999;"><i class="fas fa-images" style="font-size: 2rem; opacity: 0.3;"></i><p>No gallery images</p></div>'}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'rate-price') {
        // Individual rate price editor - show specific price in context
        const priceValue = displayContent.trim() || 'R0';
        const parts = sectionInfo.value.split('_');
        const unitType = parts[1] ? parts[1].replace('bed', ' Bedroom') : '';
        const season = parts[2] ? parts[2].charAt(0).toUpperCase() + parts[2].slice(1) : '';
        
        previewHTML = `
            <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 350px; margin: 0 auto;">
                <h3 style="color: #1a5490; margin: 0 0 1rem 0; font-size: 1.3rem;">${unitType} Units</h3>
                <p style="color: #666; margin: 0 0 1.5rem 0; font-size: 0.9rem;">Maximum ${unitType === '2' ? '6' : unitType === '3' ? '8' : '10'} guests</p>
                <div style="border: 2px solid #d4af37; border-radius: 8px; padding: 1rem; background: #fffbf0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #666; font-weight: 600;">${season} Season</span>
                        <strong style="color: #d4af37; font-size: 1.3rem; font-weight: 700;">${priceValue}/night</strong>
                    </div>
                </div>
                <div style="margin-top: 1rem; padding: 0.75rem; background: #f8f9fa; border-radius: 5px;">
                    <small style="color: #666;">
                        <i class="fas fa-info-circle"></i> This is the price that appears in the "${season} Season" row of the ${unitType} card
                    </small>
                </div>
            </div>
        `;
    } else if (sectionInfo.previewClass === 'pricing-table') {
        // Parse content to show actual rates structure
        let ratesContent = displayContent;
        if (!ratesContent || ratesContent === 'Enter your content here...' || ratesContent === 'Pricing information will appear here...') {
            ratesContent = `2 Bedroom Units
Maximum 6 guests
Low Season: R850/night
Mid Season: R1,050/night
Peak Season: R1,350/night

3 Bedroom Units
Maximum 8 guests
Low Season: R1,200/night
Mid Season: R1,500/night
Peak Season: R1,900/night

5 Bedroom Units
Maximum 10 guests
Low Season: R1,800/night
Mid Season: R2,200/night
Peak Season: R2,800/night`;
        }
        
        // Parse the content into rate cards
        const lines = ratesContent.split('\n').filter(l => l.trim());
        const rateCards = [];
        let currentCard = null;
        
        lines.forEach(line => {
            if (line.match(/\d+\s+Bedroom/)) {
                if (currentCard) rateCards.push(currentCard);
                currentCard = { title: line.trim(), guests: '', rates: [] };
            } else if (line.toLowerCase().includes('maximum') || line.toLowerCase().includes('guests')) {
                if (currentCard) currentCard.guests = line.trim();
            } else if (line.match(/Low|Mid|Peak.*Season.*R\d+/i)) {
                if (currentCard) {
                    const match = line.match(/(Low|Mid|Peak)\s+Season[:\s]+(R[\d,]+)/i);
                    if (match) {
                        currentCard.rates.push({ season: match[1], price: match[2] });
                    }
                }
            }
        });
        if (currentCard) rateCards.push(currentCard);
        
        // If parsing failed, show as formatted text
        if (rateCards.length === 0) {
            previewHTML = `
                <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="white-space: pre-wrap; line-height: 1.8; color: #333; font-size: 0.95rem;">
                        ${ratesContent}
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">Format: Unit Type, Maximum guests, then rates per season</p>' : ''}
                </div>
            `;
        } else {
            // Show as rate cards matching the website
            previewHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    ${rateCards.map(card => `
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <h3 style="color: #1a5490; margin: 0 0 0.5rem 0; font-size: 1.2rem;">${card.title}</h3>
                            <p style="color: #666; margin: 0 0 1rem 0; font-size: 0.9rem;">${card.guests || 'Maximum guests'}</p>
                            <div style="margin-bottom: 1rem;">
                                ${card.rates.map(rate => `
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e0e0e0;">
                                        <span style="color: #666; font-size: 0.9rem;">${rate.season} Season</span>
                                        <strong style="color: #d4af37; font-size: 0.95rem;">${rate.price}/night</strong>
                                    </div>
                                `).join('')}
                            </div>
                            <button style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: white; border: none; border-radius: 5px; font-weight: 600; cursor: pointer;">Book Now</button>
                        </div>
                    `).join('')}
                </div>
                ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; text-align: center;">This matches how rates appear on the website</p>' : ''}
            `;
        }
    } else if (sectionInfo.previewClass === 'offer-card') {
        // Individual offer card editor - show all cards with the current one highlighted
        const content = displayContent || '';
        let offer = {};
        
        // Parse the content
        const lines = content.split('\n').filter(l => l.trim());
        lines.forEach(line => {
            if (line.startsWith('Title:')) {
                offer.title = line.replace('Title:', '').trim();
            } else if (line.startsWith('Subtitle:')) {
                offer.subtitle = line.replace('Subtitle:', '').trim();
            } else if (line.includes('%') || line.includes('OFF') || line.match(/\d+%/)) {
                offer.discount = line.trim();
            } else if (line.trim().startsWith('-')) {
                offer.features = offer.features || [];
                offer.features.push(line.replace(/^-\s*/, '').trim());
            }
        });
        
        // Default values based on which offer
        const offerDefaults = {
            'offer_1': {
                title: 'Early Bird Special',
                subtitle: 'Book 60+ days in advance',
                discount: '15% OFF',
                gradient: 'linear-gradient(135deg, #d4af37 0%, #ff6b35 100%)',
                color: '#d4af37',
                features: [
                    'Valid for all unit types',
                    'Minimum 3 nights stay',
                    'Book 60+ days before arrival',
                    'Subject to availability'
                ]
            },
            'offer_2': {
                title: 'Long Stay Discount',
                subtitle: 'Stay 7+ nights',
                discount: '20% OFF',
                gradient: 'linear-gradient(135deg, #1a5490 0%, #2a5298 100%)',
                color: '#1a5490',
                features: [
                    '7+ consecutive nights',
                    'Perfect for extended holidays',
                    'All accommodation types',
                    'Best value for families'
                ]
            },
            'offer_3': {
                title: 'Last Minute Deal',
                subtitle: 'Book within 7 days',
                discount: '10% OFF',
                gradient: 'linear-gradient(135deg, #ff6b35 0%, #f7931e 100%)',
                color: '#ff6b35',
                features: [
                    'Book within 7 days of arrival',
                    'Spontaneous getaways welcome',
                    'Limited availability',
                    'Subject to unit availability'
                ]
            }
        };
        
        const defaults = offerDefaults[sectionInfo.value] || offerDefaults['offer_1'];
        const finalOffer = {
            title: offer.title || defaults.title,
            subtitle: offer.subtitle || defaults.subtitle,
            discount: offer.discount || defaults.discount,
            gradient: defaults.gradient,
            color: defaults.color,
            features: offer.features && offer.features.length > 0 ? offer.features : defaults.features
        };
        
        // Get all three offers to show them all, with the current one highlighted
        // Note: In a real implementation, we'd load the other offers from the database
        // For now, we show defaults for the other cards
        const allOffers = ['offer_1', 'offer_2', 'offer_3'].map(offerKey => {
            if (offerKey === sectionInfo.value) {
                return finalOffer;
            } else {
                return offerDefaults[offerKey];
            }
        });
        
        previewHTML = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 2rem; border-radius: 15px;">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #1a5490; margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">Special Offers & Packages</h1>
                    <p style="color: #666; font-size: 1.2rem; margin: 0;">Exclusive deals for your perfect Mozambique getaway</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto;">
                    ${allOffers.map((offer, index) => {
                        const isEditing = (index === 0 && sectionInfo.value === 'offer_1') || 
                                         (index === 1 && sectionInfo.value === 'offer_2') || 
                                         (index === 2 && sectionInfo.value === 'offer_3');
                        const borderStyle = isEditing ? 'border: 3px solid #d4af37; outline: 4px solid rgba(212, 175, 55, 0.3);' : 'border: 2px solid transparent;';
                        const opacity = isEditing ? '1' : '0.6';
                        
                        return `
                            <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); position: relative; ${borderStyle} opacity: ${opacity}; transition: opacity 0.3s;">
                                <div style="background: ${offer.gradient}; padding: 1.5rem; text-align: center; color: white;">
                                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">${offer.title}</h3>
                                    <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 1rem;">${offer.subtitle}</p>
                                </div>
                                <div style="padding: 2rem;">
                                    <div style="font-size: 2.5rem; font-weight: bold; color: ${offer.color}; margin-bottom: 1rem; text-align: center;">
                                        ${offer.discount}
                                    </div>
                                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; line-height: 2;">
                                        ${offer.features.map(f => `<li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: #28a745; margin-right: 0.5rem;"></i> ${f}</li>`).join('')}
                                    </ul>
                                    <a href="#" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: white; border: none; border-radius: 5px; font-weight: 600; text-align: center; display: block; text-decoration: none; cursor: pointer;">Book Now</a>
                                </div>
                                ${isEditing ? '<div style="position: absolute; top: 10px; right: 10px; background: #d4af37; color: #1a5490; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 10;">✏️ EDITING</div>' : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
                ${isPlaceholderMode ? '<p style="margin-top: 2rem; font-size: 0.85rem; color: #999; font-style: italic; text-align: center; padding-top: 2rem; border-top: 1px solid #ddd;">The highlighted card (with gold border) is the one you are currently editing</p>' : ''}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'offers-section') {
        // Parse the content to extract offer cards - match the website exactly
        const content = displayContent || '';
        let offers = [];
        
        // Try to parse structured content (if it's JSON or structured text)
        try {
            if (content.trim().startsWith('[') || content.trim().startsWith('{')) {
                offers = JSON.parse(content);
            } else {
                // Parse text format matching the placeholder format
                const sections = content.split(/\n\s*\n/).filter(s => s.trim());
                sections.forEach(section => {
                    const lines = section.split('\n').filter(l => l.trim());
                    const offer = {};
                    lines.forEach(line => {
                        if (line.startsWith('Title:')) {
                            offer.title = line.replace('Title:', '').trim();
                        } else if (line.startsWith('Subtitle:')) {
                            offer.subtitle = line.replace('Subtitle:', '').trim();
                        } else if (line.includes('%') || line.includes('OFF') || line.match(/\d+%/)) {
                            offer.discount = line.trim();
                        } else if (line.trim().startsWith('-')) {
                            offer.features = offer.features || [];
                            offer.features.push(line.replace(/^-\s*/, '').trim());
                        }
                    });
                    if (offer.title || offer.discount) {
                        offers.push(offer);
                    }
                });
            }
        } catch (e) {
            // If parsing fails, create default offers
        }
        
        // If no offers parsed, use default placeholder offers
        if (offers.length === 0) {
            offers = [
                {
                    title: 'Early Bird Special',
                    subtitle: 'Book 60+ days in advance',
                    discount: '15% OFF',
                    features: [
                        'Valid for all unit types',
                        'Minimum 3 nights stay',
                        'Book 60+ days before arrival',
                        'Subject to availability'
                    ]
                },
                {
                    title: 'Long Stay Discount',
                    subtitle: 'Stay 7+ nights',
                    discount: '20% OFF',
                    features: [
                        '7+ consecutive nights',
                        'Perfect for extended holidays',
                        'All accommodation types',
                        'Best value for families'
                    ]
                },
                {
                    title: 'Last Minute Deal',
                    subtitle: 'Book within 7 days',
                    discount: '10% OFF',
                    features: [
                        'Book within 7 days of arrival',
                        'Spontaneous getaways welcome',
                        'Limited availability',
                        'Subject to unit availability'
                    ]
                }
            ];
        }
        
        // Show offers as cards matching the website exactly
        const gradients = [
            'linear-gradient(135deg, #d4af37 0%, #ff6b35 100%)',
            'linear-gradient(135deg, #1a5490 0%, #2a5298 100%)',
            'linear-gradient(135deg, #ff6b35 0%, #f7931e 100%)'
        ];
        const colors = ['#d4af37', '#1a5490', '#ff6b35'];
        
        previewHTML = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 2rem; border-radius: 15px;">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #1a5490; margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">Special Offers & Packages</h1>
                    <p style="color: #666; font-size: 1.2rem; margin: 0;">Exclusive deals for your perfect Mozambique getaway</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
                    ${offers.map((offer, index) => {
                        const gradient = gradients[index % gradients.length];
                        const color = colors[index % colors.length];
                        return `
                            <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); position: relative;">
                                <div style="background: ${gradient}; padding: 1.5rem; text-align: center; color: white;">
                                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">${offer.title || 'Special Offer'}</h3>
                                    ${offer.subtitle ? `<p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 1rem;">${offer.subtitle}</p>` : ''}
                                </div>
                                <div style="padding: 2rem;">
                                    <div style="font-size: 2.5rem; font-weight: bold; color: ${color}; margin-bottom: 1rem; text-align: center;">
                                        ${offer.discount || 'Special Deal'}
                                    </div>
                                    ${offer.features && offer.features.length > 0 ? `
                                        <ul style="list-style: none; padding: 0; margin-bottom: 2rem; line-height: 2;">
                                            ${offer.features.map(f => `<li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: #28a745; margin-right: 0.5rem;"></i> ${f}</li>`).join('')}
                                        </ul>
                                    ` : ''}
                                    <a href="#" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: white; border: none; border-radius: 5px; font-weight: 600; text-align: center; display: block; text-decoration: none; cursor: pointer;">Book Now</a>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                ${isPlaceholderMode ? '<p style="margin-top: 2rem; font-size: 0.85rem; color: #999; font-style: italic; text-align: center; padding-top: 2rem; border-top: 1px solid #ddd;">This matches exactly how special offers appear on the website</p>' : ''}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'offer-field') {
        // Individual offer field editor (title, subtitle, discount, features)
        const fieldType = sectionInfo.value.split('_').pop(); // Get last part: title, subtitle, discount, or features
        const offerNum = sectionInfo.value.includes('offer_1') ? 1 : (sectionInfo.value.includes('offer_2') ? 2 : 3);
        
        let fieldValue = displayContent || '';
        let fieldLabel = 'Field';
        let fieldIcon = 'fa-edit';
        
        // Default values based on offer number and field type
        const offerDefaults = {
            1: {
                title: 'Early Bird Special',
                subtitle: 'Book 60+ days in advance',
                discount: '15% OFF',
                gradient: 'linear-gradient(135deg, #d4af37 0%, #ff6b35 100%)',
                color: '#d4af37',
                features: 'Valid for all unit types\nMinimum 3 nights stay\nBook 60+ days before arrival\nSubject to availability'
            },
            2: {
                title: 'Long Stay Discount',
                subtitle: 'Stay 7+ nights',
                discount: '20% OFF',
                gradient: 'linear-gradient(135deg, #1a5490 0%, #2a5298 100%)',
                color: '#1a5490',
                features: '7+ consecutive nights\nPerfect for extended holidays\nAll accommodation types\nBest value for families'
            },
            3: {
                title: 'Last Minute Deal',
                subtitle: 'Book within 7 days',
                discount: '10% OFF',
                gradient: 'linear-gradient(135deg, #ff6b35 0%, #f7931e 100%)',
                color: '#ff6b35',
                features: 'Book within 7 days of arrival\nSpontaneous getaways welcome\nLimited availability\nSubject to unit availability'
            }
        };
        
        const defaults = offerDefaults[offerNum] || offerDefaults[1];
        
        if (fieldType === 'title') {
            fieldLabel = 'Title';
            fieldIcon = 'fa-heading';
            if (!fieldValue) fieldValue = defaults.title;
        } else if (fieldType === 'subtitle') {
            fieldLabel = 'Subtitle';
            fieldIcon = 'fa-text-width';
            if (!fieldValue) fieldValue = defaults.subtitle;
        } else if (fieldType === 'discount') {
            fieldLabel = 'Discount';
            fieldIcon = 'fa-percent';
            if (!fieldValue) fieldValue = defaults.discount;
        } else if (fieldType === 'features') {
            fieldLabel = 'Features';
            fieldIcon = 'fa-list-check';
            if (!fieldValue) fieldValue = defaults.features;
        }
        
        // Build the full offer card for preview
        const fullOffer = {
            title: fieldType === 'title' ? fieldValue : defaults.title,
            subtitle: fieldType === 'subtitle' ? fieldValue : defaults.subtitle,
            discount: fieldType === 'discount' ? fieldValue : defaults.discount,
            gradient: defaults.gradient,
            color: defaults.color,
            features: fieldType === 'features' ? fieldValue.split('\n').filter(f => f.trim()) : defaults.features.split('\n').filter(f => f.trim())
        };
        
        previewHTML = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 2rem; border-radius: 15px;">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #1a5490; margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">Special Offers & Packages</h1>
                    <p style="color: #666; font-size: 1.2rem; margin: 0;">Exclusive deals for your perfect Mozambique getaway</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto;">
                    <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); position: relative; border: 3px solid #d4af37; outline: 4px solid rgba(212, 175, 55, 0.3);">
                        <div style="background: ${fullOffer.gradient}; padding: 1.5rem; text-align: center; color: white;">
                            <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; ${fieldType === 'title' ? 'background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px;' : ''}">${fullOffer.title}</h3>
                            <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 1rem; ${fieldType === 'subtitle' ? 'background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; display: inline-block;' : ''}">${fullOffer.subtitle}</p>
                        </div>
                        <div style="padding: 2rem;">
                            <div style="font-size: 2.5rem; font-weight: bold; color: ${fullOffer.color}; margin-bottom: 1rem; text-align: center; ${fieldType === 'discount' ? 'background: rgba(212, 175, 55, 0.1); padding: 8px; border-radius: 8px; border: 2px dashed #d4af37;' : ''}">
                                ${fullOffer.discount}
                            </div>
                            <ul style="list-style: none; padding: 0; margin-bottom: 2rem; line-height: 2;">
                                ${fullOffer.features.map((f, idx) => `<li style="margin-bottom: 0.5rem; ${fieldType === 'features' ? 'background: rgba(212, 175, 55, 0.1); padding: 4px 8px; border-radius: 4px; border-left: 3px solid #d4af37;' : ''}"><i class="fas fa-check" style="color: #28a745; margin-right: 0.5rem;"></i> ${f}</li>`).join('')}
                            </ul>
                            <a href="#" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: white; border: none; border-radius: 5px; font-weight: 600; text-align: center; display: block; text-decoration: none; cursor: pointer;">Book Now</a>
                        </div>
                        <div style="position: absolute; top: 10px; right: 10px; background: #d4af37; color: #1a5490; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 10;">✏️ Editing: ${fieldLabel}</div>
                    </div>
                </div>
                ${isPlaceholderMode ? '<p style="margin-top: 2rem; font-size: 0.85rem; color: #999; font-style: italic; text-align: center; padding-top: 2rem; border-top: 1px solid #ddd;">The highlighted part shows what you are currently editing</p>' : ''}
            </div>
        `;
    } else if (sectionInfo.previewClass === 'contact-field') {
        // Individual contact field editor (phone, email, whatsapp, address)
        const fieldType = sectionInfo.value.replace('contact_', '');
        let fieldIcon = 'fa-phone';
        let fieldLabel = 'Phone';
        let fieldValue = displayContent || '';
        
        if (fieldType === 'phone') {
            fieldIcon = 'fa-phone';
            fieldLabel = 'Phone';
            if (!fieldValue) fieldValue = '+27 72 565 7091';
        } else if (fieldType === 'email') {
            fieldIcon = 'fa-envelope';
            fieldLabel = 'Email';
            if (!fieldValue) fieldValue = 'info@goldenpalmsbeachresort.com';
        } else if (fieldType === 'whatsapp') {
            fieldIcon = 'fab fa-whatsapp';
            fieldLabel = 'WhatsApp';
            if (!fieldValue) fieldValue = '+27 72 565 7091';
        } else if (fieldType === 'address') {
            fieldIcon = 'fa-map-marker-alt';
            fieldLabel = 'Address';
            if (!fieldValue) fieldValue = 'Guinjata Bay, Inhambane, Mozambique';
        }
        
        previewHTML = `
            <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px;" data-clickable="true">
                <h3 style="color: #1a5490; margin: 0 0 1.5rem 0; font-size: 1.5rem;">Contact Information</h3>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 2px solid ${fieldType === 'phone' || fieldType === 'email' || fieldType === 'whatsapp' || fieldType === 'address' ? '#d4af37' : 'transparent'};">
                        <i class="${fieldIcon}" style="color: #d4af37; font-size: 1.8rem; margin-top: 0.25rem;"></i>
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: 0.25rem; color: #333;">${fieldLabel}</strong>
                            <div style="color: #1a5490; font-weight: 600; white-space: pre-wrap;">
                                ${fieldValue}
                            </div>
                        </div>
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This is the ' + fieldLabel.toLowerCase() + ' that appears in the contact section</p>' : ''}
                </div>
            </div>
        `;
    } else if (sectionInfo.previewClass === 'section-content' || sectionInfo.previewClass === 'page-content') {
        // Special handling for contact_extra (additional contact info)
        if (sectionInfo.value === 'contact_extra') {
            previewHTML = `
                <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px;">
                    <h3 style="color: #1a5490; margin: 0 0 1.5rem 0; font-size: 1.5rem;">Contact Information</h3>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fas fa-phone" style="color: #d4af37; font-size: 1.8rem; margin-top: 0.25rem;"></i>
                            <div>
                                <strong style="display: block; margin-bottom: 0.25rem; color: #333;">Phone</strong>
                                <a href="tel:+27725657091" style="color: #1a5490; text-decoration: none;">+27 72 565 7091</a>
                            </div>
                        </div>
                        <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fas fa-envelope" style="color: #d4af37; font-size: 1.8rem; margin-top: 0.25rem;"></i>
                            <div>
                                <strong style="display: block; margin-bottom: 0.25rem; color: #333;">Email</strong>
                                <a href="mailto:info@goldenpalmsbeachresort.com" style="color: #1a5490; text-decoration: none;">info@goldenpalmsbeachresort.com</a>
                            </div>
                        </div>
                        <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fab fa-whatsapp" style="color: #d4af37; font-size: 1.8rem; margin-top: 0.25rem;"></i>
                            <div>
                                <strong style="display: block; margin-bottom: 0.25rem; color: #333;">WhatsApp</strong>
                                <a href="https://wa.me/27725657091" style="color: #1a5490; text-decoration: none;">+27 72 565 7091</a>
                            </div>
                        </div>
                        ${displayContent && displayContent.trim() && !displayContent.includes('Enter your content') ? `
                            <div style="padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 1rem;">
                                <div style="white-space: pre-wrap; line-height: 1.8; color: #333;">
                                    ${displayContent}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This matches how contact information appears on the website</p>' : ''}
                </div>
            `;
        } else if (sectionInfo.value === 'about') {
            previewHTML = `
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #1a5490; margin: 0 0 1rem 0; font-size: 2rem;">About Us</h2>
                    <div style="white-space: pre-wrap; line-height: 1.8; color: #333; font-size: 1rem;">
                        ${displayContent}
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This is how your about section will appear</p>' : ''}
                </div>
            `;
        } else if (sectionInfo.value === 'faq') {
            previewHTML = `
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #1a5490; margin: 0 0 1.5rem 0; font-size: 2rem;">Frequently Asked Questions</h2>
                    <div style="white-space: pre-wrap; line-height: 1.8; color: #333; font-size: 1rem;">
                        ${displayContent}
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This is how your FAQ section will appear</p>' : ''}
                </div>
            `;
        } else {
            previewHTML = `
                <div style="padding: 20px; line-height: 1.8; color: #333; background: white; border-radius: 8px;">
                    <div style="white-space: pre-wrap; font-size: 1rem;">
                        ${displayContent}
                    </div>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This is how your content will appear on the page</p>' : ''}
                </div>
            `;
        }
    } else if (sectionInfo.previewClass === 'page-intro') {
        // For rates page intro, show it matches the website header
        if (currentWebsitePage === 'rates') {
            previewHTML = `
                <div style="text-align: center; padding: 30px 20px; background: white; border-radius: 8px;">
                    <h1 style="color: #1a5490; margin: 0 0 10px 0; font-size: 2.5rem; font-weight: 700;">Rates & Pricing</h1>
                    <p style="color: #666; font-size: 1.2rem; margin: 0; line-height: 1.6;">
                        ${displayContent || 'Transparent pricing for your perfect beach holiday'}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic;">This appears below the main title on the rates page</p>' : ''}
                </div>
            `;
        } else if (currentWebsitePage === 'specials') {
            previewHTML = `
                <div style="text-align: center; padding: 30px 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px;">
                    <h1 style="color: #1a5490; margin: 0 0 10px 0; font-size: 2.5rem; font-weight: 700;">Special Offers & Packages</h1>
                    <p style="color: #666; font-size: 1.2rem; margin: 0; line-height: 1.6;">
                        ${displayContent || 'Exclusive deals for your perfect Mozambique getaway'}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic;">This appears below the main title on the specials page</p>' : ''}
                </div>
            `;
        } else {
            previewHTML = `
                <div style="padding: 30px 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #1a5490;">
                    <h2 style="color: #1a5490; margin: 0 0 15px 0;">Page Introduction</h2>
                    <p style="color: #666; line-height: 1.8; margin: 0;">
                        ${displayContent}
                    </p>
                    ${isPlaceholderMode ? '<p style="margin-top: 10px; font-size: 0.85rem; color: #999; font-style: italic;">This introduction appears at the top of the page</p>' : ''}
                </div>
            `;
        }
    } else {
        // Default preview for any section without specific preview class
        previewHTML = `
            <div style="padding: 20px; line-height: 1.8; color: #333; background: white; border-radius: 8px; border: 1px solid #dee2e6;">
                <h3 style="color: #1a5490; margin: 0 0 15px 0; font-size: 1.2rem;">${sectionInfo.label}</h3>
                <div style="white-space: pre-wrap; font-size: 1rem;">
                    ${displayContent}
                </div>
                ${isPlaceholderMode ? '<p style="margin-top: 15px; font-size: 0.85rem; color: #999; font-style: italic; border-top: 1px solid #eee; padding-top: 15px;">This is how your content will appear</p>' : ''}
            </div>
        `;
    }
    
    previewEl.innerHTML = previewHTML;
    
    // Add click-to-edit functionality to preview (only once, prevent loops)
    if (sectionInfo && currentWebsitePage && !previewEl.dataset.clickHandlerAdded) {
        // Mark that we've added the handler to prevent duplicates
        previewEl.dataset.clickHandlerAdded = 'true';
        
        // Find the main preview container (the one with data-clickable or the first div)
        const previewContainer = previewEl.querySelector('[data-clickable="true"]') || previewEl.querySelector('div');
        if (previewContainer) {
            previewContainer.style.cursor = 'pointer';
            previewContainer.style.position = 'relative';
            previewContainer.title = 'Click to edit this section';
            
            // Add visual indicator
            const editIndicator = document.createElement('div');
            editIndicator.className = 'edit-indicator';
            editIndicator.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: linear-gradient(135deg, #1a5490 0%, #2a5298 100%);
                color: white;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
                opacity: 0;
                transition: opacity 0.2s;
                pointer-events: none;
                z-index: 10;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            `;
            editIndicator.innerHTML = '✏️ Click to Edit';
            previewContainer.appendChild(editIndicator);
            
            // Add hover effect
            previewContainer.addEventListener('mouseenter', function() {
                this.style.outline = '2px solid #d4af37';
                this.style.outlineOffset = '4px';
                this.style.transition = 'all 0.2s';
                editIndicator.style.opacity = '1';
            });
            
            previewContainer.addEventListener('mouseleave', function() {
                this.style.outline = '';
                this.style.outlineOffset = '';
                editIndicator.style.opacity = '0';
            });
            
            // Add click handler to select this section (with debouncing)
            let isSelecting = false;
            previewContainer.addEventListener('click', function(e) {
                // Don't trigger if clicking on links, buttons, or the indicator
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || 
                    e.target.closest('a') || e.target.closest('button') ||
                    e.target.classList.contains('edit-indicator')) {
                    return;
                }
                
                // Prevent multiple rapid clicks
                if (isSelecting) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                // Check if section is already selected
                const sectionSelect = document.getElementById('sectionSelector');
                if (sectionSelect && sectionSelect.value === currentWebsiteSection) {
                    // Already selected, just focus the editor
                    const editor = document.getElementById('contentEditor');
                    if (editor) {
                        editor.focus();
                    }
                    return;
                }
                
                isSelecting = true;
                console.log('Preview clicked - selecting section:', currentWebsiteSection, 'on page:', currentWebsitePage);
                
                if (!currentWebsitePage || !currentWebsiteSection) {
                    console.error('Missing page or section:', { currentWebsitePage, currentWebsiteSection });
                    isSelecting = false;
                    return;
                }
                
                // Ensure the page is selected (via page list links)
                const pageLink = document.querySelector(`.page-list a[data-page="${currentWebsitePage}"]`);
                if (pageLink && !pageLink.classList.contains('active')) {
                    pageLink.click();
                    // Wait for page to load before selecting section
                    setTimeout(() => {
                        selectSectionInDropdown();
                        isSelecting = false;
                    }, 300);
                } else {
                    // Page is already selected, just select the section
                    selectSectionInDropdown();
                    setTimeout(() => { isSelecting = false; }, 500);
                }
                
                // Helper function to select section in dropdown
                function selectSectionInDropdown() {
                    const sectionSelect = document.getElementById('sectionSelector');
                    if (sectionSelect && currentWebsiteSection) {
                        if (sectionSelect.value !== currentWebsiteSection) {
                            sectionSelect.value = currentWebsiteSection;
                            // Trigger the change event to load the section
                            const changeEvent = new Event('change', { bubbles: true });
                            sectionSelect.dispatchEvent(changeEvent);
                        }
                    } else {
                        console.error('Section selector not found:', sectionSelect, 'or section not set:', currentWebsiteSection);
                    }
                }
            });
        }
    }
}

// Get placeholder content for section
function getPlaceholderContent(sectionInfo) {
    if (!sectionInfo) return 'Your content here...';
    
    // Use sectionInfo.value to get the key
    const sectionKey = sectionInfo.value;
    
    const placeholders = {
        'hero_title': 'Golden Palms Beach Resort',
        'hero_subtitle': 'The Jewel of Inhambane, Mozambique',
        'hero_description': 'Experience paradise at Guinjata Bay - 30km South of Inhambane, Mozambique. One of the most unique Mozambique accommodation options with beautiful sandy white beaches and clear blue sea.',
        'accommodation_intro': 'Self-catering units fully equipped for your perfect beach holiday. All our Mozambique accommodation units come with your everyday essentials such as linen, cutlery, crockery, fridge, deep freeze, kettle, gas stove and gas geyser.',
        'accommodation_2bed': 'Perfect for families or small groups seeking comfort and privacy in a self-catering environment.',
        'accommodation_3bed': 'Spacious accommodation ideal for larger families or groups wanting extra comfort and space.',
        'accommodation_5bed': 'Luxury accommodation perfect for large families, groups, or extended stays with maximum comfort.',
        'activities_intro': 'Make the most of your stay with these amazing activities at Guinjata Bay',
        'testimonials_intro': 'Real reviews from our valued guests - Rated as one of the best Mozambique Lodges',
        'main': 'Main content for this page...',
        'intro': 'Transparent pricing for your perfect beach holiday',
        'rate_2bed_low': 'R850',
        'rate_2bed_mid': 'R1,050',
        'rate_2bed_peak': 'R1,350',
        'rate_3bed_low': 'R1,200',
        'rate_3bed_mid': 'R1,500',
        'rate_3bed_peak': 'R1,900',
        'rate_5bed_low': 'R1,800',
        'rate_5bed_mid': 'R2,200',
        'rate_5bed_peak': 'R2,800',
        'pricing_table': `2 Bedroom Units
Maximum 6 guests
Low Season: R850/night
Mid Season: R1,050/night
Peak Season: R1,350/night

3 Bedroom Units
Maximum 8 guests
Low Season: R1,200/night
Mid Season: R1,500/night
Peak Season: R1,900/night

5 Bedroom Units
Maximum 10 guests
Low Season: R1,800/night
Mid Season: R2,200/night
Peak Season: R2,800/night`,
        'about': 'Golden Palms Beach Resort is a premier self-catering accommodation located at Guinjata Bay, 30km South of Inhambane, Mozambique. We offer beautiful beachfront units with stunning ocean views, perfect for families and groups seeking an authentic Mozambican beach holiday experience.',
        'faq': 'Q: What is included in the accommodation?\nA: All units include linen, cutlery, crockery, fridge, deep freeze, kettle, gas stove, and gas geyser.\n\nQ: Are towels provided?\nA: No, please bring your own towels.\n\nQ: What is the minimum stay?\nA: Minimum stay is 2 nights (3 nights during peak season).\n\nQ: How do I get there?\nA: Accessible by 2×4 diff lock or 4×4 vehicle via dirt road. We are 660km from Mbombela, 970km from Pretoria, and 480km North of Maputo.',
        'contact_phone': '+27 72 565 7091',
        'contact_email': 'info@goldenpalmsbeachresort.com',
        'contact_whatsapp': '+27 72 565 7091',
        'contact_address': 'Guinjata Bay, Inhambane, Mozambique',
        'contact_extra': 'For bookings and enquiries, please contact us. We are located 30km South of Inhambane, accessible by 2×4 diff lock or 4×4 vehicle.',
        'terms': 'Terms and conditions content...',
        'current_offers': `Title: Early Bird Special
Subtitle: Book 60+ days in advance
15% OFF
- Valid for all unit types
- Minimum 3 nights stay
- Book 60+ days before arrival
- Subject to availability

Title: Long Stay Discount
Subtitle: Stay 7+ nights
20% OFF
- 7+ consecutive nights
- Perfect for extended holidays
- All accommodation types
- Best value for families

Title: Last Minute Deal
Subtitle: Book within 7 days
10% OFF
- Book within 7 days of arrival
- Spontaneous getaways welcome
- Limited availability
- Subject to unit availability`
    };
    
    const placeholder = placeholders[sectionKey];
    console.log(`Getting placeholder for ${sectionKey}:`, placeholder || 'NOT FOUND');
    return placeholder || 'Enter your content here...';
}

// Update preview when content changes
function setupPreviewUpdate() {
    // Update preview when text editor content changes
    const editor = document.getElementById('contentEditor');
    if (editor) {
        editor.addEventListener('input', function() {
            updatePreviewForCurrentSection();
        });
        editor.addEventListener('paste', function() {
            setTimeout(() => updatePreviewForCurrentSection(), 10);
        });
    }
    
    // Update preview when image URL changes
    const imageUrlInput = document.getElementById('imageUrlInput');
    if (imageUrlInput) {
        imageUrlInput.addEventListener('input', function() {
            updatePreviewForCurrentSection();
        });
        imageUrlInput.addEventListener('paste', function() {
            setTimeout(() => updatePreviewForCurrentSection(), 10);
        });
    }
    
    // Update preview when video URL changes
    const videoUrlInput = document.getElementById('videoUrlInput');
    if (videoUrlInput) {
        videoUrlInput.addEventListener('input', function() {
            updatePreviewForCurrentSection();
        });
        videoUrlInput.addEventListener('paste', function() {
            setTimeout(() => updatePreviewForCurrentSection(), 10);
        });
    }
}

// Helper function to update preview for current section
function updatePreviewForCurrentSection() {
    if (!currentWebsitePage || !currentWebsiteSection) return;
    
    // Get the current content from the editor
    let currentContent = '';
    const contentEditor = document.getElementById('contentEditor');
    const ratePriceInput = document.getElementById('ratePriceInput');
    const imageUrlInput = document.getElementById('imageUrlInput');
    const videoUrlInput = document.getElementById('videoUrlInput');
    const galleryTextarea = document.getElementById('galleryTextarea');
    
    // Get content based on which editor is active
    if (ratePriceInput && ratePriceInput.offsetParent !== null) {
        // Rate price editor is visible
        const priceValue = ratePriceInput.value.trim();
        if (priceValue) {
            // Extract number and format
            const number = priceValue.replace(/[^\d,]/g, '').replace(/,/g, '');
            currentContent = number ? `R${number}` : '';
        }
    } else if (imageUrlInput && imageUrlInput.offsetParent !== null) {
        // Image editor is visible
        currentContent = imageUrlInput.value.trim();
    } else if (videoUrlInput && videoUrlInput.offsetParent !== null) {
        // Video editor is visible
        currentContent = videoUrlInput.value.trim();
    } else if (galleryTextarea && galleryTextarea.offsetParent !== null) {
        // Gallery editor is visible
        currentContent = galleryTextarea.value.trim();
    } else if (contentEditor) {
        // Text editor is visible
        currentContent = contentEditor.value.trim();
    }
    
    const sections = pageSections[currentWebsitePage] || [];
    const sectionInfo = sections.find(s => s.value === currentWebsiteSection);
    if (sectionInfo) {
        // Update preview with actual content from the editor (not from database)
        updateSectionPreview(sectionInfo, false, currentContent);
    }
}

// Clear editor
function clearEditor() {
    const editor = document.getElementById('contentEditor');
    if (editor) {
        editor.value = '';
    }
    currentContentId = null;
    currentWebsiteContent = null;
    
    const sectionInfoEl = document.getElementById('sectionInfo');
    if (sectionInfoEl) {
        sectionInfoEl.style.display = 'none';
    }
    
    // Hide all editors
    showTextEditor();
    const mediaContainer = document.getElementById('mediaEditorContainer');
    if (mediaContainer) mediaContainer.style.display = 'none';
}

// Load website content
async function loadWebsiteContent(page, section) {
    if (!page || !section) {
        clearEditor();
        return;
    }
    
    try {
        let url = `${API_BASE_URL}/website/content?page=${page}&section=${section}&all=true`;
        const response = await fetch(url, {
            headers: { 'Authorization': `Bearer ${authToken}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Get the latest version of content for this section
            // IMPORTANT: Find the exact section match
            const contentItem = data && data.length > 0 ? data.find(item => 
                item.page === page && item.section === section
            ) : null;
            
            const sections = pageSections[page] || [];
            const sectionInfo = sections.find(s => s.value === section);
            
            if (contentItem) {
                // Found saved content for this specific section
                currentContentId = contentItem.id;
                currentWebsiteContent = contentItem;
                
                // Load into appropriate editor
                if (sectionInfo && sectionInfo.contentType === 'media') {
                    loadMediaContent(contentItem, sectionInfo);
                } else if (sectionInfo && sectionInfo.previewClass === 'rate-price') {
                    // Load rate price into special input - THIS IS THE SPECIFIC PRICE FOR THIS SECTION
                    const editor = document.getElementById('contentEditor');
                    const ratePriceInput = document.getElementById('ratePriceInput');
                    const currentValue = contentItem.content || '';
                    
                    console.log(`Loading price for section ${section}:`, currentValue);
                    
                    if (editor) {
                        editor.value = currentValue;
                    }
                    
                    if (ratePriceInput) {
                        if (currentValue) {
                            // Extract number from "R850" or "R1,200" format
                            const number = currentValue.replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
                            ratePriceInput.value = number;
                            console.log(`Set price input to:`, number);
                        } else {
                            // Load placeholder if no saved value
                            const placeholder = getPlaceholderContent(sectionInfo);
                            const number = placeholder.replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
                            ratePriceInput.value = number;
                            if (editor) editor.value = placeholder;
                            console.log(`No saved value, using placeholder:`, placeholder);
                        }
                    }
                    
                    // Setup the editor after loading - ensure contentEditor has the value first
                    // Set the value IMMEDIATELY, don't wait
                    if (editor && currentValue) {
                        editor.value = currentValue;
                    }
                    
                    // Then setup the editor handlers
                    setTimeout(() => {
                        setupRatePriceEditor();
                    }, 50);
                } else {
                    const editor = document.getElementById('contentEditor');
                    editor.value = contentItem.content || '';
                }
            } else {
                // No content yet - show placeholder
                if (sectionInfo && sectionInfo.contentType === 'media') {
                    clearMediaContent(sectionInfo);
                } else if (sectionInfo && sectionInfo.previewClass === 'rate-price') {
                    // For rate prices, load placeholder from defaults
                    const editor = document.getElementById('contentEditor');
                    const ratePriceInput = document.getElementById('ratePriceInput');
                    const placeholder = getPlaceholderContent(sectionInfo);
                    
                    console.log(`[DEBUG] No saved content for ${currentWebsiteSection}`);
                    console.log(`[DEBUG] Section info:`, sectionInfo);
                    console.log(`[DEBUG] Placeholder from function:`, placeholder);
                    
                    // Set values IMMEDIATELY, synchronously
                    if (editor) {
                        editor.value = placeholder;
                        console.log(`[DEBUG] Set editor.value to:`, placeholder);
                    }
                    if (ratePriceInput) {
                        const number = placeholder.replace(/[R,\s]/g, '').replace(/\/night.*/i, '');
                        ratePriceInput.value = number;
                        console.log(`[DEBUG] Set ratePriceInput.value to:`, number);
                    }
                    
                    // Setup the editor handlers (but values are already set above)
                    setTimeout(() => {
                        setupRatePriceEditor();
                        // Update preview with placeholder
                        updateSectionPreview(sectionInfo, true);
                    }, 50);
                } else {
                    const editor = document.getElementById('contentEditor');
                    editor.value = '';
                    editor.placeholder = 'No content exists for this section. Enter content and save to create it.';
                }
                currentContentId = null;
            }
            
            // Update preview after loading content
            if (sectionInfo) {
                // Update preview with actual content (not placeholder)
                updateSectionPreview(sectionInfo, false);
            }
        } else {
            console.error('Failed to load content, status:', response.status);
        }
    } catch (error) {
        console.error('Error loading website content:', error);
        // Show error but don't break the UI
    }
}

// Load media content into media editor
function loadMediaContent(contentItem, sectionInfo) {
    const content = contentItem.content || '';
    
    if (sectionInfo.value.includes('image') || sectionInfo.value === 'logo' || sectionInfo.value === 'hero_image') {
        const currentImage = document.getElementById('currentImage');
        const noImageText = document.getElementById('noImageText');
        const imageUrlInput = document.getElementById('imageUrlInput');
        const removeImageBtn = document.getElementById('removeImageBtn');
        
        if (content) {
            currentImage.src = content;
            currentImage.style.display = 'block';
            noImageText.style.display = 'none';
            imageUrlInput.value = content;
            removeImageBtn.style.display = 'inline-block';
        } else {
            currentImage.style.display = 'none';
            noImageText.style.display = 'block';
            imageUrlInput.value = '';
            removeImageBtn.style.display = 'none';
        }
    } else if (sectionInfo.value.includes('video')) {
        const videoUrlInput = document.getElementById('videoUrlInput');
        videoUrlInput.value = content;
        if (content) {
            previewVideo();
        }
    } else if (sectionInfo.value.includes('gallery')) {
        // Load gallery images
        try {
            const images = content ? JSON.parse(content) : [];
            displayGalleryImages(images);
        } catch (e) {
            displayGalleryImages([]);
        }
    }
}

// Clear media content
function clearMediaContent(sectionInfo) {
    if (sectionInfo.value.includes('image') || sectionInfo.value === 'logo' || sectionInfo.value === 'hero_image') {
        document.getElementById('currentImage').style.display = 'none';
        document.getElementById('noImageText').style.display = 'block';
        document.getElementById('imageUrlInput').value = '';
        document.getElementById('removeImageBtn').style.display = 'none';
    } else if (sectionInfo.value.includes('video')) {
        document.getElementById('videoUrlInput').value = '';
        document.getElementById('videoPreview').style.display = 'none';
    } else if (sectionInfo.value.includes('gallery')) {
        document.getElementById('galleryImages').innerHTML = '<p style="color: #999; grid-column: 1/-1; text-align: center; padding: 20px;">No gallery images yet. Upload images to get started.</p>';
    }
}

// Save website content
async function saveWebsiteContent() {
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    
    if (!currentWebsitePage || !currentWebsiteSection) {
        alert('Please select a page and section first.');
        return;
    }
    
    const sections = pageSections[currentWebsitePage] || [];
    const sectionInfo = sections.find(s => s.value === currentWebsiteSection);
    const isMedia = sectionInfo && sectionInfo.contentType === 'media';
    
    let content = '';
    let contentType = 'text';
    
    if (isMedia) {
        // Get content from media editor
        if (sectionInfo.value.includes('image') || sectionInfo.value === 'logo' || sectionInfo.value === 'hero_image') {
            const imageUrlInput = document.getElementById('imageUrlInput');
            content = imageUrlInput ? imageUrlInput.value.trim() : '';
            contentType = 'media';
        } else if (sectionInfo.value.includes('video')) {
            const videoUrlInput = document.getElementById('videoUrlInput');
            content = videoUrlInput ? videoUrlInput.value.trim() : '';
            contentType = 'media';
        } else if (sectionInfo.value.includes('gallery')) {
            const galleryImages = getGalleryImages();
            content = JSON.stringify(galleryImages);
            contentType = 'media';
        }
    } else {
        // Get content from text editor or rate price input
        const sections = pageSections[currentWebsitePage] || [];
        const sectionInfo = sections.find(s => s.value === currentWebsiteSection);
        
        if (sectionInfo && sectionInfo.previewClass === 'rate-price') {
            // For rate prices, get from the special price input
            const ratePriceInput = document.getElementById('ratePriceInput');
            const number = ratePriceInput ? ratePriceInput.value.replace(/[^0-9]/g, '') : '';
            if (number) {
                const formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                content = `R${formatted}`;
            } else {
                content = '';
            }
        } else {
            const editor = document.getElementById('contentEditor');
            content = editor ? editor.value.trim() : '';
        }
    }
    
    if (!content) {
        alert('Please enter some content to save.');
        return;
    }
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    try {
        let response;
        
        // If we have existing content, update it
        if (currentContentId) {
            response = await fetch(`${API_BASE_URL}/website/content/${currentContentId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content: content,
                    content_type: contentType,
                    is_published: true
                })
            });
        } else {
            // Create new content
            const payload = {
                page: currentWebsitePage,
                section: currentWebsiteSection,
                content_key: currentWebsiteSection,
                content_type: contentType,
                content: content,
                is_published: true
            };
            
            console.log('Creating new content:', payload);
            
            response = await fetch(`${API_BASE_URL}/website/content`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
        }
        
        if (response.ok) {
            const updated = await response.json();
            console.log('Content saved successfully:', updated);
            currentContentId = updated.id;
            currentWebsiteContent = updated;
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; z-index: 10000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);';
            successMsg.textContent = '✓ Content saved and published successfully!';
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
            
            // Reload content to get latest version and update preview
            await loadWebsiteContent(currentWebsitePage, currentWebsiteSection);
        } else {
            const error = await response.json();
            console.error('Error saving content:', error);
            alert('Error saving content: ' + (error.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving content:', error);
        alert('Connection error. Please check if the server is running.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

// Preview website content
function previewWebsiteContent() {
    const editor = document.getElementById('contentEditor');
    const content = editor.value;
    
    if (!content) {
        alert('No content to preview.');
        return;
    }
    
    // Open preview in new window
    const previewWindow = window.open('', '_blank');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Preview - ${currentWebsitePage}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
                h1 { color: #2c3e50; }
                .content { line-height: 1.6; }
            </style>
        </head>
        <body>
            <h1>Preview: ${currentWebsitePage.charAt(0).toUpperCase() + currentWebsitePage.slice(1)}</h1>
            <div class="content">${content.replace(/\n/g, '<br>')}</div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}


