class BasketballTrainerEmbed extends HTMLElement {
    connectedCallback() {
        this.frame = this.querySelector('[data-embed-frame]');
        this.loading = this.querySelector('[data-embed-loading]');

        if (!this.frame) {
            return;
        }

        this.allowedOrigin = new URL(this.frame.src, window.location.href).origin;
        this.handleMessage = this.handleMessage.bind(this);
        window.addEventListener('message', this.handleMessage);
    }

    disconnectedCallback() {
        window.removeEventListener('message', this.handleMessage);
    }

    handleMessage(event) {
        if (
            event.origin !== this.allowedOrigin
            || event.source !== this.frame?.contentWindow
            || typeof event.data !== 'object'
            || event.data === null
        ) {
            return;
        }

        if (event.data.type === 'basketballtrainer:ready') {
            this.loading?.remove();
            this.frame.classList.remove('hidden');
        }

        if (event.data.type === 'basketballtrainer:resize') {
            const requestedHeight = Number(event.data.height);

            if (!Number.isFinite(requestedHeight)) {
                return;
            }

            const height = Math.min(Math.max(Math.ceil(requestedHeight), 672), 1800);
            this.frame.style.height = `${height}px`;
        }
    }
}

if (!customElements.get('basketball-trainer-embed')) {
    customElements.define('basketball-trainer-embed', BasketballTrainerEmbed);
}
