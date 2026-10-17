(() => {
    const PHONE_KEY = 'salon_deki_client_phone';
    const NAME_KEY = 'salon_deki_client_name';

    function getStoredPhone() {
        const value = localStorage.getItem(PHONE_KEY);
        return value && /^\d{10}$/.test(value) ? value : null;
    }

    function getStoredName() {
        return localStorage.getItem(NAME_KEY) || '';
    }

    function saveClient(phone, name) {
        localStorage.setItem(PHONE_KEY, String(phone));
        localStorage.setItem(NAME_KEY, String(name));
    }

    function clearClient() {
        localStorage.removeItem(PHONE_KEY);
        localStorage.removeItem(NAME_KEY);
    }

    async function clearClientSession(config) {
        clearClient();

        if (!config || !config.apiClientSession) {
            return;
        }

        await fetch(config.apiClientSession, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: config.csrfToken,
                action: 'clear',
            }),
        });
    }

    async function saveClientSession(config, phone, name) {
        const response = await fetch(config.apiClientSession, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: config.csrfToken,
                phone,
                name,
            }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Sesija nije sačuvana.');
        }

        saveClient(phone, name);
        return data;
    }

    async function fetchBookingByPhone(config, phone) {
        if (!phone || !config || !config.apiMyBooking) {
            return null;
        }

        try {
            const response = await fetch(
                `${config.apiMyBooking}?phone=${encodeURIComponent(phone)}`
            );
            const data = await response.json();

            if (!response.ok || !data.success) {
                return null;
            }

            return data.booking;
        } catch (error) {
            return null;
        }
    }

    async function fetchStoredBooking(config) {
        const phone = getStoredPhone();
        return fetchBookingByPhone(config, phone);
    }

    function myBookingUrl(config) {
        return config.myBookingUrl || 'booking.php?moj=1';
    }

    async function initLandingPage(config) {
        const phone = getStoredPhone();

        if (!phone) {
            return;
        }

        const booking = await fetchBookingByPhone(config, phone);

        if (booking && booking.is_active) {
            window.location.replace(myBookingUrl(config));
        }
    }

    async function prefillClientForm(nameInput, phoneInput) {
        const phone = getStoredPhone();
        const name = getStoredName();

        if (phoneInput && phone) {
            phoneInput.value = phone;
        }

        if (nameInput && name) {
            nameInput.value = name;
        }
    }

    window.SalonClientBooking = {
        getStoredPhone,
        getStoredName,
        saveClient,
        clearClient,
        clearClientSession,
        saveClientSession,
        fetchBookingByPhone,
        fetchStoredBooking,
        myBookingUrl,
        initLandingPage,
        prefillClientForm,
    };
})();
