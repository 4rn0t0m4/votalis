import { Passkeys } from '@laravel/passkeys';

// Clés d'accès (WebAuthn) : boutons déclarés par attributs data-*, sans script en ligne (CSP).
document.addEventListener('DOMContentLoaded', () => {
    const supported = typeof window.PublicKeyCredential !== 'undefined';

    document.querySelectorAll('[data-passkey-register]').forEach((button) => {
        if (!supported) {
            button.disabled = true;
            button.title = 'Votre navigateur ne prend pas en charge les clés d’accès.';
            return;
        }

        button.addEventListener('click', async () => {
            const input = document.querySelector(button.dataset.passkeyRegister);
            const name = input?.value?.trim() || 'Clé d’accès';
            button.disabled = true;

            try {
                await Passkeys.register({ name });
                window.location.reload();
            } catch (error) {
                showPasskeyError(button, error);
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-passkey-login]').forEach((button) => {
        if (!supported) {
            button.hidden = true;
            return;
        }

        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                const response = await Passkeys.verify();
                window.location.assign(response?.redirect ?? button.dataset.passkeyLogin ?? '/');
            } catch (error) {
                showPasskeyError(button, error);
                button.disabled = false;
            }
        });
    });
});

function showPasskeyError(button, error) {
    const target = document.querySelector(button.dataset.passkeyError ?? '[data-passkey-error]');

    if (target) {
        target.textContent = error?.message || 'La clé d’accès n’a pas pu être utilisée. Réessayez.';
        target.hidden = false;
    }
}
