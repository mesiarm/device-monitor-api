<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Ping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_list_is_an_empty_json_array(): void
    {
        $response = $this->getJson('/api/devices')->assertOk()->assertExactJson([]);

        $this->assertSame('[]', $response->getContent());
    }

    public function test_list_returns_plain_array_without_wrapper(): void
    {
        Device::factory()->count(3)->create();

        $this->getJson('/api/devices')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonMissingPath('data')
            ->assertJsonStructure(['*' => ['uuid', 'name', 'last_battery_percent']]);
    }

    public function test_list_returns_last_battery_percent(): void
    {
        $withPing = Device::factory()->create(['uuid' => '550e8400-e29b-41d4-a716-446655440000', 'name' => 'Sensor 01']);
        Ping::factory()->for($withPing)->create(['battery_percent' => 90, 'created_at' => '2026-05-26 09:00:00']);
        Ping::factory()->for($withPing)->create(['battery_percent' => 85, 'created_at' => '2026-05-26 10:00:00']);
        $withoutPing = Device::factory()->create(['name' => 'Sensor 02']);

        $this->getJson('/api/devices')
            ->assertOk()
            ->assertExactJson([
                ['uuid' => '550e8400-e29b-41d4-a716-446655440000', 'name' => 'Sensor 01', 'last_battery_percent' => 85],
                ['uuid' => $withoutPing->uuid, 'name' => 'Sensor 02', 'last_battery_percent' => null],
            ]);
    }

    public function test_each_device_shows_its_own_last_battery_percent(): void
    {
        $a = Device::factory()->create();
        $b = Device::factory()->create();
        Ping::factory()->for($a)->create(['battery_percent' => 11]);
        Ping::factory()->for($b)->create(['battery_percent' => 22]);

        $this->getJson('/api/devices')
            ->assertJsonPath('0.last_battery_percent', 11)
            ->assertJsonPath('1.last_battery_percent', 22);
    }

    public function test_list_is_ordered_by_creation(): void
    {
        $first = Device::factory()->create();
        $second = Device::factory()->create();
        $third = Device::factory()->create();

        $this->getJson('/api/devices')
            ->assertJsonPath('0.uuid', $first->uuid)
            ->assertJsonPath('1.uuid', $second->uuid)
            ->assertJsonPath('2.uuid', $third->uuid);
    }

    public function test_list_does_not_expose_internal_fields(): void
    {
        Device::factory()->create();

        $this->getJson('/api/devices')
            ->assertJsonMissingPath('0.id')
            ->assertJsonMissingPath('0.created_at')
            ->assertJsonMissingPath('0.last_ping');
    }

    public function test_list_avoids_n_plus_one_queries(): void
    {
        Device::factory()->count(5)->create()->each(
            fn (Device $device) => Ping::factory()->for($device)->count(2)->create()
        );

        \DB::enableQueryLog();
        $this->getJson('/api/devices')->assertOk();

        $this->assertLessThanOrEqual(2, count(\DB::getQueryLog()));
    }

    public function test_newly_created_device_appears_in_list(): void
    {
        $this->postJson('/api/devices', ['uuid' => '550e8400-e29b-41d4-a716-446655440000', 'name' => 'Sensor 01'])
            ->assertCreated();

        $this->getJson('/api/devices')
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Sensor 01');
    }

    public function test_list_does_not_accept_post_with_get_semantics(): void
    {
        $this->putJson('/api/devices', [])->assertStatus(405);
        $this->deleteJson('/api/devices')->assertStatus(405);
    }
}
