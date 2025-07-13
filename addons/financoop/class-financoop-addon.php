<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-financoop-form-bridge.php';
require_once 'hooks.php';
require_once 'shortcodes.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'FinanCoop';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'financoop';

    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Finan_Coop_Form_Bridge';

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
        $bridge = new Finan_Coop_Form_Bridge(
            [
                'name' => '__financoop-' . time(),
                'endpoint' => '/api/campaign',
                'method' => 'GET',
                'backend' => $backend,
            ],
            'financoop'
        );

        $response = $bridge->submit();
        return !is_wp_error($response);
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint API endpoint.
     * @param string $backend Backend name.
     * @params null $credential Credential name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        $bridge = new Finan_Coop_Form_Bridge(
            [
                'name' => '__financoop-' . time(),
                'endpoint' => $endpoint,
                'backend' => $backend,
                'method' => 'GET',
            ],
            'financoop'
        );

        return $bridge->submit();
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
        $bridge = new Finan_Coop_Form_Bridge(
            [
                'name' => '__financoop-' . time(),
                'endpoint' => $endpoint,
                'backend' => $backend,
                'method' => 'GET',
            ],
            'financoop'
        );

        if (
            !preg_match(
                '/\/api\/campaign\/\d+\/([a-z_]+)$/',
                $bridge->endpoint,
                $matches
            )
        ) {
            return [];
        }

        $source = $matches[1];

        $common_schema = [
            [
                'name' => 'vat',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'firstname',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'lastname',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'email',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'address',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'zip_code',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'phone',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'lang',
                'schema' => ['type' => 'string'],
            ],
            [
                'name' => 'country_code',
                'schema' => ['type' => 'string'],
            ],
        ];

        switch ($source) {
            case 'subscription_request':
                return array_merge(
                    [
                        [
                            'name' => 'ordered_parts',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'type',
                            'schema' => ['type' => 'string'],
                        ],
                    ],
                    $common_schema
                );
                break;
            case 'donation_request':
                return array_merge(
                    [
                        [
                            'name' => 'donation_amount',
                            'schema' => ['type' => 'integer'],
                        ],
                        // [
                        //     'name' => 'tax_receipt_option',
                        //     'schema' => ['type' => 'string'],
                        // ],
                    ],
                    $common_schema
                );
                break;
            case 'loan_request':
                return array_merge(
                    [
                        [
                            'name' => 'loan_amount',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    $common_schema
                );
                break;
        }
    }
}

Finan_Coop_Addon::setup();
