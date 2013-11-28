<?php
/**
 * Provider sms : envoyerSMSpro (http://envoyersmspro.com)
 * 
 * Gère l'envoi de sms via la plateforme envoyerSMSpro
 */

class SMS_smspro extends SMS {
	
	/**
	 * Envoi du sms
	 * 
	 * @param string $text Texte du sms, sera tronqué à 160 caractères
	 * @return boolean
	 */
	public function send($text) {
		$phone = $this->phone_number;
		if($phone{0}=='0') $phone = '33'.substr($phone, 1);
		
		$host = "https://www.envoyersmspro.com/api/message/send";
		$data = "text=".urlencode(substr($text, 0, 160))."&recipients=".$phone."&sendername=".$this->params['sender'];
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, "$host");
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Authorization: Basic '.base64_encode($this->params['login'].':'.$this->params['password'])));		
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
		$r = curl_exec($curl_handle);
		echo $r;
		$err = curl_errno($curl_handle);
		curl_close($curl_handle);
		return $err === 0;
	}
}
