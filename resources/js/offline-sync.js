import {
    saveOfflineData,
    getOfflineData,
    getSyncQueueItems,
    removeSyncQueueItem,
    addSyncQueueItem,
} from "./indexeddb";

const OFFLINE_DATA_URL = "/offline-data";
const OFFLINE_STATUS_EVENT = "pwa-offline-status";
const OFFLINE_QUEUE_UPDATED_EVENT = "offline-queue-updated";

function dispatchOfflineStatus(detail) {
    window.dispatchEvent(new CustomEvent(OFFLINE_STATUS_EVENT, { detail }));
}

function dispatchOfflineQueueUpdated(count, items) {
    window.dispatchEvent(
        new CustomEvent(OFFLINE_QUEUE_UPDATED_EVENT, {
            detail: { count, items },
        }),
    );
}

function buildQueueLabel(item) {
    const url = item.url || "";
    const method = item.method || "POST";
    let action = "Action";

    if (url.includes("/ventes")) {
        action = "Vente";
    } else if (url.includes("/dettes") || url.includes("/payer")) {
        action = "Paiement dette";
    } else if (
        url.includes("/magasinier/transferts") &&
        url.includes("/expedier")
    ) {
        action = "Expédition de transfert";
    } else if (url.includes("/transferts") && method === "POST") {
        action = "Demande de transfert";
    } else if (url.includes("/transferts") && method === "PATCH") {
        action = "Confirmation de transfert";
    } else if (url.includes("/recharges")) {
        action = "Recharge";
    }

    return action;
}

function buildQueueDescription(item) {
    const body = item.body;
    if (!body) {
        return item.url;
    }

    let parsed = null;
    try {
        parsed = typeof body === "string" ? JSON.parse(body) : body;
    } catch (error) {
        return item.url;
    }

    if (parsed.montant) {
        return `Montant : ${parsed.montant}`;
    }

    if (parsed.quantite) {
        return `Quantité : ${parsed.quantite}`;
    }

    if (parsed.produit_id) {
        return `Produit #${parsed.produit_id}`;
    }

    return item.url;
}

function getQueuePanelElements() {
    return {
        panel: document.getElementById("offline-queue-panel"),
        title: document.getElementById("offline-queue-title"),
        summary: document.getElementById("offline-queue-summary"),
        list: document.getElementById("offline-queue-list"),
        toggle: document.getElementById("offline-queue-toggle"),
    };
}

function updateOfflineQueuePanel(items) {
    const { panel, title, summary, list, toggle } = getQueuePanelElements();
    if (!panel || !title || !summary || !list || !toggle) {
        return;
    }

    const count = items.length;

    if (count === 0) {
        panel.classList.add("hidden");
        title.textContent = "Aucune action en attente";
        summary.textContent =
            "Quand vous êtes hors ligne, les ventes et demandes sont mises en attente.";
        list.innerHTML = "";
        return;
    }

    panel.classList.remove("hidden");
    title.textContent = `Actions en attente : ${count}`;
    summary.textContent = `Prêtes à être synchronisées dès que la connexion revient.`;
    list.innerHTML = "";

    items.slice(0, 10).forEach((item) => {
        const entry = document.createElement("div");
        entry.className = "rounded-2xl border border-slate-200 bg-slate-50 p-3";
        const label = document.createElement("div");
        label.className = "font-semibold text-slate-900";
        label.textContent = buildQueueLabel(item);
        const details = document.createElement("div");
        details.className = "text-xs text-slate-500 mt-1";
        details.textContent = `${buildQueueDescription(item)} • ${new Date(item.created_at).toLocaleString()}`;
        entry.appendChild(label);
        entry.appendChild(details);
        list.appendChild(entry);
    });

    if (items.length > 10) {
        const more = document.createElement("div");
        more.className = "text-xs text-slate-400 mt-2";
        more.textContent = `${items.length - 10} autres action(s) en attente...`;
        list.appendChild(more);
    }

    if (!toggle.dataset.initialized) {
        toggle.addEventListener("click", () => {
            list.classList.toggle("hidden");
            toggle.textContent = list.classList.contains("hidden")
                ? "Détails"
                : "Masquer";
        });
        toggle.dataset.initialized = "true";
    }
}

async function refreshOfflineQueueStatus() {
    const items = await getSyncQueueItems();
    dispatchOfflineQueueUpdated(items.length, items);
    updateOfflineQueuePanel(items);
    return items;
}

