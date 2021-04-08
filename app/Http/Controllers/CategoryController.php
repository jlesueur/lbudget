<?php

namespace LBudget\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LBudget\CategoryPeriod;

class CategoryController extends Controller {
    public function updateCategoryPeriod($categoryPeriodId, Request $request) {
		DB::enableQueryLog();
        $categoryPeriod = CategoryPeriod::find($categoryPeriodId);
        $category = $categoryPeriod->category;
		$settableFields = ['name', 'style', 'amount'];
		foreach ($settableFields as $fieldName) {
			var_dump($fieldName);
			var_dump($request->exists($fieldName));
			var_dump($request->input($fieldName));
			var_dump($category->getAttribute($fieldName));
			if ($request->exists($fieldName)) {
				$category->$fieldName = $request->input($fieldName);
			}
		}
        $category->save();
        $settableFields = ['amount'];
        foreach ($settableFields as $fieldName) {
			var_dump($fieldName);
			var_dump($request->exists($fieldName));
			var_dump($request->input($fieldName));
			var_dump($categoryPeriod->$fieldName);
			if ($request->exists($fieldName)) {
				$categoryPeriod->$fieldName = $request->input($fieldName);
			}
		}
		
		$categoryPeriod->save();
		var_dump(DB::getQueryLog());
		return [
			'success' => true,
            'category' => $category,
            'category_period' => $categoryPeriod,
		];
	}
}
