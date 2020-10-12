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
<h1>
	Import transactions from a bank or credit card download.
</h1>

<div id="app" class="container">
	<el-row>
		<el-col :span="18">
	<el-upload
		action="/importExpenses"
		:headers="{'x-csrf-token': '{{csrf_token()}}'}"
		:on-success="succeeded"
		>
		<el-button size="small" type="primary">Upload a file</el-button>
		<div slot="tip" class="el-upload__tip">OFX, QFX files preferred</div>
	</el-upload>
		</el-col>
		@verbatim
		<el-col :span="6">
			<a :href="lastImportUrl">Last Import ({{lastImport.created_at}})</a>
		</el-col>
		@endverbatim
	</el-row>
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
				lastImport: {!! $lastImport !!}
			};
		},
		computed: {
			lastImportUrl: function() {
				return "/import/" + this.lastImport.id + "/expenses";
			}
		}

	});
</script>
@endsection