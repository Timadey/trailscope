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

    public function test_dashboard_asset_route_serves_compiled_css(): void
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../dist/.vite/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $asset = basename($manifest['resources/js/app.tsx']['css'][0]);

        $this->get('/trail/assets/' . $asset)
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_dashboard_root_view_loads_compiled_css(): void
    {
        $this->withVite();

        $this->get('/trail/login')
            ->assertOk()
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('/trail/assets/app-', false)
            ->assertSee('.css', false);
    }
}
