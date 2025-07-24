<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * Odoo Addon class.
 */
class Odoo_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Odoo';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'odoo';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Odoo_Form_Bridge';

    public const credential_class = '\FORMS_BRIDGE\RPC_Credential';

    protected static function defaults()
    {
        $defaults = parent::defaults();
        $defaults['credentials'] = [];
        return $defaults;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Target backend name.
     *
     * @return boolean
     */
    public function ping($backend)
    {
        $bridge = new Odoo_Form_Bridge(
            [
                'name' => '__odoo-' . time(),
                'method' => 'search',
                'endpoint' => 'res.users',
                'backend' => $backend,
            ],
            self::name
        );

        $response = $bridge->submit();
        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend model and retrive the response data.
     *
     * @param string $endpoint Target model name.
     * @param string $backend Target backend name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend)
    {
        $bridge = new Odoo_Form_Bridge(
            [
                'name' => '__odoo-' . time(),
                'method' => 'search_read',
                'endpoint' => $endpoint,
                'backend' => $backend,
            ],
            self::name
        );

        return $bridge->submit([], ['id', 'name']);
    }

    /**
     * Performs an introspection of the backend model and returns API fields
     * and accepted content type.
     *
     * @param string $model Target model name.
     * @param string $backend Target backend name.
     *
     * @return array List of fields and content type of the model.
     */
    public function get_endpoint_schema($model, $backend)
    {
        $bridge = new Odoo_Form_Bridge(
            [
                'name' => '__odoo-' . time(),
                'method' => 'fields_get',
                'endpoint' => $model,
                'backend' => $backend,
            ],
            self::name
        );

        $response = $bridge->submit();

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['result'] as $name => $spec) {
            if ($spec['readonly']) {
                continue;
            }

            if ($spec['type'] === 'char' || $spec['type'] === 'html') {
                $schema = ['type' => 'string'];
            } elseif ($spec['type'] === 'float') {
                $schema = ['type' => 'number'];
            } elseif (
                in_array(
                    $spec['type'],
                    ['one2many', 'many2one', 'many2many'],
                    true
                )
            ) {
                $schema = [
                    'type' => 'array',
                    'items' => [['type' => 'integer'], ['type' => 'string']],
                    'additionalItems' => false,
                ];
            } else {
                $schema = ['type' => $spec['type']];
            }

            $schema['required'] = $spec['required'];

            $fields[] = [
                'name' => $name,
                'schema' => $schema,
            ];
        }

        return $fields;
    }
}

Odoo_Addon::setup();
