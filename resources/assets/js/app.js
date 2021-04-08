
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

function formatDate(dateString) {
    var options = { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' };
    dateParts = dateString.split(/\s*\-\s*/g);
    timeParts = dateParts[2].split(/\s* \s*/g);
    dateParts[2] = timeParts.shift();
    var date  = new Date();
    date.setFullYear(Number(dateParts[0]));
    date.setMonth(Number(dateParts[1]) - 1);
    date.setDate(Number(dateParts[2]));
    return date.toLocaleDateString("en-US", options); // Saturday, September 17, 2016
}
Vue.filter('format_date', formatDate);

Vue.component('ExpenseDialog', require('./components/ExpenseDialog.vue').default);
Vue.component('SplitExpenseDialog', require('./components/SplitExpenseDialog.vue').default);
Vue.component('ExpenseRow', require('./components/ExpenseRow.vue').default);
Vue.component('BudgetRow', require('./components/BudgetRow.vue').default);
Vue.component('CategoryDialog', require('./components/CategoryDialog.vue').default);

/*const app = new Vue({
    el: '#app'
});*/

const queryString = require('query-string');
