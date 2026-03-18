<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpsEnforcementTest extends TestCase
{
    public function test_http_requests_are_redirected_to_https_when_enforced(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('http://localhost/');

        $response->assertRedirect('https://localhost');
    }

    public function test_secure_requests_continue_through_web_middleware_when_enforced(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('https://localhost/');

        $response->assertRedirect('https://localhost/login');
    }
}
