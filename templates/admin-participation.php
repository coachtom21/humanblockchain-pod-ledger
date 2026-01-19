<div class="wrap">
    <h1>HBC PoD Ledger - Participation</h1>
    
    <table class="hbc-admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Device Hash</th>
                <th>Branch Preference</th>
                <th>Pledge Intent</th>
                <th>Created</th>
                <th>Receipt Hash</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($participations)): ?>
                <tr>
                    <td colspan="6">No participation records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($participations as $participation): ?>
                    <tr>
                        <td><?php echo esc_html($participation->id); ?></td>
                        <td><code><?php echo esc_html(substr($participation->device_hash, 0, 16)) . '...'; ?></code></td>
                        <td><?php echo esc_html($participation->branch_preference ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($participation->pledge_intent ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($participation->created_at); ?></td>
                        <td><code><?php echo esc_html(substr($participation->receipt_hash, 0, 16)) . '...'; ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
