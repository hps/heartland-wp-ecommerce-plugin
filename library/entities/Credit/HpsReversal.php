<?php

class HpsReversal extends HpsTransaction{
    public  $avsResultCode  = null,
            $avsResultText  = null,
            $cvvResultCode  = null,
            $cvvResultText  = null,
            $cpcIndicator   = null;

    public static function fromDict($rsp,$txnType,$returnType = 'HpsReversal'){
        $reverseResponse = $rsp->Transaction->$txnType;

        $reverse = parent::fromDict($rsp,$txnType,$returnType);
        $reverse->avsResultCode = (isset($reverseResponse->AVSRsltCode) ? (string)$reverseResponse->AVSRsltCode : null);
        $reverse->avsResultText = (isset($reverseResponse->AVSRsltText) ? (string)$reverseResponse->AVSRsltText : null);
        $reverse->ccpIndicator = (isset($reverseResponse->CPCInd) ? (string)$reverseResponse->CPCInd : null);
        $reverse->cvvResultCode = (isset($reverseResponse->CVVRsltCode) ? (string)$reverseResponse->CVVRsltCode : null);
        $reverse->cvvResultText = (isset($reverseResponse->CVVRsltText) ? (string)$reverseResponse->CVVRsltText : null);

        return $reverse;
    }
} 