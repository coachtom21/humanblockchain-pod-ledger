<div id="hbc-enter-modal" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 9999; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; padding: 40px; border-radius: 10px; max-width: 500px; text-align: center;">
        <h2>Is This Proof of Delivery?</h2>
        <p style="margin: 20px 0;"><?php echo esc_html(HBC_POD_LEDGER_DISCLAIMER); ?></p>
        <div style="margin-top: 30px;">
            <button id="hbc-pod-yes" style="padding: 12px 30px; margin: 0 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">YES</button>
            <button id="hbc-pod-no" style="padding: 12px 30px; margin: 0 10px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">NO</button>
        </div>
    </div>
</div>

<div id="hbc-pod-flow" style="display: none;">
    <?php echo do_shortcode('[hbc_pod_flow]'); ?>
</div>
