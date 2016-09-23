<?php
class HpsException extends Exception{
    public $innerException = null;
    public $code = null;

    public function __construct($message, $code = null,$innerException = null){
        $this->message = $message;
        if ( $code != null ) {
            $this->code = $code;
        }
        if ( $code != null ) {
            $this->innerException = $innerException;
        }
    }
}
