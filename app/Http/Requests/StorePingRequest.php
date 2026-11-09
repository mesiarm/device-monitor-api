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
            /** @example 85 */
            'battery_percent' => ['required', 'integer:strict', 'between:0,100'],
        ];
    }
}
