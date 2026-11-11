<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation d'une demande de rendez-vous (API). Le honeypot `website` doit rester vide. */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_id' => ['required', 'integer'],
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^[0-9+().\s-]{6,32}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string'],
        ];
    }
}
