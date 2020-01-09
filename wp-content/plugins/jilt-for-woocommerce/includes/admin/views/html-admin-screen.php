<?php
/**
 * WooCommerce Jilt
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@jilt.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Jilt to newer
 * versions in the future. If you wish to customize WooCommerce Jilt for your
 * needs please refer to http://help.jilt.com/jilt-for-woocommerce
 *
 * @package   WC-Jilt/Admin/Views
 * @author    Jilt
 * @category  Admin
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Jilt admin screen wrapper.
 *
 * @since 1.5.0
 * @version 1.5.0
 */
?>

<div class="wrap woocommerce">

	<?php if ( 'welcome' === $current_tab ) : ?>

		<?php include( 'html-admin-welcome-page.php' ); ?>

	<?php else : ?>

		<h1><img src="<?php echo esc_url( wc_jilt()->get_plugin_url() ); ?>/assets/img/jilt-logo-blue.svg" id="wc-jilt-admin-logo" alt="<?php esc_attr_e( 'Jilt', 'jilt-for-woocommerce' ); ?>"></h1>
		<p><?php esc_html_e( 'Automatically send reminder emails to customers who have abandoned their cart, and recover lost sales.', 'jilt-for-woocommerce' ); ?></p>

		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_id => $tab_title ) :

				$class = ( $tab_id === $current_tab ) ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' );
				$url   = add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=wc-jilt' ) );

				printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $url ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab_title ) );

			endforeach;
			?>
		</h2>

		<?php wc_jilt()->get_message_handler()->show_messages(); ?>

		<form method="post" id="mainform" action="" enctype="multipart/form-data">
			<?php

			if ( 'settings' === $current_tab ) {

				$this->integration->admin_options();

				wp_nonce_field( 'save-jilt-settings' );
				submit_button( __( 'Save settings', 'jilt-for-woocommerce' ) );
			}

			?>
		</form>
	<?php endif; ?>
</div>
