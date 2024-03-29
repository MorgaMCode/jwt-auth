<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
                'title'=>$this->faker->name(),
                'amount'=>$this->faker->randomDigit(),
                'price'=>$this->faker->randomNumber()
        ];
    }
}
