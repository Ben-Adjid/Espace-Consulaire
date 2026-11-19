<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\Dependent;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Espace famille : gérer les personnes à charge du compte connecté. */
class DependentController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        return response()->json(['data' => $citizen->dependents()->get()->map(fn (Dependent $d) => $this->view($d))]);
    }

    public function store(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request, active: true);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'relation' => ['required', Rule::in(Dependent::RELATIONS)],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]);

        if (Dependent::where('citizen_id', $citizen->id)->count() >= 20) {
            return response()->json(['message' => __('Limite de personnes à charge atteinte.')], 422);
        }

        $dependent = Dependent::create($data + ['citizen_id' => $citizen->id]);

        return response()->json(['data' => $this->view($dependent)], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        Dependent::where('citizen_id', $citizen->id)->where('id', $id)->firstOrFail()->delete();

        return response()->json(['message' => __('Personne retirée de votre espace famille.')]);
    }

    private function citizen(Request $request, bool $active = false): Citizen|JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }
        if ($active && ! $citizen->isActive()) {
            return response()->json(['message' => __('Votre compte n’est pas encore actif.')], 403);
        }

        return $citizen;
    }

    private function view(Dependent $d): array
    {
        return [
            'id' => $d->id,
            'full_name' => $d->full_name,
            'relation' => $d->relation,
            'relation_label' => __('relation_'.$d->relation),
            'birth_date' => $d->birth_date?->toDateString(),
        ];
    }
}
