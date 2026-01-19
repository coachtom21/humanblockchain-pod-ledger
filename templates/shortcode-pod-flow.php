<div class="hbc-pod-flow">
    <h2>2-Scan Proof of Delivery Flow</h2>
    
    <!-- Scan 1: Seller Initiation -->
    <div id="hbc-scan1" style="margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h3>Scan 1: Seller Initiation</h3>
        <form id="hbc-scan1-form">
            <p>
                <label>Seller Device ID:</label><br>
                <input type="text" id="seller-device-id" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Buyer Identifier (email/handle):</label><br>
                <input type="text" id="buyer-identifier" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Voucher ID:</label><br>
                <input type="text" id="voucher-id" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Order Reference (optional):</label><br>
                <input type="text" id="order-ref" style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Latitude:</label><br>
                <input type="number" id="scan1-lat" step="any" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Longitude:</label><br>
                <input type="number" id="scan1-lng" step="any" required style="width: 100%; padding: 8px;">
            </p>
            <button type="submit" id="hbc-scan1-submit" style="padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;">Initiate</button>
        </form>
        <div id="hbc-scan1-result"></div>
    </div>
    
    <!-- Scan 2: Buyer Acceptance -->
    <div id="hbc-scan2" style="display: none; margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h3>Scan 2: Buyer Acceptance</h3>
        <form id="hbc-scan2-form">
            <p>
                <label>Buyer Device ID:</label><br>
                <input type="text" id="buyer-device-id" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Voucher ID (must match Scan 1):</label><br>
                <input type="text" id="voucher-id-confirm" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Latitude:</label><br>
                <input type="number" id="scan2-lat" step="any" required style="width: 100%; padding: 8px;">
            </p>
            <p>
                <label>Longitude:</label><br>
                <input type="number" id="scan2-lng" step="any" required style="width: 100%; padding: 8px;">
            </p>
            <button type="submit" id="hbc-scan2-submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Confirm Delivery</button>
        </form>
        <div id="hbc-scan2-result"></div>
    </div>
    
    <p style="margin-top: 20px; font-size: 12px; color: #666;"><?php echo esc_html(HBC_POD_LEDGER_DISCLAIMER); ?></p>
</div>
