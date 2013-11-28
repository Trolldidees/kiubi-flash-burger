# FlashBurger
---

## Le concept

À chaque commande passée sur un site, une demande d'impression d'un ticket récapitulatif de la commande est envoyée à Berg Cloud, l'éditeur de Little Printer, pour une impression sur l'imprimante thermique. Un tableau de bord connecté à la console d'administration du site permet de suivre les commandes passées et leurs états. Le ticket imprimé comporte un code-barre qui sera scanné pour indiquer par SMS au client que sa commande est prête à être retirée. Un deuxième scan du ticket permet de marquer la commande comme étant payée et livrée. Si le cumul de l'ensemble des achats du client est supérieur à un seuil défini préalablement, il obtient sur son dernier ticket un bon de réduction utilisable lors de sa prochaine commande.




 - Une imprimante Little Printer configurée sur [Berg Cloud](http://bergcloud.com)
 - Un lecteur de code barre type douchette supportant le format code128 
 - Un serveur web compatible PHP 5.2+ avec l'extension cURL
 - Le serveur doit acoir un accès entrant et sortant à internet avec une url joignable depuis internet







 - Renseignez le paramètre `code_site` du fichier de configuration avec le  code site de votre site relié à l'application
 
 - Définissez le paramètre `sms_gateway` en fonction du choix du provider sms choisit et renseignez les auters paramètres spécifiques du provider choisit.
 
 - Recopiez le contenu du fichier `validation_commande.js` dans le template du widget de confirmation de commande du thème personnalisé de votre site (fichier `theme/fr/widgets/commandes/validation/index.html`), avant la fin du bloc `main` en y personnalisant **l'url de votre application**.
 

  

## Tests de l'installation

L'application comporte trois tests permettant de vérifier la connexion aux différents API utilisées par l'application. Chaque test peut être appelé dans votre navigateur et affiche `OK` ou `KO` en fonction du résultat du test :

 - `http://votre-url.com/kiubi.php` : Permet de vérifier l'accès à l'API Kiubi sur le site configuré
 - `http://votre-url.com/little_printer.php` : Envoi un test d'impression à votre imprimante Little Printer via l'API Berg Cloud
 - `http://votre-url.com/sms.php` : Envoi un sms de test via le provider configuré. Le numéro de téléphone doit être passé en GET dans le paramètre `phone`
 
 
## Accès à l'application

Vous devez connaitre l'url d'accès où sont déposés vos fichiers et devez accéder au fichier `dashboard.php` qui va afficher la liste des commandes du jour et permettre leurs traitements via la douchette de code barres.
  

## Comment ça marche ?


		'/checkout/orders.json?status=pending&creation_date_min='.date('Y-m-d').
		'&limit=10&extra_fields=price_label')



Dupliquez ce fichier pour créer un nouveau template pour le modifier à votre convenance et déclarez le dans le fichier config.php. Dans notre exemple, le ticket inclut un code-barre, le nom du client, la récapitulation de la commande et un bon de réduction.
 
	          $order_id.'.json?status=shipped&is_paid=true');

