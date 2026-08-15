const databaseName = 'flashing-training-offline';
const databaseVersion = 3;

function database() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(databaseName, databaseVersion);

        request.onupgradeneeded = () => {
            const { result } = request;

            if (! result.objectStoreNames.contains('trainings')) {
                result.createObjectStore('trainings', { keyPath: 'id' });
            }

            if (! result.objectStoreNames.contains('events')) {
                result.createObjectStore('events', { keyPath: 'uuid' });
            }

            if (! result.objectStoreNames.contains('progress')) {
                result.createObjectStore('progress', { keyPath: 'training_id' });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function put(storeName, value) {
    const db = await database();
    const transaction = db.transaction(storeName, 'readwrite');
    transaction.objectStore(storeName).put(value);
}

window.trainingOffline = {
    async saveTraining(training) {
        await put('trainings', training);
        await put('progress', {
            training_id: training.id,
            current_index: 0,
            started_at: new Date().toISOString(),
            paused_at: null,
            total_paused_seconds: 0,
            block_started_at: new Date().toISOString(),
            block_added_seconds: 0,
            block_notes: {},
        });

        window.dispatchEvent(new CustomEvent('training-offline-saved'));
    },
};

window.trainingTimer = (config) => ({
    ...config,
    online: navigator.onLine,
    now: Date.now(),
    offlineSaved: false,
    init() {
        setInterval(() => { this.now = Date.now(); }, 1000);
        addEventListener('online', () => { this.online = true; });
        addEventListener('offline', () => { this.online = false; });
        addEventListener('training-offline-saved', () => { this.offlineSaved = true; });
        navigator.wakeLock?.request('screen').catch(() => {});
    },
    reset(config) {
        Object.assign(this, config);
        this.now = Date.now();
    },
    elapsed(start, pausedAt, paused) {
        const started = new Date(start).getTime();
        const end = paused ? new Date(pausedAt).getTime() : this.now;

        return Math.max(0, Math.floor((end - started) / 1000));
    },
    remaining() {
        return Math.max(0, this.planned + this.added - this.elapsed(this.startedAt, this.pausedAt, this.paused));
    },
    totalElapsed() {
        return Math.max(0, this.elapsed(this.totalStarted, this.pausedAt, this.paused) - this.totalPaused);
    },
    format(seconds) {
        const roundedSeconds = Math.max(0, Math.round(seconds));

        return `${String(Math.floor(roundedSeconds / 60)).padStart(2, '0')}:${String(roundedSeconds % 60).padStart(2, '0')}`;
    },
});

if ('serviceWorker' in navigator) {
    addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
}

document.addEventListener('submit', event => {
    const form = event.target;

    if (form instanceof HTMLFormElement && form.action.endsWith('/logout')) {
        indexedDB.deleteDatabase(databaseName);
    }
});
