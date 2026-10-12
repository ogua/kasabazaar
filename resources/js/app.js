import './bootstrap';

/*
 * Shared Alpine components. Livewire bundles Alpine, so register them on
 * `alpine:init` rather than importing Alpine again.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Ticking countdown to an ISO timestamp, used by auction cards and the
     * auction page. `label` is "2d 4h", "3h 12m" or "04:59" depending on how
     * much time is left; `urgent` turns on inside the last hour.
     */
    window.Alpine.data('countdown', (isoTarget) => ({
        target: Date.parse(isoTarget),
        now: Date.now(),
        timer: null,

        init() {
            this.timer = setInterval(() => (this.now = Date.now()), 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        get remaining() {
            return Math.max(0, this.target - this.now);
        },

        get done() {
            return this.remaining === 0;
        },

        get urgent() {
            return this.remaining > 0 && this.remaining < 60 * 60 * 1000;
        },

        get label() {
            const total = Math.floor(this.remaining / 1000);
            const days = Math.floor(total / 86400);
            const hours = Math.floor((total % 86400) / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            const seconds = total % 60;
            const pad = (n) => String(n).padStart(2, '0');

            if (days > 0) return `${days}d ${hours}h`;
            if (hours > 0) return `${hours}h ${pad(minutes)}m`;

            return `${pad(minutes)}:${pad(seconds)}`;
        },
    }));
});
