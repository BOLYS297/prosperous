<!-- Section Déductions en attente -->
@if($pendingDeductions->count() > 0)
<div class="mb-8 glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-slate-200 bg-slate-50">
        <div class="flex items-center gap-2">
            <i class="ri-alert-line text-2xl text-amber-600"></i>
            <h3 class="text-xl font-bold text-slate-800">Déductions en attente d'approbation</h3>
            <span class="ml-auto px-3 py-1 bg-amber-100 text-amber-700 text-sm font-bold rounded-full">{{ $pendingDeductions->count() }}</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                    <th class="p-4 text-left font-semibold">Employé</th>
                    <th class="p-4 text-left font-semibold">Type</th>
                    <th class="p-4 text-left font-semibold">Heure événement</th>
                    <th class="p-4 text-left font-semibold">Retard</th>
                    <th class="p-4 text-left font-semibold">Montant ({{ param("currency") }})</th>
                    <th class="p-4 text-center font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingDeductions as $deduction)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <div class="font-bold text-slate-800">{{ $deduction->user->nom_utilisateur }}</div>
                            <div class="text-xs text-slate-500">{{ ucfirst($deduction->user->role) }}</div>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $deduction->event_type_label }}</span>
                        </td>
                        <td class="p-4 text-slate-700">{{ $deduction->actual_event_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-sm font-semibold">
                                {{ intval($deduction->minutes_late / 60) }}h{{ $deduction->minutes_late % 60 }}min
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-lg font-bold text-rose-600">{{ number_format($deduction->amount, 0, ',', ' ') }}</span>
                        </td>
                        <td class="p-4 flex items-center justify-center gap-2">
                            <form action="{{ route('admin.deductions.approve', $deduction) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors" onclick="return confirm('Approuver cette déduction ?')">
                                    <i class="ri-check-line"></i> OK
                                </button>
                            </form>
                            <form action="{{ route('admin.deductions.reject', $deduction) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="px-3 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 transition-colors" onclick="return confirm('Rejeter cette déduction ?')">
                                    <i class="ri-close-line"></i> Rejeter
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Section Paramètres -->
