<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Helper: get stored allowed roles for a given context 
function ardtdw_get_roles( $context ) {
	$stored = get_option( 'ardtdw-roles-' . $context, '' );
	if ( $stored === '' ) {
		if ( $context === 'dashboard' ) {
			$legacy = get_option( 'ardtdw-checkbox-admineditor' );
			return $legacy ? array( 'administrator', 'editor' ) : array( 'administrator' );
		}
		if ( $context === 'frontend' ) {
			$roles = array();
			if ( get_option( 'ardtdw-checkbox' ) )        $roles[] = 'administrator';
			if ( get_option( 'ardtdw-checkbox-editor' ) ) $roles[] = 'editor';
			return $roles;
		}
	}
	return is_array( $stored ) ? $stored : array();
}

// Helper: check if current user's role is in an allowed list 
function ardtdw_user_has_role( $allowed_roles ) {
	$user = wp_get_current_user();
	return ! empty( array_intersect( (array) $user->roles, $allowed_roles ) );
}

// Register dashboard widget 
if ( ! function_exists( 'ardtdw_widgetsetup' ) ) {
	function ardtdw_widgetsetup() {
		$allowed = ardtdw_get_roles( 'dashboard' );
		if ( ardtdw_user_has_role( $allowed ) ) {
			wp_add_dashboard_widget( 'ardtdw', 'Website To-Do List', 'ardtdw_widget' );
		}
	}
	add_action( 'wp_dashboard_setup', 'ardtdw_widgetsetup' );
}

// Shared kses rules 
function ardtdw_kses_rules() {
	return array(
		'a'      => array( 'href' => array(), 'target' => array(), 'title' => array() ),
		'em'     => array(),
		'strong' => array(),
		'b'      => array(),
		'u'      => array(),
	);
}

// AJAX: update items only (add / delete)
function ardtdw_update_items() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ardtdw_update_list' ) ) {
		wp_send_json_error( __( 'Security check failed.', 'dashboard-to-do-list' ) );
	}
	if ( ! ardtdw_user_has_role( ardtdw_get_roles( 'dashboard' ) ) ) {
		wp_send_json_error( __( 'Permission denied.', 'dashboard-to-do-list' ) );
	}
	$content = isset( $_POST['ardtdw-textarea'] ) ? wp_kses( wp_unslash( $_POST['ardtdw-textarea'] ), ardtdw_kses_rules() ) : '';
	update_option( 'ardtdw-textarea', $content, '', 'yes' );
	wp_send_json_success();
}
add_action( 'wp_ajax_ardtdw_update_items', 'ardtdw_update_items' );

