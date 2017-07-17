<?php

namespace LBudget;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'account';

	public function user() {
		return $this->belongsTo(User::class);
	}

	public function category() {
		return $this->belongsTo(User::class);
	}
}
