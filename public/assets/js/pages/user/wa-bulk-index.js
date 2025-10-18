"use strict";

$('.select2').select2();
$('.save-template').on('change',function(){
   if ($(this).is(':checked')) {
       $('.receivers').hide();
       $('.bulk_send_form').addClass('wa_instant_reload');
       $('.bulk_send_form').removeClass('ajaxform');
   }else{

       $('.bulk_send_form').removeClass('wa_instant_reload');
       $('.bulk_send_form').addClass('ajaxform');
       $('.receivers').show()
   }  

});



$(document).on('submit', '.wa_instant_reload', function (e) {
    e.preventDefault();

    let $this = $(this);
    let $submitBtn = $this.find('.submit-btn');
    let $oldSubmitBtn = $submitBtn.html();

    
        $.ajax({
            type: 'POST',
            url: this.action,
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $submitBtn.html($savingLoader).addClass('disabled').attr('disabled', true);
            },
            success: function (res) {
                $submitBtn.html($oldSubmitBtn).removeClass('disabled').attr('disabled', false);
                window.sessionStorage.hasPreviousMessage = true;
                window.sessionStorage.previousMessage = res.message ?? null;

                if (res.redirect) {
                    location.href = res.redirect;
                }
            },
            error: function (xhr) {
                $submitBtn.html($oldSubmitBtn).removeClass('disabled').attr('disabled', false);
                NotifyAlert('error', xhr.responseJSON);
                showInputErrors(xhr.responseJSON);
            }
        });
    
});