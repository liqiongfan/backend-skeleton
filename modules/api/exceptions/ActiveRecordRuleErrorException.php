<?php

namespace app\modules\api\exceptions;

/**
 * Class RuleErrorException
 * ActiveRecord 规则错误
 *
 * @author hiscaler <hiscaler@gmail.com>
 */
class ActiveRecordRuleErrorException extends UserException
{

    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        if (is_array($message)) {
            $message = array_values($message);
            $message = $message ? $message[0] : var_export($message);
        }
        parent::__construct(701, $message, $code, $previous);
    }

}
