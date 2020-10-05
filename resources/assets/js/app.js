
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



Vue.component('ExpenseDialog', require('./components/ExpenseDialog.vue').default);
Vue.component('ExpenseRow', require('./components/ExpenseRow.vue').default);

/*const app = new Vue({
    el: '#app'
});*/

const queryString = require('query-string');
