<?php

class WCPT_WPCF7_Conditional_Rule extends Contactable\SWV\Rule
{
    const rule_name = 'conditional';

    public function validate($context)
    {
        $field = $this->get_property('field');
        if (!isset($_POST[$field])) return true;

        $rule_class = $this->get_property('rule');
        $props = $this->to_array();
        $rule = new $rule_class($props);

        return $rule->validate($context);
    }
}
