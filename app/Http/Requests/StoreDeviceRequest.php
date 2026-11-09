<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
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
            'uuid' => ['required', 'uuid', 'unique:devices,uuid'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
