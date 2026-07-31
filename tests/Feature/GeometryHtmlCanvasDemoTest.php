<?php

namespace Tests\Feature;

use Tests\TestCase;

class GeometryHtmlCanvasDemoTest extends TestCase
{
    public function test_public_geometry_html_canvas_demo_opens(): void
    {
        $this->get('/demo/geometry-html-canvas')
            ->assertOk()
            ->assertSee('HTML-in-Canvas', false)
            ->assertSee('geometry-html-canvas-demo.js', false)
            ->assertSee('data-geometry-html-canvas-demo', false);
    }
}
