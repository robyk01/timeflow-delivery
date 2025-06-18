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
                $('.timeflow_time_slot').hide();
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
        const $timeSlotContainer = $('.timeflow_time_slot'); // Container for label + select
        const $formContainer = $('#timeflow-delivery-form');
        const $datePickerInput = $('#date_slot_selection.timeflow-date-picker'); // Target our specific input

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
            // Hide time slot container initially if no date is selected
            $timeSlotContainer.hide();
        }

        // Initialize Flatpickr
        if ($datePickerInput.length) {
            flatpickr($datePickerInput[0], { // Pass the DOM element
                dateFormat: "Y-m-d", // Format expected by backend
                minDate: "today",
                maxDate: new Date().fp_incr(timeflowCheckoutParams.dateRangeDays), // Calculate max date
                disable: timeflowCheckoutParams.unavailableDates, // Disable specific dates
                onChange: function(selectedDates, dateStr, instance) {
                    // This function triggers when a valid date is SELECTED
                    var selectedDate = dateStr;
                    var deliveryType = $deliveryTypeInput.val();
                    
                    console.log('Flatpickr onChange: ', selectedDate);
                    
                     // Clear time slot and fee before updating
                     clearTimeSlotSelection(function() {
                        // Save date to session (optional but good practice)
                        setDateSlotSession(selectedDate);
                        
                        // If delivery type is already selected, update time slots
                        if (deliveryType) {
                             updateTimeSlots(selectedDate, deliveryType);
                        } else {
                             // Otherwise, just show prompt (or hide time slots again)
                             $timeSlotContainer.hide();
                             $timeSelect.prop('disabled', true);
                             // Optional: Alert user to select delivery type first
                             // alert(timeflowCheckoutParams.i18n.selectDeliveryPrompt);
                        }
                     });
                }
            });
        }

        // Handle delivery type selection
        $('.delivery-buttons').on('click', function() {
            var deliveryType = $(this).data('delivery-type');
            var $clickedButton = $(this);
            
            // Update UI immediately for responsiveness
            $('.delivery-buttons').removeClass('selected');
            $clickedButton.addClass('selected');
            
            // Update hidden input
            $('#delivery-type').val(deliveryType);
            
            // --- Chain AJAX calls --- 
            // 1. Save Delivery Type to session
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
                        
                        // 2. Clear Time Slot selection *after* delivery type is saved
                        clearTimeSlotSelection(function() {
                            // This callback inside clearTimeSlotSelection triggers update_checkout
                            
                            var selectedDate = $('#date_slot_selection').val();
                            if (selectedDate) {
                                
                                console.log('Date was selected, time slots cleared.')
                            }
                        });

                    } else {
                        console.error('Failed to set delivery type:', response.data);
                        // Revert UI selection on error?
                        // $clickedButton.removeClass('selected'); 
                        // Consider if previous button should be re-selected
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error setting delivery type session:', textStatus, errorThrown);
                     // Revert UI selection on error?
                     // $clickedButton.removeClass('selected');
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