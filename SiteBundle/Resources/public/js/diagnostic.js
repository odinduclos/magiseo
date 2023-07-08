function getStatus(id, t)
{
    $.ajax({url: Routing.generate('magiseo_crawler_state', {id: id}),
	    type: 'GET'})
        .success(function(data) {
            console.log(data);

            var percent = Math.round(parseInt(data.parsed) * 100 / parseInt(data.found));

            $('div#progress span#state').html(data.state);
            $('div#progress span.progressDiagnos').html(percent);
            $('div#progress progress#progressBarDiagnos').val(percent);
            if (percent !== 100 && $('#doneIcon').hasClass('hide')) {
                $('div#progress div#urlParsed').html(data.page_parsed);
            }
            if (percent === 100) {
                $('#doneIcon').removeClass('hide')
            }

            
            if (data.end) {
                clearInterval(t);
                $('#nextStep button').prop('disabled', false);
                $('#accordion').hide();
                $('.intro div#progress').hide();
                $('#nextStep').show();
            }
        });
}

function changeViewToRun(id)
{
    console.log('repport id: ' + id);
    $('div#accordion').css('display', 'none');
    $('div#progress').css('display', '');
    $('html,body').animate({scrollTop: $('#progress').offset().top},'slow');

    var t = setInterval(function() {
	getStatus(id, t);
    }, 1000);

    $('#nextStep a#nextStepBtn').attr('href', $('#nextStep a#nextStepBtn').attr('href') + '/' + id);
}

$(document).ready(function() {
    $.ajax({url: Routing.generate('magiseo_crawler_state'),
	    type:'GET'}).success(function (data) {
		if (data && data.id) {
                    if (data.end) {
                        $('#alreadyFinishedReport a.btn').attr('href', $('#nextStep a#nextStepBtn').attr('href') + '/' + data.id);
                        $('#alreadyFinishedReport').hide().removeClass('hide').slideDown();
                    } else {
                        changeViewToRun(data.id);
                    }
                }
	    });

    $('input#form_url').prop('placeholder', "URL de votre site");

    $('form#diagnosticForm button#analyze').click(function(e) {
        e.preventDefault();

        var url = $('form#diagnosticForm input#url').val();

        if (url == "" || url == "http://") {
            $('#urlStatusMsg').html("Veuillez entrer une adresse web (URL) valide.");
            $('#urlStatus').show();
            return false;
        }
        $('input#url').css('background-size', '16px');
        $('button#analyze').prop('disabled', true);
        $('#urlStatus').hide();

        $.ajax({url: Routing.generate('magiseo_crawler_start', {_url: url}),
                type: 'GET'})
            .success(function(data) {
		changeViewToRun(data);
            })
            .error(function(data) {
                $('#urlStatus').show();
                $('#urlStatusMsg').html(data.responseJSON.msg);
                $('button#analyze').prop('disabled', false);
                $('input#url').css('background-size', '0px');
            });
    });
});
