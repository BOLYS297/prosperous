const CACHE_NAME = "prosperous-motos-cache-v2";
const OFFLINE_URL = "/offline.html";
// Fichiers essentiels. /build/manifest.json n'est PAS ici : selon la version de
// Vite, le manifeste est à /build/manifest.json OU /build/.vite/manifest.json.
// On le gère séparément, de façon tolérante (voir MANIFEST_CANDIDATES).
const PRECACHE_URLS = ["/", OFFLINE_URL, "/logo.jpg", "/manifest.webmanifest"];
const MANIFEST_CANDIDATES = [
    "/build/.vite/manifest.json",
    "/build/manifest.json",
];

function getBuildAssetUrlsFromManifest(manifest) {
    const urls = new Set();
    for (const key in manifest) {
        const entry = manifest[key];
        if (!entry) {
            continue;
        }
        if (entry.file) {
            urls.add(`/build/${entry.file}`);
        }
        if (Array.isArray(entry.css)) {
            entry.css.forEach((cssFile) => urls.add(`/build/${cssFile}`));
        }
        if (Array.isArray(entry.imports)) {
            entry.imports.forEach((importFile) =>
                urls.add(`/build/${importFile}`),
            );
        }
        if (Array.isArray(entry.assets)) {
            entry.assets.forEach((assetFile) =>
                urls.add(`/build/${assetFile}`),
            );
        }
    }
    return Array.from(urls);
}

self.addEventListener("install", function (event) {
    event.waitUntil(
        (async function () {
            const cache = await caches.open(CACHE_NAME);

            // Pré-cache RÉSILIENT : chaque fichier est mis en cache indépendamment.
            // Une 404 (ex. un fichier absent) ne doit JAMAIS faire échouer
            // l'installation du service worker (bug historique de cache.addAll).
            await Promise.all(
                PRECACHE_URLS.map((url) => cache.add(url).catch(() => null)),
            );

            // Découverte du manifeste Vite : on essaie les deux emplacements connus.
            for (const manifestUrl of MANIFEST_CANDIDATES) {
                try {
                    const manifestResponse = await fetch(manifestUrl, {
                        cache: "no-store",
                    });
                    if (!manifestResponse || !manifestResponse.ok) {
                        continue;
                    }
                    await cache.put(manifestUrl, manifestResponse.clone());
                    const manifest = await manifestResponse.json();
                    const buildUrls = getBuildAssetUrlsFromManifest(manifest);
                    await Promise.all(
                        buildUrls.map((assetUrl) =>
                            cache.add(assetUrl).catch(() => null),
                        ),
                    );
                    break; // manifeste trouvé et traité, on s'arrête
                } catch (error) {
                    // emplacement suivant
                }
            }
        })(),
    );
    self.skipWaiting();
});

self.addEventListener("activate", function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) {
                        return key !== CACHE_NAME;
                    })
                    .map(function (key) {
                        return caches.delete(key);
                    }),
            );
        }),
    );
    self.clients.claim();
});

function networkFirst(request) {
    return fetch(request)
        .then(function (response) {
            if (
                !response ||
                response.status !== 200 ||
                response.type === "opaque"
            ) {
                return response;
            }
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(function (cache) {
                cache.put(request, responseClone);
            });
            return response;
        })
        .catch(function () {
            return caches.match(request).then(function (cachedResponse) {
                if (cachedResponse) {
                    return cachedResponse;
                }
                if (request.mode === "navigate") {
                    return caches.match(OFFLINE_URL);
                }
                return null;
            });
        });
}

function cacheFirst(request) {
    return caches.match(request).then(function (cachedResponse) {
        if (cachedResponse) {
            return cachedResponse;
        }
        return fetch(request)
            .then(function (response) {
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(function () {
                return (
                    cachedResponse ||
                    new Response(null, {
                        status: 503,
                        statusText: "Service Unavailable",
                    })
                );
            });
    });
}

self.addEventListener("fetch", function (event) {
    if (event.request.method !== "GET") {
        return;
    }

    const request = event.request;
    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;
    const isBuildAsset = isSameOrigin && url.pathname.startsWith("/build/");

    if (request.mode === "navigate") {
        event.respondWith(networkFirst(request));
        return;
    }

    if (
        request.destination === "style" ||
        request.destination === "script" ||
        request.destination === "image" ||
        request.destination === "font" ||
        isBuildAsset
    ) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (
        isSameOrigin &&
        (url.pathname.startsWith("/api") ||
            request.headers.get("accept")?.includes("application/json"))
    ) {
        event.respondWith(networkFirst(request));
    }
});
