<?php

use Tests\Fixtures;

$factory->define(Fixtures\Person::class, function (Faker\Generator $faker) {
    return [
        'first_name' 	=> $faker->firstName,
        'last_name' 	=> $faker->lastName,
        'email'   		=> $faker->email,
        'deleted_at'    => null,
    ];
});

$factory->define(Fixtures\Vehicle::class, function (Faker\Generator $faker) {
	$cars = [
		'tesla' 	=> ['roadster','model-6','model-3'],
		'ford' 	=> ['fiesta','falcon','discovery'],
		'toyota' 	=> ['camry','prius','lexus'],
	];
	$make = array_rand($cars);
	$model = array_random($cars[$make]);
    return [
        'year' 	=> rand(1995,2018),
        'make'  => $make,
        'model' => $model,
    ];
});

$factory->define(Fixtures\Family::class, function (Faker\Generator $faker) {
    return [
        'surname' => $faker->name,
    ];
});