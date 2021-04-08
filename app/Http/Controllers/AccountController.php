<?php

namespace LBudget\Http\Controllers;

use Illuminate\Http\Request;
use LBudget\Account;
use LBudget\Category;

class AccountController extends Controller {
	public function profile(Request $request) {
		return view('profile', [
			'user' => $request->user()->toJson(),
			'accounts' => Account::where('user_id', $request->user()->id)->whereNull('deleted_at')->orderBy('name', 'asc')->get()->makeHidden('number')->toJson(),
			'categories' => Category::where('user_id', $request->user()->id)->whereNull('deleted_at')->orderBy('amount', 'desc')->orderBy('name', 'asc')->get()->toJson()
		]);
	}

	public function updateCategory(Request $request) {
		$categoryId = $request->route('categoryId');
		$category = Category::find($categoryId);
		$settableFields = ['name', 'style', 'amount'];
		foreach ($settableFields as $fieldName) {
			if ($request->exists($fieldName)) {
				$category->$fieldName = $request->input($fieldName);
			}
		}
		$category->save();
		return [
			'success' => true,
			'category' => $category
		];
	}
}
