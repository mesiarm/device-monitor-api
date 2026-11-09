<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\StorePingRequest;
use App\Http\Resources\DeviceDetailResource;
use App\Http\Resources\DeviceListResource;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceController extends Controller
{
    /**
     * Zoznam zariadení
     *
     * Zariadenia zoradené podľa vytvorenia. Ak zariadenie nemá žiadny ping, `last_battery_percent` je `null`.
     */
    public function index(): AnonymousResourceCollection
    {
        return DeviceListResource::collection(
            Device::with('latestPing')->orderBy('id')->get()
        );
    }

    /**
     * Vytvorenie zariadenia
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $device = Device::create($request->validated());

        return DeviceResource::make($device)->response()->setStatusCode(201);
    }

    /**
     * Detail zariadenia
     *
     * Vráti posledný ping zariadenia. Ak žiadny nie je, `last_ping` aj `last_battery_percent` sú `null`.
     *
     * @param  Device  $device  UUID zariadenia (nie číselné `id`)
     */
    public function show(Device $device): DeviceDetailResource
    {
        return DeviceDetailResource::make($device->load('latestPing'));
    }

    /**
     * Uloženie pingu zariadenia
     *
     * @param  Device  $device  UUID zariadenia (nie číselné `id`)
     */
    public function ping(StorePingRequest $request, Device $device): JsonResponse
    {
        $device->pings()->create($request->validated());

        return response()->json(['status' => 'ok'], 201);
    }
}
