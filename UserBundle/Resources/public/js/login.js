$(document).ready(function () {
    //$('div#changePasswdDialog').dialog({autoOpen: false, modal: true, width: 325});

    $('a#login').click(function () {
        $('#ajax_loaderImg').show();
	$.ajax({url: Routing.generate('fos_user_security_login'),
		type: 'GET'})
	    .success(function(data) {
                $('#ajax_loaderImg').hide();
		$('#loginDialog').html(data);
		$('#loginDialog input[type=submit]').click(function (e) {
		    e.preventDefault();
                    if ($('#loginDialog #username').val() === ""
                        || $('#loginDialog #password').val() === "")
                    {
                        show_error("Veuillez renseigner tous les champs.");
                        return;
                    }
                    var $btnInput = $(this);
                    $btnInput.prop("disabled", true);
		    $.ajax({url: Routing.generate('fos_user_security_check'),
			    type: 'POST',
			    data: $('#loginDialog form').serialize()})
			.success(function (data) {
			    console.log(data);
			    if (data.success)
				window.document.location = window.document.location;
			    if (!data.success && data.message)
			    {
                                $btnInput.prop("disabled", false);
                                show_error(data.message);
			    }
			})
			.fail(function (data) {
                            $btnInput.prop("disabled", false);
			    console.log(data);
			});
		});
	    })
	    .fail(function(e) {
		console.log(e);
	    });
	$('#loginDialog').dialog({modal: true, width: 235, resizable: false});
    });

    $('button#changePasswd').click(function () {
	$.ajax({url: Routing.generate('fos_user_change_password'),
		type: 'GET'})
	    .success(function (data) {
		$('div#changePasswdDialog').html(data);

		$('div#changePasswdDialog input[type=submit]').click(function (e) {
		    e.preventDefault();
		    e.stopPropagation();
		    $.ajax({url: Routing.generate('fos_user_change_password'),
			    type: 'POST',
			    data: $('div#changePasswdDialog form').serialize()})
			.success(function (data) {
			})
			.fail(function (data) {
			    console.log('fail');
			    console.log(data);
			});
		});

		$('div#changePasswdDialog').dialog("open")
	    });
    });
});

function show_error(msg) {
    if ($('#loginDialog div#error').html() == undefined)
        $('#loginDialog').prepend('<div id="error" class="alert alert-danger" style="display: none;">' + msg + '</div>');
    $('#loginDialog div#error').show();
    var t = setTimeout(function () { clearTimeout(t); $('#loginDialog div#error').hide();}, 5000);
}