<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation du formulaire de contact (API). Honeypot `website` et horodatage `_t` traités dans le contrôleur. */
class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+().\s-]{6,32}$/'],
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string'],
            '_t' => ['nullable', 'string'],
        ];
    }
}
