$(function() {
    $("#modal-dialog").dialog({
        modal: true,
        hide: 'fade',
        show: 'fade',
        autoOpen: false
    });

    $('#update-motivations-button').click(function(e) {
        e.preventDefault();

        var textarea = $('#motivations-textarea');
        var motivations = textarea.val();

        // Get Twig-defined variable for URL of AJAX call
        var ajax_path = $(this).attr('href');

        $.ajax({
            url: ajax_path,
            data: {
                motivations: motivations
            },
            method: 'post',
            beforeSend: function(){
                $("#modal-dialog").dialog('open');
                $('#modal-dialog').html($('.loader')[0].outerHTML);
                $('#modal-dialog .loader').show();
            },
            success: function(response) {
                $('#modal-dialog').html('<p>Vos motivations ont bien été mises à jour.</p>');
                $('.contract-motivations').html(response);
            },
            error: function() {
                $('#modal-dialog').html("<p>OUPS, erreur imprévue</p>");
            }
        });
    });

    $(".incr-btn").on("click", function (e) {
        var $button = $(this);
        var oldValue = $button.parent().find('.quantity').val();
        $button.parent().find('.incr-btn[data-action="decrease"]').removeClass('inactive');
        if ($button.data('action') == "increase") {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            // Don't allow decrementing below 0
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
                $button.addClass('inactive');
            }
        }
        $button.parent().find('.quantity').val(newVal);
        e.preventDefault();
    });

});

