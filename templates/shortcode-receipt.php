<?php
/**
 * Participation Determination Receipt
 * Reads receipt_hash from querystring ?r=...
 */

$receipt_hash = isset($_GET['r']) ? sanitize_text_field($_GET['r']) : '';

if (empty($receipt_hash)) {
    echo '<div class="hbc-container"><div class="hbc-panel"><p class="hbc-message hbc-message-error">No receipt hash provided.</p></div></div>';
    return;
}
?>

<div class="hbc-container">
    <div class="hbc-panel">
        <h1 class="hbc-heading">Your Voice. Your Choice.</h1>
        <p class="hbc-microcopy">Participation Determination Receipt</p>
        
        <div id="hbc-receipt-loading" class="hbc-loading">
            <div class="hbc-spinner"></div>
            <p>Loading receipt...</p>
        </div>
        
        <div id="hbc-receipt-content" style="display: none;">
            <!-- Device Assignments -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Device Assignments</div>
                <div class="hbc-receipt-grid">
                    <div>
                        <div class="hbc-receipt-label">Branch</div>
                        <div class="hbc-receipt-value" id="receipt-branch"></div>
                    </div>
                    <div>
                        <div class="hbc-receipt-label">Branch Preference</div>
                        <div class="hbc-receipt-value" id="receipt-branch-pref"></div>
                    </div>
                    <div>
                        <div class="hbc-receipt-label">Buyer POC</div>
                        <div class="hbc-receipt-value" id="receipt-buyer-poc" style="font-size: 14px; word-break: break-all;"></div>
                    </div>
                    <div>
                        <div class="hbc-receipt-label">Seller POC</div>
                        <div class="hbc-receipt-value" id="receipt-seller-poc" style="font-size: 14px; word-break: break-all;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Participation Details -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Your Determinations</div>
                <div class="hbc-receipt-value" id="receipt-pledge-intent" style="text-transform: capitalize;"></div>
                <div id="receipt-confirmation-flags" style="margin-top: 16px;"></div>
                <div id="receipt-user-message" style="margin-top: 16px; padding: 16px; background: var(--hbc-bg); border-radius: 8px; font-style: italic;"></div>
            </div>
            
            <!-- Impact Preference -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Impact Preference</div>
                <div style="margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Individual Patronage</span>
                        <span class="hbc-receipt-value" id="receipt-individual"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Group Pool</span>
                        <span class="hbc-receipt-value" id="receipt-group"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Treasury Reserve</span>
                        <span class="hbc-receipt-value">0.10</span>
                    </div>
                </div>
            </div>
            
            <!-- Location & Timestamp -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Location & Timestamp</div>
                <div class="hbc-receipt-grid">
                    <div>
                        <div class="hbc-receipt-label">Approximate Location</div>
                        <div class="hbc-receipt-value" id="receipt-location"></div>
                    </div>
                    <div>
                        <div class="hbc-receipt-label">Created</div>
                        <div class="hbc-receipt-value" id="receipt-created"></div>
                    </div>
                </div>
            </div>
            
            <!-- Maturity Window -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Maturity Window</div>
                <p class="hbc-text">
                    Pledges mature in 8-12 weeks, or reconcile at closing moments.
                </p>
                <div style="margin-top: 16px;">
                    <div style="margin-bottom: 8px;">
                        <strong>Earliest Maturity:</strong> <span id="receipt-maturity-date"></span>
                    </div>
                    <div>
                        <strong>Latest Mature By:</strong> <span id="receipt-mature-by"></span>
                    </div>
                </div>
            </div>
            
            <!-- Reconciliation Schedule -->
            <div class="hbc-receipt-section">
                <div class="hbc-receipt-label">Reconciliation Schedule</div>
                <ul style="margin: 16px 0; padding-left: 24px; line-height: 1.8;">
                    <li><strong>Month-End Close:</strong> Last day of month (records receipts/obligations snapshot)</li>
                    <li><strong>First-Day Distributions:</strong> First day of month (marks matured items eligible)</li>
                    <li><strong>Year-End Close:</strong> September 1 (waives maturity for completed actions; no carry-forward of obligations)</li>
                </ul>
            </div>
        </div>
        
        <div id="hbc-receipt-error" style="display: none;">
            <div class="hbc-message hbc-message-error">
                Receipt not found or error loading receipt data.
            </div>
        </div>
    </div>
    
    <p class="hbc-microcopy" style="text-align: center; margin-top: 32px;">
        <?php echo esc_html(HBC_POD_LEDGER_DISCLAIMER); ?>
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    const receiptHash = '<?php echo esc_js($receipt_hash); ?>';
    
    $.ajax({
        url: hbcPodLedger.apiUrl + 'participation/receipt/' + receiptHash,
        method: 'GET',
        success: function(response) {
            const participation = response.participation;
            const device = response.device;
            
            // Device Assignments
            $('#receipt-branch').text(device.branch || 'N/A');
            $('#receipt-branch-pref').text(participation.branch_preference || 'N/A');
            $('#receipt-buyer-poc').text(device.buyer_poc_id || 'N/A');
            $('#receipt-seller-poc').text(device.seller_poc_id || 'N/A');
            
            // Participation Details
            $('#receipt-pledge-intent').text(participation.pledge_intent || 'N/A');
            
            if (participation.confirmation_flags) {
                const flagsHtml = Object.keys(participation.confirmation_flags).map(function(key) {
                    return '<div style="margin: 8px 0;">✓ ' + key.replace(/_/g, ' ') + '</div>';
                }).join('');
                $('#receipt-confirmation-flags').html(flagsHtml);
            }
            
            if (participation.user_message) {
                $('#receipt-user-message').text(participation.user_message).show();
            } else {
                $('#receipt-user-message').hide();
            }
            
            // Impact Preference
            if (participation.allocation_preference) {
                $('#receipt-individual').text('$' + participation.allocation_preference.patronage_individual);
                $('#receipt-group').text('$' + participation.allocation_preference.patronage_group_pool);
            }
            
            // Location (derive from rounded lat/lng)
            if (device.lat && device.lng) {
                const roundedLat = Math.round(device.lat * 10) / 10;
                const roundedLng = Math.round(device.lng * 10) / 10;
                $('#receipt-location').text(roundedLat + ', ' + roundedLng);
            } else {
                $('#receipt-location').text('N/A');
            }
            
            // Timestamp
            const createdDate = new Date(participation.created_at);
            $('#receipt-created').text(createdDate.toLocaleString());
            
            // Maturity dates (8-12 weeks from now)
            const maturityMinDays = 56;
            const maturityMaxDays = 84;
            const maturityDate = new Date(createdDate);
            maturityDate.setDate(maturityDate.getDate() + maturityMinDays);
            const matureByDate = new Date(createdDate);
            matureByDate.setDate(matureByDate.getDate() + maturityMaxDays);
            
            $('#receipt-maturity-date').text(maturityDate.toLocaleDateString());
            $('#receipt-mature-by').text(matureByDate.toLocaleDateString());
            
            $('#hbc-receipt-loading').hide();
            $('#hbc-receipt-content').show();
        },
        error: function(xhr) {
            $('#hbc-receipt-loading').hide();
            $('#hbc-receipt-error').show();
        }
    });
});
</script>
