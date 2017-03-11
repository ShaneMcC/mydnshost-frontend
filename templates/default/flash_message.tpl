	<div class="alert alert-{{ type }} alert-dismissible fade show" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
		<h4 class="alert-heading">{{ title }}</h4>
		{{ message }}
	</div>


	<script type="text/javascript">
		$(".alert").alert()
	</script>
