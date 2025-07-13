<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-brevo-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * REST API Addon class.
 */
class Brevo_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Brevo';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'brevo';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Brevo_Form_Bridge';

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Backend name.
     * @param null $credential Credential name.
     *
     * @return boolean
     */
    public function ping($backend, $credential = null)
    {
        $bridge = new Brevo_Form_Bridge(
            [
                'name' => '__brevo-' . time(),
                'endpoint' => '/v3/contacts/lists',
                'method' => 'GET',
                'backend' => $backend,
            ],
            'brevo'
        );

        $response = $bridge->submit(['limit' => 1]);
        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @param null $credential Credential name.
     *
     * @return array Fetched records.
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        $bridge = new Brevo_Form_Bridge(
            [
                'name' => '__brevo-' . time(),
                'endpoint' => $endpoint,
                'backend' => $backend,
                'method' => 'GET',
            ],
            'brevo'
        );

        $response = $bridge->submit();
        if (is_wp_error($response)) {
            return [];
        }

        return $response['data'];
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @params null $credential Credential name.
     *
     * @return array
     */
    public function get_endpoint_schema($endpoint, $backend, $credential = null)
    {
        $bridge = new Brevo_Form_Bridge(
            [
                'name' => '__brevo-' . time(),
                'endpoint' => $endpoint,
                'backend' => $backend,
                'method' => 'GET',
            ],
            'brevo'
        );

        if (strstr($bridge->endpoint, 'contacts')) {
            $response = $bridge
                ->patch([
                    'name' => 'brevo-contacts-attributes',
                    'endpoint' => '/v3/contacts/attributes',
                    'method' => 'GET',
                ])
                ->submit();

            if (is_wp_error($response)) {
                return [];
            }

            if ($bridge->endpoint === '/v3/contacts/doubleOptinConfirmation') {
                $fields = [
                    [
                        'name' => 'email',
                        'schema' => ['type' => 'string'],
                        'required' => true,
                    ],
                    [
                        'name' => 'includeListIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                        'required' => true,
                    ],
                    [
                        'name' => 'excludeListIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'templateId',
                        'schema' => ['type' => 'integer'],
                        'required' => true,
                    ],
                    [
                        'name' => 'redirectionUrl',
                        'schema' => ['type' => 'string'],
                        'required' => true,
                    ],
                    [
                        'name' => 'attributes',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ];
            } else {
                $fields = [
                    [
                        'name' => 'email',
                        'schema' => ['type' => 'string'],
                        'required' => true,
                    ],
                    [
                        'name' => 'ext_id',
                        'schema' => ['type' => 'string'],
                    ],
                    [
                        'name' => 'emailBlacklisted',
                        'schema' => ['type' => 'boolean'],
                    ],
                    [
                        'name' => 'smsBlacklisted',
                        'schema' => ['type' => 'boolean'],
                    ],
                    [
                        'name' => 'listIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'updateEnabled',
                        'schema' => ['type' => 'boolean'],
                    ],
                    [
                        'name' => 'smtpBlacklistSender',
                        'schema' => ['type' => 'boolean'],
                    ],
                    [
                        'name' => 'attributes',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ];
            }

            foreach ($response['data']['attributes'] as $attribute) {
                $fields[] = [
                    'name' => 'attributes.' . $attribute['name'],
                    'schema' => ['type' => 'string'],
                ];
            }

            return $fields;
        } else {
            if (preg_match('/\/([a-z]+)$/', $bridge->endpoint, $matches)) {
                return [];
            }

            $module = $matches[1];
            $response = $bridge
                ->patch([
                    'name' => "brevo-{$module}-attributes",
                    'endpoint' => "/v3/crm/attributes/{$module}",
                    'method' => 'GET',
                ])
                ->submit();

            if (is_wp_error($response)) {
                return [];
            }

            if ($module === 'companies') {
                $fields = [
                    [
                        'name' => 'name',
                        'schema' => ['type' => 'string'],
                        'required' => true,
                    ],
                    [
                        'name' => 'countryCode',
                        'schema' => ['type' => 'integer'],
                    ],
                    [
                        'name' => 'linkedContactsIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'linkedDealsIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'attributes',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ];
            } elseif ($module === 'deals') {
                $fields = [
                    [
                        'name' => 'name',
                        'schema' => ['type' => 'string'],
                        'required' => true,
                    ],
                    [
                        'name' => 'linkedDealsIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'linkedCompaniesIds',
                        'schema' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'name' => 'attributes',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ];
            }

            foreach ($response['data'] as $attribute) {
                switch ($attribute['attributeTypeName']) {
                    case 'number':
                        $type = 'number';
                        break;
                    case 'text':
                        $type = 'string';
                        break;
                    case 'user':
                        $type = 'email';
                        break;
                    case 'date':
                        $type = 'date';
                        break;
                    default:
                        $type = 'string';
                }

                $fields[] = [
                    'name' => 'attributes.' . $attribute['internalName'],
                    'schema' => ['type' => $type],
                ];
            }

            return $fields;
        }
    }
}

Brevo_Addon::setup();
