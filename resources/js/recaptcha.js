const getSiteKey = () => document.querySelector('meta[name="recaptcha-site-key"]')?.content?.trim() ?? '';

const waitForRecaptcha = (siteKey) => new Promise((resolve, reject) => {
    if (typeof window.grecaptcha?.execute === 'function') {
        resolve(window.grecaptcha);
        return;
    }

    let attempts = 0;
    const maxAttempts = 50;
    const interval = window.setInterval(() => {
        attempts += 1;

        if (typeof window.grecaptcha?.execute === 'function') {
            window.clearInterval(interval);
            resolve(window.grecaptcha);
            return;
        }

        if (attempts >= maxAttempts) {
            window.clearInterval(interval);
            reject(new Error('reCAPTCHA failed to load.'));
        }
    }, 100);
});

const setRecaptchaToken = (form, token) => {
    let input = form.querySelector('input[name="recaptcha_token"]');

    if (! input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'recaptcha_token';
        form.appendChild(input);
    }

    input.value = token;
};

const bindRecaptchaForms = () => {
    const siteKey = getSiteKey();

    if (! siteKey) {
        return;
    }

    document.querySelectorAll('form[data-recaptcha-action]').forEach((form) => {
        if (form.dataset.recaptchaBound === 'true') {
            return;
        }

        form.dataset.recaptchaBound = 'true';

        form.addEventListener('submit', (event) => {
            if (form.dataset.recaptchaSubmitting === 'true') {
                return;
            }

            event.preventDefault();

            const action = form.dataset.recaptchaAction;

            if (! action) {
                form.submit();
                return;
            }

            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach((button) => {
                button.disabled = true;
            });

            waitForRecaptcha(siteKey)
                .then((grecaptcha) => grecaptcha.execute(siteKey, { action }))
                .then((token) => {
                    setRecaptchaToken(form, token);
                    form.dataset.recaptchaSubmitting = 'true';
                    form.submit();
                })
                .catch(() => {
                    submitButtons.forEach((button) => {
                        button.disabled = false;
                    });
                    window.alert('CAPTCHA verification failed. Please try again.');
                });
        });
    });
};

document.addEventListener('DOMContentLoaded', bindRecaptchaForms);
