<template>
<tr :style="{backgroundColor: expense.category_id ? selectCategories[expense.category_id].color : 'white'}">
	<td>
		<a :href="'#expense/' + expense.id" tabindex="-1" @click="$emit('edit-expense', expense)">edit</a>
	</td>
	<td v-bind:id="'expenseCatCell' + expense.id">
		<select :value="expense.category_id" tabindex="1" @change="updateCategory(expense, $event)">
			<option value=""></option>
			<option v-for="category in selectCategories" :value="category.id" :key="category.id">{{category.name}}</option>
		</select>
		<span style="display: none">{{expense.category_id ? selectCategories[expense.category_id].name : 'Non-budget transaction'}}</span>
	</td>
	<td>
		<span v-if="expense.credit"><b><em>${{(expense.amount / expense.span_months) | round(2)}}</em></b></span>
		<span v-else>(${{(expense.amount / expense.span_months) | round(2)}})</span>
		<div v-if="expense.span_months >1"><small>over {{expense.span_months}} months</small></div>
	</td>
	<td>
		{{expense.description}}
	</td>
	<td>
		{{accounts[expense.account_id].name}}
	</td>
	<td>{{expense.ymdt}}</td>
	<td>
		<a href="#" v-on:click="deleteExpense" class="delete" tabindex="-1">X</a>
	</td>
</tr>
</template>
<script>
export default {
  props: {
	  expense: Object,
	  categories: Object,
	  accounts: Object,
	  deletedCategories: Object
  },
	methods: {
		deleteExpense() {
			var component = this;
			axios.delete('/expense/' + this.expense.id).then(function() {
				component.$emit('deleted', component.expense.id);
			});
		},
		updateCategory(expense, event) {
			var component = this;
			axios.post("/expense/" + expense.id, {
				'category_id': event.target.value
			}).then(function() {
				expense.category_id = event.target.value;
				component.$emit('recategorized');
			}).catch(function(error) {
				event.target.value = expense.category_id;
				console.log("error updating the category", error);
			});
		}
	},
	computed: {
		selectCategories () {
			if (this.expense.category_id !== null && undefined == this.categories[this.expense.category_id]) {
				var newCategoryList = Object.assign({}, this.categories);
				newCategoryList[this.expense.category_id] = this.deletedCategories[this.expense.category_id];
				return newCategoryList;
			}
			return this.categories;
		}
	}
}
</script>