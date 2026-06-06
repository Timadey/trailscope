<?php

namespace Trail\Tests\Feature;

use Trail\Tests\TestCase;

class DashboardAssetTest extends TestCase
{
    public function test_dashboard_asset_route_serves_compiled_javascript(): void
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../dist/.vite/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $asset = basename($manifest['resources/js/app.tsx']['file']);

        $this->get('/trail/assets/' . $asset)
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript');
    }
}
