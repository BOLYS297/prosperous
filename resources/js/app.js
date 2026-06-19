import "./bootstrap";
import "./offline-sync";
import { initProduitSearch } from "./produit-search";

import Alpine from "alpinejs";

window.Alpine = Alpine;

// Enregistrer les composants Alpine AVANT de démarrer Alpine
initProduitSearch(Alpine);

Alpine.start();

window.addEventListener("pwa-offline-status", (event) => {
    updatePwaStatus(event.detail);
});

const PWA_PING_URL = "/pwa-ping";
let pwaInstallPrompt = null;
window.__pwaServerReachable = null;
let __previousServerState = null;
let __initialCheckDone = false;

function updatePwaStatus({
    title,
    message,
    icon,
    showInstall = false,
    persistent = false,
}) {
    const statusContainer = document.getElementById("pwa-status");
    const statusText = document.getElementById("pwa-status-text");
    const statusSubtext = document.getElementById("pwa-status-subtext");
    const statusIcon = document.getElementById("pwa-status-icon");
    const installBtn = document.getElementById("pwa-install-btn");

    if (
        !statusContainer ||
        !statusText ||
        !statusSubtext ||
        !statusIcon ||
        !installBtn
    ) {
        return;
    }

    statusText.textContent = title;
    statusSubtext.textContent = message;
    statusIcon.innerHTML = `<i class="${icon} text-xl"></i>`;

    installBtn.classList.toggle("hidden", !showInstall);
    statusContainer.classList.remove("hidden");

    if (!persistent) {
        clearTimeout(window.__pwaStatusTimeout);
        window.__pwaStatusTimeout = setTimeout(() => {
            statusContainer.classList.add("hidden");
        }, 4500);
    }
}

function setPwaOnlineStatus() {
    const newState = "online";
    if (__previousServerState === newState) {
        return;
    }

    __previousServerState = newState;
    window.__pwaServerReachable = true;
    updatePwaStatus({
        title: "En ligne",
        message: "L'application est connectée.",
        icon: "ri-wifi-line",
        showInstall: false,
    });
}

function setPwaServerUnavailableStatus() {
    const newState = "unavailable";
    if (__previousServerState === newState) {
        return;
    }

    __previousServerState = newState;
    window.__pwaServerReachable = false;
    updatePwaStatus({
        title: "Serveur indisponible",
        message: "Le backend est coupé, mais l'interface peut rester en cache.",
        icon: "ri-cloud-offline-line",
        showInstall: false,
        persistent: true,
    });
}

function setPwaOfflineStatus() {
    const newState = "offline";
    if (__previousServerState === newState) {
        return;
    }

    __previousServerState = newState;
    window.__pwaServerReachable = false;
    updatePwaStatus({
        title: "Hors ligne",
        message: "Vous naviguez sans connexion réseau.",
        icon: "ri-wifi-off-line",
        showInstall: false,
        persistent: true,
    });
}

async function checkServerConnectivity() {
    if (!navigator.onLine) {
        setPwaOfflineStatus();
        return;
    }

    try {
        const response = await fetch(PWA_PING_URL, {
            method: "GET",
            cache: "no-store",
            credentials: "same-origin",
        });

        if (response.ok) {
            const wasUnavailable = window.__pwaServerReachable === false;
            setPwaOnlineStatus();
            if (wasUnavailable && __initialCheckDone) {
                window.dispatchEvent(new Event("pwa-server-reachable"));
            }
        } else {
            setPwaServerUnavailableStatus();
        }
    } catch (error) {
        setPwaServerUnavailableStatus();
    }

    __initialCheckDone = true;
}

if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker
            .register("/sw.js")
            .then((registration) => {
                console.log(
                    "Service Worker registered with scope:",
                    registration.scope,
                );
            })
            .catch((error) => {
                console.warn("Service Worker registration failed:", error);
            });

        checkServerConnectivity();
        window.__pwaConnectivityInterval = setInterval(
            checkServerConnectivity,
            20000,
        );
    });
}

window.addEventListener("online", () => {
    checkServerConnectivity();
    console.log("Application en ligne");
});

window.addEventListener("offline", () => {
    setPwaOfflineStatus();
    console.log("Application hors ligne");
});

window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    pwaInstallPrompt = event;
    updatePwaStatus({
        title: "Installer l'application",
        message: "Cliquez pour installer Prosperous Motos.",
        icon: "ri-add-circle-line",
        showInstall: true,
        persistent: true,
    });
});

window.addEventListener("appinstalled", () => {
    updatePwaStatus({
        title: "Installée",
        message: "L'application est installée sur votre appareil.",
        icon: "ri-checkbox-circle-line",
        showInstall: false,
        persistent: true,
    });
    pwaInstallPrompt = null;
});

window.addEventListener("load", () => {
    const installBtn = document.getElementById("pwa-install-btn");
    if (!installBtn) {
        return;
    }

    installBtn.addEventListener("click", async () => {
        if (!pwaInstallPrompt) {
            updatePwaStatus({
                title: "Installation indisponible",
                message: "L'installation n'est pas possible pour le moment.",
                icon: "ri-information-line",
                showInstall: false,
                persistent: true,
            });
            return;
        }

        pwaInstallPrompt.prompt();
        const choiceResult = await pwaInstallPrompt.userChoice;
        if (choiceResult.outcome === "accepted") {
            updatePwaStatus({
                title: "Installation démarrée",
                message: "Merci ! L'installation va commencer.",
                icon: "ri-check-line",
                showInstall: false,
                persistent: true,
            });
        } else {
            updatePwaStatus({
                title: "Installation refusée",
                message: "Vous pouvez installer plus tard depuis le menu.",
                icon: "ri-close-circle-line",
                showInstall: false,
                persistent: true,
            });
        }
        pwaInstallPrompt = null;
    });
});

// Toggle password visibility without jQuery (if used in the UI)
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".password");
    if (!btn) return;
    const input = document.getElementById("password");
    if (!input) return;
    input.type = input.type === "password" ? "text" : "password";
});
