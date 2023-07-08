
// IIFE - Immediately Invoked Function Expression
(function (yourcode) {
    // The global jQuery object is passed as a parameter
    yourcode(window.jQuery, window, document);
}(function ($, window, document) {
    
    // The $ is now locally scoped 

    $(function () {
        
        // The DOM is ready!
        
        $('#logoPDF').on('click', getPdfRapport);
        
    });
    
    // The rest of your code goes here!

    function getPdfRapport(e) {
        e.preventDefault();

        var $this = $(this);
        
        if ($this.hasClass('disabledLink')) {
            return;
        }        
        
        $this.addClass('disabledLink').find('button').prop('disabled', true);
        $this.find('img').addClass('hide');
        $this.find('span.glyphicon-time').removeClass('hide');

        var rapportId = $(this).data('id');

        $.ajax({
            url: Routing.generate('magiseo_site_getpdfrapport'),
            data: { id: rapportId },
	    type: 'POST',
            success: function(data) {
                if (data.status === undefined) {
                    return;
                }
                
                if (data.status === "KO") {
                    if (data.msg !== undefined) {
                        alert("Erreur :" + data.msg);
                    }
                    
                    return;
                }
                
                if (data.pdfPath === undefined) {
                    return;
                }
                
                window.location.href = data.pdfPath;
            },
            error: function(data) {
                if (data.statusText === undefined)
                    return;

                alert(data.statusText);
            },
            complete: function() {
                $this.removeClass('disabledLink').find('button').prop('disabled', false);
                $this.find('img').removeClass('hide');
                $this.find('span.glyphicon-time').addClass('hide');
            }
        });
    }
}
));