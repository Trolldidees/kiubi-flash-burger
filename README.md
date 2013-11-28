# FlashBurger
---Permettre à tous les commerçants ayant un site de joindre l’outil à l’agréable en liant sites Web et points de vente physique pour augmenter la valeur du service rendu au client. Voilà une promesse alléchante que l’on croise de plus en plus et que nous allons tenir en la réalisant concrètement.Ce dépôt est une application utilisant l'API Developpers Back-office de [Kiubi](http://www.kiubi.com) pour gérer les commandes d'un site à travers une imprimante Little Printer tout en pouvant avertir le client par SMS.

## Le concept

À chaque commande passée sur un site, une demande d'impression d'un ticket récapitulatif de la commande est envoyée à Berg Cloud, l'éditeur de Little Printer, pour une impression sur l'imprimante thermique. Un tableau de bord connecté à la console d'administration du site permet de suivre les commandes passées et leurs états. Le ticket imprimé comporte un code-barre qui sera scanné pour indiquer par SMS au client que sa commande est prête à être retirée. Un deuxième scan du ticket permet de marquer la commande comme étant payée et livrée. Si le cumul de l'ensemble des achats du client est supérieur à un seuil défini préalablement, il obtient sur son dernier ticket un bon de réduction utilisable lors de sa prochaine commande.
Bien sûr, la gestion des commandes est toujours disponible dans la console d'administration de Kiubi : la prise de commande via le site e-commerce, le paiement en ligne, la gestion des commandes en backoffice, les changements d'état, les emails de notification sont toujours gérés par Kiubi. L'impression du ticket est gérée par Berg Cloud, et les SMS sont envoyés par le provider choisi.
En clair, toute la partie e-commerce, prise et gestion des commandes, notifications email on laisse Kiubi le gérer. L'impression du ticket sur la Little Printer, c'est Berg Cloud. Et les SMS sont fournis par le provider choisi.Et nous, on vous explique comment on a fait travailler tout ce petit monde ensemble.
## PrérequisPour mettre en place et utiliser FlashBurger il est nécessaire d'avoir :
 - Un site Kiubi en thème personnalisé
 - Une imprimante Little Printer configurée sur [Berg Cloud](http://bergcloud.com)
 - Un lecteur de code barre type douchette supportant le format code128 
 - Un serveur web compatible PHP 5.2+ avec l'extension cURL
 - Le serveur doit acoir un accès entrant et sortant à internet avec une url joignable depuis internet - Un compte chez un provider de SMS (optionnel)## Mise en place 
Télécharger les fichiers de cette applications et déposez l'ensemble sur un espace web de votre serveur.

Ensuite, configurez l'application :
 - Copiez le fichier `config.php-default` en `config.php`
 - Dans votre espace prestataire Kiubi, dans la section API, générez une clé API dédiée à l'application. Collez la clé API Kiubi dans le paramètre `api_token` du fichier de configuration config.php
 - Dans votre espace Berg Cloud, dans Developers > Tools > Direct link API vous trouverez la code de votre Little Printer. Collez le code Little Printer dans le paramètre `little_printer_code` du fichier de configuration

 - Renseignez le paramètre `code_site` du fichier de configuration avec le  code site de votre site relié à l'application
 
 - Définissez le paramètre `sms_gateway` en fonction du choix du provider sms choisit et renseignez les auters paramètres spécifiques du provider choisit.
 
 - Recopiez le contenu du fichier `validation_commande.js` dans le template du widget de confirmation de commande du thème personnalisé de votre site (fichier `theme/fr/widgets/commandes/validation/index.html`), avant la fin du bloc `main` en y personnalisant **l'url de votre application**.
 <pre>&lt;script type="text/javascript">	$(function(){   $.get('http://<strong>votre-url.com</strong>/ping.php?ref={reference}'); });&lt;/script></pre>

  

## Tests de l'installation

L'application comporte trois tests permettant de vérifier la connexion aux différents API utilisées par l'application. Chaque test peut être appelé dans votre navigateur et affiche `OK` ou `KO` en fonction du résultat du test :

 - `http://votre-url.com/kiubi.php` : Permet de vérifier l'accès à l'API Kiubi sur le site configuré
 - `http://votre-url.com/little_printer.php` : Envoi un test d'impression à votre imprimante Little Printer via l'API Berg Cloud
 - `http://votre-url.com/sms.php` : Envoi un sms de test via le provider configuré. Le numéro de téléphone doit être passé en GET dans le paramètre `phone`
 
 
## Accès à l'application

Vous devez connaitre l'url d'accès où sont déposés vos fichiers et devez accéder au fichier `dashboard.php` qui va afficher la liste des commandes du jour et permettre leurs traitements via la douchette de code barres.
  

## Comment ça marche ?
### Récupérer les commandesOuvrez le fichier `stats.php`. Ce fichier permet de récupérer des informations du site via l'API de Kiubi. Ces informations sont utilisées et affichées par le tableau de bord de l'application dans le fichier dashboard.php. Pour récupérer la liste des commandes, utilisez la classe Kiubi_API_DBO_Client et limitez la liste au statut `pending' (à traiter) et aux 10 dernières commandes du jour.
	$API = new Kiubi_API_DBO_Client($cfg['api_token']);	$response = $API->get('sites/'.$cfg['code_site'].
		'/checkout/orders.json?status=pending&creation_date_min='.date('Y-m-d').
		'&limit=10&extra_fields=price_label')	if($response->hasSucceed()) {	  $orders = $response->getData() 	}### Récupérer le CALe tableau de bord de l'application affiche quelques statistiques, comme le CA journalier. Toujours dans le fichier stats.php, requêtez toutes les commandes du jour. Le CA est calculé à partir des commandes dont le statut  `is_paid` (payé) est à  `true`. Le client API permet de savoir grâce à la méthode `hasNextPage()` si la requête contient une autre page de réponses pour permettre de parcourir l'ensemble des résultats.
	$CA = 0;	$response = $API->get('sites/'.$cfg['code_site'].		'/checkout/orders.json?creation_date_min='.		date('Y-m-d'));	do {	  $next = false;	  $data = $response->getData();	  foreach ($data as $order) {	    if($order['is_paid']) {	      $CA += $order['price_total_ex_vat'];	    }	  }	  if($API->hasNextPage($response)) {	    $next = true;	    $response = $API->getNextPage($response);	  }   	}while($next && $response->hasSucceed());### Imprimer le ticketLa validation d'une commande sur le site entraine l'impression d'un ticket via l'imprimante connectée Little Printer. L'API de Berg Cloud (éditeur de l'imprimante) est utilisée pour piloter les impressions dans le fichier ping.php via la classe `LittlePrinter`.
	$LittlePrinter = new LittlePrinter($cfg['little_printer_code']);	$LittlePrinter->printOrder($order);L'API de Berg Cloud permet également de personnaliser le template utilisé pour le rendu du ticket. Le template est un simple fichier HTML que vous trouverez dans le dossier `/libs/LittlePrinter/templates/default.php`.

Dupliquez ce fichier pour créer un nouveau template pour le modifier à votre convenance et déclarez le dans le fichier config.php. Dans notre exemple, le ticket inclut un code-barre, le nom du client, la récapitulation de la commande et un bon de réduction.	$LittlePrinter = new LittlePrinter($cfg['little_printer_code']);	$LittlePrinter->setTemplate($cfg['littleprinter_template']);	$LittlePrinter->printOrder($order);### Capturer le code barreChaque ticket est imprimé avec un code-barre qui permet d'identifier la commande. Tout type de lecteur de code-barre, compatible avec la norme code128, peut être utilisé pour scanner le ticket. En pratique, un lecteur se comporte comme un clavier avec une vitesse de frappe très rapide. Le fichier `dashboard.php` compte le nombre de frappes dans un certain laps de temps et détecte un code-barre si il y a plus de 6 frappes en 0,5sec.	$(function(){	  var pressed = false; 	  var chars = []; 	  $(window).keypress(function(e) {	    chars.push(String.fromCharCode(e.which));	    if (pressed == false) {	      setTimeout(function(){	        if (chars.length > 6) {	          var barcode = $.trim(chars.join(""));	        }	        chars = [];	        pressed = false;	     },500);	    }    	pressed = true;	  });	});### Traiter la commande, notifier par emailUne fois le code-barre scanné une première fois, le fichier  `confirm_commande.php` change le statut de la commande en `processed` (traitée) grâce à la méthode `put()` de l'API de Kiubi. Le paramètre `notify` permet d'envoyer automatiquement un email de notification au client. Le contenu de cet email peut être personnalisé directement via la console d'administration de Kiubi dans Commandes > Emails de confirmation.	$API->put('sites/'.$cfg['code_site'].		'/checkout/orders/'.$order_id.		'.json?status=processed&notify=true');### Envoyer un SMSSi un provider de SMS est déclaré dans le fichier `config.php` et que le client qui a passé commande a saisi un numéro de téléphone portable, un SMS lui est envoyé pour lui indiquer que la commande est prête à être récupérée. L'application comporte un système de gestion de plusieurs providers de SMS qui permet d'uniformiser le code nécessaire à l'envoi d'un SMS quel que soit le provider retenu. 	$smsClass = 'SMS_'.$cfg['sms_gateway'];	if(class_exists($smsClass)) {	  $sms = new $smsClass($phone, $cfg[$cfg['sms_gateway']]);	  if($sms instanceof SMS) {	    $sms->send("Bonjour ".$client['firstname'].	               ", Votre commande est prête, elle n'attends plus que vous !");	  }	}### Commande payée et livréeA la livraison de la commande, le ticket est scanné une deuxième fois. Le fichier `confirm_commande.php` vérifie si la commande a un statut `processed` (traitée). Si c'est le cas, la méthode `put()` change le statut en `shipped` (expédiée) et marque la commande comme étant payée (`is_paid=true`). Le fichier `dashboard.php` rafraichit alors les listes de commandes et les statistiques du tableau de bord de l'application.
 	$API->put('sites/'.$cfg['code_site'].'/checkout/orders/'.
	          $order_id.'.json?status=shipped&is_paid=true');### Afficher une fiche client
À chaque scan du code barre, le tableau de bord affiche le détail de la commande et les informations relatives au client, comme son numéro de téléphone et le CA total qu'il a généré sur le site. Pour récupérer ces informations, utilisez la méthode `get()` de l'API de Kiubi en requêtant l'identifiant du client et en spécifiant votre besoin des statistiques d'achat (`extra_fields=orders`).
	$response = $API->get('sites/'.$cfg['code_site'].	                      '/account/customers/'.$customer_id.'.json?extra_fields=orders');	if($response->hasSucceed()) {	  $client = $response->getData();	} 