<?php
/**
 * Script de test de la liaison entre l'application et l'API LittlePrinter
 * 
 * Affiche "OK" ou "KO" si la demande d'impression a réussi ou non
 * Un ticket d'impression de test doit sortir de l'imprimante
 */

include_once '../config.php';
include_once '../libs/LittlePrinter/little_printer.php';

$LittlePrinter = new LittlePrinter($cfg['little_printer_code']);
if($LittlePrinter->testPrint("<h1>Test d'impression</h1> Date et heure du test : ".date('d/m/Y H\hi'))) {
	echo 'OK';
} else {
	echo 'KO';
}
