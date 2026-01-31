<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['registration', 'contract', 'policy', 'certificate', 'other']),
            'category' => $this->faker->randomElement(['legal', 'financial', 'operational', 'other']),
            'description' => $this->faker->sentence(),
            'file_path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'status' => $this->faker->randomElement(['active', 'archived', 'draft']),
        ];
    }
}
