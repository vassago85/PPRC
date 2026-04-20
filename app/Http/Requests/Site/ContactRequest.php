<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'min:2', 'max:120'],
            'email'   => ['required', 'email:rfc,dns', 'max:255'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],

            // Honeypot — must be empty. Real users don't see this field.
            'website' => ['nullable', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'     => 'Please enter a valid email address.',
            'message.min'     => 'Please share a little more detail so we can help.',
            'website.size'    => 'Submission blocked.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'    => 'your name',
            'email'   => 'your email',
            'subject' => 'subject',
            'message' => 'message',
        ];
    }

    /**
     * Fail silently on honeypot hits — don't let bots learn they were caught.
     * We still abort the request, just without showing the field error.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('website')) {
            abort(422, 'Submission blocked.');
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag ?? 'default')
            ->redirectTo($this->getRedirectUrl());
    }
}
