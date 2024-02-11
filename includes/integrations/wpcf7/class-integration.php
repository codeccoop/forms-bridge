<?php

namespace WPCT_ERP_FORMS\WPCF7;

use WPCT_ERP_FORMS\Abstract\Integration as BaseIntegration;
use WPCT_ERP_FORMS\WPCF7\Fields\Iban\Field as IbanField;
use WPCT_ERP_FORMS\WPCF7\Fields\Conditional\Field as ConditionalField;
use WPCT_ERP_FORMS\WPCF7\Fields\ConditionalFile\Field as ConditionalFileField;

// Fields
require_once dirname(__FILE__, 3) . '/fields/wpcf7/iban/class-field.php';
require_once dirname(__FILE__, 3) . '/fields/wpcf7/conditional/class-field.php';
require_once dirname(__FILE__, 3) . '/fields/wpcf7/conditionalfile/class-field.php';

class Integration extends BaseIntegration
{
    public static $fields = [
        IbanField::class,
        ConditionalField::class,
        ConditionalFileField::class,
    ];

    protected function __construct()
    {
        parent::__construct();

        add_filter('wpcf7_before_send_mail', function ($form, &$abort, $submission) {
            $this->do_submission($submission, $form);
        }, 10, 3);

        add_filter('wpcf7_form_elements', function ($tags) {
            $plugin_url = plugin_dir_url(dirname(__FILE__, 4) . '/wpct-erp-forms.php');
            $script_url = $plugin_url . 'assets/js/conditional-fields.js';
            $style_url = $plugin_url . 'assets/css/wpct7-theme.css';
            ob_start();
?>
            <script src="<?= $script_url ?>" type="module"></script>
            <link rel="stylesheet" href="<?= $style_url ?>" />
            <style>
                .wpcf7-form-control-conditional-wrap {
                    display: none
                }
            </style>
<?php
            $assets = ob_get_clean();
            return $tags . $assets;
        }, 90, 1);
    }

    public function serialize_submission($submission, $form = null)
    {
        $data = $submission->get_posted_data();
        $data['id'] = $submission->get_posted_data_hash();

        return $data;
    }

    public function serialize_form($form)
    {
        return [
            'id' => $form->id(),
            'title' => $form->title(),
            'name' => $form->name(),
            'properties' => $form->get_properties(),
            'tag' => $form->unit_tag(),
            'locale' => $form->locale(),
        ];
    }

    public function get_files($submission, $form)
    {
        $files = [];
        $uploads = $submission->uploaded_files();
        foreach ($uploads as $file_name => $paths) {
            if (sizeof($paths) > 0 && $$paths[0]) {
                $files[$file_name] = $paths[0];
            }
        };

        return $files;
    }
}
