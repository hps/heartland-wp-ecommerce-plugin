<?php

class HpsGiftCardAlias extends HpsTransaction{
    /**
     * The HPS gift card alias response.
     */

    public $giftCard = null;

    static public function fromDict($rsp,$txnType,$returnType = null){
        $item = $rsp->Transaction->$txnType;

        $alis = new HpsGiftCardAlias();
        $alis->transactionId = (string)$rsp->Header->GatewayTxnId;
        $alis->giftCard = new HpsGiftCard((string)$item->CardData);
        $alis->responseCode = (isset($item->RspCode) ? (string)$item->RspCode : null);
        $alis->responseText = (isset($item->RspText) ? (string)$item->RspText : null);

        return $alis;
    }
}