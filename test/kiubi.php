<?php
/**
 * Script de test de la liaison entre l'application et l'API Back-office du site Kiubi
 * 
 * Affiche "OK" ou "KO" si le teste a réussi ou non
 */

include_once '../config.php';
include_once '../libs/Kiubi_API_DBO/kiubi_api_dbo_client.php';

$API = new Kiubi_API_DBO_Client($cfg['api_token']);
$response = $API->get('sites/'.$cfg['code_site'].'.json');
if($response->hasFailed()) echo 'KO';
if($response->hasSucceed()) echo 'OK';
