<?php
/**
 * Class Settings_Store
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WPCT_PLUGIN\Settings_Store as Base_Settings_Store;
use HTTP_BRIDGE\Http_Setting;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Plugin settings.
 */
class Settings_Store extends Base_Settings_Store {

	/**
	 * Handle plugin settings rest controller class name.
	 *
	 * @var string REST Controller class name.
	 */
	const REST_CONTROLLER = '\FORMS_BRIDGE\REST_Settings_Controller';

	/**
	 * Inherits the parent constructor and sets up settings' validation callbacks.
	 *
	 * @param mixed[] ...$args Array of constructor arguments.
	 */
	protected function construct( ...$args ) {
		parent::construct( ...$args );

		$admin_email = get_option( 'admin_email' );

		self::register_setting(
			array(
				'name'       => 'general',
				'properties' => array(
					'notification_receiver' => array(
						'type'    => 'string',
						'format'  => 'email',
						'default' => $admin_email,
					),
				),
				'required'   => array( 'notification_receiver' ),
				'default'    => array(
					'notification_receiver' => $admin_email,
				),
			)
		);

		Http_Setting::register( $this );
	}
}
