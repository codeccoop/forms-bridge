<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * REST API Addon class.
 */
class Rest_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'REST API';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'rest';
}

Rest_Addon::setup();
