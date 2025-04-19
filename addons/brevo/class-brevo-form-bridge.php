<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Brevo_Form_Bridge extends Rest_Form_Bridge
{
    protected $api = 'brevo';

    /**
     * Handles a custom http origin token to be unseted from headers
     * before submits.
     *
     * @var array<string>
     */
    public const api_headers = ['Accept', 'Content-Type', 'Api-Key'];

    /**
     * Gets bridge's default body encoding schema.
     *
     * @return string|null
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        add_filter(
            'http_request_args',
            '\FORMS_BRIDGE\Brevo_Form_Bridge::filter_headers',
            10,
            1
        );

        $response = parent::do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            $error_response = $response->get_error_data()['response'];
            if (
                $error_response['response']['code'] !== 425 &&
                $error_response['response']['code'] !== 400
            ) {
                return $response;
            }

            $data = json_decode($error_response['body'], true);
            if ($data['code'] !== 'duplicate_parameter') {
                return $response;
            }

            if (
                !isset($payload['email']) ||
                strstr($this->endpoint, '/v3/contacts') === false
            ) {
                return $response;
            }

            $update_response = $this->patch([
                'name' => 'brevo-update-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}?identifierType=email_id",
                'method' => 'PUT',
            ])->submit($payload);

            if (is_wp_error($update_response)) {
                return $update_response;
            }

            return $this->patch([
                'name' => 'brevo-search-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}",
                'method' => 'GET',
            ])->submit(['identifierType' => 'email_id']);
        }

        return $response;
    }

    protected function api_schema()
    {
        if (strstr($this->endpoint, 'contacts')) {
            $response = $this->patch([
                'name' => 'brevo-contacts-attributes',
                'endpoint' => '/v3/contacts/attributes',
                'method' => 'GET',
            ])->submit([]);

            if (is_wp_error($response)) {
                return [];
            }

            if ($this->endpoint === '/v3/contacts/doubleOptinConfirmation') {
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
            preg_match('/\/([a-z]+)$/', $this->endpoint, $matches);
            $module = $matches[1];
            $response = $this->patch([
                'name' => "brevo-{$module}-attributes",
                'endpoint' => "/v3/crm/attributes/{$module}",
                'method' => 'GET',
            ])->submit([]);

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

    /**
     * Filter and decoration of default http headers.
     *
     * @param array $args HTTP request args.
     *
     * @return array $args Filtered args.
     */
    public static function filter_headers($args)
    {
        $api_headers = [];
        foreach ($args['headers'] as $name => $value) {
            if (in_array($name, self::api_headers)) {
                $api_headers[strtolower($name)] = $value;
            }
        }

        $args['headers'] = $api_headers;

        remove_filter(
            'http_request_args',
            '\FORMS_BRIDGE\Brevo_Form_Bridge::filter_headers',
            10,
            1
        );

        return $args;
    }
}
