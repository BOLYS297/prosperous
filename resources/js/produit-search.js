// Composant Alpine pour la recherche de produits avec autocomplete
export function initProduitSearch(Alpine) {
    Alpine.data("produitSearch", () => ({
        produits: [],
        isOpen: false,
        suggestions: [],
        selectedIndex: -1,
        currentValue: "",
        componentId: null,

        init() {
            // Charger les produits depuis l'attribut data (encodé en base64)
            const produitsBase64 = this.$el.getAttribute("data-produits");
            if (produitsBase64) {
                try {
                    const produitsJson = atob(produitsBase64);
                    this.produits = JSON.parse(produitsJson);
                    console.log(
                        "Produits chargés:",
                        this.produits.length,
                        "produits",
                    );
                } catch (error) {
                    console.error(
                        "Erreur lors du parsing des produits:",
                        error,
                    );
                    this.produits = [];
                }
            }

            // Store the text input ID for later use
            const textInput = this.$el.querySelector('input[type="text"]');
            if (textInput) {
                this.componentId = textInput.id;
                console.log("Component initialized with ID:", this.componentId);
            }
        },

        search(value) {
            this.currentValue = value;
            this.selectedIndex = -1;

            if (!value || value.length < 1) {
                this.suggestions = [];
                this.isOpen = false;
                return;
            }

            const queryLower = value.toLowerCase();
            this.suggestions = this.produits
                .filter(
                    (p) =>
                        p.nom.toLowerCase().includes(queryLower) ||
                        (p.reference &&
                            p.reference.toLowerCase().includes(queryLower)),
                )
                .map((p) => ({
                    id: p.id,
                    nom: p.nom,
                    reference: p.reference,
                    label: p.reference ? `${p.nom} (${p.reference})` : p.nom,
                }))
                .slice(0, 20);

            this.isOpen = this.suggestions.length > 0;
            console.log("Suggestions trouvées:", this.suggestions.length);
        },

        selectCurrent() {
            if (
                this.selectedIndex >= 0 &&
                this.selectedIndex < this.suggestions.length &&
                this.suggestions[this.selectedIndex]
            ) {
                this.selectItem(this.suggestions[this.selectedIndex]);
            }
        },

        selectNext() {
            if (this.isOpen) {
                this.selectedIndex = Math.min(
                    this.selectedIndex + 1,
                    this.suggestions.length - 1,
                );
            }
        },

        selectPrev() {
            if (this.isOpen) {
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            }
        },

        selectItem(item) {
            if (!this.componentId) {
                console.error("selectItem - componentId not set!");
                return;
            }

            // Use the stored component ID to find inputs
            const textInput = document.getElementById(this.componentId);
            const hiddenInput = document.getElementById(
                this.componentId + "_id",
            );

            console.log("selectItem - item:", item);
            console.log("selectItem - componentId:", this.componentId);
            console.log("selectItem - textInput found:", !!textInput);
            console.log("selectItem - hiddenInput found:", !!hiddenInput);

            if (textInput && hiddenInput && item) {
                textInput.value = item.label || item.nom;
                hiddenInput.value = item.id;

                // Déclencher les événements pour mettre à jour Alpine et les listeners
                textInput.dispatchEvent(new Event("input", { bubbles: true }));
                textInput.dispatchEvent(new Event("change", { bubbles: true }));
                hiddenInput.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );

                console.log(
                    "Champs mis à jour - textInput.value:",
                    textInput.value,
                    "hiddenInput.value:",
                    hiddenInput.value,
                );
            } else {
                console.error("selectItem - missing elements", {
                    textInput: !!textInput,
                    hiddenInput: !!hiddenInput,
                    item: !!item,
                });
            }

            // Fermer la dropdown et réinitialiser
            this.isOpen = false;
            this.suggestions = [];
            this.currentValue = "";
            this.selectedIndex = -1;
        },

        closeSuggestions() {
            this.isOpen = false;
        },
    }));
}
