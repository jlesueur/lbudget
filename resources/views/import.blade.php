@extends('layouts.app')
@section('content')
<style>
h1 {
		text-align: center;
	}
</style>
<h1>
	Import transactions from a bank or credit card download.
</h1>
<div id="app" class="container">
	<el-upload
		action="/importExpenses"
		:headers="{'x-csrf-token': '{{csrf_token()}}'}"
		>
		<el-button size="small" type="primary">Upload a file</el-button>
		<div slot="tip" class="el-upload__tip">OFX, QFX files preferred</div>
	</el-upload>
</div>
@endsection
@section('pagejs')
<script type="text/javascript">
	var vm = new Vue({
		el: '#app'
	});
</script>
@endsection