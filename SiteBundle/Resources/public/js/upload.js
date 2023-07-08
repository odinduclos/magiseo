$(document).ready(function() {

    $('input#form_url')
//            .prop('value', 'http://')
            .prop('placeholder', "URL de votre site");
    
    $('form#archiveForm').ajaxForm({
        datatype: 'json',
        beforeSend: function(e) {
            $('#uploadStatus').hide();
            $('#uploadStatusMsg').empty();
            var percentVal = '0%';
            $('.bar').width(percentVal)
            $('.percent').html(percentVal);
            $('input#submit_archive').prop('disabled', true);
        },
        uploadProgress: function(event, position, total, percentComplete) {
            var percentVal = percentComplete + '%';
            $('.bar').width(percentVal)
            $('.percent').html(percentVal);
        },
        success: function(data) {
            // SUCCES !
            if (data.status === "OK") {
                var percentVal = '100%';
                $('.bar').width(percentVal);
                $('.percent').html(percentVal);

                $.ajax({url: Routing.generate('magiseo_crawler_start', {id: data.id}),
                    type: 'GET'})
                        .success(function(data2) {
                            console.log(data2);
                            $('div#accordion').css('display', 'none');
                            $('div#progress').css('display', '');
                            $('html,body').animate({scrollTop: $('#progress').offset().top},'slow');
                            
                            var t = setInterval(function() {
                                $.ajax({url: Routing.generate('magiseo_crawler_state', {id: data2}),
                                    type: 'GET'})
                                        .success(function(data3) {
                                            console.log(data3);

                                            var percent = Math.round(parseInt(data3.parsed) * 100 / parseInt(data3.found));

                                            $('div#progress p#state').html(data3.state);
                                            $('div#progress span.progressDiagnos').html(percent);
                                            $('div#progress progress#progressBarDiagnos').val(percent);
                                            $('div#progress div#urlParsed').html(data3.page_parsed);

                                            if (data3.end) {
                                                clearInterval(t);
                                                $('#nextStep button').prop('disabled', false);
                                                $('#accordion').hide();
                                                $('.intro div#progress').hide();
                                                $('#nextStep').show();
                                            }
                                        })
                                        .error(function(jqXHR, textStatus, errorThrown) {
                                            clearInterval(t);
                                            $('#diagnosticErrorMsg').show();
//                                            $('#diagnosticErrorMsg p').html(textStatus);
                                        });
                            }, 1000);
                            var $btn = $('#nextStep a#nextStepBtn');
                            var nextStepHref = $btn.prop('href');
                            $btn.prop('href', nextStepHref + '/' + data2);
                        });

                // PROBLEME :(
            }
            else if (data.status === "KO")
            {
                $('input#submit_archive').prop('disabled', false);
                var percentVal = '0%';
                $('.bar').width(percentVal)
                $('.percent').html(percentVal);
                $('#uploadStatus').show();
                $('#uploadStatusMsg').html(data.msg);
            }
        },
        // GROS BUG
        error: function(xhr) {
            $('input#submit_archive').prop('disabled', false);
            $('#uploadStatus').show().html(xhr.responseText);
        },
        complete: function() {
        }
    });
});
