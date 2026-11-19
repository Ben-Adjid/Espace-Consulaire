<div class="space-y-4">
    @if ($phase === 'candidacy' || $phase === 'voting')
        <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ __('Le scrutin est en cours. Les résultats ne sont jamais affichés pendant le vote : ils seront publiés automatiquement à la fermeture.') }}</p>
    @elseif (empty($rounds))
        <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ __('Le scrutin est clos. Le dépouillement est en cours : les résultats seront publiés automatiquement.') }}</p>
    @endif

    @foreach ($rounds as $round)
        <div class="rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-gray-950/5">
            <p class="font-semibold text-gray-900">{{ $round['round'] === 1 ? __('Premier tour') : __('Second tour') }}</p>
            <table class="mt-2 w-full text-sm">
                <tbody>
                    @foreach ($round['roles'] as $row)
                        <tr class="border-t border-gray-100">
                            <td class="py-1.5 text-gray-800">{{ $row['label'] }}</td>
                            <td class="py-1.5 text-gray-800">
                                @if ($row['status'] === 'elected')
                                    {{ __('Élu(e)') }} : {{ $row['winner'] }}
                                @elseif ($row['status'] === 'second_round')
                                    {{ __('Second tour') }}
                                @else
                                    {{ __('Vacant') }}
                                @endif
                            </td>
                            <td class="py-1.5 text-end tabular-nums text-gray-600">{{ trans_choice(':count voix|:count voix', $row['votes'], ['count' => $row['votes']]) }} / {{ $row['turnout'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <p class="text-xs text-gray-500">{{ __('Le détail des électeurs n’est pas affiché : seuls les totaux par candidat et par fonction sont visibles.') }}</p>
</div>
