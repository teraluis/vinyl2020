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
 * Jilt admin welcome splash screen.
 *
 * @since 1.5.0
 * @version 1.5.0
 */
?>

<div class="wc-jilt-welcome-splash">

	<h1><img src="<?php echo esc_url( wc_jilt()->get_plugin_url() ); ?>/assets/img/jilt-logo-blue.svg" class="logo" alt="<?php esc_attr_e( 'Jilt', 'jilt-for-woocommerce' ); ?>"></h1>
	<h2><?php esc_html_e( "Hooray, your store is now connected to Jilt! You're ready to start sending automated emails and driving more revenue!", 'jilt-for-woocommerce' ); ?></h2>
	<h4><?php esc_html_e( "Let's talk next steps:", 'jilt-for-woocommerce' ); ?></h4>

	<div class="next-steps">
		<ol>
			<?php /* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */ ?>
			<li><?php printf( esc_html__( 'You can %1$sview your Jilt dashboard here%2$s — setting up your first campaign takes only minutes and lets you start recovering revenue right away.', 'jilt-for-woocommerce' ), '<a href="' . esc_url( wc_jilt()->get_integration()->get_jilt_app_url() ) . '">', '</a>' ); ?></li>
			<?php /* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */ ?>
			<li><?php printf( esc_html__( 'You can adjust your shop\'s %1$sStorefront settings here%2$s. We recommend enabling add-to-cart popovers to collect more emails from customers (increasing the number of carts you can recover).', 'jilt-for-woocommerce' ), '<a href="' . esc_url( wc_jilt()->get_integration()->get_jilt_app_url( 'edit' ) ) . '">', '</a>' ); ?></li>
			<?php /* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */ ?>
			<li><?php printf( esc_html__( 'You can see the %1$splugin settings here%2$s. We recommend keeping debug mode off unless you\'re working on an issue with our support team.', 'jilt-for-woocommerce' ), '<a href="' . esc_url( wc_jilt()->get_settings_url() ) . '">', '</a>' ); ?></li>
			<?php /* translators: Placeholders: %1$s and %3$s - opening <a> tag, %2$s and %4$s - closing </a> tag */ ?>
			<li><?php printf( esc_html__( 'If you run into any questions or issues, our %1$sknowledge base is here%2$s and you can %3$sreach our support team here%4$s.', 'jilt-for-woocommerce' ), '<a href="' . esc_url( wc_jilt()->get_documentation_url() ) . '">', '</a>', '<a href="' . esc_url( wc_jilt()->get_support_url() ) . '">', '</a>' ); ?></li>
		</ol>
	</div>

	<?php /* translators: Placeholders: %1$s and %3$s - opening <a> tag, %2$s and %4$s - closing </a> tag */ ?>
	<p class="ready-to-go"><?php printf( esc_html__( 'Ready to keep going? %1$sLet\'s start configuring!%2$s', 'jilt-for-woocommerce' ), '<a href="' . esc_url( wc_jilt()->get_integration()->get_jilt_app_url( 'edit' ) ) . '">', '</a>' ); ?></p>
</div>
