<?php
/**
 * Déclencheur lors d'une nouvelle commande
 * 
 * Vérifie l'existence et le statut de la commande reçue
 * Crée un bon de réduction si le CA atteint par le client dépasse le montant configuré, et limite l'utilisation du bon au client
 * Lance l'impression du ticket LittlePrinter
 */

ignore_user_abort();

$ref = $_GET['ref'];
if(!$ref) return;

include_once './config.php';
include_once './libs/Kiubi_API_DBO/kiubi_api_dbo_client.php';
include_once './libs/LittlePrinter/little_printer.php';

$API = new Kiubi_API_DBO_Client($cfg['api_token']);
$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders.json?reference='.$ref);
if($response->hasFailed()) return;

$orders = $response->getData();
if(count($orders)!=1) return;

$response = $API->get('sites/'.$cfg['code_site'].'/checkout/orders/'.$orders[0]['order_id'].'.json?extra_fields=price_label');
if($response->hasFailed()) return;
$order = $response->getData();
if($order['status']!='pending') return;

@unlink('./cache/stats.php');

$site_title = $site_accroche = '';
$response = $API->get('sites/'.$cfg['code_site'].'/prefs.json');
if($response->hasSucceed()) {
	$data = $response->getData();
	$site_title = $data['site_title'];
}
$response = $API->get('sites/'.$cfg['code_site'].'/prefs/theme.json');
if($response->hasSucceed()) {
	$data = $response->getData();
	$site_accroche = $data['site_excerpt'];
}

$LittlePrinter = new LittlePrinter($cfg['little_printer_code'], $site_title, $site_accroche);
if(isset($cfg['littleprinter_template']) && $cfg['littleprinter_template']!='') {
	$LittlePrinter->setTemplate($cfg['littleprinter_template']);
}

$response = $API->get('sites/'.$cfg['code_site'].'/account/customers/'.$order['customer_id'].'.json?extra_fields=orders');
if($response->hasFailed()) {
	$LittlePrinter->printOrder($order);
	return;
}

$customer = $response->getData();
if($customer['order_revenues'] < $cfg['client_min_ca']) {	
	$LittlePrinter->printOrder($order);
	return;
}

$response = $API->get('sites/'.$cfg['code_site'].'/checkout/vouchers.json');
if($response->hasFailed()) {
	$LittlePrinter->printOrder($order);
	return;
}

$client_has_voucher = false;
do {
	$next = false;
	$data = $response->getData();
	foreach($data as $voucher) {
		if($voucher['code'] == $cfg['code_reduc_prefix'].'-'.$order['customer_number']) {
			$client_has_voucher = true;
			break 2;
		}
	}
	if($API->hasNextPage($response)) {
		$next = true;
		$response = $API->getNextPage($response);
	}   
}while($next && $response->hasSucceed());

if($client_has_voucher) {
	$LittlePrinter->printOrder($order, $cfg['code_reduc_prefix'].'-'.$order['customer_number']);
	return;
}

$params = array(
	'code' => $cfg['code_reduc_prefix'].'-'.$order['customer_number'],
	'type' => 'percent',
	'is_enabled' => true,
	'start_date' => date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))),
	'end_date' => date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+10, date('Y'))),
	'value' => $cfg['code_reduc_percent'],
	'threshold' => 0,
	'quota' => 1,
	'is_quota_unlimited' => false,
	'stock' => 1,
	'is_stock_unlimited' => false,
);
$response = $API->post('sites/'.$cfg['code_site'].'/checkout/vouchers.json', $params);
if($response->hasSucceed()) {
	$API->put('sites/'.$cfg['code_site'].'/checkout/vouchers/'.$response->getData().'/restrictions.json?allow_customers='.$order['customer_id']);		
	$LittlePrinter->printOrder($order, $cfg['code_reduc_prefix'].'-'.$order['customer_number']);
} else {
	$LittlePrinter->printOrder($order);
}