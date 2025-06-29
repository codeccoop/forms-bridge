<?php

namespace FORMS_BRIDGE;

use FBAPI;

if (!defined('ABSPATH')) {
    exit();
}

trait Form_Bridge_Custom_Fields
{
    private static function get_tags()
    {
        return [
            'site_title' => static function () {
                return get_bloginfo('name');
            },
            'site_description' => static function () {
                return get_bloginfo('description');
            },
            'blog_url' => static function () {
                return get_bloginfo('wpurl');
            },
            'site_url' => static function () {
                return get_bloginfo('url');
            },
            'admin_email' => static function () {
                return get_bloginfo('admin_email');
            },
            'wp_version' => static function () {
                return get_bloginfo('version');
            },
            'ip_address' => static function () {
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return sanitize_text_field(
                        $_SERVER['HTTP_X_FORWARDED_FOR']
                    );
                } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                    return sanitize_text_field($_SERVER['REMOTE_ADDR']);
                }
            },
            'referer' => static function () {
                if (isset($_SERVER['HTTP_REFERER'])) {
                    return sanitize_text_field($_SERVER['HTTP_REFERER']);
                }
            },
            'user_agent' => static function () {
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    return sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
                }
            },
            'browser_locale' => static function () {
                if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    return sanitize_text_field(
                        $_SERVER['HTTP_ACCEPT_LANGUAGE']
                    );
                }
            },
            'locale' => static function () {
                return get_locale();
            },
            'language' => static function () {
                include_once ABSPATH .
                    'wp-admin/includes/translation-install.php';
                $translations = wp_get_available_translations();
                $locale = get_locale();
                return $translations[$locale]['native_name'] ?? $locale;
            },
            'datetime' => static function () {
                return date('Y-m-d H:i:s', time());
            },
            'gmt_datetime' => static function () {
                return gmdate('Y-m-d H:i:s', time());
            },
            'timestamp' => static function () {
                return time();
            },
            'iso_date' => static function () {
                return date('c', time());
            },
            'gmt_iso_date' => static function () {
                return gmdate('c', time());
            },
            'user_id' => static function () {
                $user = wp_get_current_user();
                return $user->ID;
            },
            'user_login' => static function () {
                $user = wp_get_current_user();
                return $user->user_login;
            },
            'user_name' => static function () {
                $user = wp_get_current_user();
                return $user->display_name;
            },
            'user_email' => static function () {
                $user = wp_get_current_user();
                return $user->user_email;
            },
            'submission_id' => static function () {
                return FBAPI::get_submission_id();
            },
            'form_title' => static function () {
                $form = FBAPI::get_current_form();
                return $form['title'] ?? null;
            },
            'form_id' => static function () {
                $form = FBAPI::get_current_form();
                return $form['id'] ?? null;
            },
        ];
    }

    final public function add_custom_fields($payload = [])
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $finger = new JSON_Finger($payload);

        $custom_fields = $this->custom_fields ?: [];

        foreach ($custom_fields as $custom_field) {
            $is_value = JSON_Finger::validate($custom_field['name']);
            if (!$is_value) {
                continue;
            }

            $value = $this->replace_field_tags($custom_field['value']);
            $finger->set($custom_field['name'], $value);
        }

        return $finger->data();
    }

    private function replace_field_tags($value)
    {
        $tags = self::get_tags();
        foreach ($tags as $tag => $getter) {
            if (strstr($value, '$' . $tag) !== false) {
                $value = str_replace('$' . $tag, $getter(), $value);
            }
        }

        return $value;
    }
}
