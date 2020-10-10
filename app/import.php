<?php

namespace LBudget;

use Illuminate\Database\Eloquent\Model;

class Import extends Model {
    protected $table = 'import';
    public $incrementing = false;

    // In Laravel 6.0+ make sure to also set $keyType
    protected $keyType = 'string';
}
