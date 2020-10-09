<?php

namespace LBudget\Http\Controllers;

use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use LBudget\Account;
use LBudget\Category;
use LBudget\Expense;
use LBudget\Import;
use LBudget\Parsers\OfxExpenseParser;
use Ramsey\Uuid\Uuid;

use function view;

class ImportController extends Controller
{
	public function start() {
		return view('import');
	}

	public function upload(Request $request) {
		$parser = new OfxExpenseParser();
		$parser->open($request->file('file')->getRealPath());
		$importId = Uuid::uuid4();
		$accounts = Account::query()->where('user_id', '=', $request->user()->id)->get();
		$newExpenses = collect([]);
		while (null !== ($transactionData = $parser->getTransaction())) {
			$expense = new Expense();
			$matchingAccount = $accounts->first(function($account, $key) use ($transactionData) {
				return $account->number === $transactionData['raw_account_id'] && $account->deleted_at === null;
			});
			if (!$matchingAccount) {
				$matchingAccount = new Account();
				$matchingAccount->name = str_repeat('*', strlen($transactionData['raw_account_id']) - 4) . substr($transactionData['raw_account_id'], -4);
				$matchingAccount->number = $transactionData['raw_account_id'];
				$matchingAccount->user_id = $request->user()->id;
				$matchingAccount->save();
				$accounts->push($matchingAccount);
			}
			$expense->account_id = $matchingAccount->id;
			$expense->amount = number_format($transactionData['amount'], 2, '.', '');
			$expense->credit = $transactionData['credit'];
			$expense->description = $transactionData['description'];
			$expense->ymdt = $transactionData['ymdt'];
			$expense->import_id = $importId;
			$expense->import_hash = md5($expense->description . $expense->amount . $expense->ymdt . $expense->credit . $expense->account_id);
			$newExpenses->push($expense);
		}
		$importHashes = $newExpenses->pluck('import_hash');
		$existingImportHashes = Expense::query()->whereIn('import_hash', $importHashes)->pluck('import_hash');
		DB::transaction(function() use ($newExpenses, $importId, $request, $existingImportHashes) {
			$newExpenses->each(function($expense) use ($existingImportHashes) {
				if ($existingImportHashes->contains($expense->import_hash)) {
					return;
				}
				$expense->save();
			});
			$import = new Import();
			$import->id = $importId;
			$import->user_id = $request->user()->id;
			$import->save();
		});
		return [
			'import_id' => $importId,
		];
	}
}
