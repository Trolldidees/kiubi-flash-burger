<?php
/**
 * Driver pour l'imprimante LittlePrinter
 */

class LittlePrinter {
		
	private $lp_code;
	private $site;
	private $accroche;
	private $tpl = 'default';

	/**
	 * Instancie la classe
	 * 
	 * @param string $lp_code Code de l'imprimante LittlePrinter
	 * @param string $site Nom du site
	 * @param string $accroche Accroche du site
	 */
	public function __construct($lp_code, $site = '', $accroche = '') {
		$this->lp_code = $lp_code;
		$this->site = $site;
		$this->accroche = $accroche;
	}
	
	/**
	 * Change le template utilisé pour le ticket
	 * 
	 * @param string $tpl Nom du template. Le fichier doit se situé dans le dossier templates/ de la librairie
	 */
	public function setTemplate($tpl) {
		if(file_exists(dirname(__FILE__).'/templates/'.$tpl.'.php')) {
			$this->tpl = $tpl;
		}
	}

	/**
	 * Lance l'impression du ticket
	 * 
	 * @param array $order Données de la commande issues de l'API Back-office de Kiubi
	 * @param string $bon Code du bon
	 * @return string
	 */
	public function printOrder($order, $bon = null) {
		$html = $this->getHtmlTicket($order, $bon);
		return $this->send($html);
	}
	
	/**
	 * Lance un test d'impression
	 * 
	 * @param string $txt Le texte à imprimer
	 * @return string
	 */
	public function testPrint($txt) {
		return $this->send($txt);
	}

	/**
	 * Appel de l'API LittlePrinter pour l'impression du ticket
	 * 
	 * @param string $html Code html à imprimer
	 * @return string 
	 */
	private function send($html) {
		$host = "http://remote.bergcloud.com/playground/direct_print/".$this->lp_code;   
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, "$host");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "html=".urlencode($html));
		$result = curl_exec($curl_handle);
		curl_close($curl_handle);
		return $result;
	}
	
	/**
	 * Charge le template du ticket et renvoi le code html à imprimer
	 * 
	 * @param array $order Données de la commande issues de l'API Back-office de Kiubi
	 * @param string $bon Code du bon
	 * @return string
	 */
	private function getHtmlTicket($order, $bon) {		
		$site = $this->site;
		$accroche = $this->accroche;
		ob_start();
		include dirname(__FILE__).'/templates/'.$this->tpl.'.php';		
		return ob_get_clean();
	}	

}