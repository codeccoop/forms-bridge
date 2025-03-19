<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Zoho addon credential
 */
class Zoho_Credential
{
    /**
     * Handles credentials data.
     *
     * @var array|null
     */
    private $data = null;

    /**
     * Class constructor. Binds setting data to the instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Proxies class attributes to the API key settings data.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'backend':
                return $this->backend();
            default:
                return $this->data[$name] ?? null;
        }
    }

    /**
     * API key's backend instance getter.
     *
     * @return Http_Backend Http Backend instance.
     */
    private function backend()
    {
        $backend_name = $this->data['backend'] ?? null;
        if (empty($backend_name)) {
            return;
        }

        return apply_filters('http_bridge_backend', null, $backend_name);
    }
}