async function fetchOfflineDataFromServer() {
    if (!navigator.onLine) {
        return null;
    }

    try {
        const response = await fetch(OFFLINE_DATA_URL, {
            method: "GET",
            cache: "no-store",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        await saveOfflineData(payload);

        return payload;
    } catch (error) {
        console.warn("Offline sync failed:", error);
        return null;
    }
}

async function loadOfflineDataFromCache() {
    try {
        const payload = await getOfflineData();

        if (
            (payload.produits?.length || 0) > 0 ||
            (payload.stocks?.length || 0) > 0
        ) {
            return payload;
        }

        return { produits: [], stocks: [] };
    } catch (error) {
        console.warn("Failed to load offline data:", error);
        return { produits: [], stocks: [] };
    }
}

export async function initOfflineData() {
    if (navigator.onLine) {
        return await fetchOfflineDataFromServer();
    }
    return await loadOfflineDataFromCache();
}

function getCsrfToken() {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    return token ? token.content : "";
}

function formDataToObject(formData) {
    const data = {};
    for (const [key, value] of formData.entries()) {
        if (value instanceof File || value instanceof Blob) {
            continue;
        }

        if (data[key] !== undefined) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }
    return data;
}

function hasFileInputsWithFiles(form) {
    return Array.from(form.querySelectorAll("input[type='file']")).some(
        (input) => input.files && input.files.length > 0,
    );
}

function shouldQueueOfflineForm(form) {
    return (
        form instanceof HTMLFormElement &&
        String(form.dataset.offlineSync).toLowerCase() === "true" &&
        form.method.toUpperCase() === "POST"
    );
}

async function isBackendReachable() {
    if (!navigator.onLine) {
        return false;
    }

    try {
        const response = await fetch("/pwa-ping", {
            method: "GET",
            cache: "no-store",
            credentials: "same-origin",
        });
        return response.ok;
    } catch (error) {
        return false;
    }
}

function markFormOfflineSaved(form) {
    const submitBtn =
        form.querySelector(".submit-sale-button") ||
        form.querySelector("button[type='submit']") ||
        form.querySelector("input[type='submit']");
    if (!submitBtn) {
        return;
    }

    const originalText = submitBtn.textContent;
    submitBtn.textContent = "Action enregistrée hors-ligne";
    submitBtn.disabled = true;

    setTimeout(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }, 5000);
}

export async function queueOfflineRequest(url, options) {
    if (!url || !options || typeof options !== "object") {
        return false;
    }

    const body =
        options.body && typeof options.body !== "string"
            ? JSON.stringify(options.body)
            : options.body;

    const headers = {
        "Content-Type": "application/json",
        ...options.headers,
    };

    try {
        await addSyncQueueItem({
            url,
            method: options.method || "POST",
            headers,
            body,
            created_at: new Date().toISOString(),
        });

        dispatchOfflineStatus({
            title: "Action hors-ligne enregistrée",
            message: "Elle sera synchronisée lorsque la connexion revient.",
            icon: "ri-save-line",
            showInstall: false,
            persistent: true,
        });

        await refreshOfflineQueueStatus();
        return true;
    } catch (error) {
        console.warn("Failed to queue offline request:", error);
        return false;
    }
}

export async function syncQueuedRequests() {
    if (!navigator.onLine) {
        return;
    }

    try {
        const queue = await getSyncQueueItems();
        if (!queue || queue.length === 0) {
            return;
        }

        let syncedCount = 0;

        for (const item of queue) {
            try {
                const headers = {
                    ...item.headers,
                };

                if (!headers["X-CSRF-TOKEN"] && !headers["x-csrf-token"]) {
                    const csrfToken = getCsrfToken();
                    if (csrfToken) {
                        headers["X-CSRF-TOKEN"] = csrfToken;
                    }
                }

                const response = await fetch(item.url, {
                    method: item.method,
                    headers,
                    body: item.body,
                    credentials: "same-origin",
                });

                if (response.ok) {
                    await removeSyncQueueItem(item.id);
                    syncedCount++;
                }
            } catch (error) {
                console.warn("Queued request sync failed:", error);
            }
        }

        if (syncedCount > 0) {
            dispatchOfflineStatus({
                title: "Synchronisation réussie",
                message: `${syncedCount} action(s) ont été synchronisées.`,
                icon: "ri-refresh-line",
                showInstall: false,
                persistent: false,
            });
        }

        await refreshOfflineQueueStatus();
    } catch (error) {
        console.warn("Failed to sync queued requests:", error);
    }
}

document.addEventListener("submit", async (event) => {
    const form = event.target;
    if (!shouldQueueOfflineForm(form)) {
        return;
    }

    if (hasFileInputsWithFiles(form)) {
        dispatchOfflineStatus({
            title: "Fichier non pris en charge",
            message:
                "Les formulaires contenant une pièce jointe ne peuvent pas être enregistrés hors-ligne.",
            icon: "ri-alert-line",
            showInstall: false,
            persistent: true,
        });
        return;
    }

    event.preventDefault();
    const backendReachable = await isBackendReachable();
    if (backendReachable) {
        form.submit();
        return;
    }

    const formData = new FormData(form);
    const payload = formDataToObject(formData);
    const token = getCsrfToken();
    if (token && !payload._token) {
        payload._token = token;
    }

    const success = await queueOfflineRequest(form.action, {
        method: form.method || "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": token,
        },
        body: payload,
    });

    if (success) {
        markFormOfflineSaved(form);
    } else {
        dispatchOfflineStatus({
            title: "Action hors-ligne échouée",
            message: "Impossible d'enregistrer la demande en local.",
            icon: "ri-alert-line",
            showInstall: false,
            persistent: true,
        });
    }
});

window.addEventListener("online", async () => {
    await initOfflineData();
    await syncQueuedRequests();
});

window.addEventListener("pwa-server-reachable", async () => {
    await syncQueuedRequests();
});

window.addEventListener("load", async () => {
    await initOfflineData();
    await syncQueuedRequests();
    await refreshOfflineQueueStatus();
});
