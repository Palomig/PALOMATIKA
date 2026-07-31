<?php

namespace Tests\Feature;

use Tests\TestCase;

class Geometry3dDemoTest extends TestCase
{
    public function test_public_geometry_3d_demo_opens(): void
    {
        $this->get('/demo/geometry-3d')
            ->assertOk()
            ->assertSee('3D-разбор геометрии', false)
            ->assertSee('geometry-3d-demo.js', false)
            ->assertSee('data-geometry-3d-demo', false)
            ->assertSee('Three.js + HTML labels', false);
    }
}
