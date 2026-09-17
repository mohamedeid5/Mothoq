<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_renders_successfully(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('موثوق');
    }

    public function test_vue_history_routes_render_the_application_shell(): void
    {
        foreach (['/centers/example-center', '/login', '/owner', '/admin'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('id="app"', false);
        }
    }
}
