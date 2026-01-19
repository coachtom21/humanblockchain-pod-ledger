<div class="wrap">
    <h1>HBC PoD Ledger - Devices</h1>
    
    <table class="hbc-admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Device Hash</th>
                <th>Platform</th>
                <th>Branch</th>
                <th>Buyer POC</th>
                <th>Seller POC</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($devices)): ?>
                <tr>
                    <td colspan="7">No devices registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><?php echo esc_html($device->id); ?></td>
                        <td><code><?php echo esc_html(substr($device->device_hash, 0, 16)) . '...'; ?></code></td>
                        <td><?php echo esc_html($device->platform); ?></td>
                        <td><?php echo esc_html($device->branch); ?></td>
                        <td><?php echo esc_html($device->buyer_poc_id); ?></td>
                        <td><?php echo esc_html($device->seller_poc_id); ?></td>
                        <td><?php echo esc_html($device->registered_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
