@extends('layouts.magasinier')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Recharge #{{ $recharge->id }} - {{ $recharge->fournisseur?->nom ?? '-' }}</h2>
    <p class="text-sm text-slate-500">Destination: {{ $recharge->destination?->nom ?? '-' }}</p>
</div>

@if($recharge->justificatifs->count())
    <div class="glass-panel mb-6 rounded-2xl bg-white p-6">
        <h3 class="font-semibold mb-4">Justificatifs reçus</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($recharge->justificatifs as $justificatif)
                <a href="{{ asset('storage/' . $justificatif->path) }}" target="_blank" class="block overflow-hidden rounded border bg-slate-50">
                    <img src="{{ asset('storage/' . $justificatif->path) }}" alt="Justificatif #{{ $justificatif->id }}" class="h-28 w-full object-cover">
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="glass-panel p-6 rounded-2xl bg-white mb-6">
    <h3 class="font-semibold mb-4">Lignes</h3>

    @if($recharge->statut !== 'en_attente')
        <div class="mb-2 rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm flex items-center">
            <i class="ri-information-line text-lg mr-2"></i>
            Cette recharge a déjà été traitée (statut : <strong class="mx-1">{{ str_replace('_', ' ', $recharge->statut) }}</strong>). Aucune nouvelle action n'est possible.
        </div>
    @else

    <div class="mb-6 p-4 border rounded bg-slate-50">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button id="start-camera" type="button" class="px-3 py-2 bg-blue-600 text-black rounded">Ouvrir webcam</button>
                <button id="take-photo" type="button" class="px-3 py-2 bg-indigo-600 text-black rounded" disabled>Prendre photo</button>
                <button id="stop-camera" type="button" class="px-3 py-2 bg-slate-500 text-black rounded" disabled>Arrêter</button>
                <span id="camera-status" class="text-sm text-slate-500">Caméra inactive.</span>
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <video id="camera-stream" autoplay playsinline class="w-full rounded border bg-black hidden"></video>
                <div id="camera-preview" class="grid grid-cols-2 gap-3"></div>
            </div>
            <p class="text-xs text-slate-500">La capture webcam est envoyée avec le formulaire sous forme d'image. Vous pouvez prendre plusieurs photos.</p>
        </div>
    </div>

    <form id="confirm-form" action="{{ route('magasinier.recharges.confirmer', $recharge) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="space-y-3">
            @foreach($recharge->lignes as $ligne)
                <div class="p-3 border rounded flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $ligne->produit->nom }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif</div>
                        <div class="text-sm text-slate-500">Qté attendue: {{ $ligne->quantite_envoyee }}</div>
                    </div>
                    <div class="w-48">
                        <label class="block text-xs text-slate-500">Qté reçue</label>
                        <input type="hidden" name="lignes[{{ $loop->index }}][quantite_recue]" value="{{ $ligne->quantite_envoyee }}">
                        <input type="number" value="{{ $ligne->quantite_envoyee }}" min="{{ $ligne->quantite_envoyee }}" max="{{ $ligne->quantite_envoyee }}" class="w-full px-2 py-1 border rounded bg-slate-100" readonly>
                        <input type="hidden" name="lignes[{{ $loop->index }}][id]" value="{{ $ligne->id }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <label class="block text-sm">Justificatifs (photos)</label>
            <input type="file" name="justificatifs[]" multiple accept="image/*" class="mt-2">
            <div class="captured-image-inputs"></div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-black rounded">Confirmer réception</button>
        </div>
    </form>

    <hr class="my-4">

    <form id="probleme" action="{{ route('magasinier.recharges.probleme', $recharge) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="space-y-4 mb-4">
            <h3 class="font-semibold text-lg">Signalement d'anomalie par produit</h3>
            <p class="text-sm text-slate-500">Cochez seulement le(s) produit(s) en anomalie et indiquez la quantité réellement reçue. Les autres produits seront considérés comme reçus normalement.</p>
            @foreach($recharge->lignes as $ligne)
                @php
                    $oldQuantite = old("lignes.{$loop->index}.quantite_recue", $ligne->quantite_recue ?? $ligne->quantite_envoyee);
                    $isAnomalie = $oldQuantite < $ligne->quantite_envoyee;
                @endphp
                <div class="p-3 border rounded">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="font-medium">{{ $ligne->produit->nom }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif</div>
                            <div class="text-sm text-slate-500">Qté attendue: {{ $ligne->quantite_envoyee }}</div>
                        </div>
                        <div class="flex flex-col gap-2 w-full lg:w-96">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" class="anomalie-toggle" data-index="{{ $loop->index }}" {{ $isAnomalie ? 'checked' : '' }}>
                                Signaler une anomalie sur ce produit
                            </label>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="hidden" name="lignes[{{ $loop->index }}][id]" value="{{ $ligne->id }}">
                                <input type="hidden" name="lignes[{{ $loop->index }}][quantite_recue]" class="quantite-recue-hidden" value="{{ $oldQuantite }}">
                                <div>
                                    <label class="block text-xs text-slate-500">Qté reçue réelle</label>
                                    <input type="number" class="quantite-recue-input w-full px-2 py-1 border rounded {{ $isAnomalie ? 'bg-white' : 'bg-slate-100' }}" value="{{ $oldQuantite }}" min="0" max="{{ $ligne->quantite_envoyee }}" {{ $isAnomalie ? '' : 'disabled' }} data-expected="{{ $ligne->quantite_envoyee }}">
                                </div>
                                <p class="text-xs text-slate-500 anomaly-note {{ $isAnomalie ? '' : 'hidden' }}">La quantité renseignée sera prise en compte pour cette ligne.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>
            <label class="block text-sm">Décrire le problème</label>
            <textarea name="message" class="w-full mt-2 p-2 border rounded" required>{{ old('message') }}</textarea>
        </div>
        <div class="mt-2">
            <label class="block text-sm">Justificatifs (photos)</label>
            <input type="file" name="justificatifs[]" multiple accept="image/*" class="mt-2">
            <div class="captured-image-inputs"></div>
        </div>
        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-rose-600 text-black rounded">Signaler un problème</button>
        </div>
    </form>
    @endif
</div>

<script>
    const startCamera = document.getElementById('start-camera');
    const takePhoto = document.getElementById('take-photo');
    const stopCamera = document.getElementById('stop-camera');
    const cameraStream = document.getElementById('camera-stream');
    const cameraPreview = document.getElementById('camera-preview');
    const cameraStatus = document.getElementById('camera-status');
    const formContainers = document.querySelectorAll('.captured-image-inputs');
    let mediaStream = null;

    function updateCameraButtons(active) {
        startCamera.disabled = active;
        takePhoto.disabled = !active;
        stopCamera.disabled = !active;
        cameraStream.classList.toggle('hidden', !active);
        cameraStatus.textContent = active ? 'Caméra active.' : 'Caméra inactive.';
    }

    function addCapturedImageInput(dataUrl) {
        const inputs = [];
        formContainers.forEach(container => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'captured_images[]';
            input.value = dataUrl;
            container.appendChild(input);
            inputs.push(input);
        });
        return inputs;
    }

    function addPhotoThumbnail(dataUrl) {
        const inputs = addCapturedImageInput(dataUrl);
        const wrapper = document.createElement('div');
        wrapper.className = 'relative rounded overflow-hidden border';
        wrapper.innerHTML = `
            <img src="${dataUrl}" alt="Photo" class="w-full h-32 object-cover" />
            <button type="button" class="absolute top-1 right-1 bg-white/90 rounded-full p-1 text-slate-700 hover:text-slate-900" aria-label="Supprimer">
                ×
            </button>
        `;
        const button = wrapper.querySelector('button');
        button.addEventListener('click', () => {
            inputs.forEach(input => input.remove());
            wrapper.remove();
        });
        cameraPreview.appendChild(wrapper);
    }

    startCamera.addEventListener('click', async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Votre navigateur ne supporte pas la capture webcam.');
            return;
        }

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({ video: true });
            cameraStream.srcObject = mediaStream;
            updateCameraButtons(true);
        } catch (error) {
            console.error(error);
            alert('Impossible d\'accéder à la webcam. Vérifiez les permissions.');
        }
    });

    stopCamera.addEventListener('click', () => {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
        updateCameraButtons(false);
    });

    takePhoto.addEventListener('click', () => {
        if (!mediaStream) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = cameraStream.videoWidth;
        canvas.height = cameraStream.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(cameraStream, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        addCapturedImageInput(dataUrl);
        addPhotoThumbnail(dataUrl);
    });

    function updateAnomalieLine(lineIndex, checked) {
        const lineWrapper = document.querySelector(`.anomalie-toggle[data-index="${lineIndex}"]`).closest('.p-3');
        const hiddenInput = lineWrapper.querySelector('.quantite-recue-hidden');
        const visibleInput = lineWrapper.querySelector('.quantite-recue-input');
        const note = lineWrapper.querySelector('.anomaly-note');
        const expected = parseInt(visibleInput.dataset.expected, 10);

        if (checked) {
            visibleInput.disabled = false;
            visibleInput.classList.remove('bg-slate-100');
            visibleInput.classList.add('bg-white');
            note.classList.remove('hidden');
            hiddenInput.value = visibleInput.value;
        } else {
            visibleInput.disabled = true;
            visibleInput.value = expected;
            hiddenInput.value = expected;
            visibleInput.classList.remove('bg-white');
            visibleInput.classList.add('bg-slate-100');
            note.classList.add('hidden');
        }
    }

    document.querySelectorAll('.anomalie-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', event => {
            updateAnomalieLine(event.target.dataset.index, event.target.checked);
        });
    });

    document.querySelectorAll('.quantite-recue-input').forEach(input => {
        input.addEventListener('input', event => {
            const wrapper = event.target.closest('.p-3');
            const hiddenInput = wrapper.querySelector('.quantite-recue-hidden');
            hiddenInput.value = event.target.value;
        });
    });

    window.addEventListener('beforeunload', () => {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
        }
    });
</script>

@endsection
