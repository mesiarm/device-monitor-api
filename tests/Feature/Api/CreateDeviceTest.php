<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDeviceTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_device_can_be_created(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'Sensor 01'])
            ->assertCreated()
            ->assertExactJson(['id' => 1, 'uuid' => self::UUID, 'name' => 'Sensor 01']);

        $this->assertDatabaseHas('devices', ['uuid' => self::UUID, 'name' => 'Sensor 01']);
    }

    public function test_response_contains_only_documented_fields(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'Sensor 01'])
            ->assertJsonMissingPath('created_at')
            ->assertJsonMissingPath('updated_at')
            ->assertJsonMissingPath('data');
    }

    public function test_timestamps_are_stored(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'Sensor 01']);

        $device = Device::firstOrFail();
        $this->assertNotNull($device->created_at);
        $this->assertNotNull($device->updated_at);
    }

    public function test_multiple_devices_get_incrementing_ids(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'A'])->assertJsonPath('id', 1);
        $this->postJson('/api/devices', ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8', 'name' => 'B'])
            ->assertJsonPath('id', 2);

        $this->assertDatabaseCount('devices', 2);
    }

    public function test_name_is_trimmed(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => '  Sensor 01  '])
            ->assertCreated()
            ->assertJsonPath('name', 'Sensor 01');
    }

    public function test_name_with_maximum_length_is_accepted(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => str_repeat('a', 255)])
            ->assertCreated();
    }

    public function test_empty_body_is_rejected(): void
    {
        $this->postJson('/api/devices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid', 'name']);

        $this->assertDatabaseCount('devices', 0);
    }

    public function test_uuid_is_required(): void
    {
        $this->postJson('/api/devices', ['name' => 'Sensor 01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid'])
            ->assertJsonMissingValidationErrors(['name']);
    }

    public function test_name_is_required(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name'])
            ->assertJsonMissingValidationErrors(['uuid']);
    }

    public function test_empty_strings_are_rejected(): void
    {
        $this->postJson('/api/devices', ['uuid' => '', 'name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid', 'name']);
    }

    public function test_invalid_uuid_is_rejected(): void
    {
        foreach (['not-a-uuid', '12345', '550e8400-e29b-41d4-a716', '550e8400e29b41d4a716446655440000x', 123, true] as $value) {
            $this->postJson('/api/devices', ['uuid' => $value, 'name' => 'Sensor'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['uuid']);
        }

        $this->assertDatabaseCount('devices', 0);
    }

    public function test_name_must_be_a_string(): void
    {
        foreach ([123, true, ['a'], ['x' => 'y']] as $value) {
            $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => $value])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        }
    }

    public function test_name_longer_than_255_characters_is_rejected(): void
    {
        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_duplicate_uuid_is_rejected(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'Duplicate'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid']);

        $this->assertDatabaseCount('devices', 1);
    }

    public function test_duplicate_name_is_allowed(): void
    {
        Device::factory()->create(['name' => 'Sensor 01']);

        $this->postJson('/api/devices', ['uuid' => self::UUID, 'name' => 'Sensor 01'])
            ->assertCreated();
    }

    public function test_id_in_request_is_ignored(): void
    {
        $this->postJson('/api/devices', ['id' => 999, 'uuid' => self::UUID, 'name' => 'Sensor 01'])
            ->assertCreated()
            ->assertJsonPath('id', 1);
    }

    public function test_validation_errors_are_json_without_accept_header(): void
    {
        $this->post('/api/devices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid', 'name']);
    }
}