// Dashboard widget output 
if ( ! function_exists( 'ardtdw_widget' ) ) {
	function ardtdw_widget() {
		$message      = '';
		$message_type = 'ardtdw-updated';

		if ( isset( $_POST['ardtdw-save'] ) ) {
			if ( ! isset( $_POST['ardtdw_confirm'] ) || ! wp_verify_nonce( $_POST['ardtdw_confirm'], 'ardtdw_update_list' ) ) {
				$message      = __( 'Security check failed.', 'dashboard-to-do-list' );
				$message_type = 'ardtdw-error';
			} else {
				$textarea_post = isset( $_POST['ardtdw-textarea'] )
				? wp_kses( wp_unslash( $_POST['ardtdw-textarea'] ), ardtdw_kses_rules() )
				: '';
				update_option( 'ardtdw-textarea', $textarea_post, '', 'yes' );

				if ( current_user_can( 'administrator' ) ) {
					$dashboard_roles_post = isset( $_POST['ardtdw-dashboard-roles'] ) && is_array( $_POST['ardtdw-dashboard-roles'] )
					? array_map( 'sanitize_key', $_POST['ardtdw-dashboard-roles'] ) : array();
					if ( ! in_array( 'administrator', $dashboard_roles_post ) ) $dashboard_roles_post[] = 'administrator';
					update_option( 'ardtdw-roles-dashboard', $dashboard_roles_post );

					$frontend_roles_post = isset( $_POST['ardtdw-frontend-roles'] ) && is_array( $_POST['ardtdw-frontend-roles'] )
					? array_map( 'sanitize_key', $_POST['ardtdw-frontend-roles'] ) : array();
					if ( ! empty( $frontend_roles_post ) && empty( $textarea_post ) ) {
						$frontend_roles_post = array();
						$message_type        = 'ardtdw-error';
						$message             = __( 'You must have at least one to-do in your list to show it on the website.', 'dashboard-to-do-list' );
					}
					update_option( 'ardtdw-roles-frontend', $frontend_roles_post );

					$position_post = isset( $_POST['ardtdw-position'] ) && in_array( $_POST['ardtdw-position'], array( 'left', 'right' ) )
					? $_POST['ardtdw-position'] : 'right';
					update_option( 'ardtdw-position', $position_post );

					$color_post = isset( $_POST['ardtdw-color'] ) ? sanitize_hex_color( $_POST['ardtdw-color'] ) : '';
					update_option( 'ardtdw-color', $color_post );
				}

				if ( empty( $message ) ) {
					$message = __( 'To-Do list saved.', 'dashboard-to-do-list' );
				}
			}
		}

		$textarea = stripslashes( get_option( 'ardtdw-textarea' ) );
		$position = get_option( 'ardtdw-position', 'right' );
		$color = get_option( 'ardtdw-color', '' );
		$dashboard_roles = ardtdw_get_roles( 'dashboard' );
		$frontend_roles  = ardtdw_get_roles( 'frontend' );
		if ( empty( $position ) ) $position = 'right';
		$all_roles = wp_roles()->get_names();

		if ( $message ) : ?>
			<div class="ardtdw-message <?php echo esc_attr( $message_type ); ?>"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<form id="ardtdw-form" method="post" action="<?php echo esc_url( admin_url( 'index.php' ) ); ?>">
			<?php wp_nonce_field( 'ardtdw_update_list', 'ardtdw_confirm' ); ?>

			<?php /* Hidden textarea — JS populates this before submit */ ?>
			<textarea name="ardtdw-textarea" id="ardtdw-textarea" style="display:none;"><?php echo esc_html( $textarea ); ?></textarea>

			<?php /* Checklist — rendered by JS from the hidden textarea */ ?>
			<ul id="ardtdw-checklist"></ul>

			<?php /* Add new item */ ?>
			<div class="ardtdw-add-row">
				<input type="text" id="ardtdw-new-item" placeholder="<?php esc_attr_e( 'Add a new to-do…', 'dashboard-to-do-list' ); ?>">
				<button type="button" id="ardtdw-add-btn" class="button"><?php _e( 'Add', 'dashboard-to-do-list' ); ?></button>
			</div>

			<p class="field-comment"><?php _e( 'Accepts HTML: a (href, title, target), em, strong, b, u.', 'dashboard-to-do-list' ); ?></p>

			<?php /* Bulk edit escape hatch */ ?>
			<div class="ardtdw-raw-section">
				<button type="button" id="ardtdw-raw-toggle"><?php _e( 'Bulk Edit', 'dashboard-to-do-list' ); ?></button>
				<div id="ardtdw-raw-wrap" style="display:none;">
					<textarea id="ardtdw-raw-textarea" rows="8"></textarea>
					<p class="field-comment"><?php _e( 'One to-do per line. End any item with <strong>DONE</strong> to show it crossed out on the website. Accepts HTML: a (href, title, target), em, strong, b, u.', 'dashboard-to-do-list' ); ?></p>
				</div>
			</div>

			<?php if ( current_user_can( 'administrator' ) ) : ?>

				<hr class="ardtdw-divider">

				<button type="button" id="ardtdw-settings-toggle">
					<?php _e( 'Settings', 'dashboard-to-do-list' ); ?>
					<span class="toggle-indicator ardtdw-settings-arrow" aria-hidden="true" style="transform:rotate(180deg)"></span>
				</button>

				<div id="ardtdw-settings-panel" style="display:none;">

				<p><strong><?php _e( 'Show dashboard widget for:', 'dashboard-to-do-list' ); ?></strong></p>
				<div class="ardtdw-roles-grid">
					<?php foreach ( $all_roles as $role_slug => $role_name ) :
						$checked  = in_array( $role_slug, $dashboard_roles ) ? 'checked' : '';
						$disabled = ( $role_slug === 'administrator' ) ? 'disabled checked' : $checked;
						?>
						<label>
							<input type="checkbox" name="ardtdw-dashboard-roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php echo $disabled; ?>>
							<?php echo esc_html( translate_user_role( $role_name ) ); ?>
						</label>
					<?php endforeach; ?>
					<input type="hidden" name="ardtdw-dashboard-roles[]" value="administrator">
				</div>

				<hr class="ardtdw-divider">

				<p><strong><?php _e( 'Show floating list on website for:', 'dashboard-to-do-list' ); ?></strong></p>
				<div class="ardtdw-roles-grid">
					<?php foreach ( $all_roles as $role_slug => $role_name ) :
						$checked = in_array( $role_slug, $frontend_roles ) ? 'checked' : '';
						?>
						<label>
							<input type="checkbox" name="ardtdw-frontend-roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php echo $checked; ?>>
							<?php echo esc_html( translate_user_role( $role_name ) ); ?>
						</label>
					<?php endforeach; ?>
					<label>
						<input type="checkbox" name="ardtdw-frontend-roles[]" value="guest" <?php echo in_array( 'guest', $frontend_roles ) ? 'checked' : ''; ?>>
						<?php _e( 'Guests (not logged in)', 'dashboard-to-do-list' ); ?>
					</label>
				</div>
				<p class="field-comment"><?php _e( 'Leave all unchecked to hide the list from the website entirely.', 'dashboard-to-do-list' ); ?></p>

				<hr class="ardtdw-divider">

				<div class="ardtdw-row">
					<div>
						<p><strong><?php _e( 'List position:', 'dashboard-to-do-list' ); ?></strong></p>
						<label><input type="radio" name="ardtdw-position" value="left" <?php checked( $position, 'left' ); ?>> <?php _e( 'Left', 'dashboard-to-do-list' ); ?></label>
						&nbsp;&nbsp;
						<label><input type="radio" name="ardtdw-position" value="right" <?php checked( $position, 'right' ); ?>> <?php _e( 'Right', 'dashboard-to-do-list' ); ?></label>
					</div>
					<div>
						<p><strong><?php _e( 'Panel colour:', 'dashboard-to-do-list' ); ?></strong></p>
						<input type="text" name="ardtdw-color" id="ardtdw-color" value="<?php echo esc_attr( $color ); ?>" class="ardtdw-color-field">
					</div>
				</div>

				</div><?php /* #ardtdw-settings-panel */ ?>

			<?php endif; ?>

			<p>
				<input type="submit" value="<?php _e( 'Save', 'dashboard-to-do-list' ); ?>" class="button-primary" name="ardtdw-save" id="ardtdw-save-btn">
			</p>
		</form>
		<?php
	}
	}

// Frontend widget injection 
if ( ! function_exists( 'ardtdw_widgethtml' ) ) {
	function ardtdw_widgethtml() {
		if ( ! get_option( 'ardtdw-textarea' ) ) return;
		$frontend_roles = ardtdw_get_roles( 'frontend' );
		if ( empty( $frontend_roles ) ) return;

		$show = ! is_user_logged_in()
		? in_array( 'guest', $frontend_roles )
		: ardtdw_user_has_role( $frontend_roles );

		if ( $show ) ardtdw_widget_html();
	}
	add_action( 'wp_footer', 'ardtdw_widgethtml' );
}
