<?php
/**
 * Gestion de l'avancement du traitement des commandes
 * 
 * Vérifie l'existence de la commande reçue
 * Si la commande est en statut "à traiter" :
 *  - Passe la commande en statut "traitée"
 *  - Notifie le client par mail de l'avancement de sa commande
 *  - Envoie un SMS si le téléphone est un 06 ou 07 et que le service sms est configuré
 * Si la commande est en statut "traitée :
 *  - Passe la commande en statut "expédiée" et payée
 * Récupère le détail de la commande
 * Récupère le détail du client
 * Retourne les informations au format json
 */

$id = (int)$_GET['id'];
$ref = $_GET['ref'];
if(!$id && !$ref) return;

include_once './config.php';
include_once './libs/Kiubi_API_DBO/kiubi_api_dbo_client.php';

$API = new Kiubi_API_DBO_Client($cfg['api_token']);
if($id) {

	include_once './libs/SMSGateway/interface.php';
	$sms = false;
	if(isset($cfg['sms_gateway']) && $cfg['sms_gateway']!='interface' && file_exists('./libs/SMSGateway/'.$cfg['sms_gateway'].'.php')) {
		$sms = true;
		include_once './libs/SMSGateway/'.$cfg['sms_gateway'].'.php';
	}

	$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders/'.$id.'.json');
	if($response->hasFailed()) return;

	$order = $response->getData();

	if($order['status']=='pending') {
		$API->put('sites/'.$cfg['code_site'].'/checkout/orders/'.$id.'.json?status=processed&notify=true');
		@unlink('./cache/stats.php');

		$phone = $order['billing_address']['phone'];
		$tel = substr($phone, 0, 2);
		if($sms && ($tel == '06' || $tel == '07')) {		
			$smsClass = 'SMS_'.$cfg['sms_gateway'];
			if(class_exists($smsClass)) {
				$sms = new $smsClass($phone, $cfg[$cfg['sms_gateway']]);
				if($sms instanceof SMS) {		
					$sms->send("Bonjour ".$order['billing_address']['firstname'].", votre commande est prête, elle n'attends plus que vous !");
				}
			}
		}		
	}


	elseif($order['status'] == 'processed') {
		$API->put('sites/'.$cfg['code_site'].'/checkout/orders/'.$id.'.json?status=shipped&is_paid=true');
		@unlink('./cache/stats.php');
	}

}

elseif($ref) {	
	$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders.json?reference='.$ref);
	if($response->hasFailed()) return;
	$order = $response->getData();
	$id = $order[0]['order_id'];
}

$json = array('order'=>array(), 'customer'=>array());

$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders/'.$id.'.json?extra_fields=price_label');
if($response->hasSucceed()) {
	$json['order'] = $response->getData();
}

$response = $API->get('sites/'.$cfg['code_site'].'/account/customers/'.$json['order']['customer_id'].'.json?extra_fields=orders');
if($response->hasSucceed()) {
	$json['customer'] = $response->getData();
}

echo json_encode($json);
