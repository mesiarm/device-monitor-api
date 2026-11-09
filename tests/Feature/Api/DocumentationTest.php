<?php

namespace Tests\Feature\Api;

use Dedoc\Scramble\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    // Scramble reads column types from the database schema, so migrations must be applied.
    use RefreshDatabase;

    public function test_openapi_document_describes_all_endpoints(): void
    {
        $document = app(Generator::class)();

        $this->assertSame('Device Monitor API', $document['info']['title']);
        $this->assertEqualsCanonicalizing(
            ['/devices', '/devices/{device}', '/devices/{device}/ping'],
            array_keys($document['paths'])
        );
        $this->assertArrayHasKey('get', $document['paths']['/devices']);
        $this->assertArrayHasKey('post', $document['paths']['/devices']);
        $this->assertArrayHasKey('get', $document['paths']['/devices/{device}']);
        $this->assertArrayHasKey('post', $document['paths']['/devices/{device}/ping']);
    }

    public function test_committed_openapi_file_is_up_to_date(): void
    {
        $generated = json_decode(json_encode(app(Generator::class)()), true);
        $committed = json_decode(file_get_contents(base_path('docs/openapi.json')), true);

        $this->assertSame($generated['paths'], $committed['paths'], 'Run: php artisan scramble:export');
        $this->assertSame($generated['components'], $committed['components'], 'Run: php artisan scramble:export');
    }
}
