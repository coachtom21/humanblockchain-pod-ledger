jQuery(document).ready(function($) {
    
    // Enter modal handlers
    $('#hbc-pod-yes').on('click', function() {
        $('#hbc-enter-modal').hide();
        $('#hbc-pod-flow').show();
    });
    
    $('#hbc-pod-no').on('click', function() {
        window.location.href = '/faq';
    });
    
    // Scan 1 (Seller Initiation)
    $('#hbc-scan1-submit').on('click', function(e) {
        e.preventDefault();
        
        const data = {
            seller_device_id: $('#seller-device-id').val(),
            buyer_identifier: $('#buyer-identifier').val(),
            voucher_id: $('#voucher-id').val(),
            order_ref: $('#order-ref').val() || '',
            lat: parseFloat($('#scan1-lat').val()),
            lng: parseFloat($('#scan1-lng').val()),
            timestamp: new Date().toISOString()
        };
        
        $.ajax({
            url: hbcPodLedger.apiUrl + 'pod/initiate',
            method: 'POST',
            headers: {
                'X-WP-Nonce': hbcPodLedger.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                $('#hbc-scan1-result').html('<p class="success">Scan 1 initiated successfully. Entry ID: ' + response.entry_id + '</p>');
                $('#hbc-scan2').show();
            },
            error: function(xhr) {
                $('#hbc-scan1-result').html('<p class="error">Error: ' + xhr.responseJSON.message + '</p>');
            }
        });
    });
    
    // Scan 2 (Buyer Acceptance)
    $('#hbc-scan2-submit').on('click', function(e) {
        e.preventDefault();
        
        const data = {
            buyer_device_id: $('#buyer-device-id').val(),
            voucher_id: $('#voucher-id-confirm').val(),
            confirm_delivery: true,
            lat: parseFloat($('#scan2-lat').val()),
            lng: parseFloat($('#scan2-lng').val()),
            timestamp: new Date().toISOString()
        };
        
        $.ajax({
            url: hbcPodLedger.apiUrl + 'pod/confirm',
            method: 'POST',
            headers: {
                'X-WP-Nonce': hbcPodLedger.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                $('#hbc-scan2-result').html('<p class="success">Proof of Delivery confirmed! Entry ID: ' + response.entry_id + '</p>');
            },
            error: function(xhr) {
                $('#hbc-scan2-result').html('<p class="error">Error: ' + xhr.responseJSON.message + '</p>');
            }
        });
    });
    
    // Get geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            $('#scan1-lat').val(position.coords.latitude);
            $('#scan1-lng').val(position.coords.longitude);
            $('#scan2-lat').val(position.coords.latitude);
            $('#scan2-lng').val(position.coords.longitude);
        });
    }
});
