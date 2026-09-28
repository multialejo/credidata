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

        $this->assertCount(15, $specification['paths']);
        $this->assertArrayNotHasKey('/api/v1/api-key/rotar', $specification['paths']);
        $this->assertArrayNotHasKey('/api/v1/api-key/revocar', $specification['paths']);
        $this->assertArrayHasKey('/api/v1/colaboradores/datos', $specification['paths']);
        $this->assertArrayHasKey('/api/v1/admin/recargas/{recarga}/comprobante', $specification['paths']);
        $this->assertArrayHasKey('ApiKeyBearer', $specification['components']['securitySchemes']);
        $this->assertArrayHasKey('SanctumBearer', $specification['components']['securitySchemes']);

        // Definir el esquema no basta: las operaciones de colaboradores deben *aplicarlo*, o la
        // documentación pediría un token de sesión que estas rutas ya no aceptan.
        $this->assertSame([['ApiKeyBearer' => []]], $specification['paths']['/api/v1/colaboradores/registro']['post']['security']);
        $this->assertSame([['ApiKeyBearer' => []]], $specification['paths']['/api/v1/colaboradores/datos']['post']['security']);
        $this->assertSame([['ApiKeyBearer' => []]], $specification['paths']['/api/v1/colaboradores/aportes/{aporte}']['get']['security']);

        $cedulaResponses = $specification['paths']['/api/v1/consulta/cedula']['post']['responses'];
        $this->assertStringContainsString('revocada', $cedulaResponses['401']['description']);
        $this->assertStringContainsString('scope', $cedulaResponses['403']['description']);
        $this->assertStringContainsString('no se consumen créditos', $cedulaResponses['503']['description']);
        $this->assertNotContains('API Key', array_column($specification['tags'] ?? [], 'name'));

        $this->get('/api/documentation')->assertOk();
        $this->get('/docs')->assertOk()->assertJsonPath('openapi', '3.0.0');
        $css = $this->get('/docs/asset/swagger-ui.css')->assertOk();
        $javascript = $this->get('/docs/asset/swagger-ui-bundle.js')->assertOk();

        $this->assertStringStartsWith('text/css', (string) $css->headers->get('Content-Type'));
        $this->assertStringStartsWith('application/javascript', (string) $javascript->headers->get('Content-Type'));
    }
}
