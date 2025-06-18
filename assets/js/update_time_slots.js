(function($) {
    'use strict';

    function clearTimeSlotSelection(callback){
        $.ajax({
            url: timeflowCheckoutParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_time_slot_selection',
                security: timeflowCheckoutParams.deliveryNonce,
                time_slot_selection: '-',
                clear_fee: true
            },
            success: function(response){
                console.log('Cleared time slot selection and fee.');
                $('#time_slot_selection').val('').prop('disabled', true);
                //$('.timeflow_time_slot').hide();
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
        
        // Show the time slot container
        $('.timeflow_time_slot').show();
        
        $.ajax({
            url: timeflowCheckoutParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_time_slots',
                security: timeflowCheckoutParams.getSlotsNonce,
                date_slot_selection: selectedDate,
                delivery_type: deliveryType
            },
            success: function(response){
                var timeSlotSelect = $('#time_slot_selection');
                timeSlotSelect.empty();
                timeSlotSelect.append('<option value="">' + timeflowCheckoutParams.i18n.selectTimePrompt + '</option>');

                if (response && response.success && response.data && response.data.length > 0) { 
                    $.each(response.data, function(index, timeSlot){
                        var feeText = timeSlot.fee ? ' (Taxă: ' + timeSlot.fee + ')' : '';
                        timeSlotSelect.append('<option value="' + timeSlot.id + '">' + timeSlot.range + feeText + '</option>');
                    });
                    timeSlotSelect.prop('disabled', false);
                } else {
                    console.warn('No matching time slots found for the selected date/delivery method.');
                    timeSlotSelect.append('<option value="" disabled>' + timeflowCheckoutParams.i18n.noSlotsAvailable + '</option>');
                    timeSlotSelect.prop('disabled', true);
                }
                timeSlotSelect.val('');
                $(document.body).trigger('update_checkout');
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.error('AJAX FAILED:', textStatus, errorThrown);
                var timeSlotSelect = $('#time_slot_selection');
                timeSlotSelect.empty();
                timeSlotSelect.append('<option value="">' + timeflowCheckoutParams.i18n.errorFetchingSlots + '</option>');
                timeSlotSelect.prop('disabled', true);
            }
        });
    }

    function setDeliveryTypeSession(deliveryType) {
        $.ajax({
            url: timeflowCheckoutParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_delivery_type_session',
                security: timeflowCheckoutParams.deliveryNonce,
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
            url: timeflowCheckoutParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_date_slot_session',
                security: timeflowCheckoutParams.deliveryNonce,
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

    function saveTimeSlotSelection(timeSlotId) {
        $.ajax({
            url: timeflowCheckoutParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_time_slot_selection',
                security: timeflowCheckoutParams.deliveryNonce,
                time_slot_selection: timeSlotId
            },
            success: function(response) {
                if (response.success) {
                    console.log('Time slot saved via AJAX:', timeSlotId);
                    // Trigger update *after* successful save
                    console.log('[TimeFlow JS Debug] Triggering update_checkout after saveTimeSlotSelection success.');
                    $(document.body).trigger('update_checkout');
                    console.log('[TimeFlow JS Debug] update_checkout triggered.');
                } else {
                    console.error('Failed to save time slot:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error saving time slot:', textStatus, errorThrown);
            }
        });
    }

    $(document).ready(function() {
        const $dateInput = $('#date_slot_selection');
        const $timeSelect = $('#time_slot_selection');
        const $deliveryTypeInput = $('#delivery-type');
        const $deliveryButtons = $('.delivery-buttons');
        const $timeSlotContainer = $('.timeflow_time_slot'); 
        const $formContainer = $('#timeflow-delivery-form');
        const $datePickerInput = $('#date_slot_selection.timeflow-date-picker'); 

        // Initial state
        $timeSelect.prop('disabled', true);
        
        // Check if there's a saved delivery type in the session
        var savedDeliveryType = $('#delivery-type').val();
        if (savedDeliveryType) {
            $('.delivery-buttons[data-delivery-type="' + savedDeliveryType + '"]').addClass('selected');
        }

        // Check if there's a saved date
        var savedDate = $dateInput.val();
        if (savedDate && savedDeliveryType) {
            updateTimeSlots(savedDate, savedDeliveryType);
        } else {
            //$timeSlotContainer.hide();
        }

        // Initialize Flatpickr
        if ($datePickerInput.length) {
            flatpickr($datePickerInput[0], {
                dateFormat: "Y-m-d", 
                minDate: "today",
                maxDate: new Date().fp_incr(timeflowCheckoutParams.dateRangeDays),
                disable: timeflowCheckoutParams.unavailableDates, 
                onChange: function(selectedDates, dateStr, instance) {
                    var selectedDate = dateStr;
                    var deliveryType = $deliveryTypeInput.val();
                    
                    console.log('Flatpickr onChange: ', selectedDate);
                    
                     clearTimeSlotSelection(function() {
                        setDateSlotSession(selectedDate);
                        
                        if (deliveryType) {
                             updateTimeSlots(selectedDate, deliveryType);
                        } else {
                             //$timeSlotContainer.hide();
                             $timeSelect.prop('disabled', true);
                        }
                     });
                }
            });
        }

        // Handle delivery type selection
        $('.delivery-buttons').on('click', function() {
            var deliveryType = $(this).data('delivery-type');
            var $clickedButton = $(this);
            
            $('.delivery-buttons').removeClass('selected');
            $clickedButton.addClass('selected');
            
            $('#delivery-type').val(deliveryType);
            
            $.ajax({
                url: timeflowCheckoutParams.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_delivery_type_session',
                    security: timeflowCheckoutParams.deliveryNonce,
                    delivery_type: deliveryType
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Delivery type session set:', deliveryType);
                        
                        clearTimeSlotSelection(function() {
                            
                            var selectedDate = $('#date_slot_selection').val();
                            if (selectedDate) {
                                
                                console.log('Date was selected, time slots cleared.')
                            }
                        });

                    } else {
                        console.error('Failed to set delivery type:', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error setting delivery type session:', textStatus, errorThrown);
                }
            });
            
        });

        // Handle time slot selection
        $('#time_slot_selection').on('change', function() {
            var selectedTimeSlot = $(this).val();
            if (selectedTimeSlot) {
                saveTimeSlotSelection(selectedTimeSlot);
            }
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