<?php

namespace LBudget;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    protected $table = 'expense';

	public function account() {
		return $this->belongsTo(Account::class);
	}

	public function category() {
		return $this->belongsTo(Category::class);
	}

	private static function buildJoiningQuery($userId, $month, $year) {
		$endDateSql = "(expense.ymdt + interval '1 month' * (span_months - 1))";
		$paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
		$monthStartDate = new DateTime($year . '-' . $paddedMonth . '-01', new DateTimeZone('UTC'));
		$monthEndDate = new DateTime($year . '-' . $paddedMonth . '-' . $monthStartDate->format('t'), new DateTimeZone('UTC'));
		return Expense::join('account', 'expense.account_id', '=', 'account.id')
				->join('users', 'account.user_id', '=', 'users.id')
				->where('users.id', $userId)
				->whereDate(DB::raw($endDateSql), '>=', $monthStartDate->format('Y-m-d'))
				->whereDate('expense.ymdt', '<=', $monthEndDate->format('Y-m-d'));
	}

	public static function countExpensesForYearAndMonth($userId, $month, $year) {
		$expensesQuery = static::buildJoiningQuery($userId, $month, $year);
		return $expensesQuery->count();
	}

	public static function expensesForYearAndMonth($userId, $month, $year, $offset = 0, $limit = -1) {
		$expensesQuery = static::buildJoiningQuery($userId, $month, $year)
				->select('expense.*')
				->orderBy('expense.ymdt', 'asc')
				->orderBy('expense.id', 'asc')
				->offset($offset);
		if ($limit != -1) {
			$expensesQuery = $expensesQuery->limit($limit);
		}

				
		$expenses = $expensesQuery->get();
		return $expenses;
	}

	public static function getDataForTransactionsList($userId, $month, $year, $offset = 0, $limit = -1) {
		$expenses = static::expensesForYearAndMonth($userId, $month, $year, $offset, $limit);
		$expenses->load('account');
		//$expenseCategories = $expenses->pluck('category', 'category_id')->unique();
		$expenseAccounts = $expenses->pluck('account', 'account_id')->unique();
		$expenses = $expenses->map(function($expense) {
			return $expense->makeHidden('account');
		});
		return [
			'expenses' => $expenses->toArray(),
			//'expenseCategories' => $expenseCategories->toArray(),
			'expenseAccounts' => $expenseAccounts->toArray(),
		];
	}

	public static function getHistoricTotalsByCategoryUntil($categories, $monthStartDate) {
		$expenses = DB::table('expense')->select('category_id')
				->selectRaw('sum(
				(case when credit then 1 else -1 end) * 
				(amount * least(extract(month from age(?, ymdt)), span_months) / span_months)) as used',[
					'date' => $monthStartDate
				])
				->selectRaw('sum(
				(case when credit then 1 else -1 end) *
				(amount)) as spent')
				->where('ymdt', '<', $monthStartDate)
				->whereIn('category_id', $categories->pluck('id'))
				->groupBy('category_id')
				->get()->keyBy('category_id');
		return $expenses;
	}

	public static function getMonthTotalsByCategoryBetween($userId, $month, $year) {
		return static::buildJoiningQuery($userId, $month, $year)
				->select('category_id')
				->selectRaw('
					sum(
						(case when credit then 1 else -1 end) *
						(amount / span_months)
					) as used'
				)
				->selectRaw('sum(
					(case when credit then 1 else -1 end) *
					(amount)) as spent'
				)
				->groupBy('category_id')
				->get()->keyBy('category_id');
	}
}
