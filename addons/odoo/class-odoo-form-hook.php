<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_Form_Hook extends Form_Hook
{
    public function __construct($data)
    {
        $this->api = 'odoo';

        parent::__construct(
            array_merge($data, [
                'endpoint' => '/jsonrpc',
                'method' => 'POST',
            ])
        );

        add_filter(
            'forms_bridge_hook_database',
            function ($db, $hook) {
                if ($hook->name === $this->name) {
                    $db = $this->database();
                }

                return $db;
            },
            10,
            2
        );
    }

    protected function content_type()
    {
        return 'application/json';
    }

    protected function backend()
    {
        return $this->database()->backend;
    }

    private function database()
    {
        $dbs = Forms_Bridge::setting('odoo')->databases;
        foreach ($dbs as $db) {
            if ($db['name'] === $this->data['database']) {
                return new Odoo_DB($db);
            }
        }
    }
}
