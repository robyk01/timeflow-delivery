document.addEventListener('DOMContentLoaded', function(){
    const timeSlotSelect = document.getElementById('time_slot_selection');
  
    if (timeSlotSelect){
        timeSlotSelect.addEventListener('change', function(){
            const selectedID = this.value;
            const data = new URLSearchParams();
            data.append('action', 'save_time_slot_selection');
            data.append('time_slot_selection', selectedID);
            data.append('security', timeflow_ajax_fees_params.nonce);

            fetch(timeflow_ajax_fees_params.ajax_url,{
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data.toString()
            })
            .then(response => {
                if (!response.ok){
                    throw new Error('HTTP error, status = ' + response.status);
                }
                return response.text();
            })
            .then(data => {
                console.log('Time Slot saved to session via ajax. response:', data);
                jQuery(document.body).trigger('update_checkout');
            })
            .catch(error => {
                console.error('Error saving time slot to seesion:', error);
            });
        });
    }
});