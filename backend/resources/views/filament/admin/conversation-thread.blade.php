<x-filament-panels::page>
    @php($conversation = $this->record)
    <div class="space-y-4">
        <div class="rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-gray-950/5">
            <p class="font-semibold text-gray-900">{{ $conversation->citizen?->full_name }} · <span class="font-normal text-gray-500">{{ $conversation->citizen?->email }}</span></p>
            <p class="mt-1 text-gray-600">
                {{ __('Statut') }} : <strong>{{ $this->statusLabel() }}</strong>
                @if ($conversation->pre_request_id)
                    · {{ __('Dossier') }} #{{ $conversation->pre_request_id }}
                @endif
            </p>
        </div>

        <ol class="space-y-3">
            @foreach ($this->threadMessages() as $message)
                @php($fromStaff = $message->sender === 'staff')
                <li style="display:flex;justify-content:{{ $fromStaff ? 'flex-end' : 'flex-start' }};">
                    {{-- Couleurs en style direct : le thème Filament compilé n'inclut pas toutes les classes utilitaires. --}}
                    <div class="rounded-xl p-3 text-sm shadow-sm" style="max-width:42rem;border:1px solid {{ $fromStaff ? 'rgb(var(--primary-200))' : 'rgb(226 232 240)' }};background:{{ $fromStaff ? 'rgb(var(--primary-50))' : '#fff' }};">
                        <p class="text-xs font-semibold text-gray-500">
                            {{ $fromStaff ? __('Consulat').($message->user ? ' ('.$message->user->name.')' : '') : $conversation->citizen?->full_name }}
                            · {{ $message->created_at?->format('d/m/Y H:i') }}
                        </p>
                        <p class="mt-1 whitespace-pre-line text-gray-900">{{ $message->body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-filament-panels::page>
