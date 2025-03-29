<?php namespace moodle_dev_utils\http\filters\exceptions\context;

class filter_context{
    public string $field;
    public string $operator;
    public string $choices;
    public string $accepts;

    public function __construct(string $field, string $operator = '', array|string $accepts = '', array|string $choices = ''){
        $this->field = $field;
        $this->operator = $operator;
        $this->set_accepted_choices($choices);
        $this->set_accepted_operators($accepts);
    }

    public function set_accepted_operators(array|string $operators) : static {
        $this->accepts = is_array($operators) ? implode(',', $operators) : $operators;
        return $this;
    }
    
    public function set_accepted_choices(array|string $choices) : static {
        $this->choices = is_array($choices) ? implode(',', $choices) : $choices;
        return $this;
    }
}
