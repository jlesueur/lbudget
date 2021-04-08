@extends('layouts.app')
@section('content')
<style>
h1 {
		text-align: center;
	}
input[type="file"] {
	display: none;
}
</style>
<div id="app" class="container">
@verbatim
<h1>
	Welcome {{user.name}}
</h1>
<el-form :model="internalUser" label-position="right" label-width="6.5em">
	<el-form-item label="Name">
		<el-input type="text" v-model="internalUser.name"/>
	</el-form-item>
	<el-form-item label="Email">
		<el-input type="email" v-model="internalUser.email"/>
	</el-form-item>
	<el-button type="success">Save</el-button>
</el-form>
<el-tabs>
	<el-tab-pane label="Budget Categories">
		<table id="categories" class="table">
		<thead>
				<tr>
					<th>
						Name
					</th>
					<th>
						Envelope
						<el-tooltip content="Envelope style categories keep any unspent funds month-to-month">
							<i class="el-icon-question"></i>
						</el-tooltip>
					</th>
					<th>
						Amount
					</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(category, index) in formCategories" :key="category.id">
					<td>
						<el-input type="text" v-model="category.name" @change="updateCategoryName(index)"/>
					</td>
					<td>
						<el-checkbox v-model="category.style == 'savings'" @change="updateCategoryStyle(index)"/>
					</td>
					<td>
						<el-input type="number" v-model="category.amount" @change="updateCategoryAmount(index)"/>
					</td>
					<td>
						<a href="#" @click="deleteCategory(category.id)">X</a>
					</td>
				</tr>
			</tbody>
		</table>
	</el-tab-pane>
	<el-tab-pane label="Accounts">
		<table id="accounts" class="table">
			<thead>
				<tr>
					<th>
						Name
					</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="account in accounts" :key="account.id">
					<td>
						{{account.name}}
					</td>
					<td>
						<a href="#" @click="deleteAccount(account.id)">X</a>
					</td>
				</tr>
			</tbody>
		</table>
	</el-tab-pane>
@endverbatim
</div>
@endsection
@section('pagejs')
<script type="text/javascript">
	var vm = new Vue({
		el: '#app',
		methods: {
			succeeded: function(response, file, fileList) {
				const importId = response.import_id;
				window.location.href = "/import/" + importId + "/expenses";
			}
		},
		data: function() {
			return {
				user: {!! $user !!},
				internalUser: {!! $user !!},
				accounts: {!! $accounts !!},
				categories: {!! $categories !!},
				formCategories: {!! $categories !!}
			};
		},
		computed: {
			
		},
		methods: {
			deleteAccount: function(accountId) {

			},
			updateCategoryName: function(index) {
				newCategoryName = event.target.value;
				targetFormEl = event.target;
				app = this;
				if (newCategoryName !== this.categories[index].name) {
					axios.post("/category/" + this.categories[index].id ,{
						'name': event.target.value
					}).then(function() {
						app.categories[index].name = newCategoryName;
					}).catch(function() {
						targetFormEl.value = app.categories[index].name;
						console.log("error saving category name");
					});
				}
			},
			updateCategoryStyle: function(index) {
				newStyle = event.target.checked ? 'savings' : 'allowance';
				targetFormEl = event.target;
				app = this;
				if (newStyle !== this.categories[index].style) {
					axios.post("/category/" + this.categories[index].id ,{
						'style': newStyle
					}).then(function() {
						app.categories[index].style = newStyle;
						app.formCategories[index].style = newStyle;
					}).catch(function() {
						targetFormEl.checked = app.categories[index].style == 'savings';
						console.log("error saving category style");
					});
				}
			},
			updateCategoryAmount: function(index) {
				newAmount = event.target.value;
				targetFormEl = event.target;
				app = this;
				if (newAmount !== this.categories[index].name) {
					axios.post("/category/" + this.categories[index].id ,{
						'amount': newAmount
					}).then(function() {
						app.categories[index].amount = newAmount;
					}).catch(function() {
						targetFormEl.value = app.categories[index].amount;
						console.log("error saving category amount");
					});
				}
			}
		}

	});
</script>
@endsection