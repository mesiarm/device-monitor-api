<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Ping;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_has_many_pings(): void
    {
        $device = Device::factory()->create();
        Ping::factory()->for($device)->count(3)->create();

        $this->assertInstanceOf(HasMany::class, $device->pings());
        $this->assertCount(3, $device->pings);
    }

    public function test_ping_belongs_to_device(): void
    {
        $ping = Ping::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $ping->device());
        $this->assertInstanceOf(Device::class, $ping->device);
    }

    public function test_latest_ping_returns_newest_ping(): void
    {
        $device = Device::factory()->create();
        Ping::factory()->for($device)->create(['battery_percent' => 10, 'created_at' => '2026-01-01 10:00:00']);
        Ping::factory()->for($device)->create(['battery_percent' => 20, 'created_at' => '2026-01-02 10:00:00']);

        $this->assertSame(20, $device->latestPing->battery_percent);
    }

    public function test_latest_ping_is_null_without_pings(): void
    {
        $this->assertNull(Device::factory()->create()->latestPing);
    }

    public function test_device_route_key_is_uuid(): void
    {
        $this->assertSame('uuid', (new Device)->getRouteKeyName());
    }

    public function test_ping_has_no_updated_at_column(): void
    {
        $ping = Ping::factory()->create();

        $this->assertNull(Ping::UPDATED_AT);
        $this->assertArrayNotHasKey('updated_at', $ping->getAttributes());
    }

    public function test_uuid_column_is_unique_in_database(): void
    {
        Device::factory()->create(['uuid' => '550e8400-e29b-41d4-a716-446655440000']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Device::factory()->create(['uuid' => '550e8400-e29b-41d4-a716-446655440000']);
    }

    public function test_deleting_device_removes_its_pings(): void
    {
        $device = Device::factory()->create();
        Ping::factory()->for($device)->count(2)->create();
        $other = Ping::factory()->create();

        $device->delete();

        $this->assertDatabaseCount('pings', 1);
        $this->assertDatabaseHas('pings', ['id' => $other->id]);
    }

    public function test_device_mass_assignment_only_allows_uuid_and_name(): void
    {
        $device = Device::create(['id' => 50, 'uuid' => '550e8400-e29b-41d4-a716-446655440000', 'name' => 'S']);

        $this->assertNotSame(50, $device->id);
    }

    public function test_factories_produce_valid_records(): void
    {
        $device = Device::factory()->create();
        $ping = Ping::factory()->for($device)->create();

        $this->assertTrue(\Illuminate\Support\Str::isUuid($device->uuid));
        $this->assertGreaterThanOrEqual(0, $ping->battery_percent);
        $this->assertLessThanOrEqual(100, $ping->battery_percent);
    }
}
