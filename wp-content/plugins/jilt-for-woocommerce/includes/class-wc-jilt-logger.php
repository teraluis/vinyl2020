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
 * @package   WC-Jilt
 * @author    Jilt
 * @category  Admin
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Extend the WC core logger class to add support for log levels, and automatic
 * API logging.
 *
 * Note: WC 3.0 adds core support for logging levels, so we should remove our
 * level handling in favor of core's once we drop 2.6 compat
 *
 * @since 1.2.0
 */
class WC_Jilt_Logger extends WC_Logger {


	/** Information interesting for Developers, when trying to debug a problem */
	const DEBUG = 100;

	/** Information interesting for Support staff trying to figure out the context of a given error */
	const INFO = 200;

	/** Indicates potentially harmful events or states in the program */
	const WARNING = 400;

	/** Indicates non-fatal errors in the application */
	const ERROR = 500;

	/** Indicates the most severe of error conditions */
	const EMERGENCY = 800;

	/** Logging disabled: custom log level not found in WC 3.0 core */
	const OFF = 900;

	/** @var int the current log level */
	protected $threshold;

	/** @var string the default log id */
	private $log_id;

	/** @var array data from last request, if any. see SV_WC_API_Base::broadcast_request() for format */
	private $last_api_request;

	/** @var array data from last API response, if any */
	private $last_api_response;

	/** @var string the last logged API request ID, if any */
	private $last_logged_request_id;


	/**
	 * Construct the logger with a given threshold and log id
	 *
	 * @param int $threshold one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 * @param string $log_id the default log id
	 */
	public function __construct( $threshold, $log_id ) {

		parent::__construct();

		$this->log_id    = $log_id;
		$this->threshold = $threshold;
	}


	/** Core methods ******************************************************/


	/**
	 * Saves errors or messages to WC log when logging is enabled.
	 *
	 * Note: this should become an overridden WC_Logger::log when WC 2.6 compat is dropped
	 *
	 * @since 1.1.0
	 * @param int $level one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 * @param string $message error or message to save to log
	 * @param string $log_id optional log id to segment the files by, defaults to plugin id
	 */
	public function log_with_level( $level, $message, $log_id = null ) {

		// allow logging?
		if ( $this->logging_enabled( $level ) ) {

			$level_name = $this->get_log_level_name( $level );

			// if we're logging an error or fatal, and there is an unlogged API
			// request, log it as well
			if ( $this->last_api_request && $level >= self::ERROR ) {
				$this->log_api_request_helper( $level_name, $this->last_api_request, $this->last_api_response, $log_id );

				$this->last_api_request = null;
				$this->last_api_response = null;
			}

			$this->add( $this->get_log_id( $log_id ), "{$level_name} : {$message}" );
		}

	}


	/**
	 * Adds an emergency level message.
	 *
	 * System is unusable.
	 *
	 * Note: this matches the WC 3.0 signature and should be removed once WC 2.6 compat is dropped
	 *
	 * @param string $message the message to log
	 * @param array $context additional context ('source')
	 */
	public function emergency( $message, $context = array() ) {
		$this->log_with_level( self::EMERGENCY, $message, isset( $context['source'] ) ? $context['source'] : null );
	}


	/**
	 * Adds an error level message.
	 *
	 * Runtime errors that do not require immediate action but should typically be logged
	 * and monitored.
	 *
	 * Note: this matches the WC 3.0 signature and should be removed once WC 2.6 compat is dropped
	 *
	 * @param string $message the message to log
	 * @param array $context additional context ('source')
	 */
	public function error( $message, $context = array() ) {
		$this->log_with_level( self::ERROR, $message, isset( $context['source'] ) ? $context['source'] : null );
	}


	/**
	 * Adds a warning level message.
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
	 * necessarily wrong.
	 *
	 * Note: this matches the WC 3.0 signature and should be removed once WC 2.6 compat is dropped
	 *
	 * @param string $message the message to log
	 * @param array $context additional context ('source')
	 */
	public function warning( $message, $context = array() ) {
		$this->log_with_level( self::WARNING, $message, isset( $context['source'] ) ? $context['source'] : null );
	}


	/**
	 * Adds a info level message.
	 *
	 * Interesting events.
	 * Example: User logs in, SQL logs.
	 *
	 * Note: this matches the WC 3.0 signature and should be removed once WC 2.6 compat is dropped
	 *
	 * @param string $message the message to log
	 * @param array $context additional context ('source')
	 */
	public function info( $message, $context = array() ) {
		$this->log_with_level( self::INFO, $message, isset( $context['source'] ) ? $context['source'] : null );
	}


	/**
	 * Adds a debug level message.
	 *
	 * Detailed debug information.
	 *
	 * Note: this matches the WC 3.0 signature and should be removed once WC 2.6 compat is dropped
	 *
	 * @param string $message the message to log
	 * @param array $context additional context ('source')
	 */
	public function debug( $message, $context = array() ) {
		$this->log_with_level( self::DEBUG, $message, isset( $context['source'] ) ? $context['source'] : null );
	}


	/** Getters/Setters ******************************************************/


	/**
	 * Returns the current log level threshold
	 *
	 * @since 1.0.0
	 * @return int one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 */
	public function get_threshold() {

		return $this->threshold;
	}


