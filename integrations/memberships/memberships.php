<?php

/*
* @return \WC_Memberships_Membership_Plan[]|\WC_Memberships_Integration_Subscriptions_Membership_Plan[]
 */
function wc_dynamic_pricing_membership_get_all_plans() {

	$plans = wc_memberships_get_membership_plans();
	return is_array($plans) ? $plans : array();
}

add_action( 'woocommerce_dynamic_pricing_applies_to_options', 'wc_dynamic_pricing_membership_applies_to_option', 10, 4 );

function wc_dynamic_pricing_membership_applies_to_option( $module_name, $condition, $name, $condition_index ) {
	?>
    <option <?php selected( 'membership', $condition['args']['applies_to'] ); ?> value="membership"><?php _e( 'Membership Plan', 'wc_dynamic_pricing' ); ?></option>
	<?php
}

add_action( 'woocommerce_dynamic_pricing_applies_to_selectors', 'wc_dynamic_pricing_membership_applies_to_selector', 10, 4 );

function wc_dynamic_pricing_membership_applies_to_selector( $module_name, $condition, $name, $condition_index ) {

	$div_style = ( $condition['args']['applies_to'] != 'membership' ) ? 'display:none;' : '';

	$all_plans = wc_dynamic_pricing_membership_get_all_plans();
	?>

    <div class="membership" style="<?php echo $div_style; ?>">
		<?php $chunks = array_chunk( $all_plans, ceil( count( $all_plans ) / 3 ), true ); ?>
		<?php foreach ( $chunks as $chunk ) : ?>
            <ul class="list-column">
				<?php foreach ( $chunk as $plan ) : ?>
					<?php $plan_id = $plan->get_id() ?>
					<?php $checked = ( isset( $condition['args']['memberships'] ) && is_array( $condition['args']['memberships'] ) && in_array( $plan_id, $condition['args']['memberships'] ) ) ? 'checked="checked"' : ''; ?>
                    <li>
                        <label for="<?php echo $name; ?>_membership_<?php echo $plan_id; ?>" class="selectit">
                            <input <?php echo $checked; ?> type="checkbox" id="<?php echo $name; ?>_membership_<?php echo $plan_id; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][memberships][]" value="<?php echo $plan_id; ?>"/><?php echo $plan->get_name(); ?>
                        </label>
                    </li>
				<?php endforeach; ?>
            </ul>
		<?php endforeach; ?>
    </div>

	<?php
}

add_action( 'woocommerce_dynamic_pricing_metabox_js', 'woocommerce_dynamic_membership_pricing_metabox_js' );

function woocommerce_dynamic_membership_pricing_metabox_js( $module_name ) {
	?>
    $('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_apply_to', 'change', function(event) {
    var value = $(this).val();
    if (value != 'membership' && $('.membership', $(this).parent()).is(':visible')) {
    $('.membership', $(this).parent() ).fadeOut();
    $('.membership input[type=checkbox]', $(this).closest('div')).removeAttr('checked');
    }

    if (value == 'membership') {
    $('.membership', $(this).parent() ).fadeIn();
    }

    });
	<?php
}

add_filter( 'woocommerce_dynamic_pricing_is_rule_set_valid_for_user', 'woocommerce_dynamic_pricing_memberships_is_rule_set_valid_for_user', 10, 3 );

function woocommerce_dynamic_pricing_memberships_is_rule_set_valid_for_user( $result, $condition, $rule_set ) {
	switch ( $condition['type'] ) {
		case 'apply_to':
			if ( is_array( $condition['args'] ) && isset( $condition['args']['applies_to'] ) ) {
				if ( $condition['args']['applies_to'] == 'membership' && isset( $condition['args']['memberships'] ) && is_array( $condition['args']['memberships'] ) ) {
					if ( is_user_logged_in() ) {
						foreach ( $condition['args']['memberships'] as $plan_id ) {
							if ( wc_memberships_is_user_active_member( get_current_user_id(), $plan_id ) ) {
								$result = 1;
								break;
							}
						}
					}
				}
			}
			break;
		default:
			break;
	}

	return $result;
}
