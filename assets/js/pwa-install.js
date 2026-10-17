(() => {
    const installBtn = document.getElementById('pwaInstallBtn');
    const installHint = document.getElementById('pwaInstallHint');
    let deferredPrompt = null;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function showHint(text) {
        if (!installHint) {
            return;
        }

        installHint.hidden = false;
        installHint.textContent = text;
    }

    if ('serviceWorker' in navigator) {
        const swUrl = (window.SALON_PWA && window.SALON_PWA.swUrl) || '/sw.js';
        navigator.serviceWorker.register(swUrl).catch(() => {});
    }

    if (isStandalone()) {
        if (installBtn) {
            installBtn.hidden = true;
        }

        return;
    }

    if (installBtn) {
        installBtn.hidden = false;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;

        if (installBtn) {
            installBtn.hidden = false;
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;

        if (installBtn) {
            installBtn.hidden = true;
        }

        showHint('Aplikacija je instalirana. Otvori je sa početnog ekrana.');
    });

    if (installBtn) {
        if (isIos()) {
            installBtn.hidden = false;
        }

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                installBtn.hidden = true;
                return;
            }

            if (isIos()) {
                showHint('Na iPhone-u: Podeli → Dodaj na početni ekran.');
                return;
            }

            showHint('U Chrome-u otvori meni (⋮) pa izaberi Instaliraj aplikaciju.');
        });
    }
})();
