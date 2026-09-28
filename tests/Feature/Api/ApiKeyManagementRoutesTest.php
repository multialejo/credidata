<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyManagementRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_keys_no_se_pueden_administrar_presentando_una_api_key(): void
    {
        $this->postJson('/api/v1/api-key/rotar')->assertNotFound();
        $this->postJson('/api/v1/api-key/revocar')->assertNotFound();
    }
}
