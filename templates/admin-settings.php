<div class="wrap">
    <h1>HBC PoD Ledger - Settings</h1>
    
    <form method="post">
        <?php wp_nonce_field('hbc_settings'); ?>
        
        <h2>Pledge Amounts</h2>
        <table class="form-table">
            <tr>
                <th><label for="pledge_total">Pledge Total</label></th>
                <td><input type="number" step="0.01" id="pledge_total" name="pledge_total" value="<?php echo esc_attr(get_option('hbc_pledge_total', '10.30')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="buyer_rebate">Buyer Rebate</label></th>
                <td><input type="number" step="0.01" id="buyer_rebate" name="buyer_rebate" value="<?php echo esc_attr(get_option('hbc_buyer_rebate', '5.00')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="social_impact">Social Impact</label></th>
                <td><input type="number" step="0.01" id="social_impact" name="social_impact" value="<?php echo esc_attr(get_option('hbc_social_impact', '4.00')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="patronage_total">Patronage Total</label></th>
                <td><input type="number" step="0.01" id="patronage_total" name="patronage_total" value="<?php echo esc_attr(get_option('hbc_patronage_total', '1.00')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="patronage_individual">Patronage Individual</label></th>
                <td><input type="number" step="0.01" id="patronage_individual" name="patronage_individual" value="<?php echo esc_attr(get_option('hbc_patronage_individual', '0.50')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="patronage_group_pool">Patronage Group Pool</label></th>
                <td><input type="number" step="0.01" id="patronage_group_pool" name="patronage_group_pool" value="<?php echo esc_attr(get_option('hbc_patronage_group_pool', '0.40')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="patronage_treasury_reserve">Patronage Treasury Reserve</label></th>
                <td><input type="number" step="0.01" id="patronage_treasury_reserve" name="patronage_treasury_reserve" value="<?php echo esc_attr(get_option('hbc_patronage_treasury_reserve', '0.10')); ?>" class="regular-text"></td>
            </tr>
        </table>
        
        <h2>Maturity Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="maturity_min_days">Maturity Min Days (8 weeks = 56)</label></th>
                <td><input type="number" id="maturity_min_days" name="maturity_min_days" value="<?php echo esc_attr(get_option('hbc_maturity_min_days', 56)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="maturity_max_days">Maturity Max Days (12 weeks = 84)</label></th>
                <td><input type="number" id="maturity_max_days" name="maturity_max_days" value="<?php echo esc_attr(get_option('hbc_maturity_max_days', 84)); ?>" class="regular-text"></td>
            </tr>
        </table>
        
        <h2>GitHub Integration (Licensing Protocol)</h2>
        <table class="form-table">
            <tr>
                <th><label for="github_token">GitHub Token (Fine-grained)</label></th>
                <td>
                    <?php 
                    $github_token = get_option('hbc_github_token', '');
                    $display_token = $github_token ? substr($github_token, 0, 8) . '...' . substr($github_token, -4) : '';
                    ?>
                    <input type="password" id="github_token" name="github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text">
                    <?php if ($display_token): ?>
                        <p class="description">Current: <?php echo esc_html($display_token); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="github_owner">GitHub Owner/Org</label></th>
                <td><input type="text" id="github_owner" name="github_owner" value="<?php echo esc_attr(get_option('hbc_github_owner', '')); ?>" class="regular-text" placeholder="e.g., SmallStreetApplied"></td>
            </tr>
            <tr>
                <th><label for="github_repo">GitHub Repository</label></th>
                <td><input type="text" id="github_repo" name="github_repo" value="<?php echo esc_attr(get_option('hbc_github_repo', 'SmallStreetApplied-Atlanta')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="github_branch">GitHub Branch</label></th>
                <td><input type="text" id="github_branch" name="github_branch" value="<?php echo esc_attr(get_option('hbc_github_branch', 'main')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="github_logs_path">Logs Path Root</label></th>
                <td><input type="text" id="github_logs_path" name="github_logs_path" value="<?php echo esc_attr(get_option('hbc_github_logs_path', 'append-only-ledger/')); ?>" class="regular-text" placeholder="append-only-ledger/"></td>
            </tr>
        </table>
        
        <h2>Other Integration Settings (Stubs)</h2>
        <table class="form-table">
            <tr>
                <th><label for="discord_token">Discord Token</label></th>
                <td><input type="text" id="discord_token" name="discord_token" value="<?php echo esc_attr(get_option('hbc_discord_token', '')); ?>" class="regular-text" placeholder="Not implemented"></td>
            </tr>
            <tr>
                <th><label for="qrtiger_api_key">QRTiger API Key</label></th>
                <td><input type="text" id="qrtiger_api_key" name="qrtiger_api_key" value="<?php echo esc_attr(get_option('hbc_qrtiger_api_key', '')); ?>" class="regular-text" placeholder="Not implemented"></td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="Save Settings">
        </p>
    </form>
    
    <hr>
    
    <h2>API Key</h2>
    <p>Your API key for REST endpoints: <code><?php echo esc_html(get_option('hbc_api_key', '')); ?></code></p>
    <p><small>Use this in the X-API-Key header for non-authenticated requests.</small></p>
</div>
