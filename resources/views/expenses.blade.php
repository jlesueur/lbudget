@extends('layouts.app')
@section('content')
@verbatim
<style>
	h1 {
		text-align: center;
	}

	.table tr td {
		mix-blend-mode: multiply;
	}
</style>
<script type="text/x-template" id="budget-row">

</script>
<div id="app" class="container">
	<h1>
		<a :href="prevMonthLink">&lt;</a>
		<select v-model="month">
			<option disabled></option>
			<option v-for="monthObj in months" :value="monthObj.number">{{monthObj.abbr}}</option>
		</select>
		<select v-model="year">
			<option disabled></option>
			<option v-for="yearOpt in years" :value="yearOpt">{{yearOpt}}</option>
		</select>
		<a :href="nextMonthLink">&gt;</a>
	</h1>
	<el-col :span=10>
		<v-paginator :options="options" @update="updateResource" ref="vpaginator" :resource_url="resource_url"></v-paginator>
	</el-col>
	<el-col :span=10>
		&nbsp;
	</el-col>
	<el-col :span=2>
		<el-button type="primary">Create</el-button>
	</el-col>
	<el-col :span=2 style="text-align:right">
		<el-button type="success" @click="importExpenses"><i class="el-icon-upload el-icon-left"></i>Import</el-button>
	</el-col>
	<table v-if="expenses.length > 0" id='expenses' class="table">
		<tr>
			<th>
				&nbsp;
			</th>
			<th>
				Category
			</th>
			<th>
				Amount
			</th>
			<th>
				Store
			</th>
			<th>
				Account
			</th>
			<th>
				Date
			</th>
			<th>
				Delete
			</th>
		</tr>
		<tr is="expense-row" 
			v-for="expense in expenses" :key="expense.id"
			:expense="expense" :categories="categories" :deleted-categories="deletedCategories" :accounts="expenseAccounts" 
			v-on:recategorized="refetchBudget" v-on:deleted="deleteExpense" v-on:edit-expense="editExpense" v-on:split="splitExpense"></tr>
	</table>
	<table id="budget" class="table">
		<tr>
			<th>
				Category
			</th>
			<th>
				Used
			</th>
			<th>
				Allocated
			</th>
			<th>
				Left
			</th>
		</tr>
		<tr is="budget-row" 
			v-for="budget in budgetRows" :key="budget.id"
			:budget="budget"
			v-on:edit-budget="editCategory"></tr>
	</table>
	<expense-dialog
		:mode="mode" 
		:dialog-form-visible="expenseDialogFormVisible" 
		:loading="loading" :form-expense="formExpense" v-on:cancel="cancelExpenseModal" v-on:save="saveExpenseModal">
	</expense-dialog>
	<split-expense-dialog
		:dialog-form-visible="splitExpenseDialogFormVisible" :categories="categories"
		:loading="loading" :form-expense="formExpense" v-on:cancel="cancelSplitExpenseModal" v-on:save="saveSplitExpenseModal">
	</split-expense-dialog>
	<category-dialog
		:mode="mode"
		:dialog-form-visible="categoryDialogFormVisible"
		:loading="loading" :form-category="formCategory" v-on:cancel="cancelCategoryModal" v-on:save="saveCategoryModal">
	</category-dialog>
	
</div>
	
