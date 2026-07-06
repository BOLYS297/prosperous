// =====================================================================
//  Recherche instantanée générique (filtre côté client, sans rechargement)
//  Utilisation (déclaratif, aucun JS à écrire par page) :
//
//    <input data-instant-search="#ma-liste"
//           data-instant-search-empty="#ma-liste-vide">   (optionnel)
//    <tbody id="ma-liste">
//        <tr data-search="texte recherchable en minuscules">...</tr>
//    </tbody>
//    <div id="ma-liste-vide" style="display:none">Aucun résultat</div>
//
//  Fonctionne pour les tableaux (<tr>) comme pour les grilles/cartes.
//  Insensible aux accents (« demarreur » trouve « Démarreur »).
// =====================================================================

function normalizeText(value) {
    return (value || "")
        .toString()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

function applyInstantFilter(input) {
    const targetSelector = input.getAttribute("data-instant-search");
    if (!targetSelector) {
        return;
    }

    // Plusieurs conteneurs possibles (séparés par des virgules), ex. deux tableaux.
    const containers = targetSelector
        .split(",")
        .map((selector) => document.querySelector(selector.trim()))
        .filter((element) => element !== null);
    if (containers.length === 0) {
        return;
    }

    const term = normalizeText(input.value.trim());
    let visible = 0;

    containers.forEach((container) => {
        container.querySelectorAll("[data-search]").forEach((item) => {
            const haystack = normalizeText(item.getAttribute("data-search"));
            const match = term === "" || haystack.includes(term);
            item.style.display = match ? "" : "none";
            if (match) {
                visible += 1;
            }
        });
    });

    const emptySelector = input.getAttribute("data-instant-search-empty");
    if (emptySelector) {
        const emptyEl = document.querySelector(emptySelector);
        if (emptyEl) {
            emptyEl.style.display = visible === 0 ? "" : "none";
        }
    }
}

function initInstantSearch() {
    document.querySelectorAll("[data-instant-search]").forEach((input) => {
        // Le formulaire GET devient un filtre en direct : Entrée ne recharge plus.
        const form = input.closest("form");
        if (form) {
            form.addEventListener("submit", (event) => {
                event.preventDefault();
                applyInstantFilter(input);
            });
        }

        input.addEventListener("input", () => applyInstantFilter(input));

        // Filtre initial (utile si le champ a une valeur pré-remplie).
        applyInstantFilter(input);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initInstantSearch);
} else {
    initInstantSearch();
}
