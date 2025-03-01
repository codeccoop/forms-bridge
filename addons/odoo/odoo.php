<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-odoo-db.php';
require_once 'class-odoo-form-bridge.php';
require_once 'class-odoo-form-bridge-template.php';

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
        add_filter('forms_bridge_odoo_dbs', static function ($dbs) {
            if (!wp_is_numeric_array($dbs)) {
                $dbs = [];
            }

            return array_merge($dbs, self::databases());
        });

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
        return array_map(static function ($db_data) {
            return new Odoo_DB($db_data);
        }, self::setting()->databases);
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
            [
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
                            'name' => ['type' => 'string'],
                            'database' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                            'mappers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'from' => ['type' => 'string'],
                                        'to' => ['type' => 'string'],
                                        'cast' => [
                                            'type' => 'string',
                                            'enum' => [
                                                'boolean',
                                                'string',
                                                'integer',
                                                'float',
                                                'json',
                                                'null',
                                            ],
                                        ],
                                    ],
                                    'required' => ['from', 'to', 'cast'],
                                ],
                            ],
                        ],
                        'required' => [
                            'name',
                            'database',
                            'form_id',
                            'model',
                            'mappers',
                        ],
                    ],
                ],
            ],
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
        $data['databases'] = self::validate_databases($data['databases']);
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            $data['databases']
        );

        return $data;
    }

    /**
     * Database setting field validation.
     *
     * @param array $dbs Databases data.
     *
     * @return array Validated databases data.
     */
    private static function validate_databases($dbs)
    {
        if (!wp_is_numeric_array($dbs)) {
            return [];
        }

        $backends = array_map(
            function ($backend) {
                return $backend['name'];
            },
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: []
        );

        return array_filter($dbs, function ($db_data) use ($backends) {
            return in_array($db_data['backend'] ?? null, $backends);
        });
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with the
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $dbs Array with databases data.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges, $dbs)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $templates = array_map(function ($template) {
            return $template['name'];
        }, apply_filters('forms_bridge_templates', [], 'odoo'));

        $valid_bridges = [];
        for ($i = 0; $i < count($bridges); $i++) {
            $bridge = $bridges[$i];

            // Valid only if database and form id exists
            $is_valid =
                array_reduce(
                    $dbs,
                    static function ($is_valid, $db) use ($bridge) {
                        return $bridge['database'] === $db['name'] || $is_valid;
                    },
                    false
                ) &&
                in_array($bridge['form_id'], $_ids) &&
                (empty($bridge['template']) ||
                    empty($templates) ||
                    in_array($bridge['template'], $templates));

            if ($is_valid) {
                $bridge['mappers'] = array_values(
                    array_filter($bridge['mappers'], function ($pipe) {
                        return !(
                            empty($pipe['from']) ||
                            empty($pipe['to']) ||
                            empty($pipe['cast'])
                        );
                    })
                );

                $valid_bridges[] = $bridge;
            }
        }

        return $valid_bridges;
    }
}

Odoo_Addon::setup();
