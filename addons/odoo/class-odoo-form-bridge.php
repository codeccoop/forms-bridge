<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Odoo JSON-RPC api.
 */
class Odoo_Form_Bridge extends Form_Bridge
{
    /**
     * Handles the bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Odoo_Form_Bridge_Template';

    /**
     * Inherits the parent constructor and sets data constants.
     *
     * @param array $data Bridge data.
     * @param string $api Bridge API name.
     */
    public function __construct($data, $api)
    {
        parent::__construct(
            array_merge($data, [
                'endpoint' => '/jsonrpc',
                'method' => 'POST',
            ]),
            $api
        );
    }

    /**
     * Parent getter interceptor ti short circtuit database access.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        if ($name === 'database') {
            return $this->database();
        }

        return parent::__get($name);
    }

    /**
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Intercepts backend access and returns it from the database.
     *
     * @return Http_Backend|null
     */
    protected function backend()
    {
        return $this->database()->backend;
    }

    /**
     * Bridge's database private getter.
     *
     * @return Odoo_DB|null
     */
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
