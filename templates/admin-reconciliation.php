<div class="wrap">
    <h1>HBC PoD Ledger - Reconciliation</h1>
    
    <table class="hbc-admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Period Start</th>
                <th>Period End</th>
                <th>Totals</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recons)): ?>
                <tr>
                    <td colspan="6">No reconciliation entries found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recons as $recon): ?>
                    <?php $totals = json_decode($recon->totals_json, true); ?>
                    <tr>
                        <td><?php echo esc_html($recon->id); ?></td>
                        <td><?php echo esc_html($recon->recon_type); ?></td>
                        <td><?php echo esc_html($recon->period_start); ?></td>
                        <td><?php echo esc_html($recon->period_end); ?></td>
                        <td>
                            Count: <?php echo esc_html($totals['count'] ?? 0); ?><br>
                            Total: $<?php echo esc_html($totals['total_pledge'] ?? '0.00'); ?>
                        </td>
                        <td><?php echo esc_html($recon->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