	/**
	 * Set the log level threshold
	 *
	 * @since 1.0.1
	 * @param int $threshold new log level one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 */
	public function set_threshold( $threshold ) {
		$this->threshold = $threshold;
	}


	/**
	 * Returns the current log level as a string name
	 *
	 * Note: this should be removed in favor of WC_Log_Levels::$severity_to_level once WC 2.6 compat is dropped
	 *
	 * @since 1.1.0
	 * @param int $level optional level one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 * @return string one of 'OFF', 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'EMERGENCY'
	 */
	public function get_log_level_name( $level = null ) {

		if ( null === $level ) {
			$level = $this->get_threshold();
		}

		switch ( $level ) {
			case self::DEBUG:     return 'DEBUG';
			case self::INFO:      return 'INFO';
			case self::WARNING:   return 'WARNING';
			case self::ERROR:     return 'ERROR';
			case self::EMERGENCY: return 'EMERGENCY';
			case self::OFF:       return 'OFF';
		}
	}


	/**
	 * Gets the full URL to the log file for a given $handle.
	 *
	 * Note: this just delegates to the existing SV_WC_Helper method, which
	 * I imagine would be permanently moved to this class if the framework
	 * logging code were refactored to match this proof of concept
	 *
	 * @since 1.2.0
	 * @param string $handle log handle
	 * @return string URL to the log file identified by $handle
	 */
	public static function get_log_file_url( $handle ) {
		// delegate to SV_WC_Helper
		return Framework\SV_WC_Helper::get_wc_log_file_url( $handle );
	}


	/** API logging methods ******************************************************/


	/**
	 * Log API requests/responses
	 *
	 * @since 1.1.0
	 * @see SV_WC_Plugin::log_api_request
	 * @param array $request request data, see SV_WC_API_Base::broadcast_request() for format
	 * @param array $response response data
	 * @param string|null $log_id log to write data to
	 */
	public function log_api_request( $request, $response, $log_id = null ) {

		// defaults to DEBUG level
		if ( $this->logging_enabled( self::DEBUG ) ) {
			$this->log_api_request_helper( 'DEBUG', $request, $response, $log_id );

			$this->last_api_request = null;
			$this->last_api_response = null;
		} else {
			// save the request/response data in case our log level is higher than
			// DEBUG but there was an error
			$this->last_api_request  = $request;
			$this->last_api_response = $response;
		}

	}


	/**
	 * Log API requests/responses with a given log level
	 *
	 * @since 1.1.0
	 * @see self::log_api_request()
	 * @param string $level_name one of 'OFF', 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'EMERGENCY'
	 * @param array $request request data, see SV_WC_API_Base::broadcast_request() for format
	 * @param array $response response data
	 * @param string|null $log_id log to write data to
	 */
	private function log_api_request_helper( $level_name, $request, $response, $log_id = null ) {

		// use the x-request-id if present to avoid double-logging certain API
		// requests, e.g. 401 response to shop update that requires a token refresh
		// TODO: fix this properly by avoiding the double logging, probably requires some work in the framework API base class {justinstern - 2018-04-21}
		$x_request_id = null;

		if ( isset( $response['headers']['x-request-id'] ) ) {
			$x_request_id = $response['headers']['x-request-id'];

			if ( $x_request_id && $this->last_logged_request_id == $x_request_id ) {
				return;
			}
		}

		$log_id = $this->get_log_id( $log_id );

		$this->add( $log_id, "{$level_name} : Request\n" . $this->get_api_log_message( $request ));

		if ( ! empty( $response ) ) {
			$this->add( $log_id, "{$level_name} : Response\n" . $this->get_api_log_message( $response ) );
		}

		$this->last_logged_request_id = $x_request_id;
	}


	/**
	 * Transform the API request/response data into a string suitable for logging
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::get_api_log_message()
	 * @param array $data
	 * @return string
	 */
	public function get_api_log_message( $data ) {

		$messages = array();

		$messages[] = isset( $data['uri'] ) && $data['uri'] ? 'Request' : 'Response';

		foreach ( (array) $data as $key => $value ) {
			$messages[] = trim( sprintf( '%s: %s', $key, is_array( $value ) || ( is_object( $value ) && 'stdClass' === get_class( $value ) ) ? print_r( (array) $value, true ) : $value ) );
		}

		return implode( "\n", $messages ) . "\n";
	}


	/** Helper methods ******************************************************/


	/**
	 * Is logging enabled for the given level?
	 *
	 * Note: this should be removed once WC 2.6 compat is dropped
	 *
	 * @since 1.1.0
	 * @param int $level one of OFF, DEBUG, INFO, WARNING, ERROR, EMERGENCY
	 * @return boolean true if logging is enabled for the given $level
	 */
	private function logging_enabled( $level ) {
		return $level >= $this->get_threshold();
	}


	/**
	 * Get the log id
	 *
	 * @since 1.2.0
	 * @param string $log_id the log id to use; defaults to plugin id if null
	 * @return string the log id to use
	 */
	private function get_log_id( $log_id = null ) {

		if ( null === $log_id ) {
			$log_id = $this->log_id;
		}

		return $log_id;
	}


}
