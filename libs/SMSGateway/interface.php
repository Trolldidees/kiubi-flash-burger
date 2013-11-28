<?php
/**
 * Interface de gestion des SMS
 * 
 * Chaque provider sms doit étendre cette classe de base et implémenter la méthode send()
 */

abstract class SMS {
	
	protected $phone_number;
	protected $params;

	/**
	 * Instancie l'objet sms
	 * 
	 * @param string $phone_number Numéro de téléphone du destinataire
	 * @param array $params Paramètres de config pour le provider utilisé pour l'envoi du sms
	 */
	public function __construct($phone_number, $params) {
		$this->phone_number = $phone_number;
		$this->params = $params;
	}

	/**
	 * Envoi du sms
	 * 
	 * @param string $text Texte du sms
	 */
	abstract public function send($text);
}