<div
    x-data="produitSearch()"
    data-produits="{{ base64_encode(json_encode($produits)) }}"
    class="relative"
>
    <input
        id="{{ $id }}"
        type="text"
        placeholder="{{ $placeholder ?? 'Rechercher un produit...' }}"
        class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-slate-800"
        autocomplete="off"
        @input.debounce.300="search($event.target.value)"
        @keydown.arrow-down="selectNext()"
        @keydown.arrow-up="selectPrev()"
        @keydown.enter="selectCurrent()"
        @keydown.escape="closeSuggestions()"
        {{ isset($required) && $required ? 'required' : '' }}
        {{ isset($attributes) ? $attributes : '' }}
    >

    <input
        id="{{ $id }}_id"
        type="hidden"
        name="{{ $fieldName }}"
        value="{{ $value ?? '' }}"
    >

    <div
        id="{{ $id }}_suggestions"
        x-show="isOpen && suggestions.length > 0"
        @click.outside="closeSuggestions()"
        class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-300 rounded-xl shadow-lg z-50 max-h-64 overflow-y-auto"
    >
        <template x-for="(item, index) in suggestions" :key="item.id">
            <div
                :class="{ 'bg-blue-100': selectedIndex === index, 'hover:bg-slate-50': selectedIndex !== index }"
                @click="selectItem(item)"
                @mouseenter="selectedIndex = index"
                class="px-4 py-3 cursor-pointer border-b border-slate-100 last:border-b-0 text-sm text-slate-800"
            >
                <div class="font-medium" x-text="item.nom"></div>
                <div class="text-xs text-slate-500 font-mono" x-show="item.reference" x-text="item.reference"></div>
            </div>
        </template>
    </div>
</div>
