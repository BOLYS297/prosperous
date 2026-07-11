@extends('layouts.boutiquier')

@section('content')
<div class="mb-8">
    <a href="{{ route('boutiquier.dashboard') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour au point de vente
    </a>
    <h2 class="text-3xl font-bold text-white mb-2 tracking-tight">Déclarer une Perte ou Dépense</h2>
</div>

@php
    $prefill = [
        'type' => request()->query('type') ?: (request()->query('intitule') || request()->query('montant') ? 'depense' : 'perte'),
        'intitule' => request()->query('intitule', ''),
        'montant' => request()->query('montant', ''),
        'raison' => request()->query('raison', ''),
        'produit_id' => request()->query('produit_id', ''),
    ];
@endphp

<div class="glass-panel rounded-2xl p-8 max-w-3xl" x-data="depenseForm({{ json_encode($prefill) }})">
    <!-- Toggle -->
    <div class="flex p-1 bg-white/40 rounded-xl mb-8 w-fit shadow-inner border border-white/50">
        <button @click="type = 'perte'; closeCamera()" :class="type === 'perte' ? 'bg-white shadow text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-6 py-2.5 rounded-lg text-sm transition-all flex items-center">
            <i class="ri-delete-bin-line mr-2"></i> Perte de Stock
        </button>
        <button @click="type = 'depense'; closeCamera()" :class="type === 'depense' ? 'bg-white shadow text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-6 py-2.5 rounded-lg text-sm transition-all flex items-center">
            <i class="ri-money-dollar-circle-line mr-2"></i> Dépense Locale
        </button>
    </div>

    <form action="{{ route('boutiquier.depenses.store') }}" method="POST" enctype="multipart/form-data" data-offline-sync="true">
        @csrf
        <input type="hidden" name="type" x-model="type">

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulaire Perte -->
        <div x-show="type === 'perte'" x-transition.opacity>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm mb-6 flex">
                <i class="ri-information-line text-xl mr-3"></i>
                <p>En déclarant une perte, la quantité sera <strong>immédiatement déduite de votre stock local</strong>. L'administrateur sera notifié de cette perte et de la raison invoquée.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Produit concerné <span class="text-red-500">*</span></label>
                    <x-produit-search
                        id="produit_boutiquier"
                        fieldName="produit_id"
                        placeholder="Rechercher un produit..."
                        :required="false"
                        :produits="$produits"
                        @change.debounce="$watch('type', () => $refs.produitBoutiquier?.setAttribute('required', type === 'perte'))"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Quantité perdue (pièces) <span class="text-red-500">*</span></label>
                    <input type="number" name="quantite" min="1" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Ex: 2" :required="type === 'perte'">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Raison / Motif de la perte <span class="text-red-500">*</span></label>
                    <textarea name="raison" rows="4" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Ex: Produit cassé lors du déchargement..." :required="type === 'perte'">{{ old('raison', request()->query('raison')) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Photo Justificative (Webcam / Upload)</label>
                    <div class="space-y-3">
                        <input type="file" name="photo_justificatif" accept="image/*" capture="environment" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <button type="button" @click="toggleCamera('videoPerte')" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-yellow rounded-xl shadow-sm transition-colors text-sm">
                            <i class="ri-camera-line mr-2"></i> Utiliser la webcam
                        </button>
                        <p x-show="cameraError" x-text="cameraError" class="text-xs text-red-500"></p>
                        <div x-show="cameraActive" x-cloak class="space-y-3">
                                <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-900">
                                    <video x-ref="videoPerte" autoplay playsinline class="w-full h-72 object-cover"></video>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="capturePhoto()" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-yellow rounded-xl">Prendre la photo</button>
                                    <button type="button" @click="cameraActive = false; photoPreview = null; photoWebcamData = null; stopCamera()" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-yellow rounded-xl">Annuler</button>
                                </div>
                                <div x-show="photoPreview" class="rounded-2xl overflow-hidden border border-slate-200">
                                    <img :src="photoPreview" alt="Aperçu justificatif" class="w-full object-cover">
                                </div>
                                <input type="hidden" name="photo_webcam_data" x-model="photoWebcamData">
                            </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Vous pouvez télécharger un fichier ou capturer une photo avec la webcam.</p>
                </div>
            </div>
        </div>

        <!-- Formulaire Dépense -->
        <div x-show="type === 'depense'" x-transition.opacity style="display: none;">
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm mb-6 flex">
                <i class="ri-information-line text-xl mr-3"></i>
                <p>Enregistrez une dépense liée à votre boutique (frais de transport, entretien, fournitures...). Le montant sera déduit du solde après validation par l'administrateur. Une photo justificative est recommandée.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Intitulé de la dépense <span class="text-red-500">*</span></label>
                    <input type="text" name="intitule" value="{{ old('intitule', request()->query('intitule')) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Achat fournitures de nettoyage" :required="type === 'depense'">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description (optionnel)</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Détails supplémentaires sur la dépense..."></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Montant ({{ param("currency") }}) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="montant" value="{{ old('montant', request()->query('montant')) }}" class="w-full pl-4 pr-16 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: 5000" :required="type === 'depense'">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 font-medium">
                            {{ param("currency") }}
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Photo Justificative (Webcam / Upload)</label>
                    <div class="space-y-3">
                        <input type="file" name="photo_justificatif" accept="image/*" capture="environment" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <button type="button" @click="toggleCamera('videoDepense')" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-yellow rounded-xl shadow-sm transition-colors text-sm">
                            <i class="ri-camera-line mr-2"></i> Utiliser la webcam
                        </button>
                        <p x-show="cameraError" x-text="cameraError" class="text-xs text-red-500"></p>
                        <div x-show="cameraActive" x-cloak class="space-y-3">
                                <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-900">
                                    <video x-ref="videoDepense" autoplay playsinline class="w-full h-72 object-cover"></video>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="capturePhoto()" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-yellow rounded-xl">Prendre la photo</button>
                                    <button type="button" @click="cameraActive = false; photoPreview = null; photoWebcamData = null; stopCamera()" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-yellow rounded-xl">Annuler</button>
                                </div>
                                <div x-show="photoPreview" class="rounded-2xl overflow-hidden border border-slate-200">
                                    <img :src="photoPreview" alt="Aperçu justificatif" class="w-full object-cover">
                                </div>
                                <input type="hidden" name="photo_webcam_data" x-model="photoWebcamData">
                            </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Vous pouvez télécharger un fichier ou capturer une photo avec la webcam.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-white/50 pt-6">
            <button type="submit" :class="type === 'perte' ? 'from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700' : 'from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700'" class="px-8 py-3 bg-gradient-to-r text-yellow font-bold rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center">
                <i class="ri-check-line mr-2"></i> Enregistrer
            </button>
        </div>
    </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('depenseForm', (prefill = {}) => ({
            type: prefill.type || ((prefill.intitule || prefill.montant) ? 'depense' : 'perte'),
            cameraActive: false,
            currentVideoRef: null,
            cameraError: null,
            photoPreview: null,
            photoWebcamData: null,
            async startCamera() {
                this.cameraError = null;
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    this.cameraError = 'Votre navigateur ne supporte pas l’accès à la webcam.';
                    return;
                }

                const video = this.$refs[this.currentVideoRef];
                if (!video) {
                    this.cameraError = 'Impossible de trouver l’élément vidéo.';
                    return;
                }

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    video.srcObject = stream;
                    await video.play();
                } catch (error) {
                    this.cameraError = error.name === 'NotAllowedError' || error.name === 'SecurityError'
                        ? 'Accès à la webcam refusé. Autorisez la webcam dans le navigateur.'
                        : 'Impossible d’accéder à la webcam : ' + (error.message || error.name);
                    console.error(error);
                }
            },
            capturePhoto() {
                const video = this.$refs[this.currentVideoRef];
                if (!video || !video.srcObject) return;
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                this.photoPreview = dataUrl;
                this.photoWebcamData = dataUrl;
                this.stopCamera();
            },
            stopCamera() {
                const video = this.$refs[this.currentVideoRef];
                if (!video || !video.srcObject) return;
                const stream = video.srcObject;
                stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            },
            closeCamera() {
                if (this.cameraActive) {
                    this.stopCamera();
                }
                this.cameraActive = false;
                this.currentVideoRef = null;
                this.cameraError = null;
                this.photoPreview = null;
                this.photoWebcamData = null;
            },
            toggleCamera(refName) {
                if (this.cameraActive && this.currentVideoRef === refName) {
                    this.cameraActive = false;
                    this.currentVideoRef = null;
                    this.cameraError = null;
                    this.photoPreview = null;
                    this.photoWebcamData = null;
                    this.stopCamera();
                    return;
                }

                if (this.cameraActive) {
                    this.stopCamera();
                    this.photoPreview = null;
                    this.photoWebcamData = null;
                }

                this.currentVideoRef = refName;
                this.cameraActive = true;
                this.cameraError = null;
                this.$nextTick(() => this.startCamera());
            }
        }));
    });
</script>
@endsection
