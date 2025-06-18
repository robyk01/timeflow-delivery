/**
 * TimeFlow Delivery Admin Scripts
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Initialize tabs
        initTabs();
        
        // Initialize add time slot form
        initAddTimeSlotForm();
        
        // Initialize quick edit
        initQuickEdit();
    });

    /**
     * Initialize tab functionality
     */
    function initTabs() {
        // Get current tab from URL
        var currentTab = getUrlParameter('tab') || 'time-slots';
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $('.nav-tab[href*="tab=' + currentTab + '"]').addClass('nav-tab-active');
        
        // Show current tab content
        $('.tab-content > div').hide();
        $('#' + currentTab + '-tab').show();
        
        // Handle tab clicks
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var tab = $(this).attr('href').split('tab=')[1];
            
            // Update URL without reload
            var newUrl = updateQueryStringParameter(window.location.href, 'tab', tab);
            window.location.href = newUrl;
        });
        
        // Ensure the page stays visible
        $('.wrap.timeflow-admin').show();
        
        // Override any attempts to hide the content
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === "attributes" && mutation.attributeName === "style") {
                    var element = mutation.target;
                    if ($(element).hasClass("timeflow-admin") && $(element).is(":hidden")) {
                        $(element).show();
                    }
                }
            });
        });
        
        // Start observing the admin wrapper
        if ($('.wrap.timeflow-admin').length) {
            observer.observe($('.wrap.timeflow-admin')[0], {
                attributes: true,
                attributeFilter: ["style"]
            });
        }
    }

    /**
     * Initialize add time slot form
     */
    function initAddTimeSlotForm() {
        $('#add-time-slot-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'timeflow_add_time_slot',
                nonce: $('#timeflow_admin_nonce').val(),
                start_time: $('#start_time').val(),
                end_time: $('#end_time').val(),
                available_days: $('input[name="available_days[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                delivery_method: $('input[name="delivery_method[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                fee: $('#fee').val()
            };
            
            handleFormSubmission(formData);
        });
    }

    /**
     * Initialize quick edit functionality
     */
    function initQuickEdit() {
        $('.quick-edit').on('click', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            
            // Get time slot data
            $.ajax({
                url: timeflowAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'timeflow_get_time_slot',
                    post_id: postId,
                    nonce: timeflowAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create and show quick edit form
                        showQuickEditForm(postId, response.data);
                    } else {
                        alert(response.data.message || timeflowAdmin.i18n.error);
                    }
                },
                error: function() {
                    alert(timeflowAdmin.i18n.error);
                }
            });
        });
        
        // Handle delete button clicks
        $('.delete-slot').on('click', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            
            // Confirm deletion
            if (confirm(timeflowAdmin.i18n.confirmDelete)) {
                // Send AJAX request to delete the time slot
                $.ajax({
                    url: timeflowAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'timeflow_delete_time_slot',
                        post_id: postId,
                        nonce: timeflowAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload page to update the list
                            window.location.reload();
                        } else {
                            // Show error message
                            alert(response.data.message || timeflowAdmin.i18n.error);
                        }
                    },
                    error: function() {
                        alert(timeflowAdmin.i18n.error);
                    }
                });
            }
        });
        
        // Handle quick edit form submission
        $(document).on('submit', '#quick-edit-form', function(e) {
            e.preventDefault();
            
            // Get form data
            var $form = $(this);
            var formData = {
                action: 'timeflow_update_time_slot',
                nonce: timeflowAdmin.nonce,
                post_id: $form.find('input[name="post_id"]').val(),
                start_time: $form.find('#quick-edit-start-time').val(),
                end_time: $form.find('#quick-edit-end-time').val(),
                available_days: $form.find('input[name="available_days[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                delivery_method: $form.find('input[name="delivery_method[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                fee: $form.find('#quick-edit-fee').val() || ''
            };

            // Debug log
            console.log('Form Data:', formData);
            
            // Validate form data before submission
            if (!formData.start_time || !formData.end_time) {
                alert(timeflowAdmin.i18n.fillRequired);
                return;
            }

            if (!formData.available_days || formData.available_days.length === 0) {
                alert(timeflowAdmin.i18n.selectDay);
                return;
            }

            if (!formData.delivery_method || formData.delivery_method.length === 0) {
                alert(timeflowAdmin.i18n.selectDelivery);
                return;
            }
            
            // Submit form
            $.ajax({
                url: timeflowAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        $('#quick-edit-modal').remove();
                        location.reload();
                    } else {
                        alert(response.data.message || timeflowAdmin.i18n.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', textStatus, errorThrown);
                    alert(timeflowAdmin.i18n.error);
                }
            });
        });
    }

    /**
     * Show quick edit form
     */
    function showQuickEditForm(postId, data) {
        // Create modal HTML
        var modalHtml = `
            <div id="quick-edit-modal" class="timeflow-modal">
                <div class="timeflow-modal-content">
                    <span class="timeflow-modal-close">&times;</span>
                    <h2>${timeflowAdmin.i18n.quickEdit}</h2>
                    <form id="quick-edit-form">
                        <input type="hidden" name="post_id" value="${data.post_id}">
                        
                        <div class="form-row">
                            <label for="quick-edit-start-time">${timeflowAdmin.i18n.startTime}</label>
                            <input type="time" id="quick-edit-start-time" name="start_time" value="${data.start_time}" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="quick-edit-end-time">${timeflowAdmin.i18n.endTime}</label>
                            <input type="time" id="quick-edit-end-time" name="end_time" value="${data.end_time}" required>
                        </div>
                        
                        <div class="form-row">
                            <label>${timeflowAdmin.i18n.availableDays}</label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="available_days[]" value="monday" ${data.available_days && data.available_days.includes('monday') ? 'checked' : ''}> ${timeflowAdmin.i18n.monday}</label>
                                <label><input type="checkbox" name="available_days[]" value="tuesday" ${data.available_days && data.available_days.includes('tuesday') ? 'checked' : ''}> ${timeflowAdmin.i18n.tuesday}</label>
                                <label><input type="checkbox" name="available_days[]" value="wednesday" ${data.available_days && data.available_days.includes('wednesday') ? 'checked' : ''}> ${timeflowAdmin.i18n.wednesday}</label>
                                <label><input type="checkbox" name="available_days[]" value="thursday" ${data.available_days && data.available_days.includes('thursday') ? 'checked' : ''}> ${timeflowAdmin.i18n.thursday}</label>
                                <label><input type="checkbox" name="available_days[]" value="friday" ${data.available_days && data.available_days.includes('friday') ? 'checked' : ''}> ${timeflowAdmin.i18n.friday}</label>
                                <label><input type="checkbox" name="available_days[]" value="saturday" ${data.available_days && data.available_days.includes('saturday') ? 'checked' : ''}> ${timeflowAdmin.i18n.saturday}</label>
                                <label><input type="checkbox" name="available_days[]" value="sunday" ${data.available_days && data.available_days.includes('sunday') ? 'checked' : ''}> ${timeflowAdmin.i18n.sunday}</label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <label for="quick-edit-fee">${timeflowAdmin.i18n.fee}</label>
                            <input type="number" id="quick-edit-fee" name="fee" value="${data.fee}" step="0.01" min="0">
                        </div>
                        
                        <div class="form-row">
                            <label>${timeflowAdmin.i18n.deliveryMethod}</label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="delivery_method[]" value="shipping" ${data.delivery_method && data.delivery_method.includes('shipping') ? 'checked' : ''}> ${timeflowAdmin.i18n.shipping}</label>
                                <label><input type="checkbox" name="delivery_method[]" value="pickup" ${data.delivery_method && data.delivery_method.includes('pickup') ? 'checked' : ''}> ${timeflowAdmin.i18n.pickup}</label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="button button-primary">
                                ${timeflowAdmin.i18n.save}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        // Add modal to page
        $('body').append(modalHtml);
        
        // Handle modal close
        $('.timeflow-modal-close').on('click', function() {
            $('#quick-edit-modal').remove();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).is('#quick-edit-modal')) {
                $('#quick-edit-modal').remove();
            }
        });
    }

    /**
     * Get URL parameter
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    /**
     * Update URL query string parameter
     */
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    /**
     * Handle form submission
     */
    function handleFormSubmission(formData, successCallback) {
        // Check if we have the proper nonce
        if (!formData.nonce) {
            formData.nonce = timeflowAdmin.nonce;
        }

        // Ensure we have all required data
        if (!formData.start_time || !formData.end_time) {
            alert(timeflowAdmin.i18n.fillRequired);
            return;
        }

        // Validate at least one day is selected
        if (!formData.available_days || !Array.isArray(formData.available_days) || formData.available_days.length === 0) {
            alert(timeflowAdmin.i18n.selectDay);
            return;
        }

        // Validate at least one delivery method is selected
        if (!formData.delivery_method || !Array.isArray(formData.delivery_method) || formData.delivery_method.length === 0) {
            alert(timeflowAdmin.i18n.selectDelivery);
            return;
        }

        // Ensure fee is a number or empty string
        if (formData.fee === undefined) {
            formData.fee = '';
        }

        $.ajax({
            url: timeflowAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response.data);
                    }
                    location.reload();
                } else {
                    alert(response.data.message || timeflowAdmin.i18n.error);
                }
            },
            error: function() {
                alert(timeflowAdmin.i18n.error);
            }
        });
    }

})(jQuery); 