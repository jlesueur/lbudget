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
<tr :style="{backgroundColor: budget.color ? budget.color : 'white'}">
	<td>
		{{budget.name}}
	</td>
	<td>
		<span v-html="budgetUsed"></span>
	</td>
	<td>
		<span v-if="budget.id != null && budget.id != 0">
			${{budgetAllocated|round(2)}}
		</span>
	</td>
	<td>
		<span v-if="budget.id != null && budget.id != 0">
			<span v-if="budgetAvailable < 0" syle="font-color:red">
				(${{budgetAvailable * -1 |round(2)}})
			</span>
			<span v-else>
				<b><em>${{budgetAvailable|round(2)}}</em></b>
			</span>
		</span>
	</td>
	<td></td>
</tr>
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
	<el-col :span=14 style="text-align:right">
		<el-button type="success" @click="importExpenses"><i class="el-icon-upload el-icon-left"></i>Import</el-button>
	</el-col>
	<table id='expenses' class="table">
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
		<tr is="expense-row" v-for="expense in expenses" :expense="expense" :categories="categories" :deleted-categories="deletedCategories" :accounts="expenseAccounts" v-on:recategorized="refetchBudget" v-on:deleted="deleteExpense" :key="expense.id" v-on:edit-expense="editExpense"></tr>
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
		<tr is="budget-row" v-for="budget in budgetRows" :budget="budget" :key="budget.id"></tr>
	</table>
	<expense-dialog 
		:mode="mode" 
		:dialog-form-visible="dialogFormVisible" 
		:loading="loading" :form-expense="formExpense" v-on:cancel="cancelExpenseModal" v-on:save="saveExpenseModal"/>
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
			VPaginator: VuePaginator,
			BudgetRow: {
				template: '#budget-row',
				props: ['budget'],
				computed: {
					budgetUsed: function() {
						var used = 0;
						if (this.budget.monthTotal == null) {
							used = 0;
						} else if (this.budget.id == null) {
							used = this.budget.monthTotal.spent;
						} else {
							used = this.budget.monthTotal.used;
						}
						if (used < 0) {
							return '($' + (round(used * -1, 2)) + ')';
						}
						return "<b><em>$" + round(used, 2) + "</em></b>";
					},
					budgetAvailable: function() {
						var allocated = 0;
						if (!this.budget.style || this.budget.id == 0) {
							return allocated;
						}
						if (this.budget.style == 'allowance') {
							allocated = Number.parseFloat(this.budget.amount);
						} else {
							allocated = Number.parseFloat(this.budget.allocatedSum);
							if (this.budget.historicalTotal) {
								allocated += Number.parseFloat(this.budget.historicalTotal.used);
							}
						}
						var thisMonthUsed = 0;
						if (this.budget.monthTotal) {
							thisMonthUsed = Number.parseFloat(this.budget.monthTotal.used);
						}
						return allocated + thisMonthUsed;

					},
					budgetAllocated: function() {
						if (!this.budget.amount) {
							return 0;
						}
						return this.budget.amount;
					}
				}
			},
		},
		data: {
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
			mode: "Edit",
			dialogFormVisible: false,
			// Here you define the url of your paginated API
			resource_url: "/expenses.json?month={{$month}}&year={{$year}}",
			budget_url: "/budget.json?month={{$month}}&year={{$year}}",
			expense_post_url: "/expense"
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
				console.log(date);
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
				this.dialogFormVisible = true;
			},
			cancelExpenseModal: function() {
				this.dialogFormVisible = false;
				this.formExpense = {};
			},
			saveExpenseModal: function(expense) {
				console.log("save", expense);
				this.loading=true;
				if (this.mode == "Edit") {
					var app = this;
					axios.post(this.expense_post_url + '/' + expense.id, expense).then(function(response) {
						//find the expense in our expenses, and update it...
						for (i in app.expenses) {
							if (app.expenses[i].id == expense.id) {
								app.expenses[i] = expense;
							}
						}
						app.dialogFormVisible = false;
						app.loading = false;
						app.refetchBudget();
					}).catch(function() {
						console.log('error fetching budget data.');
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