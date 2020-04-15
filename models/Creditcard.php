<?php
 
namespace leandrodsn\cielo_transactions\models;

use yii\base\Model;

/**
 * Description of Billing
 *
 * @author leandro
 */

class Creditcard extends Model
{

	public $card_name;
	public $card_number;
	public $expiration_date;
	public $cvv;
	public $card_brand;
	public $card_token;

	public function rules()
	{
		return [
			[['card_number', 'card_name', 'card_number', 'cvv', 'card_brand', 'expiration_date'], 'required', 'except' => 'search'],
			[['card_number'], 'string', 'max' => 19],
			[['cvv'], 'string', 'max'=> 3],
			[['card_token'], 'string', 'max' => 75],
			[['expiration_date'], 'validationDate']
		];	
	}

	public function validationDate($attribute, $param)
	{
		if(empty($this->$attribute))
			 $this->addError($attribute, "must be filled");
		
		$_date = explode('/', $this->$attribute);
		
		if((int)$_date[0] > 12){
			$this->addError($attribute, "month isn't valid.");
			return false;
		}

		return true;
	}

	public function attributeLabels() 
	{
		return [
			'card_name' => 'Usuário',
			'card_number' => 'Número do cartão',
			'expiration_date' => 'Data de expiração',
			'cvv' => 'CVV',
			'card_brand' => 'Bandeira'
		];
	}

}
