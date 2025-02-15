class BookingCalendar {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            minDate: new Date(),
            maxDate: new Date(new Date().setMonth(new Date().getMonth() + 6)),
            excludeDays: ['Sunday'],
            timeSlots: this.generateTimeSlots('08:00', '23:59', 30),
            ...options
        };
        this.selectedDate = null;
        this.selectedSlot = null;
        this.init();
    }

    init() {
        this.renderCalendar();
        this.attachEventListeners();
    }

    renderCalendar() {
        const calendarHtml = `
            <div class="calendar-view">
                <div class="calendar-header">
                    <button class="btn btn-link prev-month">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h5 class="month-year mb-0"></h5>
                    <button class="btn btn-link next-month">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="weekdays">
                    ${this.renderWeekdays()}
                </div>
                <div class="calendar-grid">
                    ${this.renderDays()}
                </div>
            </div>
            <div class="time-slots-container"></div>
        `;
        this.container.innerHTML = calendarHtml;
        this.updateMonthYear();
    }

    renderWeekdays() {
        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return weekdays.map(day => 
            `<div class="weekday">${day}</div>`
        ).join('');
    }

    renderDays() {
        // Calendar rendering logic
    }

    updateMonthYear() {
        // Month/year update logic
    }

    generateTimeSlots(start, end, interval) {
        // Time slot generation logic
    }

    renderTimeSlots(date) {
        const container = this.container.querySelector('.time-slots-container');
        const slotsHtml = `
            <div class="time-slots-grid">
                ${this.options.timeSlots.map(slot => `
                    <div class="time-slot-option" data-time="${slot}">
                        ${this.formatTime(slot)}
                    </div>
                `).join('')}
            </div>
        `;
        container.innerHTML = slotsHtml;
    }

    attachEventListeners() {
        // Event listener attachment logic
    }

    formatTime(time) {
        // Time formatting logic
    }

    checkAvailability(date, time) {
        // Availability checking logic
    }
}

// Helper class for handling bookings
class BookingHelper {
    constructor() {
        this.container = this.createHelper();
        this.visible = true;
        this.init();
    }

    createHelper() {
        const helper = document.createElement('div');
        helper.className = 'booking-helper';
        helper.innerHTML = `
            <button class="helper-toggle">
                <i class="fas fa-question"></i>
            </button>
            <h6>Booking Help</h6>
            <div class="helper-content">
                <p>Follow these steps:</p>
                <ol>
                    <li>Select a facility</li>
                    <li>Choose a date from the calendar</li>
                    <li>Pick an available time slot</li>
                    <li>Confirm your booking</li>
                </ol>
                <div class="legend">
                    <div><span class="available"></span> Available</div>
                    <div><span class="unavailable"></span> Unavailable</div>
                    <div><span class="selected"></span> Selected</div>
                </div>
            </div>
        `;
        document.body.appendChild(helper);
        return helper;
    }

    init() {
        this.attachEventListeners();
    }

    attachEventListeners() {
        const toggle = this.container.querySelector('.helper-toggle');
        toggle.addEventListener('click', () => this.toggleHelper());
    }

    toggleHelper() {
        const content = this.container.querySelector('.helper-content');
        this.visible = !this.visible;
        content.style.display = this.visible ? 'block' : 'none';
    }
}
