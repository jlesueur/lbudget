<template>
<tr :style="{backgroundColor: budget.color ? budget.color : 'white'}">
	<td>
		<a :href="'#budget/' + budget.id" @click="$emit('edit-budget', budget)">{{budget.name}}</a>
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
</template>
<script>
export default {
	props: {
		budget: Object,
	},
	computed: {
		budgetUsed() {
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
		budgetAvailable() {
			var allocated = 0;
			if (!this.budget.style || this.budget.id == 0) {
				return allocated;
			}
			if (this.budget.style == 'allowance') {
				allocated = Number.parseFloat(this.budget.category_period.amount);
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
		budgetAllocated() {
			if (!this.budget.category_period.amount) {
				return 0;
			}
			return this.budget.category_period.amount;
		}
	},
}
</script>