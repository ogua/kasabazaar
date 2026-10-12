import './bootstrap';

/*
 * Shared Alpine components. Livewire bundles Alpine, so register them on
 * `alpine:init` rather than importing Alpine again.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Ids in the shopper's product comparison. Seeded from the session by the
     * layout; the compare tray broadcasts `compare-updated` after each change.
     */
    window.Alpine.store('compare', {
        ids: window.__compareIds ?? [],
        has(id) {
            return this.ids.includes(id);
        },
    });

    window.addEventListener('compare-updated', (event) => {
        window.Alpine.store('compare').ids = event.detail?.ids ?? [];
    });

    /**
     * Header search type-ahead. Fetches /search/suggest (debounced, aborting
     * stale requests) and exposes a flat `items` list so arrow keys move
     * through products, categories and auctions alike.
     */
    window.Alpine.data('searchSuggest', (endpoint) => ({
        term: '',
        open: false,
        loading: false,
        results: { products: [], categories: [], auctions: [] },
        active: -1,
        controller: null,
        timer: null,

        get items() {
            return [...this.results.products, ...this.results.categories, ...this.results.auctions];
        },

        get hasResults() {
            return this.items.length > 0;
        },

        indexOf(item) {
            return this.items.indexOf(item);
        },

        query() {
            clearTimeout(this.timer);
            this.active = -1;

            if (this.term.trim().length < 2) {
                this.results = { products: [], categories: [], auctions: [] };
                this.open = false;
                return;
            }

            this.timer = setTimeout(async () => {
                this.controller?.abort();
                this.controller = new AbortController();
                this.loading = true;

                try {
                    const response = await fetch(`${endpoint}?q=${encodeURIComponent(this.term.trim())}`, {
                        headers: { Accept: 'application/json' },
                        signal: this.controller.signal,
                    });
                    this.results = await response.json();
                    this.open = true;
                } catch (error) {
                    if (error.name !== 'AbortError') this.open = false;
                } finally {
                    this.loading = false;
                }
            }, 220);
        },

        move(step) {
            if (! this.hasResults) return;
            this.open = true;
            this.active = (this.active + step + this.items.length) % this.items.length;
        },

        choose(event) {
            const item = this.items[this.active];

            if (this.open && item) {
                event.preventDefault();
                window.location.href = item.url;
            }
        },

        close() {
            this.open = false;
            this.active = -1;
        },
    }));

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
