<?php

class HpsCheckResponse extends HpsTransaction{
    public  $authorizationCode  = null,
            $customerId         = null,
            $details            = null;

    public static function fromDict($rsp,$txnType,$returnType = 'HpsCheckResponse'){
        $response = $rsp->Transaction->$txnType;

        $sale = parent::fromDict($rsp,$txnType,$returnType);
        $sale->responseCode = (isset($response->RspCode) ? (string)$response->RspCode : null);
        $sale->responseText = (isset($response->RspMessage) ? (string)$response->RspMessage : null);
        $sale->authorizationCode = (isset($response->AuthCode) ? (string)$response->AuthCode : null);

        if($response->CheckRspInfo){
            $sale->details = array();

            if(count($response->CheckRspInfo)>1){
                foreach ($response->CheckRspInfo as $key=>$details) {
                    $sale->details[] = self::_hydrateRspDetails($details);
                }
            }else{
                $sale->details = self::_hydrateRspDetails($response->CheckRspInfo);
            }
        }

        return $sale;
    }

    private static function _hydrateRspDetails($checkInfo){
        $details = new HpsCheckResponseDetails();
        $details->messageType = (isset($checkInfo->Type) ? (string)$checkInfo->Type : null);
        $details->code = (isset($checkInfo->Code) ? (string)$checkInfo->Code : null);
        $details->message = (isset($checkInfo->Message) ? (string)$checkInfo->Message : null);
        $details->fieldNumber = (isset($checkInfo->FieldNumber) ? (string)$checkInfo->FieldNumber : null);
        $details->fieldName = (isset($checkInfo->FieldName) ? (string)$checkInfo->FieldName : null);
        return $details;
    }
} 