<?php

namespace Tests\Unit;

use Tests\TestCase;

class FirestoreCredentialsTest extends TestCase
{
    public function test_google_cloud_client_config_passes_credentials_key(): void
    {
        $source = file_get_contents(
            base_path('vendor/kreait/firebase-php/src/Factory.php')
        );

        $this->assertStringContainsString(
            "\$config['credentials'] = \$credentials;",
            $source,
            'Google Cloud Firestore 2.x requires the `credentials` key. '
            . 'Ensure kreait/firebase-php is pinned to 8.x-dev or >=8.3.'
        );
    }

    public function test_google_cloud_client_config_passes_credentialsFetcher_key(): void
    {
        $source = file_get_contents(
            base_path('vendor/kreait/firebase-php/src/Factory.php')
        );

        $this->assertStringContainsString(
            "\$config['credentialsFetcher'] = \$credentials;",
            $source,
            'Google Cloud Firestore 1.x requires the `credentialsFetcher` key.'
        );
    }
}
