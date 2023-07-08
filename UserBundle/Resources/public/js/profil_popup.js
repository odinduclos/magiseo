$(document).ready(function () {

    $('button#modif_infos_compte').click(function () {
        $('#ajax_loaderImg_infosCompte').show();
	$.ajax({url: Routing.generate('magiseo_user_editprofil'),
		type: 'GET'})
	    .success(function(data) {
                $('#ajax_loaderImg_infosCompte').hide();
		$('#modify_infos').html(data);
		$('#modify_infos input[type=submit]').click(function (e) {
		    e.preventDefault();
                    
//		    $.ajax({url: Routing.generate('fos_user_security_check'),
//			    type: 'POST',
//			    data: $('#loginDialog form').serialize()})
//			.success(function (data) {
//			    console.log(data);
//			    if (data.success)
//				window.document.location = window.document.location;
//			    if (!data.success && data.message)
//			    {
//				if ($('#loginDialog div#error').html() == undefined)
//				    $('#loginDialog').prepend('<div id="error" style="display: none;">' + data.message + '</div>');
//				$('#loginDialog div#error').show();
//				var t = setTimeout(function () { clearTimeout(t); $('#loginDialog div#error').hide();}, 5000);
//			    }
//			})
//			.fail(function (data) {
//			    console.log(data);
//			});
		});
	    })
	    .fail(function(e) {
		console.log(e);
	    });
	$('#modify_infos').dialog({modal: true, width: 450, resizable: false});
    });

});
