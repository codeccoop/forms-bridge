<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Google Sheets service.
 */
class Google_Sheets_Form_Bridge extends Form_Bridge
{
    /**
     * Handles the form bridge's template class.
     *
     * @var string
     */
    protected static $template_class = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge_Template';

    /**
     * Performs a gRPC request to the Google Sheets API.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function do_submit($payload, $attachments = [])
    {
        $payload = self::flatten_payload($payload);

        return Google_Sheets_Service::write_row(
            $this->spreadsheet,
            $this->tab,
            $payload
        );
    }

    /**
     * Sheets are flat, if payload has nested arrays, flattens it and concatenate its keys
     * as field names.
     *
     * @param array $payload Submission payload.
     * @param string $path Prefix to prepend to the field name.
     *
     * @return array Flattened payload.
     */
    private static function flatten_payload($payload, $path = '')
    {
        $flat = [];
        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                $is_flat =
                    wp_is_numeric_array($value) &&
                    count(
                        array_filter($value, static function ($d) {
                            return !is_array($d);
                        })
                    ) === count($value);
                if ($is_flat) {
                    $flat[$path . $field] = implode(',', $value);
                } else {
                    $flat = array_merge(
                        $flat,
                        self::flatten_payload($value, $path . $field . '.')
                    );
                }
            } else {
                $flat[$path . $field] = $value;
            }
        }

        return $flat;
    }
}
