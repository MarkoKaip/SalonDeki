(() => {
    const config = window.BOOKING_CONFIG || {};
    const state = {
        step: 1,
        phone: '',
        name: '',
        serviceId: null,
        serviceName: '',
        date: null,
        dateLabel: '',
        time: null,
    };

    const steps = document.querySelectorAll('.booking-step');
    const indicators = document.querySelectorAll('[data-step-indicator]');
    const alertBox = document.getElementById('bookingAlert');
    const clientIdentifyForm = document.getElementById('clientIdentifyForm');
    const toStep3Btn = document.getElementById('toStep3');
    const toStep4Btn = document.getElementById('toStep4');
    const backToStep1Btn = document.getElementById('backToStep1');
    const backToStep2Btn = document.getElementById('backToStep2');
    const backToStep3Btn = document.getElementById('backToStep3');
    const slotsContainer = document.getElementById('slotsContainer');
    const bookingSummary = document.getElementById('bookingSummary');
    const bookingForm = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBooking');
    const phoneInput = document.getElementById('clientPhone');
    const nameInput = document.getElementById('clientName');
    const identifyBtn = document.getElementById('toStep2');

    if (phoneInput) {
        phoneInput.addEventListener('input', () => {
            phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    SalonClientBooking.prefillClientForm(nameInput, phoneInput);

    function showAlert(message) {
        alertBox.textContent = message;
        alertBox.hidden = false;
    }

    function hideAlert() {
        alertBox.hidden = true;
        alertBox.textContent = '';
    }

    function setStep(step) {
        state.step = step;
        steps.forEach((el) => el.classList.toggle('active', Number(el.dataset.step) === step));
        indicators.forEach((el) => {
            const indicatorStep = Number(el.dataset.stepIndicator);
            el.classList.toggle('active', indicatorStep === step);
            el.classList.toggle('done', indicatorStep < step);
        });
        hideAlert();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function clearFieldErrors() {
        document.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
        });
    }

    function setFieldError(field, message) {
        const el = document.querySelector(`[data-error-for="${field}"]`);
        if (el) {
            el.textContent = message;
        }
    }

    function validateIdentifyForm() {
        clearFieldErrors();
        const name = nameInput.value.trim();
        const phone = phoneInput.value.trim();
        let valid = true;

        if (!/^\d{10}$/.test(phone)) {
            setFieldError('phone', 'Broj telefona mora imati tačno 10 cifara.');
            valid = false;
        }

        if (!/^[A-ZŠĐČĆŽ][^\s]+ [A-ZŠĐČĆŽ][^\s]+$/u.test(name.replace(/\s+/g, ' ').trim())) {
            setFieldError('name', 'Unesite tačno ime i prezime (2 reči, obe počinju velikim slovom).');
            valid = false;
        }

        return valid;
    }

    clientIdentifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();

        if (!validateIdentifyForm()) {
            return;
        }

        identifyBtn.disabled = true;
        identifyBtn.textContent = 'Provera...';

        try {
            const data = await SalonClientBooking.saveClientSession(
                config,
                phoneInput.value.trim(),
                nameInput.value.trim()
            );

            if (data.has_active_booking) {
                let booking = data.booking;

                if (!booking) {
                    booking = await SalonClientBooking.fetchBookingByPhone(
                        config,
                        phoneInput.value.trim()
                    );
                }

                if (showClientDashboard(booking, phoneInput.value.trim(), nameInput.value.trim())) {
                    return;
                }

                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
            }

            state.phone = phoneInput.value.trim();
            state.name = nameInput.value.trim();
            setStep(2);
        } catch (error) {
            showAlert(error.message);
        } finally {
            identifyBtn.disabled = false;
            identifyBtn.textContent = 'Nastavi';
        }
    });

    document.querySelectorAll('[data-service-id]').forEach((card) => {
        card.addEventListener('click', () => {
            document.querySelectorAll('[data-service-id]').forEach((item) => item.classList.remove('selected'));
            card.classList.add('selected');
            state.serviceId = Number(card.dataset.serviceId);
            state.serviceName = card.dataset.serviceName || '';
            toStep3Btn.disabled = false;
        });
    });

    document.querySelectorAll('.day-card:not(.disabled)').forEach((card) => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.day-card').forEach((item) => item.classList.remove('selected'));
            card.classList.add('selected');
            state.date = card.dataset.date;
            state.dateLabel = card.dataset.dateLabel || card.dataset.date;
            state.time = null;
            toStep4Btn.disabled = true;
            loadSlots(state.date);
        });
    });

    async function loadSlots(date) {
        slotsContainer.className = 'slots-loading';
        slotsContainer.innerHTML = '<div class="spinner"></div><p>Učitavanje termina...</p>';

        try {
            const response = await fetch(
                `${config.slotsUrl}?date=${encodeURIComponent(date)}&_=${Date.now()}`
            );
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Greška pri učitavanju termina.');
            }

            if (!data.slots.length) {
                slotsContainer.className = 'slots-empty';
                slotsContainer.textContent = 'Nema slobodnih termina za izabrani dan.';
                return;
            }

            slotsContainer.className = 'slot-options';
            slotsContainer.innerHTML = data.slots
                .map(
                    (slot) =>
                        `<button type="button" class="slot-card" data-time="${slot}">${slot}</button>`
                )
                .join('');

            slotsContainer.querySelectorAll('.slot-card').forEach((slotBtn) => {
                slotBtn.addEventListener('click', () => {
                    slotsContainer.querySelectorAll('.slot-card').forEach((item) => item.classList.remove('selected'));
                    slotBtn.classList.add('selected');
                    state.time = slotBtn.dataset.time;
                    toStep4Btn.disabled = false;
                });
            });
        } catch (error) {
            slotsContainer.className = 'slots-empty';
            slotsContainer.textContent = error.message;
        }
    }

    function updateSummary() {
        bookingSummary.innerHTML = `
            <p><strong>Ime:</strong> ${escapeHtml(state.name)}</p>
            <p><strong>Telefon:</strong> ${escapeHtml(state.phone)}</p>
            <p><strong>Usluga:</strong> ${escapeHtml(state.serviceName)}</p>
            <p><strong>Datum:</strong> ${escapeHtml(state.dateLabel || state.date)}</p>
            <p><strong>Termin:</strong> ${escapeHtml(state.time)}</p>
        `;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function statusNote(status) {
        if (status === 'confirmed') {
            return 'Termin je odobren. Ako ne možete doći, javite se salonu na vreme.';
        }

        if (status === 'cancelled') {
            return 'Rezervacija je otkazana. Možete zakazati novi termin.';
        }

        return 'Salon pregleda rezervaciju. Status će se ažurirati ovde kada admin potvrdi ili otkaže.';
    }

    function showClientDashboard(booking, phone, name) {
        const page = document.querySelector('.booking-page .container');

        if (!page || !booking) {
            return false;
        }

        SalonClientBooking.saveClient(phone, name);

        const noteRow = booking.note
            ? `<div class="detail-row"><span>Napomena</span><strong>${escapeHtml(booking.note)}</strong></div>`
            : '';

        const actions = !booking.is_active || booking.status === 'cancelled'
            ? `<a href="${escapeHtml(config.bookingUrl || 'booking.php')}" class="btn btn-primary">Zakaži novi termin</a>`
            : '<button type="button" class="btn btn-primary" id="refreshBookingBtn">Osveži status</button>';

        page.innerHTML = `
            <article class="confirmation-card fade-in">
                <div class="client-dashboard-head">
                    <div>
                        <h1>Moj termin</h1>
                        <p class="client-greeting">Zdravo, <strong>${escapeHtml(booking.client_name || name)}</strong></p>
                    </div>
                    <a href="${escapeHtml((config.bookingUrl || 'booking.php') + '?novi=1')}" class="client-switch-link">Nisi ti? Promeni ime</a>
                </div>
                <p id="clientStatusMessage">${escapeHtml(booking.status_message || '')}</p>
                <div class="confirmation-details">
                    <div class="detail-row">
                        <span>Status</span>
                        <span class="status-badge status-${escapeHtml(booking.status)}" id="clientStatusBadge">${escapeHtml(booking.status_label || '')}</span>
                    </div>
                    <div class="detail-row">
                        <span>Usluga</span>
                        <strong>${escapeHtml(booking.service_name || '')}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Datum</span>
                        <strong>${escapeHtml(booking.booking_date_label || booking.booking_date || '')}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Vreme</span>
                        <strong>${escapeHtml(booking.booking_time || '')}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Telefon</span>
                        <strong>${escapeHtml(booking.client_phone || phone)}</strong>
                    </div>
                    ${noteRow}
                </div>
                <div class="confirmation-note" id="clientStatusNote">${escapeHtml(statusNote(booking.status))}</div>
                <div class="step-actions">
                    <a href="index.php" class="btn btn-secondary">Početna</a>
                    ${actions}
                </div>
            </article>
        `;

        window.scrollTo({ top: 0, behavior: 'smooth' });

        const refreshBtn = document.getElementById('refreshBookingBtn');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                refreshBtn.disabled = true;
                refreshBtn.textContent = 'Osvežavanje...';

                const freshBooking = await SalonClientBooking.fetchBookingByPhone(config, phone);

                if (!freshBooking) {
                    window.location.href = config.bookingUrl || 'booking.php';
                    return;
                }

                document.getElementById('clientStatusBadge').textContent = freshBooking.status_label;
                document.getElementById('clientStatusBadge').className = `status-badge status-${freshBooking.status}`;
                document.getElementById('clientStatusMessage').textContent = freshBooking.status_message;
                document.getElementById('clientStatusNote').textContent = statusNote(freshBooking.status);

                if (!freshBooking.is_active || freshBooking.status === 'cancelled') {
                    window.location.href = config.bookingUrl || 'booking.php';
                    return;
                }

                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Osveži status';
            });
        }

        return true;
    }

    toStep3Btn.addEventListener('click', () => {
        if (!state.serviceId) {
            showAlert('Izaberite uslugu.');
            return;
        }
        setStep(3);
        if (state.date) {
            loadSlots(state.date);
        }
    });

    toStep4Btn.addEventListener('click', () => {
        if (!state.date || !state.time) {
            showAlert('Izaberite datum i termin.');
            return;
        }
        updateSummary();
        setStep(4);
    });

    backToStep1Btn.addEventListener('click', () => setStep(1));
    backToStep2Btn.addEventListener('click', () => setStep(2));
    backToStep3Btn.addEventListener('click', () => setStep(3));

    bookingForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();

        const note = bookingForm.note.value.trim();

        if (note.length > 500) {
            setFieldError('note', 'Napomena može imati najviše 500 karaktera.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Slanje...';

        try {
            const response = await fetch(config.bookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    service_id: state.serviceId,
                    date: state.date,
                    time: state.time,
                    name: state.name,
                    phone: state.phone,
                    note,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Rezervacija nije uspela.');
            }

            SalonClientBooking.saveClient(state.phone, state.name);
            window.location.href = data.redirect;
        } catch (error) {
            showAlert(error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Rezerviši termin';
        }
    });
})();
