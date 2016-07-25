<?php

class HpsTransaction {
    public  $transactionId          = null,
            $clientTransactionId    = null,
            $responseCode           = null,
            $responseText           = null,
            $referenceNumber        = null;

    protected $_header                = null;

    static public function fromDict($rsp,$txnType,$returnType = null){

        $transaction = new $returnType();

        // Hydrate the header
        $transaction->_header = new HpsTransactionHeader();
        $transaction->_header->gatewayResponseCode = (string)$rsp->Header->GatewayRspCode;
        $transaction->_header->gatewayResponseMessage = (string)$rsp->Header->GatewayRspMsg;
        $transaction->_header->responseDt = (string)$rsp->Header->RspDT;
        $transaction->_header->clientTxnId = (isset($rsp->Header->ClientTxnId) ? (string)$rsp->Header->ClientTxnId : null);

        $transaction->transactionId = (string)$rsp->Header->GatewayTxnId;
        if(isset($rsp->Header->ClientTxnId) && (string)$rsp->Header->ClientTxnId != ""){
            $transaction->clientTransactionId = (string)$rsp->Header->ClientTxnId;
        }

        // Hydrate the body
        $item = $rsp->Transaction->$txnType;
        if($item != null){
            $transaction->responseCode = (isset($item->RspCode) ? (string)$item->RspCode : null);
            $transaction->responseText = (isset($item->RspText) ? (string)$item->RspText : null);
            $transaction->referenceNumber = (isset($item->RefNbr) ? (string)$item->RefNbr : null);
        }

        return $transaction;
    }

    public function gatewayResponse()
    {
        return (object)array(
            'code'    => $this->_header->gatewayResponseCode,
            'message' => $this->_header->gatewayResponseMessage,
        );
    }

    static public function transactionTypeToServiceName($transactionType){
        switch ($transactionType){
            case HpsTransactionType::Authorize :
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditAuth;
                break;

            case HpsTransactionType::Capture:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditAddToBatch;
                break;

            case HpsTransactionType::Charge:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditSale;
                break;

            case HpsTransactionType::Refund:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditReturn;
                break;

            case HpsTransactionType::Reverse:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditReversal;
                break;

            case HpsTransactionType::Verify:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditAccountVerify;
                break;

            case HpsTransactionType::ListTransaction:
                return HpsItemChoiceTypePosResponseVer10Transaction::ReportActivity;
                break;

            case HpsTransactionType::Get:
                return HpsItemChoiceTypePosResponseVer10Transaction::ReportTxnDetail;
                break;

            case HpsTransactionType::Void:
                return HpsItemChoiceTypePosResponseVer10Transaction::CreditVoid;
                break;

            case HpsTransactionType::BatchClose:
                return HpsItemChoiceTypePosResponseVer10Transaction::BatchClose;
                break;

            case HpsTransactionType::SecurityError:
                return "SecurityError";
                break;

            default:
                return "";
        }
    }

    static public function serviceNameToTransactionType($serviceName){
        switch ($serviceName){
            case HpsItemChoiceTypePosResponseVer10Transaction::CreditAuth:
                return HpsTransactionType::Capture;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditAddToBatch:
                return HpsTransactionType::Capture;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditSale:
                return HpsTransactionType::Charge;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditReturn:
                return HpsTransactionType::Refund;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditReversal:
                return HpsTransactionType::Reverse;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditAccountVerify:
                return HpsTransactionType::Verify;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::ReportActivity:
                return HpsTransactionType::ListTransaction;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::ReportTxnDetail:
                return HpsTransactionType::Get;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::CreditVoid:
                return HpsTransactionType::Void;
                break;

            case HpsItemChoiceTypePosResponseVer10Transaction::BatchClose:
                return HpsTransactionType::BatchClose;
                break;

            default:
                return null;
        }
    }
}
