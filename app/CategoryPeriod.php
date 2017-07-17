<?php

namespace LBudget;

use Illuminate\Database\Eloquent\Model;

class CategoryPeriod extends Model
{
    protected $table = 'category_period';

	public function category() {
		return $this->belongsTo(Category::class);
	}
}
