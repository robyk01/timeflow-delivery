(function($){
    $(document).ready(function(){
        var $timeSlot = $('#time_slot_selection');

        $timeSlot.on('change', function(){
            var selectedTimeSlotId = $(this).val(); 

            $.ajax({
                url: timeflow_ajax_fees_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_time_slot_selection',
                    time_slot_selection: selectedTimeSlotId,
                    security: timeflow_ajax_fees_params.security_nonce
                },
                success: function(response) {
                    console.log('Time slot saved to session via AJAX. Response:', response);
                    $.ajax({
                        url: timeflow_ajax_fees_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'reset_fee_added_flag',
                            security: timeflow_ajax_fees_params.security_nonce
                        },
                        success: function(response) {
                            console.log('Fee added flag reset. Response:', response);
                            $(document.body).trigger('update_checkout');
                        },
                        error: function(error) {
                            console.error('Error resetting fee added flag:', error);
                        }
                    });
                },
                error: function(error) {
                    console.error('Error saving time slot to session:', error);
                }
            });
        });
    });
})(jQuery);