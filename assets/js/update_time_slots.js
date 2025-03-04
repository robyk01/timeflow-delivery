(function($){

    $(document).ready(function(){
        var $dateInputField = $('#date_slot_selection');
        var $shippingButton = $('#shipping');
        var $pickupButton = $('#pickup');
        var $deliveryInput = $('#delivery-type');

        function updateTimeSlots(selectedDate, deliveryType){
            if (!selectedDate || !deliveryType){
                console.warn('No date or delivery type selected');
                return;
            }
                $.ajax({
                    url: timeflow_ajax_params.ajax_url,
                    type: 'POST',
                    data: {
                       action: 'timeflow_get_time_slots',
                       security: timeflow_ajax_params.security_nonce,
                       date_slot_selection: selectedDate,
                       delivery_type: deliveryType
                    },
                    success: function(response){
                        if (response && response.success){
                            var timeSlotSelect = $('#time_slot_selection');
                            timeSlotSelect.empty();
                            timeSlotSelect.append('<option value="">-</option>');
                            $.each(response.data, function(index, timeSlot){
                                timeSlotSelect.append('<option value="' + timeSlot.id + '">' + timeSlot.range + ' ' + 'Fee: ' + timeSlot.fee + '</option>');
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

        $dateInputField.on('change', function(){
            var selectedDate = $(this).val();
            var deliveryType = $deliveryInput.val();
            updateTimeSlots(selectedDate, deliveryType);
        });

        $shippingButton.on('click', function(e){
            e.preventDefault();
            var deliveryType = $(this).data('delivery-type');
            $deliveryInput.val(deliveryType);

            $shippingButton.addClass('selected')
            $pickupButton.removeClass('selected');

            var selectedDate = $dateInputField.val();
            updateTimeSlots(selectedDate, deliveryType);
        });

        $pickupButton.on('click', function(e){
            e.preventDefault();
            var deliveryType = $(this).data('delivery-type');
            $deliveryInput.val(deliveryType);

            $pickupButton.addClass('selected');
            $shippingButton.removeClass('selected');

            var selectedDate = $dateInputField.val();
            updateTimeSlots(selectedDate, deliveryType);
        });

    });

})(jQuery);