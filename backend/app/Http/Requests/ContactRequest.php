<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation du formulaire de contact public. Les champs anti-spam sont traités dans le contrôleur. */
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
            'website' => ['nullable', 'string'], // honeypot
            '_t' => ['nullable', 'string'],      // horodatage chiffré
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Nom complet'),
            'email' => __('Email'),
            'phone' => __('Téléphone'),
            'subject' => __('Sujet'),
            'message' => __('Message'),
        ];
    }
}
