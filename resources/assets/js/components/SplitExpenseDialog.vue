<template>
  <el-dialog
    :title="modalTitle"
    :loading="loading"
    :visible.sync="dialogFormVisible"
    v-on:open="openWithNewExpense"
  >
    <el-form
      v-if="internalFormExpense != undefined"
      :model="internalFormExpense"
      label-position="right"
      label-width="6.5em"
    >
      <h4>Total: {{ internalFormExpense.amount | round(2) }}</h4>
      <el-col :span="10">
        <div>
          {{ originalCategory.name }}
        </div>
        <div>
          {{ originalAmount | round(2) }}
        </div>
      </el-col>
      <el-col :span="4"> &lt;=&gt; </el-col>
      <el-col :span="10">
        <el-form-item label="Category">
          <el-select tabindex="1" v-model="newCategoryId" filterable>
            <el-option value=""></el-option>
            <el-option
              v-for="category in categories"
              :value="category.id"
              :key="category.id"
              :style="{backgroundColor: category.color}"
              :label="category.name"
              >{{ category.name }}</el-option
            >
          </el-select>
        </el-form-item>
        <el-form-item label="Amount">
          <el-input
            style="width: 6.5em"
            type="number"
            placeholder="Amount"
            v-model="newAmount"
          />
        </el-form-item>
        <el-form-item label="Comment">
          <el-input type="textarea" v-model="newComment" />
        </el-form-item>
      </el-col>
    </el-form>
    <span slot="footer">
      <el-button type="info" @click="$emit('cancel')">Cancel</el-button>
      <el-button type="primary" :loading="loading" @click="saveForm">
        Save
      </el-button>
    </span>
  </el-dialog>
</template>
<script>
export default {
  data() {
    return {
      internalFormExpense: this.formExpense,
      newAmount: 0,
      newCategoryId: null,
      newComment: ''
    };
  },
  methods: {
    openWithNewExpense() {
      this.internalFormExpense = this.formExpense;
    },
    saveForm() {
      this.$emit("save", {
        "original_expense_id": this.formExpense.id,
        "category_id": this.newCategoryId,
        "amount": this.newAmount,
        "comment": this.newComment
      });
    },
  },
  props: {
    loading: Boolean,
    formExpense: Object,
    dialogFormVisible: Boolean,
    categories: Object,
  },
  computed: {
    modalTitle() {
      return `Split Expense`;
    },
    originalCategory() {
      for (const category in this.categories) {
        if (
          this.categories[category].id == this.internalFormExpense.category_id
        ) {
          return this.categories[category];
        }
      }
      return {
        name: "No Category",
      };
    },
    originalAmount() {
      if (this.internalFormExpense.id != undefined) {
        return this.internalFormExpense.amount - this.newAmount;
      }
      return 0;
    },
  },
};
</script>