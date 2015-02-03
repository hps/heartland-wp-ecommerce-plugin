<?php

class HpsCPCEdit extends  HpsTransaction{
    static public function fromDict($rsp,$txnType,$returnType = 'HpsCPCEdit'){
        $cpcEdit = parent::fromDict($rsp,$txnType,$returnType);
        $cpcEdit->responseCode = '00';
        $cpcEdit->responseText = '';
        return $cpcEdit;
    }
} 