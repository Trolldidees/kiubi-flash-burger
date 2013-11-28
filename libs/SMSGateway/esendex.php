<?php
/**
 * Provider sms : Esendex (http://www.esendex.fr)
 * 
 * Gère l'envoi de sms via la plateforme Esendex
 */

class SMS_esendex extends SMS {
	
	/**
	 * Envoi du sms
	 * 
	 * @param string $text Texte du sms, sera tronqué à 160 caractères
	 * @return boolean
	 */
	public function send($text) {
		$text = substr($text, 0, 160);
		$xml = "<?xml version='1.0' encoding='UTF-8'?><messages><accountreference>".$this->params['account']."</accountreference><message><to>".$this->phone_number."</to><body>".$text."</body></message></messages>";
		$host = "https://api.esendex.com/v1.0/messagedispatcher";		
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, "$host");
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Authorization: Basic '.base64_encode($this->params['email'].':'.$this->params['password'])));		
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $xml);
		curl_exec($curl_handle);
		$err = curl_errno($curl_handle);
		curl_close($curl_handle);
		return $err === 0;
	}
	
}
