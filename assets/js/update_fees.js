(function($){
    $(document).ready(function(){
        var $timeSlot = $('#time_slot_selection');

        $timeSlot.change(function(){
            $('body').trigger('update_checkout');
            console.log('time slot changed');
        });
    })
})(jQuery);