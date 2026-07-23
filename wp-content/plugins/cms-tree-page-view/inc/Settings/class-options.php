<?php
/**
 * Plugin options: storage, defaults, the settings screen, and its save handler.
 *
 * @package cms-tree-page-view
 */

namespace CMS_Tree_Page_View\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads/writes the `cms_tpv_options` option (which post types show a tree, on the
 * dashboard and/or in the menu) and renders the Settings > CMS Tree Page View
 * screen. Consumed across the plugin via the cms_tpv_get_options() shim.
 */
class Options {

	/**
	 * Load the plugin options, normalising the dashboard/menu keys to arrays.
	 *
	 * @return array<string, mixed> Options with guaranteed 'dashboard' and 'menu' arrays.
	 */
	public static function get() {
		$arr_options = (array) get_option( 'cms_tpv_options' );

		if ( array_key_exists( 'dashboard', $arr_options ) ) {
			$arr_options['dashboard'] = (array) $arr_options['dashboard'];
		} else {
			$arr_options['dashboard'] = array();
		}

		if ( array_key_exists( 'menu', $arr_options ) ) {
			$arr_options['menu'] = (array) $arr_options['menu'];
		} else {
			$arr_options['menu'] = array();
		}

		return $arr_options;
	}

	/**
	 * On first install (no stored version), enable the tree for pages plus every
	 * hierarchical custom post type.
	 */
	public static function setup_defaults() {
		$version = get_option( 'cms_tpv_version', 0 );

		if ( $version <= 0 ) {
			$options = array();

			// Add pages to both dashboard and menu.
			$options['dashboard'] = array( 'page' );

			// Since 0.10.1 enable menu for all hierarchical custom post types.
			$post_types = get_post_types(
				array(
					'show_ui'      => true,
					'hierarchical' => true,
				),
				'objects'
			);

			foreach ( $post_types as $one_post_type ) {
				$options['menu'][] = $one_post_type->name;
			}

			$options['menu'] = array_unique( $options['menu'] );

			update_option( 'cms_tpv_options', $options );
		}
	}

	/**
	 * Post types the plugin never offers a tree for (used by other plugins).
	 *
	 * @return string[] Ignored post type slugs.
	 */
	public static function ignored_post_types() {
		return array(
			// Advanced Custom Fields.
			'acf',
		);
	}

	/**
	 * Whether a post type is on the ignore list.
	 *
	 * @param string $post_type Post type slug to check.
	 * @return bool True if the post type is ignored.
	 */
	public static function is_post_type_ignored( $post_type ) {
		return in_array( $post_type, self::ignored_post_types(), true );
	}

	/**
	 * Persist the settings form on admin_init (self-handled Post/Redirect/Get).
	 */
	public static function save() {
		$action = isset( $_POST['cms_tpv_action'] ) ? sanitize_text_field( wp_unslash( $_POST['cms_tpv_action'] ) ) : '';

		if ( 'save_settings' === $action && current_user_can( 'manage_options' ) && check_admin_referer( 'update-options' ) ) {

			$options              = array();
			$options['dashboard'] = isset( $_POST['post-type-dashboard'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['post-type-dashboard'] ) ) : array();
			$options['menu']      = isset( $_POST['post-type-menu'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['post-type-menu'] ) ) : array();

			update_option( 'cms_tpv_options', $options );

			// Self-handled save (Post/Redirect/Get): the form posts to this settings
			// page rather than core options.php, so only this handler runs. Redirect
			// back with a flag so a refresh doesn't resubmit and we can show a notice.
			// (Posting to options.php with action=update + page_options caused
			// "unregistered/deprecated setting" and "headers already sent" notices on
			// modern WordPress).
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', \CMS_Tree_Page_View\Admin\Menu::get_settings_url() ) );
			exit;
		}
	}

	/**
	 * Render the Settings > CMS Tree Page View screen (add_submenu_page callback).
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">

			<?php \CMS_Tree_Page_View\Admin\Promo::help_card(); ?>

			<h2><?php echo esc_html( CMS_TPV_NAME ); ?> <?php esc_html_e( 'settings', 'cms-tree-page-view' ); ?></h2>

			<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag set by WordPress core after a nonce-verified save; nothing is mutated here. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'cms-tree-page-view' ); ?></p></div>
			<?php endif; ?>

			<form method="post" class="cmtpv_options_form">

				<?php wp_nonce_field( 'update-options' ); ?>

				<h3><?php esc_html_e( 'Select where to show a tree for pages and custom post types', 'cms-tree-page-view' ); ?></h3>

				<table class="form-table">

					<tbody>

						<?php

						$options = self::get();

						$post_types = get_post_types(
							array(
								'show_ui' => true,
							),
							'objects'
						);

						foreach ( $post_types as $one_post_type ) {

							if ( self::is_post_type_ignored( $one_post_type->name ) ) {
								continue;
							}

							$name = $one_post_type->name;

							// Posts are intentionally supported (enabled 2011); only media/attachments are skipped.
							if ( $name === 'attachment' ) {
								continue;
							}

							$on_dashboard = in_array( $name, $options['dashboard'], true );
							$in_menu      = in_array( $name, $options['menu'], true );

							printf(
								'<tr><th scope="row"><p>%1$s</p></th><td><p>',
								esc_html( $one_post_type->label )
							);

							printf(
								'<input %1$s type="checkbox" name="post-type-dashboard[]" value="%2$s" id="post-type-dashboard-%2$s" /> <label for="post-type-dashboard-%2$s">%3$s</label>',
								checked( $on_dashboard, true, false ),
								esc_attr( $name ),
								esc_html__( 'On dashboard', 'cms-tree-page-view' )
							);

							echo '<br />';

							printf(
								'<input %1$s type="checkbox" name="post-type-menu[]" value="%2$s" id="post-type-menu-%2$s" /> <label for="post-type-menu-%2$s">%3$s</label>',
								checked( $in_menu, true, false ),
								esc_attr( $name ),
								esc_html__( 'In menu', 'cms-tree-page-view' )
							);

							echo '</p></td></tr>';

						}

						?>
					</tbody>
				</table>

				<input type="hidden" name="cms_tpv_action" value="save_settings" />
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'cms-tree-page-view' ); ?>" />
				</p>

			</form>

		</div>

		<?php
	}
}
