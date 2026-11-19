<x-filament-panels::page>
    <div class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ __('Dossiers à décider') }}</p>
                <p class="text-3xl font-bold">{{ $requests->count() }}</p>
            </div>
            <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ __('Messages non lus') }}</p>
                <p class="text-3xl font-bold">{{ $messages->count() }}</p>
            </div>
            @if ($appointmentsWaiting !== null)
                <div class="rounded-xl border bg-white p-4 dark:bg-gray-900">
                    <p class="text-sm text-gray-500">{{ __('Rendez-vous du jour non arrivés') }}</p>
                    <p class="text-3xl font-bold">{{ $appointmentsWaiting }}</p>
                </div>
            @endif
        </div>

        @if ($requests->isNotEmpty())
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ __('Dossiers à décider') }}</h2>
                    <a href="{{ $requestsUrl }}" class="text-sm font-semibold text-primary-600 hover:underline">{{ __('Ouvrir les pré-demandes') }}</a>
                </div>
                <div class="overflow-x-auto rounded-xl border bg-white dark:bg-gray-900">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="p-3">{{ __('N°') }}</th>
                                <th class="p-3">{{ __('Citoyen') }}</th>
                                <th class="p-3">{{ __('Service') }}</th>
                                <th class="p-3">{{ __('Soumise le') }}</th>
                                <th class="p-3">{{ __('Attribué à') }}</th>
                                <th class="p-3">{{ __('Alerte') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $row)
                                @php($p = $row['model'])
                                <tr class="border-t">
                                    <td class="p-3">#{{ $p->id }}</td>
                                    <td class="p-3">{{ $p->citizen?->full_name }}</td>
                                    <td class="p-3">{{ $p->service?->name }}</td>
                                    <td class="p-3">{{ $p->submitted_at?->format('d/m/Y H:i') }}</td>
                                    <td class="p-3">{{ $p->assignee?->name ?? __('Non attribué') }}</td>
                                    <td class="p-3">
                                        @if ($row['overdue'])
                                            <span class="font-semibold text-danger-600">{{ __('En retard') }}</span>
                                        @elseif ($p->is_urgent)
                                            <span class="font-semibold text-warning-600">{{ __('Urgent') }}</span>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-3 text-gray-500" colspan="6">{{ __('Aucun dossier à décider.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($messages->isNotEmpty())
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ __('Messages non lus') }}</h2>
                    @if ($messagesUrl)
                        <a href="{{ $messagesUrl }}" class="text-sm font-semibold text-primary-600 hover:underline">{{ __('Ouvrir la messagerie') }}</a>
                    @endif
                </div>
                <ul class="divide-y rounded-xl border bg-white dark:bg-gray-900">
                    @foreach ($messages as $c)
                        <li class="p-3 text-sm">
                            <span class="font-semibold">{{ $c->citizen?->full_name }}</span>
                            · {{ $c->subject }}
                            <span class="text-gray-500">· {{ $c->last_message_at?->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-filament-panels::page>
