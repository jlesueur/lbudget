<?php

namespace LBudget\Http\Controllers;

use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use LBudget\Category;
use LBudget\Expense;
use function view;

class ExpenseController extends Controller
{
	private $pageSize = 15;
    public function all(Request $request) {
		$now = new DateTime(null, new DateTimeZone('America/Denver'));
		$month = $request->get('month') ?: $now->format('n');
		$year = $request->get('year') ?: $now->format('Y');
		//$expenseCount = Expense::countExpensesForYearAndMonth($request->user()->id, $month, $year);
		$categories = Category::selectList($request->user()->id);
		return view('expenses', [
			'pageSize' => $this->pageSize,
			'month' => $month,
			'year' => $year,
			'categories' => $categories->toJson(),
			'budget' => Category::getBalances($request->user()->id, $month, $year),
		]);
	}

	public function expenseList(Request $request) {
		$month = $request->get('month') ?: date('n');
		$year = $request->get('year') ?: date('Y');
		$page = $request->get('page') ?: 1;
		$pageSize = $this->pageSize;
		$totalExpenses = Expense::countExpensesForYearAndMonth($request->user()->id, $month, $year);
		$expenseData = Expense::getDataForTransactionsList($request->user()->id, $month, $year, ($page - 1) * $pageSize, $pageSize);
		$lastPage = ceil($totalExpenses / $pageSize);
		return [
			'data' => $expenseData,
			'current_page' => $page,
			'last_page' => $lastPage,
			'next_page_url' => $page < $lastPage ? "/expenses.json?month=$month&year=$year&page=" . ($page + 1) : null,
			'prev_page_url' => $page > 1 ? "/expenses.json?month=$month&year=$year&page=" . ($page - 1) : null,
		];
	}

	public function postExpense($expenseId, Request $request) {
		$expense = Expense::find($expenseId);
		$settableFields = ['category_id', 'amount', 'credit', 'ymdt', 'span_months', 'description', 'comment'];
		foreach ($settableFields as $fieldName) {
			if ($request->has($fieldName)) {
				$expense->$fieldName = $request->input($fieldName);
			}
		}
		$expense->save();
		return [
			'success' => true,
			'expense' => $expense
		];
	}

	public function deleteExpense($expenseId, Request $request) {
		$expense = Expense::find($expenseId);
		$expense->delete();
		return [
			'success' => true
		];
	}

	public function budgetCategories(Request $request) {
		\DB::connection()->enableQueryLog();
		$month = $request->get('month') ?: date('n');
		$year = $request->get('year') ?: date('Y');
		$balances = \DB::transaction(function() use ($request, $month, $year) {
			return Category::getBalances($request->user()->id, $month, $year);
		});
		return [
			'balances' => $balances,
		];
	}
}