@endverbatim
@endsection
@section('pagejs')
<script type="text/javascript">
	function round(number, precision) {
		var factor = Math.pow(10, precision);
		var tempNumber = number * factor;
		var roundedTempNumber = Math.round(tempNumber);
		return (roundedTempNumber / factor).toFixed(2);
	}

	Vue.filter('round', window.round);
	var vm = new Vue({
		el: '#app',
		components: {
			VPaginator: VuePaginator
		},
		data: function() {
			return {
				// The resource variables
				expenses: {},
				expenseAccounts: {},
				categories: {!! $categories !!},
				deletedCategories: {!! $deletedCategories !!},
				options: {},
				month: {{$month}},
				year: {{$year}},
				budgetRows: {!! $budget !!},
				loading: false,
				formExpense: {},
				formCategory: {category_period:{}},
				mode: "Edit",
				expenseDialogFormVisible: false,
				splitExpenseDialogFormVisible: false,
				categoryDialogFormVisible: false,
				// Here you define the url of your paginated API
				resource_url: "/expenses.json?month={{$month}}&year={{$year}}",
				budget_url: "/budget.json?month={{$month}}&year={{$year}}",
				expense_post_url: "/expense",
				category_post_url: "/category_period"
			};
		},
		computed: {
			nextMonth: function() {
				var nextMonth = this.month + 1;
				if (nextMonth > 12) {
					nextMonth = 1;
				}
				return nextMonth;
			},
			prevMonth: function() {
				var prevMonth = this.month - 1;
				if (prevMonth < 1) {
					prevMonth = 12;
				}
				return prevMonth;
			},
			nextYear: function() {
				var nextYear = this.year;
				if (this.month + 1 > 12) {
					nextYear++;
				}
				return nextYear;
			},
			prevYear: function() {
				var prevYear = this.year;
				if (this.month - 1 < 1) {
					prevYear--;
				}
				return prevYear;
			},
			prevMonthLink: function() {
				return '/home?month=' + this.prevMonth + '&year=' + this.prevYear;
			},
			nextMonthLink: function() {
				return '/home?month=' + this.nextMonth + '&year=' + this.nextYear;
			},
			monthName: function() {
				var date = new Date(this.year + '-' + this.month + '-02'); //um... timezones?
				var locale = navigator.languages && navigator.languages[0] ||
					navigator.language ||
					navigator.userLanguage;
				return date.toLocaleString(locale, {
					month: "short",
					timeZone: 'America/Denver'
				});
			},
			months: function() {
				return [{
						'number': 1,
						'abbr': 'Jan'
					},
					{
						'number': 2,
						'abbr': 'Feb'
					},
					{
						'number': 3,
						'abbr': 'Mar'
					},
					{
						'number': 4,
						'abbr': 'Apr'
					},
					{
						'number': 5,
						'abbr': 'May'
					},
					{
						'number': 6,
						'abbr': 'Jun'
					},
					{
						'number': 7,
						'abbr': 'Jul'
					},
					{
						'number': 8,
						'abbr': 'Aug'
					},
					{
						'number': 9,
						'abbr': 'Sep'
					},
					{
						'number': 10,
						'abbr': 'Oct'
					},
					{
						'number': 11,
						'abbr': 'Nov'
					},
					{
						'number': 12,
						'abbr': 'Dec'
					},
				];
			},
			years: function() {
				var thisYear = this.year;
				return [
					thisYear - 1,
					thisYear,
					thisYear + 1
				];
			},
		},
		watch: {
			'year': function(newValue, oldValue) {
				var queryParams = queryString.parse(location.search);
				queryParams.year = newValue;
				document.location.search = '?' + queryString.stringify(queryParams);
			},
			'month': function(newValue, oldValue) {
				var queryParams = queryString.parse(location.search);
				queryParams.month = newValue;
				document.location.search = '?' + queryString.stringify(queryParams);
			}
		},
		methods: {
			updateResource: function(data) {
				this.expenses = data.expenses;
				this.expenseAccounts = data.expenseAccounts;
			},
			refetchBudget: function() {
				var app = this;
				axios.get(this.budget_url).then(function(response) {
					app.budgetRows = response.data.balances;
				}).catch(function() {
					console.log('error fetching budget data.');
				});
			},
			deleteExpense: function(expenseId) {
				for (i in this.expenses) {
					if (this.expenses[i].id == expenseId) {
						this.expenses.splice(i, 1);
					}
				}
				this.refetchBudget();
			},
			editExpense: function(expense) {
				this.mode = 'Edit';
				this.formExpense = this.clone(expense); // See note below
				this.expenseDialogFormVisible = true;
			},
			cancelExpenseModal: function() {
				this.expenseDialogFormVisible = false;
				this.formExpense = {};
			},
			replaceExpense(expense) {
				for (i in this.expenses) {
					if (this.expenses[i].id == expense.id) {
						this.expenses[i] = expense;
						return;
					}
				}
			},
			spliceExpense(expense_id, deleteCount, new_expense) {
				for (i in this.expenses) {
					if (this.expenses[i].id == expense_id) {
						startIndex = i;
						break;
					}
				}
				this.expenses.splice(startIndex, deleteCount, new_expense);
			},
			saveExpenseModal: function(expense) {
				this.loading=true;
				if (this.mode == "Edit") {
					var app = this;
					axios.post(this.expense_post_url + '/' + expense.id, expense).then(function(response) {
						//find the expense in our expenses, and update it...
						app.replaceExpense(expense);
						app.expenseDialogFormVisible = false;
						app.loading = false;
						app.refetchBudget();
					}).catch(function() {
						console.log('error saving expense data.');
						app.loading = false;
					});
				}
			},
			splitExpense: function(expense) {
				this.formExpense = this.clone(expense);
				this.splitExpenseDialogFormVisible = true;
			},
			cancelSplitExpenseModal: function() {
				this.splitExpenseDialogFormVisible = false;
				this.formExpense = {};
			},
			saveSplitExpenseModal: function(splitDetails) {
				var app = this;
				var postedData = {
					'category_id': splitDetails.category_id,
					'amount': splitDetails.amount,
					'comment': splitDetails.comment
				}
				axios.post(this.split_expense_url + '/' + splitDetails.original_expense_id, postedData).then(function(response) {
					app.replaceExpense(response.original_expense);
					app.spliceExpense(response.original_expense.id, 0, response.new_expense);
					app.splitExpenseDialogFormVisible = false;
					app.loading = false;
					app.refetchBudget();
				}).catch(function() {
					console.log('error splitting expense');
					app.loading = false;
				});
			},
			editCategory: function(budget) {
				console.log(budget);
				this.mode = 'Edit';
				this.formCategory = this.clone(budget); // See note below
				this.categoryDialogFormVisible = true;
			},
			cancelCategoryModal: function() {
				this.categoryDialogFormVisible = false;
				this.formCategory = {};
			},
			saveCategoryModal: function(category) {
				this.loading=true;
				if (this.mode == "Edit") {
					var app = this;
					var postedData = {
						'name': category.name,
						'style': category.style,
						'amount': category.category_period.amount
					}
					axios.post(this.category_post_url + '/' + category.category_period.id, postedData).then(function(response) {
						//find the expense in our expenses, and update it...
						category.amount = category.category_period.amount;
						for (i in app.budgetRows) {
							if (app.budgetRows[i].id == category.id) {
								app.budgetRows[i] = category;
								break;
							}
						}
						app.categoryDialogFormVisible = false;
						app.loading = false;
					}).catch(function() {
						console.log('error saving category data.');
						app.loading = false;
					});
				}
			},
			clone: function(obj) {
				if (null == obj || "object" != typeof obj) return obj;
				var copy = obj.constructor();
				for (var attr in obj) {
					if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr];
				}
				return copy;
			},
			importExpenses: function() {
				window.location.href="/importExpenses";
			}
		}
	});
</script>
@endsection