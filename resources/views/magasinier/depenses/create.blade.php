@extends('layouts.magasinier')

@section('content')
<div class="mb-8">
    <a href="{{ route('magasinier.dashboard') }}" class="text-emerald-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour au tableau de bord
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Déclarer une perte de stock</h2>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-3xl">
    <form action="{{ route('magasinier.depenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="perte">

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm mb-6 flex">
                <i class="ri-information-line text-xl mr-3"></i>
                <p>En déclarant une perte, la quantité sera <strong>immédiatement déduite de votre stock local</strong>. L'administrateur sera notifié et pourra valider ou rejeter la perte.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Produit concerné <span class="text-red-500">*</span></label>
                    <x-produit-search
                        id="produit_magasinier"
                        fieldName="produit_id"
                        placeholder="Rechercher un produit..."
                        :produits="$produits"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Quantité perdue (pièces) <span class="text-red-500">*</span></label>
                    <input type="number" name="quantite" min="1" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Ex: 2" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Raison / Motif de la perte <span class="text-red-500">*</span></label>
                    <textarea name="raison" rows="4" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Ex: Produit cassé lors du déchargement..." required></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Preuve photo / webcam</label>
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-2">
                            <input type="file" name="photo_justificatif" accept="image/*" capture="environment" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-emerald-500 outline-none text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                            <p class="text-xs text-slate-500">Importez une photo existante ou utilisez la caméra du terminal si disponible.</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                            <div class="flex flex-wrap gap-2 items-center mb-4">
                                <button id="start-camera" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-xl shadow-sm">Ouvrir webcam</button>
                                <button id="stop-camera" type="button" class="px-4 py-2 bg-slate-500 text-white rounded-xl shadow-sm" disabled>Arrêter</button>
                                <span id="camera-status" class="text-sm text-slate-500">Caméra inactive.</span>
                            </div>
                            <video id="camera-stream" autoplay playsinline class="w-full rounded-xl bg-black hidden"></video>
                            <div id="camera-preview" class="grid grid-cols-2 gap-3 mt-4"></div>
                            <input type="hidden" name="photo_webcam_data" id="photo_webcam_data">
                            <p class="text-xs text-slate-500 mt-3">Vous pouvez prendre une photo depuis la webcam qui sera transmise avec le signalement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-white/50 pt-6">
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center">
                <i class="ri-check-line mr-2"></i> Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
        const startCamera = document.getElementById('start-camera');
        const stopCamera = document.getElementById('stop-camera');
        const cameraStream = document.getElementById('camera-stream');
        const cameraPreview = document.getElementById('camera-preview');
        const cameraStatus = document.getElementById('camera-status');
        const photoWebcamData = document.getElementById('photo_webcam_data');
        let mediaStream = null;

        function updateCameraButtons(active) {
            startCamera.disabled = active;
            stopCamera.disabled = !active;
            cameraStream.classList.toggle('hidden', !active);
            cameraStatus.textContent = active ? 'Caméra active.' : 'Caméra inactive.';
        }

        async function startCameraStream() {
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                cameraStream.srcObject = mediaStream;
                updateCameraButtons(true);
            } catch (error) {
                alert('Impossible d\'accéder à la webcam. Vérifiez les permissions et que votre appareil dispose d\'une caméra.');
            }
        }

        function stopCameraStream() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            updateCameraButtons(false);
        }

        function captureCameraPhoto() {
            if (!mediaStream) {
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = cameraStream.videoWidth;
            canvas.height = cameraStream.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(cameraStream, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

            const wrapper = document.createElement('div');
            wrapper.className = 'relative rounded-xl overflow-hidden border border-slate-200';
            wrapper.innerHTML = `
                <img src="${dataUrl}" class="w-full object-cover h-36" alt="Capture webcam">
                <button type="button" class="absolute top-2 right-2 px-2 py-1 bg-rose-600 text-white rounded-md text-xs">Supprimer</button>
            `;

            wrapper.querySelector('button').addEventListener('click', () => {
                wrapper.remove();
                if (cameraPreview.children.length === 0) {
                    photoWebcamData.value = '';
                }
            });

            cameraPreview.innerHTML = '';
            cameraPreview.appendChild(wrapper);
            photoWebcamData.value = dataUrl;
        }

        if (startCamera && stopCamera && cameraStream) {
            startCamera.addEventListener('click', async () => {
                await startCameraStream();
                setTimeout(() => captureCameraPhoto(), 1000);
            });

            stopCamera.addEventListener('click', () => {
                stopCameraStream();
            });

            cameraStream.addEventListener('click', captureCameraPhoto);
        }
    </script>
@endsection
