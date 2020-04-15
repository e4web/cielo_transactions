<?php

namespace leandrodsn\cielo_transactions;

use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use app\models\UserCreditcard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;

/**
 * This class is responsible for all communication with Cielo API 3.0 PHP SDK
 *
 * @author leandro
 */

class Cielocomponent extends \yii\base\Component {

	public $merchant_id = null;
	public $merchant_key = null;
	public $sandbox;

	private $merchant;
	private $enviroment;

	public $response = ['error' => 0, 'message'=> null, 'token' => null, 'code' => null];

	/*
	 * {inheritdoc}
	 */
	public function init(){

		if(!empty($this->merchant_id) && !empty($this->merchant_key)){
			$this->merchant = new Merchant($this->merchant_id, $this->merchant_key);
		}else {
			return false;
		}

		if($this->sandbox){
			$this->enviroment = Environment::sandbox();
		}else {
			$this->enviroment = Enviroment::production();
		}

		return parent::init();
	}

	/*
	 * {inheritdoc}
	 *  
	 */
	public function createCard($card)
	{
		$creditcard = new CreditCard();
		$creditcard->setCustomerName($card->card_name);
		$creditcard->setCardNumber(str_replace('.', '', $card->card_number));
		$creditcard->setHolder($card->card_name);
		$creditcard->setExpirationDate($card->expiration_date);
		$creditcard->setSecurityCode($card->cvv);
		$creditcard->setBrand($this->setCardBrand($card->card_brand));

		try {
			$cielo_ecommerce = (new CieloEcommerce($this->merchant, $this->enviroment))->tokenizeCard($creditcard);
		} catch (CieloRequestException $e) {
			$this->response['error'] = 1;
			$this->response['message'] = $e->getCieloError();
			return $this->response;
		}

		$this->response['message'] = "Cartão validado com sucesso!";
		$this->response['token'] = $cielo_ecommerce->getCardToken();
		return $this->response;
	}

	public function createPaymentByCreditcard($sale, $card)
	{

		$sale = new Sale($sale->id);
		$sale->customer("Nome Completo");
		$sale->payment($sale->amount, $sale->installments)
				->setType(Payment::PAYMENTTYPE_CREDITCARD)
				->creditCard($card->security_code, $this->setCardBrand($card->brand))
				->setCardToken($card->card_token);

		try {
			$payment_result = (new CieloEcommerce($this->merchant, $this->enviroment))->createSale($sale);
			$payment_result = $payment_result->getPayment();
		} catch (CieloRequestException $e) {
			$this->response['error'] = 1;
			$this->response['message'] = $e->getCieloError();
			return $this->response;
		}

		$this->response['message'] = "Pagamento criado com sucesso!";
		$this->response['token'] = $payment_result->getPaymentId();
		return $this->response;
	}

	public function capturePayment($payment_id, $amount)
	{

	 	try {
	 		$payment_result = (new CieloEcommerce($this->merchant, $this->enviroment))->captureSale($payment_id, $amount);
	 		$payment_result = $payment_result->getPayment(); 
	 	} catch (CieloRequestException $e) {
	 		$this->response['error'] = 1;
			$this->response['message'] = $e->getCieloError();
			return $this->response;
	 	}

	 	$this->response['message'] = $payment_result->getReturnMessage();
		$this->response['token'] = $payment_result->getTid();
		$this->response['code'] = $payment_result->getAuthorizationCode();
		return $this->response;
	}

	public function setCardBrand($brand)
	{	

		switch($brand) {
			case 'visa':
				return CreditCard::VISA;

			case 'mastercard':
				return CreditCard::MASTERCARD;

			case 'amex':
				return CreditCard::AMEX;

			default:
				return false;

		}

	}
}