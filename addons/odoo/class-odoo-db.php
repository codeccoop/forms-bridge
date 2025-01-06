<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_DB
{
    private $data = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'backend':
                return $this->backend();
            default:
                return isset($this->data[$name]) ? $this->data[$name] : null;
        }
    }

    private function backend()
    {
        return apply_filters(
            'http_bridge_backend',
            null,
            $this->data['backend']
        );
    }
}
