<?php

namespace App\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Description of Validator
 *
 * @author asok1
 */
class Validator {
    
    protected $errors;
    
    public function validate($request, array $rules) {
        foreach ($rules as $field => $rule) {
            try{
                $rule->setName(ucfirst($field))->assert($request->getParam($field));
            } catch (NestedValidationException $e) {
                foreach ($e->getMessages() as $message) {
                    $this->errors[] = $message;
                }
            }
        }
        return $this;
    }
    
    public function failed() {
        return !empty($this->errors);
    }
    
    public function errors() {
        return $this->errors;
    }
}
