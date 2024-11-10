<?php

/**
 * Filter public attachments path and return privte if needed.
 *
 * @since 1.0.0
 *
 * @param array $path_info Attachments path info.
 * @param integer $form_id Source form ID.
 * @return array $path_info Attachments path info.
 */
function wpct_erp_forms_upload_path($path_info, $form_id)
{
    $private_upload = wpct_erp_forms_private_upload($form_id);
    if (!$private_upload) {
        return $path_info;
    }

    $base_path = wpct_erp_forms_attachment_base_path();
    $path =
        $base_path . '/' . implode('/', [$form_id, date('Y'), date('m')]) . '/';

    if (!is_dir($path)) {
        mkdir($path, 0700, true);
    }

    $htaccess = $base_path . '/.htaccess';
    if (!is_file($htaccess)) {
        $fp = fopen($htaccess, 'w');
        fwrite(
            $fp,
            'order deny,allow
deny from all'
        );
        fclose($fp);
    }

    $path_info['path'] = $path;
    $path_info['url'] = wpct_erp_forms_attachment_url($path);
    return $path_info;
}
add_filter('gform_upload_path', 'wpct_erp_forms_upload_path', 90, 2);

/**
 * Intercept GET requests with download query and send attachment file as response.
 *
 * @since 1.0.0
 */
function wpct_erp_forms_download_file()
{
    if (!isset($_GET['erp-forms-attachment'])) {
        return;
    }

    $path = wpct_erp_forms_attachment_fullpath($_GET['erp-forms-attachment']);

    if (!(is_user_logged_in() && file_exists($path))) {
        global $wp_query;
        status_header(404);
        $wp_query->set_404();
        $template_path = get_404_template();
        if (file_exists($template_path)) {
            require_once $template_path;
        }
        die();
    }

    $filetype = wp_check_filetype($path);
    if (!$filetype['type']) {
        $filetype['type'] = mime_content_type($path);
    }

    nocache_headers();
    header('X-Robots-Tag: noindex', true);
    header('Content-Type: ' . $filetype['type']);
    header('Content-Description: File Transfer');
    header(
        'Content-Disposition: inline; filename="' . wp_basename($path) . '"'
    );
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($path));

    if (ob_get_contents()) {
        ob_end_clean();
    }

    readfile($path);
    die();
}
add_action('init', 'wpct_erp_forms_download_file');

/**
 * Get gravityforms attachment store base path.
 *
 * @since 1.0.0
 *
 * @return string $base_path Attachments store base path.
 */
function wpct_erp_forms_attachment_base_path()
{
    $upload_dir = wp_upload_dir();
    $base_path = apply_filters(
        'wpct_erp_forms_upload_path',
        $upload_dir['basedir'] . '/erp-forms'
    );
    if (!($base_path && is_string($base_path))) {
        throw new Exception('WPCT ERP Forms: Invalid upload path');
    }
    $base_path = preg_replace('/\/$/', '', $base_path);
    return $base_path;
}

/**
 * Get attachment absolute path.
 *
 * @since 1.0.0
 *
 * @param string $filepath Attachment file path.
 * @return string $fullpath Attachment file absolute path.
 */
function wpct_erp_forms_attachment_fullpath($filepath)
{
    $base_path = wpct_erp_forms_attachment_base_path();
    return $base_path . urldecode($filepath);
}

/**
 * Get attachment URL.
 *
 * @since 1.0.0
 *
 * @param string $filepath Attachment file path.
 * @return string $url Attachment public URL.
 */
function wpct_erp_forms_attachment_url($filepath)
{
    $base_path = wpct_erp_forms_attachment_base_path();
    $url = get_site_url() . '/index.php?';
    $url .=
        'erp-forms-attachment=' .
        urlencode(str_replace($base_path, '', $filepath));
    return $url;
}

/**
 * Check if gravityforms should use private attachments store.
 *
 * @since 1.0.0
 *
 * @param integer $form_id Source form ID.
 * @return boolean $is_private Form uses private store.
 */
function wpct_erp_forms_private_upload($form_id)
{
    return apply_filters('wpct_erp_forms_private_upload', true, $form_id);
}
