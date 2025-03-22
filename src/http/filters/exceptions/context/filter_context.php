<?php namespace moodle_dev_utils\http\filters\exceptions\context;

class filter_context{
    public string $field;
    public string $operator;
    public string $accepts;

    public function __construct(string $field, string $operator = '', array|string $accepts = ''){
        if(is_array($accepts)){
            $accepts = implode(',', $accepts);
        }

        $this->field = $field;
        $this->operator = $operator;
        $this->accepts = $accepts;
    }
}

