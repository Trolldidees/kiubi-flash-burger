<?php
/**
 * Retourne la liste des commandes et les stats commerciales
 * 
 * Gère un fichier de cache des données
 * Récupère les 10 dernières commandes à traiter
 * Récupère les 10 dernières commandes traitées
 * Récupère la devise du site
 * Récupère le nombre de commande du jour
 * Récupère le CA HT des commandes du jour
 * Retourne l'ensemble des infos au format json
 */

include_once './config.php';
include_once './libs/Kiubi_API_DBO/kiubi_api_dbo_client.php';

if(file_exists('./cache/stats.php')) {
	$date = filemtime('./cache/stats.php');
	if(time()-$date > 15*60) {
		@unlink('./cache/stats.php');		
	}
}
clearstatcache();
if(!file_exists('./cache/stats.php')) {
	
	$orders = array('pending'=>array(), 'waiting'=>array(), 'currency'=>'');
	
	$API = new Kiubi_API_DBO_Client($cfg['api_token']);
	$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders.json?status=pending&creation_date_min='.date('Y-m-d').'&limit=10&extra_fields=price_label');
	
	if($response->hasSucceed()) {
		foreach($response->getData() as $order) {
			$orders['pending'][] = array('ref'=>$order['reference'], 'id'=>$order['order_id'], 'date'=>$order['creation_date'], 'total'=>$order['price_total_inc_vat_label'], 'client'=>$order['billing_address']['firstname'].' '.$order['billing_address']['lastname']);
		}
	}
	
	$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders.json?status=processed&creation_date_min='.date('Y-m-d').'&limit=10&extra_fields=price_label');
	if($response->hasSucceed()) {
		foreach($response->getData() as $order) {
			$orders['waiting'][] = array('ref'=>$order['reference'], 'id'=>$order['order_id'], 'date'=>$order['modification_date'], 'total'=>$order['price_total_inc_vat_label'], 'client'=>$order['billing_address']['firstname'].' '.$order['billing_address']['lastname']);
		}
	}
	
	$response = $API->get('sites/'.$cfg['code_site'].'/prefs/catalog.json');
	if($response->hasSucceed()) {
		$data = $response->getData();
		$orders['currency'] = $data['currency'] == 'EUR' ? '€' : $data['currency'];
	}
	
	$orders['ca'] = 0;
	$orders['nb'] = 0;
	$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders.json?creation_date_min='.date('Y-m-d'));
	
	do {
		$next = false;
		$data = $response->getData();		
		$orders['nb'] += count($data);
		foreach ($data as $order) {
			if($order['is_paid']) {
				$orders['ca'] += $order['price_total_ex_vat'];
			}
		}		
		if($API->hasNextPage($response)) {
			$next = true;
			$response = $API->getNextPage($response);
		}   
	}while($next && $response->hasSucceed());
	
	file_put_contents('./cache/stats.php', "<?php\nreturn ".var_export($orders, true).";");
}

$data = include './cache/stats.php';
echo json_encode($data);
