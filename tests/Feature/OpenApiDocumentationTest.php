<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_documentation_is_disabled_in_production_by_default(): void
    {
        $environment = app()->environment();
        app()['env'] = 'production';

        try {
            config()->set('l5-swagger.public', false);

            $this->get('/api/documentation')->assertNotFound();

            config()->set('l5-swagger.public', true);

            $this->get('/api/documentation')->assertOk();
        } finally {
            app()['env'] = $environment;
        }
    }

    public function test_openapi_documentation_generates_all_api_operations(): void
    {
        $this->assertSame(0, Artisan::call('l5-swagger:generate'));

        $specification = json_decode(
            file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(13, $specification['paths']);
        $this->assertArrayHasKey('/api/v1/admin/recargas/{recarga}/comprobante', $specification['paths']);
        $this->assertArrayHasKey('ApiKeyBearer', $specification['components']['securitySchemes']);
        $this->assertArrayHasKey('SanctumBearer', $specification['components']['securitySchemes']);

        $this->get('/api/documentation')->assertOk();
    }
}
