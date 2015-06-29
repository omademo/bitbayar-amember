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
			->setLabel(array('Merchant API TOKEN', 'from BitBayar merchant account --> Setting & API --> API TOKEN'))
			->addRule('required');
	}

	public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
	{
		require_once 'bitbayar/bb_lib.php';
		$data_pay = array(
			'token' => $this->getConfig('apiToken'),
			'invoice_id'	=> $invoice->public_id,
			'rupiah'		=> $invoice->first_total,
			'memo'			=> $invoice->getFirstName().' - Invoice #'.$invoice->public_id,
			'callback_url'	=> $this->getPluginUrl('ipn'),
			'url_success'	=> $this->getReturnUrl(),
			'url_failed'	=> $this->getCancelUrl()
		);

		$bbInvoice = bbCurlPost(Am_Paysystem_Bitbayar::API_URL, $data_pay);
		$response = json_decode($bbInvoice);

		if($response->success){
			Am_Di::getInstance()->errorLogTable->log("BitBayar New Order #" . $response->invoice_id . " [Amount IDR : ".$response->amount_rp.'] [Amount BTC : '.$response->amount_btc."]");
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
		return $this->request->getFiltered('id');
	}

	public function validateSource()
	{
		//~ Error Log : Looks like an invalid IPN post - no Invoice# passed
		return true;
	}

	public function validateStatus()
	{
		require_once 'bitbayar/bb_lib.php';
		
		$data_check = array(
		'token'	=> $this->plugin->getConfig('apiToken'),
		'id'	=> $this->request->getFiltered('id')
		);

		$bbInvoiceStatus = bbCurlPost(Am_Paysystem_Bitbayar::API_STATUS, $data_check);
		$response = json_decode($bbInvoiceStatus);

		Am_Di::getInstance()->errorLogTable->log("BITBAYAR INVOICE STATUS \n[Invoice ID: " . $response->invoice_id."] [Status: ".$response->status."]");
		return $response->status;
	}

	public function validateTerms()
	{
		//~ Check IDR Amount
		return doubleval($this->invoice->first_total) == doubleval($this->request->get('rp'));
	}

	public function findInvoiceId()
	{
		//~ Error Log : Unknown transaction: related invoice not found #[A1B2C3]
		return $this->request->getFiltered('invoice_id');
	}

	public function validate()
	{
		$this->autoCreate();
		if (empty($this->invoice->_autoCreated) && !$this->validateTerms())
			throw new Am_Exception_Paysystem_TransactionInvalid("INVALID IDR AMOUNT \n[Invoice Amount : ".$this->invoice->first_total."] [Paid Amount : ".$this->request->get('rp')."]");
		if (!$this->validateStatus())
			throw new Am_Exception_Paysystem_TransactionInvalid("BITBAYAR STATUS : UNPAID");
	}
}