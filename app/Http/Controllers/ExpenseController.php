<?php

namespace LBudget\Http\Controllers;

use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use LBudget\Category;
use LBudget\Expense;
use Illuminate\Support\Facades\DB;
use LBudget\Import;

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
		$deletedCategories = Category::deletedList($request->user()->id);
		return view('expenses', [
			'pageSize' => $this->pageSize,
			'month' => $month,
			'year' => $year,
			'categories' => $categories->toJson(),
			'deletedCategories' => $deletedCategories->toJson(),
			'budget' => json_encode(array_values(Category::getBalances($request->user()->id, $month, $year)->toArray())),
		]);
	}
	
	public function viewImportedExpenses(Request $request) {
		$importId = $request->route('importId');
		$import = Import::where('id', $importId)->first();
		$nextImport = Import::where('created_at', '>', $import->created_at)->orderBy('created_at', 'asc')->first();
		$prevImport = Import::where('created_at', '<', $import->created_at)->orderBy('created_at', 'desc')->first();
		$categories = Category::selectList($request->user()->id);
		$deletedCategories = Category::deletedList($request->user()->id);
		return view('importedExpenses', [
			'pageSize' => $this->pageSize,
			'importId' => $importId,
			'import' => $import->toJson(),
			'prevImportId' => $prevImport->id ?? null,
			'nextImportId' => $nextImport->id ?? null,
			'categories' => $categories->toJson(),
			'deletedCategories' => $deletedCategories->toJson(),
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

	public function importedExpenseList(Request $request) {
		$importId = $request->route('importId');
		$page = $request->get('page') ?: 1;
		$pageSize = $this->pageSize;
		$totalExpenses = Expense::countExpensesForImportId($request->user()->id, $importId);
		$expenseData = Expense::getDataForImportedTransactionsList($request->user()->id, $importId, ($page - 1) * $pageSize, $pageSize);
		$lastPage = ceil($totalExpenses / $pageSize);
		return [
			'data' => $expenseData,
			'current_page' => $page,
			'last_page' => $lastPage,
			'next_page_url' => $page < $lastPage ? "/import/$importId/expenses.json?page=" . ($page + 1) : null,
			'prev_page_url' => $page > 1 ? "/import/$importId/expenses.json?page=" . ($page - 1) : null,
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
		if ($expense->category_id == '') {
			$expense->category_id = null;
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
		DB::connection()->enableQueryLog();
		$month = $request->get('month') ?: date('n');
		$year = $request->get('year') ?: date('Y');
		$balances = DB::transaction(function() use ($request, $month, $year) {
			return Category::getBalances($request->user()->id, $month, $year);
		});
		return [
			'balances' => $balances->toArray(),
		];
	}
}
