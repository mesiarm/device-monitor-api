<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'battery_percent' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
