<?php

namespace Database\Factories;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        $firstNames = ['Juan', 'Maria', 'Jose', 'Angel', 'Mark', 'Princess', 'John', 'Sarah', 'Christian', 'Nicole'];
        $lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Ramos', 'Mendoza', 'Torres', 'Flores'];
        $sections = ['Diamond', 'Einstein', 'Euclid', 'Camia', 'Sampaguita', 'Rose', 'Orchid'];
        $advisers = ['Ernesto Arbigoso', 'Maria Santos', 'John Doe', 'Elena Cruz'];

        return [
            'first_name' => fake()->randomElement($firstNames),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->randomElement($lastNames),
            'school_year' => '2025-2026',
            'section' => fake()->optional(0.7)->randomElement($sections),
            'adviser' => fake()->optional(0.7)->randomElement($advisers),
            'address' => fake()->address(),
            'birthday' => fake()->dateTimeBetween('-18 years', '-12 years')->format('Y-m-d'),
            'parent_name' => fake()->name(),
            'parent_address' => fake()->address(),
            'parent_contact_number' => '09' . fake()->numerify('#########'),
            'photo_path' => null,
        ];
    }
}