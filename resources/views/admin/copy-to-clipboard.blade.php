{{-- window.kasaCopy(text): copy with a fallback for plain-HTTP origins, where navigator.clipboard is unavailable. --}}
<script>
    window.kasaCopy = function (text, message = 'Link copied to clipboard') {
        const notify = (title, ok) => {
            if (window.FilamentNotification) {
                const notification = new FilamentNotification().title(title);
                (ok ? notification.success() : notification.danger()).send();
            }
        };

        const fallback = () => {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (e) {
                copied = false;
            }
            document.body.removeChild(textarea);
            notify(copied ? message : 'Could not copy — please copy the link manually', copied);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => notify(message, true), fallback);
        } else {
            fallback();
        }
    };
</script>
