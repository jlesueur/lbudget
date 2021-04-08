<template>
    <el-dialog 
        :title="modalTitle" :loading="loading" 
        :visible.sync="dialogFormVisible" v-on:open="openWithNewCategory">
		<el-form 
            :model="internalFormCategory" label-position="right" 
            label-width="6.5em">
            <el-form-item label="Name">
                <el-input type="text" v-model="internalFormCategory.name"/>
            </el-form-item>
			<el-form-item label="Style">
				<el-radio v-model="internalFormCategory.style" label="allowance"/>
                <el-radio v-model="internalFormCategory.style" label="savings"/>
			</el-form-item>
			<el-form-item label="Amount">
				<el-input style="width:6.5em" type="number" placeholder="Amount" v-model="internalFormCategory.category_period.amount"/>
			</el-form-item>
			<el-form-item label="Description">
				<el-input placeholder="What is it it about?" v-model="internalFormCategory.description"/>
			</el-form-item>
			<el-form-item label="Comment">
				<el-input type="textarea" v-model="internalFormCategory.comments"/>
			</el-form-item>
		</el-form>
		<span slot="footer">
			<el-button type="info" @click="$emit('cancel')">Cancel</el-button>
			<el-button type="primary" :loading="loading" @click="$emit('save', internalFormCategory)">
				Save
			</el-button>
		</span>
	</el-dialog>
</template>
<script>
export default {
  data () {
    return {
        internalFormCategory: this.formCategory
    }
  },
  methods: {
      openWithNewCategory() {
          this.internalFormCategory = this.formCategory;
      }
  },
  props: {
      mode: String,
      loading: Boolean,
      formCategory: Object,
      dialogFormVisible: Boolean
  },
  computed: {
      modalTitle() {
          return `${ this.mode } Category`;
      }
  }
    
}
</script>