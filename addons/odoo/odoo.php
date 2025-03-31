<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-bridge.php';
require_once 'class-odoo-form-bridge-template.php';

require_once 'api-functions.php';

/**
 * Odoo Addon class.
 */
class Odoo_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Odoo JSON-RPC';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'odoo';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Odoo_Form_Bridge';

    /**
     * Handles the addon's custom bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Odoo_Form_Bridge_Template';

    /**
     * Addon constructor. Inherits from the abstract addon and initialize interceptos
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);
        self::custom_hooks();
    }

    /**
     * Addon custom hooks.
     */
    private static function custom_hooks()
    {
        add_filter(
            'forms_bridge_odoo_dbs',
            static function ($dbs) {
                if (!wp_is_numeric_array($dbs)) {
                    $dbs = [];
                }

                return array_merge($dbs, self::databases());
            },
            10,
            1
        );

        add_filter(
            'forms_bridge_odoo_db',
            static function ($db, $name) {
                if ($db instanceof Odoo_DB) {
                    return $db;
                }

                $dbs = self::databases();
                foreach ($dbs as $db) {
                    if ($db->name === $name) {
                        return $db;
                    }
                }
            },
            10,
            2
        );
    }

    /**
     * Addon databases instances getter.
     *
     * @return array List with available databases instances.
     */
    private static function databases()
    {
        return array_map(
            static function ($data) {
                return new Odoo_DB($data);
            },
            self::setting()->databases ?: []
        );
    }

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            self::merge_setting_config([
                'databases' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'user' => ['type' => 'string'],
                            'password' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'user', 'password', 'backend'],
                    ],
                ],
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'database' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                        ],
                        'required' => ['database', 'model'],
                    ],
                ],
            ]),
            [
                'databases' => [],
                'bridges' => [],
            ],
        ];
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        $backends =
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: [];
        $data['databases'] = self::validate_databases(
            $data['databases'],
            $backends
        );
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['databases']
        );

        return $data;
    }

    /**
     * Database setting field validation. Filters inconsistent databases
     * based on the Http_Bridge's backends store state.
     *
     * @param array $databases Databases data.
     * @param array $backends Backends data.
     *
     * @return array Validated databases data.
     */
    private static function validate_databases($databases, $backends)
    {
        if (!wp_is_numeric_array($databases)) {
            return [];
        }

        $backends = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $uniques = [];
        $validated = [];
        foreach ($databases as $database) {
            if (empty($database['name'])) {
                continue;
            }

            if (in_array($database['name'], $uniques)) {
                continue;
            } else {
                $uniques[] = $database['name'];
            }

            if (!in_array($database['backend'] ?? null, $backends)) {
                $database['backend'] = '';
            }

            $database['user'] = $database['user'] ?? '';
            $database['password'] = $database['password'] ?? '';

            $validated[] = $database;
        }

        return $validated;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with the
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $databases Array with databases data.
     *
     * @return array Validated bridge configurations.
     */
    private static function validate_bridges($bridges, $databases)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $db_names = array_map(function ($database) {
            return $database['name'];
        }, $databases);

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::validate_bridge($bridge, $uniques);

            if (!$bridge) {
                continue;
            }

            if (!in_array($bridge['database'], $db_names)) {
                $bridge['database'] = '';
            }

            $bridge['model'] = $bridge['model'] ?? '';

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['database']) &&
                !empty($bridge['model']);

            $validated[] = $bridge;
        }

        return $validated;
    }
}

Odoo_Addon::setup();
