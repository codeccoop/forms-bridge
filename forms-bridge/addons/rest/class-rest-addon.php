<?php

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * REST API Addon class.
 */
class Rest_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'REST API';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'rest';
}

Rest_Addon::setup();
