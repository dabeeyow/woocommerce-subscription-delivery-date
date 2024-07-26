jQuery(document).ready(function($) {
    $('#delivery_date').change(function() {
        alert('Delivery date changed');
    });

    // Handle change event for delivery date select boxes
    $(document).on('change', 'select[name^="cart"][name$="[delivery_date]"]', function() {
        alert('Delivery date changed' );
        var cart_item_key = $(this).attr('id').replace('delivery_date_', '');
        var delivery_date = $(this).val();

        $.ajax({
            url: cartDeliveryDates.ajax_url,
            type: 'POST',
            data: {
                action: 'update_delivery_date',
                cart_item_key: cart_item_key,
                delivery_date: delivery_date
            },
            success: function(response) {
                if (response.success) {
                    console.log('Delivery date updated successfully.');
                    // Trigger cart refresh
                    $(document.body).trigger('wc_update_cart');
                } else {
                    console.log('Error updating delivery date: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error: ' + error);
            }
        });
    });
});

jQuery(window).on('load', function() {
    // Function to generate the select box for delivery dates
    function generateDeliveryDateSelectBox(cart_item_key, delivery_dates, selected_date) {
        var selectBox = '<div class="delivery-date-selection">';
        selectBox += '<label for="delivery_date_' + cart_item_key + '">' + 'Change Delivery Date:' + '</label>';
        selectBox += '<select name="cart[' + cart_item_key + '][delivery_date]" id="delivery_date_' + cart_item_key + '">';
        delivery_dates.forEach(function(date) {
            var selected = date === selected_date ? ' selected="selected"' : '';
            selectBox += '<option value="' + date + '"' + selected + '>' + date + '</option>';
        });
        selectBox += '</select></div>';
        return selectBox;
    }

    // Loop through cart items and add delivery date select box
    jQuery.each(cartDeliveryDates.cart_items, function(cart_item_key, item) {
        var selectBox = generateDeliveryDateSelectBox(cart_item_key, item.delivery_dates, item.selected_date);
        jQuery('tr.wc-block-cart-items__row').find('.wc-block-cart-item__quantity').prepend(selectBox);
        console.log(item);
    });
})