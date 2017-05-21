$(function() {
	$('a[data-action="addUserDomain"]').click(function () {
		var okButton = $('#createUserDomain button[data-action="ok"]');
		okButton.text("Create");

		okButton.off('click').click(function () {
			if ($("#createUserDomainForm").valid()) {
				$("#createUserDomainForm").submit();
				$('#createUserDomain').modal('hide');
			}
		});

		var cancelButton = $('#createUserDomain button[data-action="cancel"]');
		cancelButton.off('click').click(function () {
			$("#createUserDomainForm").validate().resetForm();
		});

		$('#createUserDomain').modal({'backdrop': 'static'});
		return false;
	});

	$("#createUserDomainForm").validate({
		highlight: function(element) {
			$(element).closest('.form-group').addClass('has-danger');
		},
		unhighlight: function(element) {
			$(element).closest('.form-group').removeClass('has-danger');
		},
		errorClass: 'form-control-feedback',
		rules: {
			domainname: {
				required: true
			}
		},
	});

	$(".alert").alert()

	$("input[data-search-top]").on('input', function() {
		var value = $(this).val();
		var searchTop = $(this).data('search-top');

		if (value == "") {
			$(searchTop).find("[data-searchable-value]").show();
		} else {
			var match = new RegExp('^.*' + escapeRegExp(value) + '.*$', 'i');

			$(searchTop).find("[data-searchable-value]").each(function() {
				var show = false;

				for (val in $(this).data('searchable-value').split(" ")) {
					if ($(this).data('searchable-value').match(match)) {
						show = true;
						break;
					}
				}

				if (show) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}
	});
});

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

var entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;',
  '`': '&#x60;',
  '=': '&#x3D;'
};

function escapeHtml (string) {
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}
