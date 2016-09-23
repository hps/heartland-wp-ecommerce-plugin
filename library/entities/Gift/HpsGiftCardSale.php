<?php

class HpsGiftCardSale extends HpsGiftCardActivate {
    public $splitTenderCardAmount = null;
    public $splitTenderBalanceDue = null;

    static public function fromDict($rsp,$txnType,$returnType = null){
        $item = $rsp->Transaction;

        $sale = parent::fromDict($rsp,$txnType,$returnType);
        $sale->splitTenderCardAmount = (isset($item->SplitTenderCardAmt) ? (string)$item->SplitTenderCardAmt : null);
        $sale->splitTenderBalanceDue = (isset($item->SplitTenderBalanceDueAmt) ? (string)$item->SplitTenderBalanceDueAmt : null);

        return $sale;
    }
} 