<?php
class Am_Paysystem_Bitbayar extends Am_Paysystem_Abstract
{
	const PLUGIN_STATUS = self::STATUS_BETA;
	const PLUGIN_REVISION = '1.0';
	const API_URL = 'https://bitbayar.com/api/create_invoice';
	const API_STATUS = 'https://bitbayar.com/api/check_invoice';

	//~ Customer View
	protected $defaultTitle = 'BitBayar';
	protected $defaultDescription = 'paid by bitcoins';

	public function _initSetupForm(Am_Form_Setup $form)
	{
		$form->addText('apiToken', array('size' => 40))
			->setLabel(array('Merchant API TOKEN', 'from BitBayar merchant account -> Setting & API -> API TOKEN'))
			->addRule('required');
	}

	public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
	{
		$data=array(
			'token' => $this->getConfig('apiToken'),
			'invoice_id' => $invoice->public_id,
			'rupiah' => $invoice->first_total,
			'memo' => $invoice->getFirstName().' - Invoice #'.$invoice->public_id,
			'callback_url' => $this->getPluginUrl('ipn'),
			'url_success' => $this->getReturnUrl(),
			'url_failed' => $this->getCancelUrl()
		);

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, Am_Paysystem_Bitbayar::API_URL);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_POST, count($data));
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($data));

		//execute post
		$result = curl_exec($ch);

		//~ print_r($result);exit;

		//close connection
		curl_close($ch);

		$response = json_decode($result);

		if($response->success){
			Am_Di::getInstance()->errorLogTable->log("BitBayar: " . print_r($response, TRUE) . ".");
			header('Location: '.$response->payment_url);
			exit;
		}
		else{
			exit('BitBayar API Error: '.$response->error_message);
		}
	}

	public function getRecurringType()
	{
		return self::REPORTS_NOT_RECURRING;
	}

	public function getReadme()
	{
	return <<<CUT
<b>BitBayar payment plugin configuration</b>

Bitcoin you receive will be automatically converted into Rupiah without no fee.

For using this plugin:
  1. You must obtain an API TOKEN from the BitBayar website and paste it at '<b>Merchant API TOKEN</b>' option.
     Find your API TOKEN by logging into your merchant account and clicking on <a href="https://bitbayar.com/setting" target="blank">Setting & API</a>.

<b><a href="https://bitbayar.com/" target="blank">Bitbayar.com</a></b> 
CUT;
	}
 
	public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
	{
		return new Am_Paysystem_Transaction_BitBayar($this, $request, $response, $invokeArgs);
	}

	public function createThanksTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
	{
		return new Am_Paysystem_Transaction_BitBayar($this, $request, $response, $invokeArgs);
	}
}


class Am_Paysystem_Transaction_Bitbayar extends Am_Paysystem_Transaction_Incoming
{
	public function getUniqId()
	{
		//~ jika callback diakses manual via browser :
		//~ System Log: Looks like an invalid IPN post - no Invoice# passed
		return $this->request->getFiltered('id');
	}

	public function validateSource()
	{
		Am_Di::getInstance()->errorLogTable->log("Callback BitBayar: " . print_r($_POST, TRUE));
		return true;
	}

	public function validateStatus()
	{
		$data = array(
		'token'=>$this->getConfig('apiToken'),
		'id'=>$this->request->getFiltered('id')
		);

		$ch = curl_init();

		curl_setopt($ch,CURLOPT_URL, Am_Paysystem_Bitbayar::API_STATUS);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_POST, count($data));
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($data));

		$result = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($result);

		Am_Di::getInstance()->errorLogTable->log("Status Invoice: " . print_r($result, TRUE));
		return $response->status == 'paid';
	}

	public function validateTerms()
	{
		//~ Check IDR Amount
		return doubleval($this->invoice->first_total) == doubleval($this->request->get('rp'));
	}

	public function findInvoiceId()
	{
		//~ Ketika invoice_id tidak sama:
		//~ System Log : Unknown transaction: related invoice not found #[A1B2C3]
		return $this->request->getFiltered('invoice_id');
	}

	public function validate()
	{
		$this->autoCreate();
		if (!$this->validateSource())
			throw new Am_Exception_Paysystem_TransactionSource("IPN seems to be received from unknown source, not from the paysystem");
		if (empty($this->invoice->_autoCreated) && !$this->validateTerms())
			throw new Am_Exception_Paysystem_TransactionInvalid("Invalid IDR Amount");
		if (!$this->validateStatus())
			throw new Am_Exception_Paysystem_TransactionInvalid("Payment status is invalid");
	}
}