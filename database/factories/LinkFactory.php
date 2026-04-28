<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = Link::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'original_url' => fake()->url(),
            'short_code' => Str::random(6),
            'status' => 1,
        ];
    }

    /**
     * Indicate that the link is archived.
     */
    public function archived(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 2,
        ]);
    }
}
