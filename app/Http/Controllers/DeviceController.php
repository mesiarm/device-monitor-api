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
    public function index(): AnonymousResourceCollection
    {
        return DeviceListResource::collection(
            Device::with('latestPing')->orderBy('id')->get()
        );
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $device = Device::create($request->validated());

        return DeviceResource::make($device)->response()->setStatusCode(201);
    }

    public function show(Device $device): DeviceDetailResource
    {
        return DeviceDetailResource::make($device->load('latestPing'));
    }

    public function ping(StorePingRequest $request, Device $device): JsonResponse
    {
        $device->pings()->create($request->validated());

        return response()->json(['status' => 'ok'], 201);
    }
}
