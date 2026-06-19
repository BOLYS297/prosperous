const DB_NAME = "prosperous-motos-db";
const DB_VERSION = 1;

const STORES = {
    PRODUCTS: "products",
    STOCKS: "stocks",
    SYNC_QUEUE: "sync_queue",
};

function openDatabase() {
    return new Promise((resolve, reject) => {
        if (!window.indexedDB) {
            return reject(
                new Error("IndexedDB is not supported in this browser."),
            );
        }

        const request = window.indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onblocked = () =>
            reject(new Error("IndexedDB open request blocked."));

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains(STORES.PRODUCTS)) {
                db.createObjectStore(STORES.PRODUCTS, { keyPath: "id" });
            }

            if (!db.objectStoreNames.contains(STORES.STOCKS)) {
                db.createObjectStore(STORES.STOCKS, { keyPath: "id" });
            }

            if (!db.objectStoreNames.contains(STORES.SYNC_QUEUE)) {
                db.createObjectStore(STORES.SYNC_QUEUE, {
                    keyPath: "id",
                    autoIncrement: true,
                });
            }
        };

        request.onsuccess = () => {
            resolve(request.result);
        };
    });
}

function transactionPromise(tx) {
    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
        tx.onabort = () =>
            reject(tx.error || new Error("IndexedDB transaction aborted."));
    });
}

async function withStore(storeName, mode, callback) {
    const db = await openDatabase();
    const tx = db.transaction(storeName, mode);
    const store = tx.objectStore(storeName);
    const result = callback(store);
    await transactionPromise(tx);
    return result;
}

function requestToPromise(request) {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function saveItems(storeName, items) {
    if (!Array.isArray(items) || items.length === 0) {
        return;
    }

    return withStore(storeName, "readwrite", (store) => {
        items.forEach((item) => {
            store.put(item);
        });
    });
}

export async function getAllItems(storeName) {
    return withStore(storeName, "readonly", (store) => {
        return requestToPromise(store.getAll());
    });
}

export async function clearStore(storeName) {
    return withStore(storeName, "readwrite", (store) => {
        store.clear();
    });
}

export async function saveOfflineData(data) {
    if (!data || typeof data !== "object") {
        return;
    }

    if (Array.isArray(data.produits)) {
        await saveItems(STORES.PRODUCTS, data.produits);
    }

    if (Array.isArray(data.stocks)) {
        await saveItems(STORES.STOCKS, data.stocks);
    }
}

export async function getOfflineData() {
    const produits = await getAllItems(STORES.PRODUCTS);
    const stocks = await getAllItems(STORES.STOCKS);
    return {
        produits: produits || [],
        stocks: stocks || [],
    };
}

export async function addSyncQueueItem(item) {
    return withStore(STORES.SYNC_QUEUE, "readwrite", (store) => {
        store.add(item);
    });
}

export async function getSyncQueueItems() {
    return getAllItems(STORES.SYNC_QUEUE);
}

export async function removeSyncQueueItem(id) {
    return withStore(STORES.SYNC_QUEUE, "readwrite", (store) => {
        store.delete(id);
    });
}
