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
            /** @example 550e8400-e29b-41d4-a716-446655440000 */
            'uuid' => ['required', 'uuid', 'unique:devices,uuid'],
            /** @example Sensor 01 */
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
