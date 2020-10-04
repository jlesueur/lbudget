<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(LBudget\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
		'init_done' => true,
    ];
});

$factory->define(LBudget\Account::class, function (Faker\Generator $faker) {
	return [
		'name' => implode(' ', $faker->words(2)),
		'user_id' => function() {
			return factory(LBudget\Person::class)->create()->id;
		},
		'number' => $faker->bankAccountNumber
	];
});

$factory->define(LBudget\Category::class, function (Faker\Generator $faker) {
	return [
		'name' => implode(' ', $faker->words(2)),
		'description' => implode(' ', $faker->words(4)),
		'comments' => $faker->sentence,
		'amount' => $faker->randomFloat(2, 0, 1000),
		'color' => 'hsla(' . $faker->numberBetween(0,359) . ', ' . $faker->numberBetween(55, 100) . '%, ' . $faker->numberBetween(55, 100) . '%, 1)',//$faker->colorName,
		'user_id' => function() {
			return factory(LBudget\Person::class)->create()->id;
		},
		'style' => $faker->boolean() ? 'savings' : 'allowance',
	];
});

$factory->define(LBudget\CategoryPeriod::class, function (Faker\Generator $faker) {
	return [
		'category_id' => function() {
			return factory(LBudget\Category::class)->create()->id;
		},
		'month' => $faker->month,
		'year' => $faker->year,
		'amount' => $faker->randomFloat(2, 0, 1000),
	];
});

$factory->define(LBudget\DescriptionToAutomaticCategory::class, function (Faker\Generator $faker) {
	return [
		'category_id' => function() {
			return factory(LBudget\Category::class)->create()->id;
		},
		'description' => $faker->words(4),
		'list_order' => $faker->randomDigit,
	];
});

$factory->define(LBudget\Expense::class, function (Faker\Generator $faker) {
	return [
		'category_id' => function() {
			return factory(LBudget\Category::class)->create()->id;
		},
		'amount' => $faker->randomFloat(2, 0, 100),
		'comment' => $faker->sentence,
		'description' => implode(' ', $faker->words(4)),
		'account_id' => function() {
			return factory(LBudget\Account::class)->create()->id;
		},
		'ymdt' => $faker->dateTimeThisYear,
		'import_hash' => $faker->md5,
		'span_months' => $faker->boolean() && $faker->boolean() && $faker->boolean() ? $faker->randomDigit + 1 : 1,
		'credit' => $faker->boolean() && $faker->boolean() && $faker->boolean(),
	];
});
