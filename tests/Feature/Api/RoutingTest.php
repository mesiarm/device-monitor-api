<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class RoutingTest extends TestCase
{
    public function test_root_has_no_frontend(): void
    {
        $this->get('/')->assertNotFound();
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->get('/api/nothing')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
