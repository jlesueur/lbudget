<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', ['uses' => 'LoginController@redirectToProvider', 'as' => 'login']);
Route::get('/google/oauth2callback', ['uses' => 'LoginController@handleProviderCallback']);
Route::get('/logout', ['uses' => 'LoginController@logout', 'as' => 'logout']);

Route::group(['middleware' => 'auth'], function() {
	Route::get('/register', ['uses' => 'RegistrationController@startRegistration', 'as' => 'register']);
});

Route::group(['middleware' => 'registered'], function () {
	Route::get('/home', ['uses' => 'ExpenseController@all', 'as' => 'home']);
	Route::get('/expenses.json', ['uses' => 'ExpenseController@expenseList', 'as' => 'expenses.json']);
	Route::post('/expense/{expenseId}', ['uses' => 'ExpenseController@postExpense', 'as' => 'update-expense']);
	Route::get('/budget.json', ['uses' => 'ExpenseController@budgetCategories', 'as' => 'budget-list']);
	Route::delete('/expense/{expenseId}', ['uses' => 'ExpenseController@deleteExpense', 'as' => 'delete-expense']);
	Route::get('/importExpenses', ['uses' => 'ImportController@start', 'as' => 'start-import']);
	Route::post('/importExpenses', ['uses' => 'ImportController@upload', 'as' => 'upload-import']);
});