<?php

use Illuminate\Database\Seeder;

class ExpenseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$userId = factory(LBudget\User::class)->create(['email' => env('APP_OWNER')])->id;
		$accountId = factory(LBudget\Account::class)->create(['user_id' => $userId])->id;
		for ($iteration = 0; $iteration < 10; $iteration++) {
			if ($iteration == 0) {
				$category = factory(LBudget\Category::class)->create(['id' => 0, 'user_id' => $userId, 'name' => 'Non-budget transaction', 'style' => 'allowance', 'amount' => 0, 'color' => 'white']);
			} else {
				$category = factory(LBudget\Category::class)->create(['user_id' => $userId]);
			}
			factory(LBudget\Expense::class, 20)->create(['category_id' => $category->id, 'account_id' => $accountId]);
			if ($iteration > 0) {
				for ($month = date('n'); $month <= 12; $month++) {
					$this->buildCategoryPeriod($category, $month, date('Y') - 1);
				}
				for ($month = 1; $month <= date('n'); $month++) {
					$this->buildCategoryPeriod($category, $month, date('Y'));
				}
			}

		}
    }

	protected function buildCategoryPeriod($category, $month, $year) {
		factory(LBudget\CategoryPeriod::class)->create([
					'category_id' => $category->id,
					'month' => $month,
					'year' => $year,
					'amount' => $category->amount,
				]);
	}
}
