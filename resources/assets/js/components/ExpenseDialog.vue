<template>
    <el-dialog :title="modalTitle" :loading="loading" :visible.sync="dialogFormVisible" v-on:open="openWithNewExpense">
		<el-form :model="internalFormExpense" label-position="right" label-width="6.5em">
			<el-form-item label="Credit">
				<el-switch v-model="internalFormExpense.credit"/>
			</el-form-item>
			<el-form-item label="Amount">
				<el-input style="width:6.5em" type="number" placeholder="Amount" v-model="internalFormExpense.amount"/>
			</el-form-item>
			<el-form-item label="Date">
				<el-col :span="7">
					<el-date-picker size="small" placeholder="When" v-model="internalFormExpense.ymdt"/>
				</el-col>
				<el-col :span="3">spread over</el-col>
				<el-col :span="4">
					<el-input type="number" placeholder="how long" v-model="internalFormExpense.span_months"/>
				</el-col>
				<el-col :span="3"> months</el-col>
			</el-form-item>
			<el-form-item label="Description">
				<el-input placeholder="Where is it from?" v-model="internalFormExpense.description"/>
			</el-form-item>
			<el-form-item label="Comment">
				<el-input type="textarea" placeholder="What is it good for?" v-model="internalFormExpense.comment"/>
			</el-form-item>
		</el-form>
		<span slot="footer">
			<el-button type="info" @click="$emit('cancel')">Cancel</el-button>
			<el-button type="primary" :loading="loading" @click="$emit('save', internalFormExpense)">
				Save
			</el-button>
		</span>
	</el-dialog>
</template>
<script>
export default {
  data () {
    return {
        internalFormExpense: this.formExpense
    }
  },
  methods: {
      openWithNewExpense() {
          this.internalFormExpense = this.formExpense;
      }
  },
  props: {
      mode: String,
      loading: Boolean,
      formExpense: Object,
      dialogFormVisible: Boolean
  },
  computed: {
      modalTitle() {
          return `${ this.mode } Expense`;
      }
  }
    
}
</script>