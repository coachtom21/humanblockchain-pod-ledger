<?php
/**
 * Determination Board - Public aggregation view
 */
?>

<div class="hbc-container">
    <div class="hbc-panel">
        <h1 class="hbc-heading">Determination Board</h1>
        <p class="hbc-microcopy">Aggregated participation data (anonymized)</p>
        
        <!-- Filters -->
        <div class="hbc-determine-filters">
            <button class="hbc-filter-btn active" data-range="today">Today</button>
            <button class="hbc-filter-btn" data-range="7d">Last 7 Days</button>
            <button class="hbc-filter-btn" data-range="30d">Last 30 Days</button>
        </div>
        
        <div id="hbc-determine-loading" class="hbc-loading">
            <div class="hbc-spinner"></div>
            <p>Loading data...</p>
        </div>
        
        <div id="hbc-determine-content" style="display: none;">
            <!-- Total Participants -->
            <div class="hbc-stats-grid">
                <div class="hbc-stat-card">
                    <div class="hbc-stat-value" id="stat-total">0</div>
                    <div class="hbc-stat-label">Total Participants</div>
                </div>
            </div>
            
            <!-- Branch Distribution -->
            <div class="hbc-panel" style="margin-top: 24px;">
                <h2 class="hbc-subheading">Branch Preference Distribution</h2>
                <div id="branch-distribution"></div>
            </div>
            
            <!-- Pledge Intent Distribution -->
            <div class="hbc-panel" style="margin-top: 24px;">
                <h2 class="hbc-subheading">Pledge Intent Distribution</h2>
                <div id="intent-distribution"></div>
            </div>
            
            <!-- Top Confirmation Flags -->
            <div class="hbc-panel" style="margin-top: 24px;">
                <h2 class="hbc-subheading">Top Confirmation Flags</h2>
                <div id="flags-distribution"></div>
            </div>
        </div>
        
        <div id="hbc-determine-error" style="display: none;">
            <div class="hbc-message hbc-message-error">
                Error loading determination data.
            </div>
        </div>
    </div>
    
    <p class="hbc-microcopy" style="text-align: center; margin-top: 32px;">
        <?php echo esc_html(HBC_POD_LEDGER_DISCLAIMER); ?>
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    let currentRange = 'today';
    
    function loadAggregate(range) {
        $('#hbc-determine-loading').show();
        $('#hbc-determine-content').hide();
        $('#hbc-determine-error').hide();
        
        $.ajax({
            url: hbcPodLedger.apiUrl + 'participation/aggregate?range=' + range,
            method: 'GET',
            success: function(response) {
                // Total Participants
                $('#stat-total').text(response.total_participants);
                
                // Branch Distribution
                const branchHtml = Object.keys(response.branch_distribution || {}).map(function(branch) {
                    const count = response.branch_distribution[branch];
                    const percentage = response.total_participants > 0 ? (count / response.total_participants * 100).toFixed(1) : 0;
                    return `
                        <div style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="text-transform: capitalize;">${branch}</span>
                                <span><strong>${count}</strong> (${percentage}%)</span>
                            </div>
                            <div class="hbc-distribution-bar">
                                <div class="hbc-distribution-fill" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');
                $('#branch-distribution').html(branchHtml || '<p class="hbc-text">No data available</p>');
                
                // Intent Distribution
                const intentHtml = Object.keys(response.pledge_intent_distribution || {}).map(function(intent) {
                    const count = response.pledge_intent_distribution[intent];
                    const percentage = response.total_participants > 0 ? (count / response.total_participants * 100).toFixed(1) : 0;
                    return `
                        <div style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="text-transform: capitalize;">${intent}</span>
                                <span><strong>${count}</strong> (${percentage}%)</span>
                            </div>
                            <div class="hbc-distribution-bar">
                                <div class="hbc-distribution-fill" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');
                $('#intent-distribution').html(intentHtml || '<p class="hbc-text">No data available</p>');
                
                // Top Confirmation Flags
                const flagsHtml = Object.keys(response.top_confirmation_flags || {}).slice(0, 10).map(function(flag) {
                    const count = response.top_confirmation_flags[flag];
                    return `
                        <div style="margin-bottom: 12px; padding: 12px; background: var(--hbc-bg); border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>${flag.replace(/_/g, ' ')}</span>
                                <strong>${count}</strong>
                            </div>
                        </div>
                    `;
                }).join('');
                $('#flags-distribution').html(flagsHtml || '<p class="hbc-text">No data available</p>');
                
                $('#hbc-determine-loading').hide();
                $('#hbc-determine-content').show();
            },
            error: function(xhr) {
                $('#hbc-determine-loading').hide();
                $('#hbc-determine-error').show();
            }
        });
    }
    
    // Filter buttons
    $('.hbc-filter-btn').on('click', function() {
        $('.hbc-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentRange = $(this).data('range');
        loadAggregate(currentRange);
    });
    
    // Initial load
    loadAggregate(currentRange);
});
</script>
