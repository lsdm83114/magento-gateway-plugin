ini_get_all<?php

namespace \porn\Gateway\Block\Payment;

use Magento\Framework\DataObject;

/**
 * Class Info
 * @package Vendo\Gateway\Block\Payment
 */
class Info extends \Magento\Payment\Block\Info\Cc
{
    /**
     * @param null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        try{
            if($this->getInfo()->getMethodInstance()->getCode() === 'vendo_sepa')
            {
                $transport->unsetData((string)__('Credit Card Type'));
            }
            else{
                if ($ccType = $this->getCcTypeName()) {
                    $data[(string)__('Credit Card Type')] = $ccType;
                }
                if ($this->getInfo()->getCcLast4()) {
                    $data[(string)__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
                }
            }
        }
        catch(\Exception $e){

        }


        if ($this->getInfo()->getLastTransId()) {
            $data[(string)__('Transaction Id')] = $this->getInfo()->getLastTransId();
        }

        try{
            if($this->getInfo()->getMethodInstance()->getCode() == 'vendo_sepa'){
                $tokenDetails = $this->getInfo()->getData('additional_information');
                if(array_key_exists('sepa_payment_mandate', $tokenDetails)){
                    $tokenDetails = json_decode($tokenDetails['sepa_payment_mandate'], true);
                }

                if(!is_array($tokenDetails)){
                    $tokenDetails = [];
                }
                $tokenDetails = new DataObject($tokenDetails);
                $additionalInformation = $this->getInfo()->getData('additional_information');
                $sepaDetails = array_key_exists('cc_details', $additionalInformation) ? $additionalInformation['cc_details'] : [];
                $sepaDetails = new DataObject($sepaDetails);
                if ($sepaDetails->getData('sepa_bic_swift')) {
                    $data[(string)__('SWIFT / BIC')] = $sepaDetails->getData('sepa_bic_swift');
                }
                if ($sepaDetails->getData('sepa_iban')) {
                    $iban = $sepaDetails->getData('sepa_iban');
                    $length = strlen($iban);
                    for($i=0;$i<$length;$i++){
                        if(($i>5)&&($i<($length-6))){
                            $iban[$i] = '*';
                        }
                    }

                    $data[(string)__('IBAN - International Bank Number')] = $iban;
                }

                if($tokenDetails->getData('mandate_id')){
                    $data[(string)__('Mandate Id')] = $tokenDetails->getData('mandate_id');
                }
                if($tokenDetails->getData('mandate_signed_date')){
                    $data[(string)__('Mandate Signed Date')] = $tokenDetails->getData('mandate_signed_date');
                }
            }
        }
        catch(\Exception $e){

        }

        if (!$this->getIsSecureMode()) {
            if ($ccSsIssue = $this->getInfo()->getCcSsIssue()) {
                $data[(string)__('Switch/Solo/Maestro Issue Number')] = $ccSsIssue;
            }
            $year = $this->getInfo()->getCcSsStartYear();
            $month = $this->getInfo()->getCcSsStartMonth();
            if ($year && $month) {
                $data[(string)__('Switch/Solo/Maestro Start Date')] = $this->_formatCardDate($year, $month);
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
