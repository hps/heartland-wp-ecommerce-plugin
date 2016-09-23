<?php

class HpsBatchService extends HpsGatewayService{
    public function __construct($config=null){
        parent::__construct($config);
    }
    public function closeBatch(){
        $xml = new DOMDocument();
        $hpsTransaction = $xml->createElement('hps:Transaction');
            $hpsBatchClose = $xml->createElement('hps:BatchClose');
        $hpsTransaction->appendChild($hpsBatchClose);

        $response = $this->doTransaction($hpsTransaction);
        HpsGatewayResponseValidation::checkResponse($response,'BatchClose');

        //Process the Response
        $batchClose = $response->Transaction->BatchClose;
        $result = new HpsBatch();
        $result->id = (isset($batchClose->BatchId) ? $batchClose->BatchId : null);
        $result->sequenceNumber = (isset($batchClose->BatchSeqNbr) ? $batchClose->BatchSeqNbr : null);
        $result->totalAmount = (isset($batchClose->TotalAmt) ? $batchClose->TotalAmt : null);
        $result->transactionCount = (isset($batchClose->TxnCnt) ? $batchClose->TxnCnt : null);

        return $result;
    }
} 