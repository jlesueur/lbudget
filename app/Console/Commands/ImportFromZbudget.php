<?php

namespace LBudget\Console\Commands;

use Illuminate\Console\Command;

class ImportFromZbudget extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:zbudget {--host=localhost} {--port=5432} {--user=zbudget} {--db=zbudget} {--password=zbudget}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import expense and other information from zbudget';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$faker = \Faker\Factory::create();
        $host = $this->option('host');
		$port = $this->option('port');
		$user = $this->option('user');
		$dbName = $this->option('db');
		$password = $this->option('password');
		$databaseManager = new \Illuminate\Database\Capsule\Manager();
		$databaseManager->addConnection([
			'driver' => 'pgsql',
			'host' => $host,
			'database' => $dbName,
			'username' => $user,
			'password' => $password,
			'charset' => 'utf-8'
		],'zbudget');
		$users = $databaseManager->getConnection('zbudget')->table('person')->get();
		foreach ($users as $zUser) {
			$user = new \LBudget\User();
			$user->id = $zUser->id;
			$user->name = $faker->name;
			$user->email = $zUser->email;
			$user->password = '';
			$user->init_done = true;
			$user->save();
		}
		$databaseManager->getConnection('lbudget')->query("SELECT pg_catalog.setval(pg_get_serial_sequence('users', 'id'), MAX(id)) FROM users");
		$accounts = $databaseManager->getConnection('zbudget')->table('account')->get();
		foreach ($accounts as $zAccount) {
			$account = new \LBudget\Account();
			$account->id = $zAccount->id;
			$account->name = $zAccount->name ?: $faker->lastName;
			$account->user_id = $zAccount->owner_id;
			$account->number = $zAccount->number ?: '';
			$account->deleted_at = $zAccount->deleted ? '2010-01-01 00:00:00' : null;
			$account->save();
		}
		$databaseManager->getConnection('lbudget')->query("SELECT pg_catalog.setval(pg_get_serial_sequence('account', 'id'), MAX(id)) FROM account");
		$categories = $databaseManager->getConnection('zbudget')->table('category')->get();
		foreach ($categories as $zCategory) {
			$category = new \LBudget\Category();
			$category->id = $zCategory->id;
			$category->name = $zCategory->name;
			$category->description = $zCategory->description;
			$category->comments = $zCategory->comments;
			if ($zCategory->id == 0) {
				$category->amount = 0;
				$category->color = 'white';
				$category->user_id = 0;
				$category->style = 'allowance';
			} else {
				$category->amount = $zCategory->amount;
				$category->color = 'hsla(' . $faker->numberBetween(0,359) . ', ' . $faker->numberBetween(70, 100) . '%, ' . $faker->numberBetween(70, 100) . '%, 1)';
				$category->user_id = $zCategory->owner_id;
				$category->style = $zCategory->fund ? 'savings' : 'allowance';
				$category->deleted_at = $zCategory->deleted ? '2010-01-01 00:00:00' : null;
			}
			$category->save();
		}
		$databaseManager->getConnection('lbudget')->query("SELECT pg_catalog.setval(pg_get_serial_sequence('category', 'id'), MAX(id)) FROM category");
		$expenses = $databaseManager->getConnection('zbudget')->table('expense')->get();
		foreach($expenses as $zExpense) {
			$expense = new \LBudget\Expense();
			$expense->id = $zExpense->id;
			$expense->category_id = $zExpense->category_id;
			$expense->amount = $zExpense->amount ?: 0;
			$expense->comment = $zExpense->comment;
			$expense->description = $zExpense->store ?: '';
			$expense->account_id = $zExpense->entered_by;
			$expense->ymdt = $zExpense->date . ' 00:00:00';
			$expense->import_hash = $zExpense->unique_id;
			$expense->span_months = $zExpense->span_months;
			$expense->credit = (bool)$zExpense->credit;
			$expense->save();
		}
		$databaseManager->getConnection('lbudget')->query("SELECT pg_catalog.setval(pg_get_serial_sequence('expense', 'id'), MAX(id)) FROM expense");

		$categoryPeriods = $databaseManager->getConnection('zbudget')->table('category_period')->get();
		foreach($categoryPeriods as $zCategoryPeriod) {
			$categoryPeriod = new \LBudget\CategoryPeriod();
			$categoryPeriod->id = $zCategoryPeriod->id;
			$categoryPeriod->category_id = $zCategoryPeriod->category_id;
			list($year, $month) = explode('-', $zCategoryPeriod->period);
			$categoryPeriod->month = $month;
			$categoryPeriod->year = $year;
			$categoryPeriod->amount = $zCategoryPeriod->amount ?: 0;
			$categoryPeriod->deleted_at = $zCategoryPeriod->deleted ? '2010-01-01 00:00:00' : null;
			$categoryPeriod->save();
		}
		$databaseManager->getConnection('lbudget')->query("SELECT pg_catalog.setval(pg_get_serial_sequence('category_period', 'id'), MAX(id)) FROM category_period");
    }
}
