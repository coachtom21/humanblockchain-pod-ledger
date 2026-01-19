<div class="wrap">
    <h1>HBC PoD Ledger - Entries</h1>
    
    <div class="hbc-admin-filters">
        <form method="get">
            <input type="hidden" name="page" value="hbc-pod-ledger-entries">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="INITIATED" <?php selected($status, 'INITIATED'); ?>>INITIATED</option>
                <option value="CONFIRMED" <?php selected($status, 'CONFIRMED'); ?>>CONFIRMED</option>
                <option value="CORRECTION" <?php selected($status, 'CORRECTION'); ?>>CORRECTION</option>
                <option value="RECONCILED" <?php selected($status, 'RECONCILED'); ?>>RECONCILED</option>
            </select>
            <select name="branch">
                <option value="">All Branches</option>
                <option value="planning" <?php selected($branch, 'planning'); ?>>Planning</option>
                <option value="budget" <?php selected($branch, 'budget'); ?>>Budget</option>
                <option value="media" <?php selected($branch, 'media'); ?>>Media</option>
                <option value="distribution" <?php selected($branch, 'distribution'); ?>>Distribution</option>
                <option value="membership" <?php selected($branch, 'membership'); ?>>Membership</option>
            </select>
            <button type="submit" class="button">Filter</button>
        </form>
    </div>
    
    <table class="hbc-admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Voucher ID</th>
                <th>Status</th>
                <th>Pledge Total</th>
                <th>Branch</th>
                <th>Initiated</th>
                <th>Confirmed</th>
                <th>Maturity Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="8">No ledger entries found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->id); ?></td>
                        <td><code><?php echo esc_html($entry->voucher_id); ?></code></td>
                        <td>
                            <span class="hbc-status-badge hbc-status-<?php echo strtolower($entry->status); ?>">
                                <?php echo esc_html($entry->status); ?>
                            </span>
                        </td>
                        <td>$<?php echo esc_html($entry->pledge_total); ?></td>
                        <td><?php echo esc_html($entry->branch); ?></td>
                        <td><?php echo esc_html($entry->initiated_at); ?></td>
                        <td><?php echo esc_html($entry->confirmed_at ?: '-'); ?></td>
                        <td><?php echo esc_html($entry->maturity_date ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
