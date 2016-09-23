<?php

class HpsVoid extends HpsTransaction{

    static public function fromDict($rsp,$txnType,$returnType = 'HpsVoid'){
        $void = parent::fromDict($rsp,$txnType,$returnType);
        $void->responseCode = '00';
        $void->responseText = '';
        return $void;
    }
} 