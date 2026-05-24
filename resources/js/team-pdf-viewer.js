import * as pdfjsLib from 'pdfjs-dist/build/pdf.mjs';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?worker&url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

class TeamPdfViewer extends HTMLElement {
    static get observedAttributes() {
        return ['src', 'page'];
    }

    connectedCallback() {
        this.currentPage = Number(this.getAttribute('page') || 1);
        this.renderShell();
        this.loadPdf();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue || !this.pdf) {
            return;
        }

        if (name === 'page') {
            this.goToPage(Number(newValue || 1));
        }

        if (name === 'src') {
            this.loadPdf();
        }
    }

    renderShell() {
        this.innerHTML = `
            <div class="overflow-hidden rounded-lg border border-primary-800/10 bg-white shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-primary-800/10 px-3 py-2 dark:border-flash-orange/20">
                    <div class="flex items-center gap-2">
                        <button type="button" data-previous class="rounded-md border border-primary-800/10 px-3 py-1.5 text-sm font-medium text-primary-900 hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-flash-orange/20 dark:text-white dark:hover:bg-primary-900">Vorige</button>
                        <button type="button" data-next class="rounded-md border border-primary-800/10 px-3 py-1.5 text-sm font-medium text-primary-900 hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-flash-orange/20 dark:text-white dark:hover:bg-primary-900">Volgende</button>
                    </div>
                    <p data-status class="text-sm text-zinc-600 dark:text-zinc-300">PDF laden...</p>
                </div>
                <div data-stage class="flex min-h-[70vh] items-start justify-center overflow-auto bg-zinc-100 p-3 dark:bg-primary-950">
                    <canvas data-canvas class="max-w-full bg-white shadow-sm"></canvas>
                </div>
            </div>
        `;

        this.status = this.querySelector('[data-status]');
        this.canvas = this.querySelector('[data-canvas]');
        this.stage = this.querySelector('[data-stage]');
        this.previousButton = this.querySelector('[data-previous]');
        this.nextButton = this.querySelector('[data-next]');

        this.previousButton.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        this.nextButton.addEventListener('click', () => this.goToPage(this.currentPage + 1));
    }

    async loadPdf() {
        const src = this.getAttribute('src');

        if (!src || !this.canvas) {
            return;
        }

        this.status.textContent = 'PDF laden...';

        try {
            this.pdf = await pdfjsLib.getDocument({ url: src, withCredentials: true }).promise;
            this.currentPage = this.clampedPage(Number(this.getAttribute('page') || 1));
            await this.renderPage();
        } catch (error) {
            console.error('Team PDF kon niet geladen worden.', { src, error });
            this.status.textContent = this.errorMessage(error);
        }
    }

    async goToPage(page) {
        if (!this.pdf) {
            return;
        }

        this.currentPage = this.clampedPage(page);
        await this.renderPage();
    }

    async renderPage() {
        const page = await this.pdf.getPage(this.currentPage);
        const availableWidth = Math.max(320, (this.stage?.clientWidth || 900) - 32);
        const baseViewport = page.getViewport({ scale: 1 });
        const scale = Math.min(1.5, availableWidth / baseViewport.width);
        const viewport = page.getViewport({ scale });
        const context = this.canvas.getContext('2d');

        this.canvas.width = Math.floor(viewport.width);
        this.canvas.height = Math.floor(viewport.height);

        await page.render({ canvasContext: context, viewport }).promise;

        this.status.textContent = `Pagina ${this.currentPage} van ${this.pdf.numPages}`;
        this.previousButton.disabled = this.currentPage <= 1;
        this.nextButton.disabled = this.currentPage >= this.pdf.numPages;
        this.stage.scrollTop = 0;
    }

    clampedPage(page) {
        return Math.min(Math.max(Number.isFinite(page) ? page : 1, 1), this.pdf?.numPages || 1);
    }

    errorMessage(error) {
        const message = error?.message || '';

        if (message.includes('Unexpected server response')) {
            return 'PDF kon niet geladen worden. De PDF-route geeft geen geldig PDF-bestand terug.';
        }

        if (message.includes('Missing PDF') || message.includes('Invalid PDF')) {
            return 'PDF kon niet gelezen worden. Upload het bestand opnieuw als PDF.';
        }

        return 'PDF kon niet geladen worden. Open de browserconsole voor de technische melding.';
    }
}

if (!customElements.get('team-pdf-viewer')) {
    customElements.define('team-pdf-viewer', TeamPdfViewer);
}
