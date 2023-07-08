function Form_ChangeState($btn, $dataType, $toEdit, $input) {
    var $buttonSpan = $btn.find('span');
    var $textSpan = $('span#' + $dataType);

    if ($toEdit)
        Form_FromEditToSave($btn, $buttonSpan, $input, $textSpan);
    else
        Form_FromSaveToEdit($btn, $buttonSpan, $input, $textSpan);
}

function Form_FromSaveToEdit($btn, $buttonSpan, $input, $textSpan) {
    $btn.removeClass('save').addClass('edit').attr('title', $btn.attr('data-title-edit'));
    $buttonSpan.removeClass('fa fa-save').addClass('fa fa-edit');
    $input.addClass('hide');
    $textSpan.show();
    $btn.siblings("button.cancel").addClass("hide");
}

function Form_FromEditToSave($btn, $buttonSpan, $input, $textSpan) {
    $btn.removeClass('edit').addClass('save').attr('title', $btn.attr('data-title-save'));
    $buttonSpan.removeClass('fa fa-edit').addClass('fa fa-save');
    $textSpan.hide();
    if ($input != null) {
        $input.removeClass('hide').focus().select();
    }
}

$(document).ready(function() {

    $('button.cancel').click(function() {
        var $dataType = $(this).attr('data-type');
        var $btn = $(this).siblings('button[class!="cancel"]');
        var $btnSpan = $btn.find('span');

        if ($btn.attr('data-type') == "password") {
            var $spanPassword = $('span#password');
            $btn.prop('disabled', false);
            $btn.removeClass('save').addClass('edit');
            $btn.find("span").removeClass('fa fa-save').addClass('fa fa-edit')
                    .attr('title', $btn.attr('data-title-edit'));
            $spanPassword.show();
            $('#newPasswordForm').addClass("hide");
        } else {
            $("#" + $dataType + "_errorMsg").hide();
            var $textSpan = $('span#' + $btn.attr('data-type'));

            Form_FromSaveToEdit($btn, $btnSpan, $('input#' + $dataType), $textSpan)
        }
        $(this).addClass('hide')
    });

    $('div.controls input').keyup(function(e) {
        // esc
        if (e.keyCode == 27) {
            
            if ($(this).attr('type') == "password") {
                var $btn = $(this).parents('form').siblings('button[class!="cancel"]');
                $(this).parents('form').siblings('button.cancel').addClass('hide')
                var $btnSpan = $btn.find('span');
                var $spanPassword = $('span#password');
                $btn.prop('disabled', false);
                $btn.removeClass('save').addClass('edit');
                $btn.find("span").removeClass('fa fa-save').addClass('fa fa-edit')
                        .attr('title', $btn.attr('data-title-edit'));
                $spanPassword.show();
                $('#newPasswordForm').addClass("hide");
            } else {
                $btn = $(this).siblings('button[class!="cancel"]');
                $(this).siblings('button.cancel').addClass('hide')
                var $dataType = $btn.attr('data-type');
                $("#" + $dataType + "_errorMsg").hide();
                $btnSpan = $btn.find('span');
                var $textSpan = $('span#' + $btn.attr('data-type'));

                Form_FromSaveToEdit($btn, $btnSpan, $(this), $textSpan)
            }
        }   
    });
    $('body').delegate('button.edit', 'click', function() {
        var $this = $(this);
        var $dataType = $this.attr('data-type');
        var $input = $('input#' + $dataType);
        
        $(this).siblings('button.cancel').removeClass('hide');
        
        if($dataType === "password") {
            var $spanPassword = $('span#password');
            $spanPassword.hide();
            $('#newPasswordForm').removeClass("hide");
            $('input#password1').focus().select();
            $input = null;
        }
        Form_ChangeState($this, $dataType, true, $input);
    });

    $('body').delegate('button.save', 'click', function() {
        $("#" + $dataType + "_errorMsg").hide();
        var $this = $(this);
        var $dataType = $this.attr('data-type');
        var $input = $('input#' + $dataType);
        
        $this.prop('disabled', true);
        
        var $val = "";
        if ($dataType === "password") {
            var $spanPassword = $('span#password');
            $val = {First : $('input#' + $dataType + '1').val(),
                Second: $('input#' + $dataType + '2').val()};
            if ($val.First == "" || $val.Second == "") {
                alert('Veuillez remplir tous les champs.');
                $this.prop('disabled', false);
                return;
            }
            if ($val.First != $val.Second) {
                $this.prop('disabled', false);
                alert('Les mots de passe ne sont pas identiques.');
                return;
            }
        } else {
            $val = { data : $input.val() };
            if ($val.data == "") {
                alert('Veuillez remplir le champs requis.');
                $this.prop('disabled', false);
                return;
            }
        }
        
        $.ajax({
            url: Routing.generate('magiseo_user_change_' + $dataType),
            data: $val,
            type: 'POST'})
                .success(function(data) {
                    $this.prop('disabled', false);
                    if ($dataType === "password") {
                        $this.removeClass('save').addClass('edit');
                        $this.find("span").removeClass('fa fa-save').addClass('fa fa-edit');
                        $spanPassword.show();
                        $('#newPasswordForm').addClass("hide");
                        $this.siblings("button.cancel").addClass("hide");
                    } else {
                        Form_ChangeState($this, $dataType, false, $input);
                        $('span#' + $dataType).html(data.value).removeClass('info_missing');
                    }
                })
                .error(function(data) {
                    
                    $this.prop('disabled', false);
                    $("#" + $dataType + "_errorMsg").show().find('span').html(data.responseJSON.msg);
                    
//                    alert(
//                    "msg: ["
//                    + data.responseJSON.msg 
//                    + "]\n"
//                    + "value: ["
//                    + data.responseJSON.value
//                    + "]")
//                    Form_ChangeState($this, $dataType, false, $input);
                });

    });
});
