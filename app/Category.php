<?php

namespace LBudget;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $table = 'category';

	public function categoryPeriod() {
		return $this->hasMany(CategoryPeriod::class);
	}

	public static function selectList($userId) {
		$selectListExcludedFields = ["amount", 'user_id', 'created_at', 'updated_at', 'deleted_at'];
		return Category::where('id', 0)
				->orWhere(function($query) use ($userId) {
					$query->where('user_id', $userId)
						->whereNull('deleted_at');
				})
				->orderBy(DB::raw("(select count(expense.id) from expense where category_id = category.id and ymdt + interval '1 month' > now())"))
				->get()
				->makeHidden($selectListExcludedFields)
				->keyBy('id');
	}

	public static function getBalances($userId, $month, $year) {
		$paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
		$nextMonth = $month+1;
		$nextYear = $year;
		if ($nextMonth > 12) {
			$nextMonth = 1;
			$nextYear++;
		}
		$paddedNextMonth = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);
		$monthStartDate = new DateTime($year . '-' . $paddedMonth . '-01', new DateTimeZone('UTC'));
		$monthEndDate = new DateTime($nextYear . '-' . $paddedNextMonth . '-01', new DateTimeZone('UTC'));
		$prevMonth = $month-1;
		$prevYear = $year;
		if($prevMonth == 0)
		{
			$prevYear = $year - 1;
			$prevMonth = 12;
		}
		$categories = static::where('id', 0)
				->orWhere(function($query) use ($userId) {
					$query->where('user_id', $userId)
						->whereNull('deleted_at');
				})->get();
		static::populateCategoryPeriods($categories->where('id', '<>', 0), $month, $year, $prevMonth, $prevYear);
		//get previous expense totals (everything before this month year) for every category
		$categoryTotals = Expense::getHistoricTotalsByCategoryUntil($categories, $monthStartDate);
		//get this months expense totals for every category
		$monthTotals = Expense::getMonthTotalsByCategoryBetween($userId, $month, $year);
		//get category period totals for allowance categories for this month
		static::loadCategoryPeriods($categories, $month, $year);
		$unknownCategory = new Category();
		$unknownCategory->name = 'Unknown';
		$unknownCategory->id = null;
		$categories->put('', $unknownCategory);
		//get category period totals for savings categories from the beginning of time until the end of this month
		$savingsCategories = Category::where('style', 'savings')->where('user_id', $userId)->with('categoryPeriod')->get()->keyBy('id');
		foreach ($categories as $category) {
			if ($category->style == 'savings') {
				$category->allocatedSum = $savingsCategories->get($category->id)->categoryPeriod->sum('amount');
			}
			$category->historicalTotal = $categoryTotals->get($category->id);
			$category->monthTotal = $monthTotals->get($category->id);
		}
		return $categories;
	}

	private static function populateCategoryPeriods($categories, $month, $year, $prevMonth, $prevYear) {
		$categories->load(['categoryPeriod' => function($query) use ($month, $year, $prevMonth, $prevYear) {
			$query->where(function($query) use ($year, $month, $prevYear, $prevMonth) {
				$query->orWhere(function($query) use ($year, $month) {
						$query->where('year', $year);
						$query->where('month', $month);
					})
					->orWhere(function($query) use ($prevYear, $prevMonth) {
						$query->where('year', $prevYear);
						$query->where('month', $prevMonth);
					});
			});
		}]);
		foreach ($categories as $category) {
			$currentCategoryPeriod = $category->categoryPeriod->where('month', $month)->where('year', $year)->first();
			if (!$currentCategoryPeriod) {
				$prevCategoryPeriod = $category->categoryPeriod->where('month', $prevMonth)->where('year', $prevYear)->first();
				if ($prevCategoryPeriod && !empty($prevCategoryPeriod->amount)) {
					$amount = $prevCategoryPeriod->amount;
				} elseif (!empty($category->amount)) {
					$amount = $category->amount;
				} else {
					$amount = 0;
				}
				$deletedAt = null;
				if (!empty($prevCategoryPeriod->deleted_at)) {
					$deletedAt = $prevCategoryPeriod->deleted_at;
				}
				$currentCategoryPeriod = new CategoryPeriod;
				$currentCategoryPeriod->category_id = $category->id;
				$currentCategoryPeriod->month = $month;
				$currentCategoryPeriod->year = $year;
				$currentCategoryPeriod->amount = $amount;
				$currentCategoryPeriod->deleted_at = $deletedAt;
				$currentCategoryPeriod->save();
			}
		}
	}

	public static function loadCategoryPeriods($categories, $month, $year) {
		$categories->load(['categoryPeriod' => function($query) use ($month, $year) {
			$query->where(function($query) use ($year, $month) {
				$query->orWhere(function($query) use ($year, $month) {
					$query->where('year', $year);
					$query->where('month', $month);
				});
			});
		}]);
	}
}
