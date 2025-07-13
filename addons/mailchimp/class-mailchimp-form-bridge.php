<?php

namespace FORMS_BRIDGE;

use TypeError;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Mailchimp API.
 */
class Mailchimp_Form_Bridge extends Form_Bridge
{
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
     * @return array|WP_Error
     */
    public function submit($payload = [], $attachments = [])
    {
        $response = parent::submit($payload, $attachments);

        if (is_wp_error($response)) {
            $error_response = $response->get_error_data()['response'] ?? null;

            $code = $error_response['response']['code'] ?? null;
            if ($code !== 400) {
                return $response;
            }

            try {
                $body = json_decode($error_response['body'] ?? '', true);
                $title = $body['title'] ?? null;
            } catch (TypeError) {
                return $response;
            }

            if ($title === 'Member Exists') {
                if (
                    !preg_match(
                        '/(?<=lists\/).+(?=\/members)/',
                        $this->endpoint,
                        $matches
                    )
                ) {
                    return $response;
                }

                $list_id = $matches[0];

                $search_response = $this->patch([
                    'name' => 'mailchimp-search-member',
                    'method' => 'GET',
                    'endpoint' => '/3.0/search-members',
                ])->submit([
                    'fiels' => 'exact_matches.members.id',
                    'list_id' => $list_id,
                    'query' => $payload['email_address'],
                ]);

                if (is_wp_error($search_response)) {
                    return $response;
                }

                $member_id =
                    $search_response['data']['exact_matches']['members'][0][
                        'id'
                    ] ?? null;

                if (!$member_id) {
                    return $response;
                }

                $update_endpoint = "/3.0/lists/{$list_id}/members/{$member_id}";
                if (
                    strstr($this->endpoint, 'skip_merge_validation') !== false
                ) {
                    $update_endpoint .= '?skip_merge_validation=true';
                }

                $update_response = $this->patch([
                    'name' => 'mailchimp-update-subscription',
                    'method' => 'PUT',
                    'endpoint' => $update_endpoint,
                ])->submit($payload);

                if (is_wp_error($update_response)) {
                    return $response;
                }

                return $update_response;
            }
        }

        return $response;
    }
}
