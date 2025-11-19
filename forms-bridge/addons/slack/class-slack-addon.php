<?php
/**
 * Class Slack_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-slack-form-bridge.php';
require_once 'hooks.php';

/**
 * Slack addon class.
 */
class Slack_Addon extends Addon {
	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Slack';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'slack';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Slack_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Slack_Form_Bridge(
			array(
				'name'     => '__slack-' . time(),
				'endpoint' => '/api/conversations.list',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Slack backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$bridge = new Slack_Form_Bridge(
			array(
				'name'     => '__slack-' . time(),
				'endpoint' => $endpoint,
				'method'   => 'GET',
				'backend'  => $backend,
			),
			'zulip'
		);

		return $bridge->submit();
	}
}

Slack_Addon::setup();
