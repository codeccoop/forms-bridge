<?php

namespace WPCT_ERP_FORMS\WPCF7;

use WPCT_ERP_FORMS\Integration as BaseIntegration;
use WPCT_ERP_FORMS\WPCF7\Fields\Iban\Field as IbanField;

// Fields
require_once dirname(__FILE__, 3) . '/fields/wpcf7/iban/class-field.php';

class Integration extends BaseIntegration
{
    public static $fields = [
        IbanField::class
    ];

    protected function __construct()
    {
        parent::__construct();

        add_filter('wpcf7_before_send_mail', function ($form, &$abort, $submission) {
            $this->do_submission($submission, $form);
        }, 10, 3);
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
}
