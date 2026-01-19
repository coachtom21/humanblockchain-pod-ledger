<?php
/**
 * Participation Flow - 4 Step Guided UI
 * Step 1: Register Device
 * Step 2: Choose Branch
 * Step 3: Make Determinations
 * Step 4: Set Impact Preference
 */
?>

<div class="hbc-container">
    <div class="hbc-panel">
        <h1 class="hbc-heading">Your Voice. Your Choice.</h1>
        <p class="hbc-microcopy">Participation determines priorities. Proof determines truth.</p>
        
        <!-- Stepper -->
        <div class="hbc-stepper" role="tablist" aria-label="Participation steps">
            <div class="hbc-step active" data-step="1">
                <div class="hbc-step-circle" aria-label="Step 1">1</div>
                <div class="hbc-step-label">Register</div>
            </div>
            <div class="hbc-step" data-step="2">
                <div class="hbc-step-circle" aria-label="Step 2">2</div>
                <div class="hbc-step-label">Branch</div>
            </div>
            <div class="hbc-step" data-step="3">
                <div class="hbc-step-circle" aria-label="Step 3">3</div>
                <div class="hbc-step-label">Determine</div>
            </div>
            <div class="hbc-step" data-step="4">
                <div class="hbc-step-circle" aria-label="Step 4">4</div>
                <div class="hbc-step-label">Impact</div>
            </div>
        </div>
        
        <!-- Step 1: Register Device -->
        <div id="hbc-step-1" class="hbc-step-content" role="tabpanel" aria-labelledby="step-1">
            <h2 class="hbc-subheading">Register This Device</h2>
            <p class="hbc-text">We'll register your device and assign you to a Peace Pentagon branch and POC clusters.</p>
            
            <div class="hbc-form-group">
                <label for="device-id" class="hbc-label">Device ID</label>
                <input type="text" id="device-id" class="hbc-input" placeholder="Enter unique device identifier" required>
            </div>
            
            <div class="hbc-form-group">
                <label for="platform" class="hbc-label">Platform</label>
                <select id="platform" class="hbc-select" required>
                    <option value="">Select platform</option>
                    <option value="ios">iOS</option>
                    <option value="android">Android</option>
                    <option value="web">Web</option>
                </select>
            </div>
            
            <div class="hbc-form-group">
                <label for="timezone" class="hbc-label">Timezone (optional)</label>
                <input type="text" id="timezone" class="hbc-input" placeholder="e.g., America/New_York">
            </div>
            
            <div class="hbc-form-group">
                <label for="discord-user-id" class="hbc-label">Discord User ID <span style="color: #ff4444;">*</span></label>
                <input type="text" id="discord-user-id" class="hbc-input" placeholder="123456789012345678" required>
                <p class="hbc-microcopy">Discord connection is required. Enable Developer Mode in Discord, then right-click your profile to copy User ID.</p>
            </div>
            
            <div class="hbc-form-group">
                <label for="discord-username" class="hbc-label">Discord Username <span style="color: #ff4444;">*</span></label>
                <input type="text" id="discord-username" class="hbc-input" placeholder="username#1234" required>
                <p class="hbc-microcopy">Your Discord username (e.g., username#1234)</p>
            </div>
            
            <div class="hbc-form-group">
                <label for="discord-invite-code" class="hbc-label">Discord Invite Code (optional)</label>
                <input type="text" id="discord-invite-code" class="hbc-input" placeholder="Leave blank if not referred">
                <p class="hbc-microcopy">If you were referred by someone, enter their Discord invite code here.</p>
            </div>
            
            <div class="hbc-form-group">
                <label class="hbc-toggle">
                    <input type="checkbox" id="accept-license" name="accept_license" required>
                    <span class="hbc-toggle-label">I accept the <?php echo esc_html(HBC_LICENSE_TITLE); ?></span>
                </label>
                <p class="hbc-microcopy" style="margin-top: 8px; padding-left: 36px;">
                    <?php echo esc_html(HBC_LICENSE_CORE); ?>
                </p>
            </div>
            
            <div id="hbc-geo-status" class="hbc-message hbc-message-info" style="display: none;">
                Getting location...
            </div>
            
            <button type="button" class="hbc-btn hbc-btn-primary" id="hbc-register-device">Register Device</button>
            
            <div id="hbc-register-result"></div>
        </div>
        
        <!-- Step 2: Choose Branch -->
        <div id="hbc-step-2" class="hbc-step-content" style="display: none;" role="tabpanel" aria-labelledby="step-2">
            <h2 class="hbc-subheading">Choose Your Branch</h2>
            <p class="hbc-text">You've been assigned to <strong id="hbc-assigned-branch"></strong>. You can express a preference below.</p>
            
            <div class="hbc-branch-grid">
                <label class="hbc-branch-option">
                    <input type="radio" name="branch-preference" value="planning" id="branch-planning">
                    <div>Planning</div>
                </label>
                <label class="hbc-branch-option">
                    <input type="radio" name="branch-preference" value="budget" id="branch-budget">
                    <div>Budget</div>
                </label>
                <label class="hbc-branch-option">
                    <input type="radio" name="branch-preference" value="media" id="branch-media">
                    <div>Media</div>
                </label>
                <label class="hbc-branch-option">
                    <input type="radio" name="branch-preference" value="distribution" id="branch-distribution">
                    <div>Distribution</div>
                </label>
                <label class="hbc-branch-option">
                    <input type="radio" name="branch-preference" value="membership" id="branch-membership">
                    <div>Membership</div>
                </label>
            </div>
            
            <button type="button" class="hbc-btn hbc-btn-primary" id="hbc-continue-step-2" disabled>Continue</button>
        </div>
        
        <!-- Step 3: Make Determinations -->
        <div id="hbc-step-3" class="hbc-step-content" style="display: none;" role="tabpanel" aria-labelledby="step-3">
            <h2 class="hbc-subheading">Make Determinations</h2>
            <p class="hbc-text">Confirm your understanding and express your pledge intent.</p>
            
            <div class="hbc-toggle-group">
                <label class="hbc-toggle">
                    <input type="checkbox" id="flag-no-speculation" name="confirmation-flags" value="no_speculation">
                    <span class="hbc-toggle-label">I understand pledges are not speculative investments</span>
                </label>
                <label class="hbc-toggle">
                    <input type="checkbox" id="flag-pledge-not-payment" name="confirmation-flags" value="pledge_not_payment">
                    <span class="hbc-toggle-label">I understand pledges are obligations, not payments</span>
                </label>
                <label class="hbc-toggle">
                    <input type="checkbox" id="flag-proof-required" name="confirmation-flags" value="proof_required">
                    <span class="hbc-toggle-label">I understand proof of delivery is required for confirmation</span>
                </label>
            </div>
            
            <div class="hbc-form-group">
                <label class="hbc-label">Pledge Intent</label>
                <div class="hbc-intent-grid">
                    <label class="hbc-intent-option">
                        <input type="radio" name="pledge-intent" value="support" id="intent-support">
                        <div>Support</div>
                    </label>
                    <label class="hbc-intent-option">
                        <input type="radio" name="pledge-intent" value="trade" id="intent-trade">
                        <div>Trade</div>
                    </label>
                    <label class="hbc-intent-option">
                        <input type="radio" name="pledge-intent" value="donate" id="intent-donate">
                        <div>Donate</div>
                    </label>
                    <label class="hbc-intent-option">
                        <input type="radio" name="pledge-intent" value="learn" id="intent-learn">
                        <div>Learn</div>
                    </label>
                </div>
            </div>
            
            <button type="button" class="hbc-btn hbc-btn-primary" id="hbc-continue-step-3" disabled>Continue</button>
        </div>
        
        <!-- Step 4: Set Impact Preference -->
        <div id="hbc-step-4" class="hbc-step-content" style="display: none;" role="tabpanel" aria-labelledby="step-4">
            <h2 class="hbc-subheading">Set Impact Preference</h2>
            <p class="hbc-text">Express your preference for how the $1.00 patronage allocation is split. Your choices are bounded by community rules.</p>
            
            <div class="hbc-slider-group">
                <div class="hbc-slider-label">
                    <span>Individual Patronage</span>
                    <span class="hbc-slider-value" id="slider-individual-value">0.50</span>
                </div>
                <input type="range" id="slider-individual" class="hbc-slider" min="0.40" max="0.60" step="0.01" value="0.50">
                <p class="hbc-microcopy">Range: 0.40 - 0.60</p>
            </div>
            
            <div class="hbc-slider-group">
                <div class="hbc-slider-label">
                    <span>Group Pool</span>
                    <span class="hbc-slider-value" id="slider-group-value">0.40</span>
                </div>
                <input type="range" id="slider-group" class="hbc-slider" min="0.30" max="0.50" step="0.01" value="0.40">
                <p class="hbc-microcopy">Range: 0.30 - 0.50</p>
            </div>
            
            <div class="hbc-form-group">
                <label for="user-message" class="hbc-label">Optional Message</label>
                <textarea id="user-message" class="hbc-textarea" placeholder="Share your thoughts (optional)"></textarea>
            </div>
            
            <button type="button" class="hbc-btn hbc-btn-primary" id="hbc-submit-participation">Submit Participation</button>
            
            <div id="hbc-submit-result"></div>
        </div>
    </div>
    
    <p class="hbc-microcopy" style="text-align: center; margin-top: 32px;">
        <?php echo esc_html(HBC_POD_LEDGER_DISCLAIMER); ?>
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    let deviceData = null;
    let currentStep = 1;
    let geoData = { lat: null, lng: null };
    
    // Get geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            geoData.lat = position.coords.latitude;
            geoData.lng = position.coords.longitude;
            $('#hbc-geo-status').hide();
        }, function() {
            $('#hbc-geo-status').text('Location access denied. Please enter manually.').show();
        });
    }
    
    // Step 1: Register Device
    $('#hbc-register-device').on('click', function() {
        const deviceId = $('#device-id').val();
        const platform = $('#platform').val();
        const timezone = $('#timezone').val();
        const discordUserId = $('#discord-user-id').val();
        const discordUsername = $('#discord-username').val();
        const discordInviteCode = $('#discord-invite-code').val();
        const acceptLicense = $('#accept-license').is(':checked');
        
        if (!deviceId || !platform) {
            alert('Please fill in all required fields');
            return;
        }
        
        if (!discordUserId || !discordUsername) {
            alert('Discord connection is required. Please provide your Discord User ID and Username.');
            return;
        }
        
        if (!acceptLicense) {
            alert('You must accept the MEGAvoter Brand Licensing Protocol to register this device.');
            return;
        }
        
        if (!geoData.lat || !geoData.lng) {
            alert('Location is required. Please allow location access or refresh the page.');
            return;
        }
        
        $(this).prop('disabled', true).text('Registering...');
        
        $.ajax({
            url: hbcPodLedger.apiUrl + 'register-device',
            method: 'POST',
            headers: {
                'X-WP-Nonce': hbcPodLedger.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                device_id: deviceId,
                platform: platform,
                lat: geoData.lat,
                lng: geoData.lng,
                timestamp: new Date().toISOString(),
                timezone: timezone || null,
                accept_license: true,
                discord_user_id: discordUserId,
                discord_username: discordUsername,
                discord_invite_code: discordInviteCode || null
            }),
            success: function(response) {
                deviceData = response;
                $('#hbc-assigned-branch').text(response.branch);
                $('input[name="branch-preference"][value="' + response.branch + '"]').prop('checked', true).closest('.hbc-branch-option').addClass('selected');
                
                if (response.warning) {
                    $('#hbc-register-result').html('<div class="hbc-message hbc-message-info">' + response.warning + '</div>');
                }
                
                showStep(2);
            },
            error: function(xhr) {
                $('#hbc-register-result').html('<div class="hbc-message hbc-message-error">Error: ' + (xhr.responseJSON?.message || 'Registration failed') + '</div>');
                $('#hbc-register-device').prop('disabled', false).text('Register Device');
            }
        });
    });
    
    // Step 2: Branch Selection
    $('.hbc-branch-option').on('click', function() {
        $('.hbc-branch-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input').prop('checked', true);
        $('#hbc-continue-step-2').prop('disabled', false);
    });
    
    $('#hbc-continue-step-2').on('click', function() {
        if ($('input[name="branch-preference"]:checked').length) {
            showStep(3);
        }
    });
    
    // Step 3: Determinations
    $('input[name="confirmation-flags"], input[name="pledge-intent"]').on('change', function() {
        const flagsChecked = $('input[name="confirmation-flags"]:checked').length;
        const intentChecked = $('input[name="pledge-intent"]:checked').length;
        $('#hbc-continue-step-3').prop('disabled', !(flagsChecked > 0 && intentChecked > 0));
    });
    
    $('#hbc-continue-step-3').on('click', function() {
        showStep(4);
    });
    
    // Step 4: Impact Preference Sliders
    $('#slider-individual').on('input', function() {
        const value = parseFloat($(this).val());
        $('#slider-individual-value').text(value.toFixed(2));
        // Auto-adjust group to maintain sum
        const groupValue = Math.min(0.50, Math.max(0.30, 0.90 - value));
        $('#slider-group').val(groupValue);
        $('#slider-group-value').text(groupValue.toFixed(2));
    });
    
    $('#slider-group').on('input', function() {
        const value = parseFloat($(this).val());
        $('#slider-group-value').text(value.toFixed(2));
        // Auto-adjust individual to maintain sum
        const individualValue = Math.min(0.60, Math.max(0.40, 0.90 - value));
        $('#slider-individual').val(individualValue);
        $('#slider-individual-value').text(individualValue.toFixed(2));
    });
    
    // Submit Participation
    $('#hbc-submit-participation').on('click', function() {
        if (!deviceData) {
            alert('Please complete device registration first');
            return;
        }
        
        const confirmationFlags = {};
        $('input[name="confirmation-flags"]:checked').each(function() {
            confirmationFlags[$(this).val()] = true;
        });
        
        const participationData = {
            device_hash: deviceData.device_hash,
            branch_preference: $('input[name="branch-preference"]:checked').val(),
            pledge_intent: $('input[name="pledge-intent"]:checked').val(),
            confirmation_flags: confirmationFlags,
            allocation_preference: {
                patronage_individual: parseFloat($('#slider-individual').val()),
                patronage_group_pool: parseFloat($('#slider-group').val())
            },
            user_message: $('#user-message').val() || null
        };
        
        $(this).prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: hbcPodLedger.apiUrl + 'participation/create',
            method: 'POST',
            headers: {
                'X-WP-Nonce': hbcPodLedger.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(participationData),
            success: function(response) {
                window.location.href = '?r=' + response.receipt_hash;
            },
            error: function(xhr) {
                $('#hbc-submit-result').html('<div class="hbc-message hbc-message-error">Error: ' + (xhr.responseJSON?.message || 'Submission failed') + '</div>');
                $('#hbc-submit-participation').prop('disabled', false).text('Submit Participation');
            }
        });
    });
    
    function showStep(step) {
        $('.hbc-step-content').hide();
        $('#hbc-step-' + step).show();
        
        $('.hbc-step').removeClass('active');
        $('.hbc-step[data-step="' + step + '"]').addClass('active');
        
        // Mark previous steps as completed
        for (let i = 1; i < step; i++) {
            $('.hbc-step[data-step="' + i + '"]').addClass('completed');
        }
        
        currentStep = step;
    }
});
</script>
