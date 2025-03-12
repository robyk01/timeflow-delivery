(function($){

    function clearTimeSlotSelection(callback){
        $.ajax({
            url: timeflow_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'save_time_slot_selection',
                security: timeflow_ajax_fees_params.nonce,
                time_slot_selection: '-'
            },
            success: function(response){
                console.log('Cleared time slot selection.');
                $(document.body).trigger('update_checkout');
                if (typeof callback === 'function') {
                    callback();
                }
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.error('Error clearing time slot selection:', textStatus, errorThrown);
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }

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
                var timeSlotSelect = $('#time_slot_selection');
                timeSlotSelect.empty();
                timeSlotSelect.append('<option value="">-</option>');

                if (response && response.success && response.data && response.data.length > 0) {
                    $.each(response.data, function(index, timeSlot){
                        timeSlotSelect.append('<option value="' + timeSlot.id + '">' + timeSlot.range + ' Fee: ' + timeSlot.fee + '</option>');
                    });
                } else {
                    console.warn('No matching time slots found for the selected date/delivery method.');
                }
                timeSlotSelect.val('');
                $(document.body).trigger('update_checkout');
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.error('AJAX FAILED:', textStatus, errorThrown);
            }
        });
    }

    function setDeliveryTypeSession(deliveryType) {
        $.ajax({
            url: timeflow_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'save_delivery_type_session',
                security: timeflow_ajax_params.delivery_nonce, 
                delivery_type: deliveryType
            },
            success: function(response) {
                if (response.success) {
                    console.log('Delivery type session set:', deliveryType);
                    $(document.body).trigger('update_checkout');
                } else {
                    console.error('Failed to set delivery type:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error setting delivery type session:', textStatus, errorThrown);
            }
        });
    }

    function setDateSlotSession(dateSlot) { 
        $.ajax({
            url: timeflow_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'save_date_slot_session', 
                security: timeflow_ajax_params.delivery_nonce,
                date_slot_selection: dateSlot
            },
            success: function(response) {
                if (response.success) {
                    console.log('Date slot session set:', dateSlot);
                    $(document.body).trigger('update_checkout');
                } else {
                    console.error('Failed to set date slot:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error setting date slot session:', textStatus, errorThrown);
            }
        });
    }


    $(document).ready(function(){
        var $dateInputField = $('#date_slot_selection');
        var $shippingButton = $('#shipping');
        var $pickupButton = $('#pickup');
        var $deliveryInput = $('#delivery-type');

        $dateInputField.on('change', function(){
            var selectedDate = $(this).val();
            var deliveryType = $deliveryInput.val();

            setDateSlotSession(selectedDate);
            
            clearTimeSlotSelection(function(){
                updateTimeSlots(selectedDate, deliveryType);
            });
        });

        $shippingButton.on('click', function(e){
            e.preventDefault();
            var deliveryType = $(this).data('delivery-type');
            $deliveryInput.val(deliveryType);
            $shippingButton.addClass('selected');
            $pickupButton.removeClass('selected');

            setDeliveryTypeSession(deliveryType);

            var selectedDate = $dateInputField.val();
                clearTimeSlotSelection(function(){
                    updateTimeSlots(selectedDate, deliveryType);
                });
        });

        $pickupButton.on('click', function(e){
            e.preventDefault();
            var deliveryType = $(this).data('delivery-type');
            $deliveryInput.val(deliveryType);
            $pickupButton.addClass('selected');
            $shippingButton.removeClass('selected');

            setDeliveryTypeSession(deliveryType);

            var selectedDate = $dateInputField.val();
                clearTimeSlotSelection(function(){
                    updateTimeSlots(selectedDate, deliveryType);
                });
        });

        $('form.checkout').on('checkout_place_order', function() {
            var deliveryType = $('#delivery-type').val();
            var dateSelected = $('#date_slot_selection').val();
            var timeSlotSelected = $('#time_slot_selection').val();
    
            if (!deliveryType) {
                alert('Please select a delivery method.');
                $('#timeflow-delivery-selection').get(0).scrollIntoView();
                return false;
            }
    
            if (!dateSelected) {
                alert('Please select a delivery date.');
                $('#date_slot_selection').focus();
                return false;
            }
    
            if (!timeSlotSelected) {
                alert('Please select a delivery time slot.');
                $('#time_slot_selection').focus();
                return false;
            }
    
            return true;
        });

    });

})(jQuery);