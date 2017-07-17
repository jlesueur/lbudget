<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BudgetDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('users', function (Blueprint $table) {
			$table->boolean('init_done')->default('false');
		});
		Schema::create('account', function (Blueprint $table) {
			$table->increments('id');
			$table->text('name');
			$table->integer('user_id')->references('id')->on('user');
			$table->text('number');
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('category', function (Blueprint $table) {
			$table->increments('id');
			$table->text('name');
			$table->text('description')->nullable();
			$table->text('comments')->nullable();
			$table->decimal('amount', 11, 2);//for millionaires
			$table->string('color')->nullable();
			$table->integer('user_id')->references('id')->on('user');
			$table->enum('style', ['savings', 'allowance']);
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('category_period', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('category_id')->references('id')->on('category');
			$table->string('month');
			$table->string('year');
			$table->decimal('amount', 11, 2);
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('expense', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('category_id')->nullable()->references('id')->on('category');
			$table->decimal('amount', 23, 2);//for billionaires
			$table->text('comment')->nullable();
			$table->text('description');
			$table->integer('account_id')->references('id')->on('account');
			$table->dateTime('ymdt');
			$table->text('import_hash')->nullable();
			$table->integer('span_months')->default(1);
			$table->boolean('credit');
			$table->timestamps();
			$table->softDeletes();
		});

		DB::statement("CREATE VIEW account_balances AS
 SELECT e.id,
    sum(
        CASE
            WHEN (expense.credit = true) THEN expense.amount
            ELSE (expense.amount * ((-1))::numeric)
        END) AS balance
   FROM ((expense e
     JOIN expense ON (((((expense.ymdt < e.ymdt) OR ((expense.ymdt <= e.ymdt) AND (expense.id <= e.id))) AND (e.account_id = expense.account_id)) AND (expense.deleted_at is null))))
     JOIN account ON (((account.deleted_at is null) AND (expense.account_id = account.id))))
  WHERE (e.deleted_at is null)
  GROUP BY e.id;
		");

		Schema::create('description_to_automatic_category', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('category_id')->references('id')->on('category');
			$table->text('description');
			$table->smallInteger('list_order');
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::drop('description_to_automatic_category');
		DB::statement("drop view account_balances");
		Schema::drop('expense');
		Schema::drop('category_period');
		Schema::drop('category');
		Schema::drop('account');
		
		
    }
}
