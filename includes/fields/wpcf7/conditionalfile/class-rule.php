<?php

class WCPT_WPCF7_ConditionalFile_Rule extends WPCF7_SWV_Rule
{
    const rule_name = 'conditionalfile';

    public function validate($context)
    {
        $field = $this->get_property('field');
        if (!isset($_FILES[$field])) return true;

        $rule_class = $this->get_property('rule');
        $props = $this->to_array();
        $rule = new $rule_class($props);

        return $rule->validate($context);
    }
}
