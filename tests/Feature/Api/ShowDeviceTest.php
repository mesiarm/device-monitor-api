<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Ping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowDeviceTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_detail_returns_last_ping(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID, 'name' => 'Sensor 01']);
        Ping::factory()->for($device)->create(['battery_percent' => 90, 'created_at' => '2026-05-26 09:00:00']);
        Ping::factory()->for($device)->create(['battery_percent' => 85, 'created_at' => '2026-05-26 10:00:00']);

        $this->getJson('/api/devices/'.self::UUID)
            ->assertOk()
            ->assertExactJson([
                'uuid' => self::UUID,
                'name' => 'Sensor 01',
                'last_ping' => '2026-05-26T10:00:00Z',
                'last_battery_percent' => 85,
            ]);
    }

    public function test_detail_uses_newest_ping_regardless_of_insert_order(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);
        Ping::factory()->for($device)->create(['battery_percent' => 85, 'created_at' => '2026-05-26 10:00:00']);
        Ping::factory()->for($device)->create(['battery_percent' => 90, 'created_at' => '2026-05-26 09:00:00']);

        $this->getJson('/api/devices/'.self::UUID)
            ->assertJsonPath('last_battery_percent', 85)
            ->assertJsonPath('last_ping', '2026-05-26T10:00:00Z');
    }

    public function test_detail_without_pings_has_null_values(): void
    {
        Device::factory()->create(['uuid' => self::UUID, 'name' => 'Sensor 01']);

        $this->getJson('/api/devices/'.self::UUID)
            ->assertOk()
            ->assertExactJson([
                'uuid' => self::UUID,
                'name' => 'Sensor 01',
                'last_ping' => null,
                'last_battery_percent' => null,
            ]);
    }

    public function test_detail_ignores_pings_of_other_devices(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);
        Ping::factory()->create(['battery_percent' => 10]);

        $this->getJson('/api/devices/'.self::UUID)
            ->assertJsonPath('last_battery_percent', null);
    }

    public function test_last_battery_percent_zero_is_not_treated_as_missing(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);
        Ping::factory()->for($device)->create(['battery_percent' => 0]);

        $this->getJson('/api/devices/'.self::UUID)
            ->assertJsonPath('last_battery_percent', 0);
    }

    public function test_last_ping_is_iso8601_utc(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);
        Ping::factory()->for($device)->create();

        $this->getJson('/api/devices/'.self::UUID)
            ->assertJsonPath('last_ping', fn (string $value) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value));
    }

    public function test_detail_reflects_a_ping_sent_through_the_api(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->postJson('/api/devices/'.self::UUID.'/ping', ['battery_percent' => 33])->assertCreated();

        $this->getJson('/api/devices/'.self::UUID)
            ->assertOk()
            ->assertJsonPath('last_battery_percent', 33)
            ->assertJsonMissingPath('id');
    }

    public function test_unknown_device_returns_not_found(): void
    {
        $this->getJson('/api/devices/'.self::UUID)->assertNotFound();
    }

    public function test_numeric_id_is_not_a_valid_identifier(): void
    {
        $device = Device::factory()->create();

        $this->getJson('/api/devices/'.$device->id)->assertNotFound();
    }

    public function test_not_found_response_is_json(): void
    {
        $this->get('/api/devices/'.self::UUID)
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
