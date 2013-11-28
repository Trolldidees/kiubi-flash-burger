<?php
/**
 * Script de test de la liaison entre l'application et l'API du service de sms
 * 
 * Nécessite la configuration d'un service de sms
 * Le numéro du destinataire doit être passer en paramètre GET dans "phone", ex: test/sms.php?phone=0612345678
 * Affiche "OK" ou "KO" si le teste a réussi ou non
 * Le numéro testé doit recevoir un sms de test du service
 */

include_once '../config.php';

if(!isset($cfg['sms_gateway']) || $cfg['sms_gateway']=='interface'
|| !file_exists('../libs/SMSGateway/'.$cfg['sms_gateway'].'.php')) {
	echo "Configuration SMS inexistante";
	exit();
}
if(!isset($_GET['phone']) || (substr($_GET['phone'], 0, 2)!="06" && substr($_GET['phone'], 0, 2)!="07")) {
	echo "Le numéro de téléphone doit être passé dans le paramètre GET phone";
	exit();
}
	
include_once '../libs/SMSGateway/interface.php';
include_once '../libs/SMSGateway/'.$cfg['sms_gateway'].'.php';

$smsClass = 'SMS_'.$cfg['sms_gateway'];
if(!class_exists($smsClass)) {
	echo "Erreur: L'instance ".$cfg['sms_gateway']." n'existe pas";
	exit();
}
$sms = new $smsClass($_GET['phone'], $cfg[$cfg['sms_gateway']]);
if(!$sms instanceof SMS) {		
	echo "Erreur: L'instance ".$cfg['sms_gateway']." est invalide";
	exit();
}

if($sms->send("Test envoi sms via ".$cfg['sms_gateway'])) {
	echo 'OK';
} else {
	echo 'KO';
}
