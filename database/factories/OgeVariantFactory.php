<?php

namespace Database\Factories;

use App\Models\OgeVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OgeVariantFactory extends Factory
{
    protected $model = OgeVariant::class;

    public function definition(): array
    {
        return [
            'hash'   => Str::lower(Str::random(6)),
            'source' => OgeVariant::SOURCE_GENERATOR,
            'mode'   => OgeVariant::MODE_FULL,
        ];
    }
}
