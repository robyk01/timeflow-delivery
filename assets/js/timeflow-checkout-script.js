(function($){

    $(document).ready(function(){
        var $dateInputField = $('#date_slot_selection');

        $dateInputField.on('change', function(){
            var selectedDate = $(this).val();
            if (selectedDate){
                $.ajax({
                    url: timeflow_ajax_params.ajax_url,
                    type: 'POST',
                    data: {
                       action: 'timeflow_get_time_slots',
                       security: timeflow_ajax_params.security_nonce,
                       date_slot_selection: selectedDate
                    },
                    success: function(response){
                        if (response && response.success){
                            var timeSlotSelect = $('#time_slot_selection');
                            timeSlotSelect.empty();
                            timeSlotSelect.append('<option>-</option>');
                            $.each(response.data, function(index, timeSlot){
                                timeSlotSelect.append('<option value="' + timeSlot.id + '">' + timeSlot.range + '</option>');
                            });
                        }
                        else{
                            console.error('AJAX Error:', response.data);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown){
                        console.error('AJAX FAILED:', textStatus, errorThrown);
                    }
                });
            }
            else{
                console.log('No date selected');
            }
        });
    });

})(jQuery);