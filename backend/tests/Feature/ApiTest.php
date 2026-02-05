<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_ping_returns_ok(): void
    {
        $this->getJson('/api/ping')
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);
    }
}
