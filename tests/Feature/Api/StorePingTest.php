<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Ping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePingTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    private function ping(mixed $battery): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/devices/'.self::UUID.'/ping', ['battery_percent' => $battery]);
    }

    public function test_ping_can_be_stored(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);

        $this->ping(85)->assertCreated()->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseHas('pings', ['device_id' => $device->id, 'battery_percent' => 85]);
    }

    public function test_ping_stores_creation_time(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->freezeTime();
        $this->ping(50)->assertCreated();

        $this->assertEquals(now()->toDateTimeString(), Ping::firstOrFail()->created_at->toDateTimeString());
    }

    public function test_boundary_values_are_accepted(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->ping(0)->assertCreated();
        $this->ping(100)->assertCreated();

        $this->assertDatabaseCount('pings', 2);
    }

    public function test_device_can_have_many_pings(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);

        $this->ping(90)->assertCreated();
        $this->ping(80)->assertCreated();
        $this->ping(70)->assertCreated();

        $this->assertSame(3, $device->pings()->count());
    }

    public function test_ping_is_stored_for_the_correct_device_only(): void
    {
        $target = Device::factory()->create(['uuid' => self::UUID]);
        $other = Device::factory()->create();

        $this->ping(42)->assertCreated();

        $this->assertSame(1, $target->pings()->count());
        $this->assertSame(0, $other->pings()->count());
    }

    public function test_battery_percent_is_required(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->postJson('/api/devices/'.self::UUID.'/ping', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['battery_percent']);

        $this->assertDatabaseCount('pings', 0);
    }

    public function test_invalid_battery_values_are_rejected(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        foreach ([null, '', 'abc', -1, 101, 1000, 50.5, true, [85], '85%', '85'] as $value) {
            $this->ping($value)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['battery_percent']);
        }

        $this->assertDatabaseCount('pings', 0);
    }

    public function test_ping_for_unknown_device_returns_not_found(): void
    {
        $this->ping(85)->assertNotFound();

        $this->assertDatabaseCount('pings', 0);
    }

    public function test_ping_cannot_target_device_by_numeric_id(): void
    {
        $device = Device::factory()->create();

        $this->postJson('/api/devices/'.$device->id.'/ping', ['battery_percent' => 50])
            ->assertNotFound();
    }

    public function test_ping_does_not_create_or_change_devices(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID, 'name' => 'Sensor 01']);

        $this->ping(85)->assertCreated();

        $this->assertDatabaseCount('devices', 1);
        $this->assertSame('Sensor 01', $device->fresh()->name);
    }

    public function test_extra_fields_in_ping_request_are_ignored(): void
    {
        $device = Device::factory()->create(['uuid' => self::UUID]);

        $this->postJson('/api/devices/'.self::UUID.'/ping', [
            'battery_percent' => 60,
            'device_id' => 9999,
            'created_at' => '2000-01-01 00:00:00',
        ])->assertCreated();

        $ping = Ping::firstOrFail();
        $this->assertSame($device->id, $ping->device_id);
        $this->assertTrue($ping->created_at->isCurrentYear());
    }

    public function test_ping_requires_post_method(): void
    {
        Device::factory()->create(['uuid' => self::UUID]);

        $this->getJson('/api/devices/'.self::UUID.'/ping')->assertStatus(405);
    }
}
