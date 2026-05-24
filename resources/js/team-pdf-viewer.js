import * as pdfjsLib from 'pdfjs-dist/build/pdf.mjs';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?worker&url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const MinScale = 0.4;
const MaxScale = 3;
const ZoomStep = 0.25;
const MaxOutputScale = 3;

class TeamPdfViewer extends HTMLElement {
    static get observedAttributes() {
        return ['src', 'page'];
    }

    connectedCallback() {
        this.currentPage = Number(this.getAttribute('page') || 1);
        this.currentScale = 1;
        this.fitToWidth = true;
        this.renderTask = null;
        this.renderToken = 0;
        this.renderShell();
        this.observeStage();
        this.loadPdf();
    }

    disconnectedCallback() {
        this.resizeObserver?.disconnect();
        window.clearTimeout(this.resizeTimer);
        this.cancelRenderTask();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) {
            return;
        }

        if (name === 'page') {
            if (this.pdf) {
                this.goToPage(Number(newValue || 1));
            } else {
                this.currentPage = Number(newValue || 1);
            }
        }

        if (name === 'src' && this.canvas) {
            this.loadPdf();
        }
    }

    renderShell() {
        const buttonClass = 'rounded-md border border-primary-800/10 px-3 py-1.5 text-sm font-medium text-primary-900 transition hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-flash-orange/20 dark:text-white dark:hover:bg-primary-900';

        this.innerHTML = `
            <div class="min-w-0 overflow-hidden rounded-lg border border-primary-800/10 bg-white shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                <div class="grid gap-2 border-b border-primary-800/10 px-3 py-2 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-center dark:border-flash-orange/20">
                    <div class="flex items-center gap-2">
                        <button type="button" data-previous class="${buttonClass}">Vorige</button>
                        <button type="button" data-next class="${buttonClass}">Volgende</button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <button type="button" data-zoom-out aria-label="Zoom uit" class="${buttonClass} w-9 px-0">-</button>
                        <span data-zoom-label class="min-w-20 rounded-md bg-primary-50 px-2 py-1.5 text-center text-xs font-semibold text-primary-900 dark:bg-primary-900 dark:text-white">Passend</span>
                        <button type="button" data-zoom-in aria-label="Zoom in" class="${buttonClass} w-9 px-0">+</button>
                        <button type="button" data-fit class="${buttonClass}">Passend</button>
                        <p data-status class="min-w-0 flex-1 text-sm text-zinc-600 lg:flex-none lg:text-right dark:text-zinc-300">PDF laden...</p>
                    </div>
                </div>
                <div data-stage class="min-h-[65vh] max-h-[75vh] overflow-auto bg-zinc-100 p-2 sm:min-h-[70vh] sm:p-3 dark:bg-primary-950">
                    <canvas data-canvas class="mx-auto block bg-white shadow-sm"></canvas>
                </div>
            </div>
        `;

        this.status = this.querySelector('[data-status]');
        this.canvas = this.querySelector('[data-canvas]');
        this.stage = this.querySelector('[data-stage]');
        this.previousButton = this.querySelector('[data-previous]');
        this.nextButton = this.querySelector('[data-next]');
        this.zoomOutButton = this.querySelector('[data-zoom-out]');
        this.zoomInButton = this.querySelector('[data-zoom-in]');
        this.fitButton = this.querySelector('[data-fit]');
        this.zoomLabel = this.querySelector('[data-zoom-label]');

        this.previousButton.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        this.nextButton.addEventListener('click', () => this.goToPage(this.currentPage + 1));
        this.zoomOutButton.addEventListener('click', () => this.zoomBy(-ZoomStep));
        this.zoomInButton.addEventListener('click', () => this.zoomBy(ZoomStep));
        this.fitButton.addEventListener('click', () => this.fitWidth());
        this.disableControls(true);
    }

    observeStage() {
        if (!this.stage || !window.ResizeObserver) {
            return;
        }

        this.resizeObserver = new ResizeObserver(() => {
            window.clearTimeout(this.resizeTimer);

            this.resizeTimer = window.setTimeout(() => {
                if (this.pdf && this.fitToWidth) {
                    this.renderPage();
                }
            }, 100);
        });

        this.resizeObserver.observe(this.stage);
    }

    async loadPdf() {
        const src = this.getAttribute('src');

        if (!src || !this.canvas) {
            return;
        }

        this.status.textContent = 'PDF laden...';
        this.disableControls(true);
        this.cancelRenderTask();

        try {
            this.pdf = await pdfjsLib.getDocument({ url: src, withCredentials: true }).promise;
            this.currentPage = this.clampedPage(Number(this.getAttribute('page') || 1));
            this.fitToWidth = true;
            await this.renderPage({ resetScroll: true });
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
        await this.renderPage({ resetScroll: true });
    }

    async zoomBy(delta) {
        if (!this.pdf) {
            return;
        }

        this.fitToWidth = false;
        this.currentScale = this.clampedScale(this.currentScale + delta);
        await this.renderPage();
    }

    async fitWidth() {
        if (!this.pdf) {
            return;
        }

        this.fitToWidth = true;
        await this.renderPage();
    }

    async renderPage({ resetScroll = false } = {}) {
        if (!this.pdf || !this.canvas || !this.stage) {
            return;
        }

        const renderToken = ++this.renderToken;
        this.cancelRenderTask();
        this.status.textContent = 'Pagina renderen...';

        const page = await this.pdf.getPage(this.currentPage);
        const baseViewport = page.getViewport({ scale: 1 });
        const fitScale = this.fitScaleFor(baseViewport);
        const scale = this.fitToWidth ? fitScale : this.clampedScale(this.currentScale);
        const viewport = page.getViewport({ scale });
        const outputScale = Math.max(1, Math.min(window.devicePixelRatio || 1, MaxOutputScale));
        const context = this.canvas.getContext('2d', { alpha: false });

        this.currentScale = scale;
        this.canvas.width = Math.floor(viewport.width * outputScale);
        this.canvas.height = Math.floor(viewport.height * outputScale);
        this.canvas.style.width = `${Math.floor(viewport.width)}px`;
        this.canvas.style.height = `${Math.floor(viewport.height)}px`;

        context.setTransform(outputScale, 0, 0, outputScale, 0, 0);

        const renderTask = page.render({ canvasContext: context, viewport });
        this.renderTask = renderTask;

        try {
            await renderTask.promise;
        } catch (error) {
            if (error?.name === 'RenderingCancelledException') {
                return;
            }

            throw error;
        } finally {
            if (this.renderTask === renderTask) {
                this.renderTask = null;
            }
        }

        if (renderToken !== this.renderToken) {
            return;
        }

        this.updateControls();

        if (resetScroll) {
            this.stage.scrollTop = 0;
            this.stage.scrollLeft = 0;
        }

        this.dispatchEvent(new CustomEvent('team-pdf-page-changed', {
            bubbles: true,
            detail: { page: this.currentPage },
        }));
    }

    updateControls() {
        const percentage = Math.round(this.currentScale * 100);

        this.status.textContent = `Pagina ${this.currentPage} van ${this.pdf.numPages}`;
        this.zoomLabel.textContent = this.fitToWidth ? `Passend (${percentage}%)` : `${percentage}%`;
        this.previousButton.disabled = this.currentPage <= 1;
        this.nextButton.disabled = this.currentPage >= this.pdf.numPages;
        this.zoomOutButton.disabled = this.currentScale <= MinScale + 0.01;
        this.zoomInButton.disabled = this.currentScale >= MaxScale - 0.01;
        this.fitButton.disabled = this.fitToWidth;
    }

    disableControls(disabled) {
        [
            this.previousButton,
            this.nextButton,
            this.zoomOutButton,
            this.zoomInButton,
            this.fitButton,
        ].forEach((button) => {
            if (button) {
                button.disabled = disabled;
            }
        });
    }

    fitScaleFor(baseViewport) {
        const stageStyles = window.getComputedStyle(this.stage);
        const horizontalPadding = Number.parseFloat(stageStyles.paddingLeft) + Number.parseFloat(stageStyles.paddingRight);
        const availableWidth = Math.max(280, this.stage.clientWidth - horizontalPadding);

        return this.clampedScale(availableWidth / baseViewport.width);
    }

    clampedScale(scale) {
        return Math.min(Math.max(Number.isFinite(scale) ? scale : 1, MinScale), MaxScale);
    }

    clampedPage(page) {
        return Math.min(Math.max(Number.isFinite(page) ? page : 1, 1), this.pdf?.numPages || 1);
    }

    cancelRenderTask() {
        if (this.renderTask) {
            this.renderTask.cancel();
            this.renderTask = null;
        }
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
