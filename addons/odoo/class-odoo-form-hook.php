<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_Form_Hook extends Form_Hook
{
    public function __get($name)
    {
        switch ($name) {
            case 'api':
                return 'odoo';
            case 'method':
                return 'POST';
            case 'backend':
                return $this->backend();
            case 'database':
                return $this->database();
            default:
                return parent::__get($name);
        }
    }

    protected function backend()
    {
        return $this->database()->backend;
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
}
