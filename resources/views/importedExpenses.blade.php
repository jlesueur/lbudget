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
<div id="app" class="container">
	<h1>
		<a v-if="prevImportLink" :href="prevImportLink">&lt;</a>
		Expenses Imported on {{importObj.created_at|format_date}}
		<a v-if="nextImportLink" :href="nextImportLink">&gt;</a>
	</h1>
	<el-col :span=10>
		<v-paginator :options="options" @update="updateResource" ref="vpaginator" :resource_url="resource_url"></v-paginator>
	</el-col>
	<el-col :span=12>&nbsp;</el-col>
	<el-col :span=2 style="text-align:right">
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
		<tr is="expense-row" v-for="expense in expenses" :expense="expense" :categories="categories" :deleted-categories="deletedCategories" :accounts="expenseAccounts" v-on:deleted="deleteExpense" :key="expense.id" v-on:edit-expense="editExpense"></tr>
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
		},
		data: function () {
			return {
				// The resource variables
				expenses: {},
				expenseAccounts: {},
				categories: {!! $categories !!},
				deletedCategories: {!! $deletedCategories !!},
				options: {},
				importObj: {!! $import !!},
				prevImportId: "{{$prevImportId}}",
				nextImportId: "{{$nextImportId}}",
				loading: false,
				formExpense: {},
				mode: "Edit",
				dialogFormVisible: false,
				// Here you define the url of your paginated API
				resource_url: "/import/{{$importId}}/expenses.json",
				expense_post_url: "/expense"
			};
		},
		computed: {
			prevImportLink: function() {
				if (this.prevImportId) {
					return '/import/' + this.prevImportId + '/expenses';
				}
				return undefined;
			},
			nextImportLink: function() {
				if (this.nextImportId) {
					return '/import/' + this.nextImportId + '/expenses';
				}
				return undefined;
			},
		},
		watch: {
			'importObj.id': function(newValue, oldValue) {
				var queryParams = queryString.parse(location.search);
				queryParams.importId = newValue;
				document.location.search = '?' + queryString.stringify(queryParams);
			}
		},
		methods: {
			updateResource: function(data) {
				this.expenses = data.expenses;
				this.expenseAccounts = data.expenseAccounts;
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