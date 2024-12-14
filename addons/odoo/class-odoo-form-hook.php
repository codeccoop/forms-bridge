<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_Form_Hook extends Form_Hook
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->api = 'odoo';
        $this->data['backend'] = $this->database()->backend->name;
        $this->data['endpoint'] = $this->endpoint();
        $this->data['method'] = 'POST';

        add_filter(
            'forms_bridge_hook_database',
            function ($db, $hook) {
                return $this->db_interceptor($db, $hook);
            },
            9,
            2
        );

        add_filter(
            'forms_bridge_hook_content_type',
            function ($content_type, $hook) {
                return $this->content_type_interceptor($content_type, $hook);
            },
            9,
            2
        );
    }

    private function db_interceptor($db, $hook)
    {
        if ($hook->name !== $this->name) {
            return $db;
        } else {
            return $this->database();
        }
    }

    private function content_type_interceptor($content_type, $hook)
    {
        if ($hook->name !== $this->name) {
            return $content_type;
        } else {
            return 'application/json';
        }
    }

    private function database()
    {
        $dbs = Settings::get_setting('forms-bridge', 'odoo-api')->databases;
        foreach ($dbs as $db) {
            if ($db['name'] === $this->data['database']) {
                return new Odoo_DB($db);
            }
        }
    }

    private function endpoint()
    {
        $base_url = $this->database()->backend->base_url;
        if (preg_match('/\/jsonrpc\/?$/', $base_url)) {
            return '';
        } else {
            return '/jsonrpc';
        }
    }
}
