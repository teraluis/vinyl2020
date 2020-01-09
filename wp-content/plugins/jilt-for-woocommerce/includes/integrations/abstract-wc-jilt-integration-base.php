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
 * @package   WC-Jilt/Integrations
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Base Jilt for WC integration class
 *
 * @since 1.3.0
 */
abstract class WC_Jilt_Integration_Base {


	/**
	 * Get the title for this integration, e.g. 'Product Bundles'
	 *
	 * @since 1.3.0
	 * @return string integration title
	 */
	abstract public function get_title();


	/**
	 * Is this integration active?
	 *
	 * Integrations are assumed to be active so long as they are instantiated,
	 * however certain integrations may need to be instantiated/operational,
	 * but in a more "passive" manner if the integration is decoupled from the
	 * integratee, and the integratee is not activated.
	 *
	 * Returning false here will keep this integration from being listed as
	 * "active" (future work once UI support is added).
	 *
	 * @since 1.3.0
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}


}
