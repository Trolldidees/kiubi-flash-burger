<!DOCTYPE html>
<!--
Tableau de bord de l'application

Affichage des statistiques et de la liste des commandes
Raffraichissement automatique des données toutes les 15 secondes
Gestion de la douchette avec le traitement des commandes lors d'un scan de code barre
Affichage du détail de la commande et du détail du client lors d'un scan de code barre

-->
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
	<link rel="shortcut icon" href="assets/img/favicon.ico">
  </head>

  <body>
	<section>
		<div class="container">

			<div class="row">
				<div class="span12"><h2 class="text-center">Aujourd'hui</h2></div>
			</div>
			<div class="row">
				<div class="span12">
					<div class="well">
						<div class="span5" id="cmd_nb">
							<img src="assets/img/ajax-loader.gif"/>
						</div>
						<div class="span5 offset1" id="cmd_ca">
							<img src="assets/img/ajax-loader.gif"/>
						</div>
					</div>	
				</div>	
			</div>
			
			<br/><br/><br/>
			<div class="row hide" id="detail"></div>
			<br/><br/><br/>

			<div class="row">
					<div class="span6">
						<h4>Nouvelles commandes</h4>
						<table class="table table-condensed well" id="cmd_todo"><tr><td><img src="assets/img/ajax-loader.gif"/></td></tr></table>
					</div>

					<div class="span6">
						<h4>Commandes prêtes</h4>
						<table class="table table-condensed well" id="cmd_done"><tr><td><img src="assets/img/ajax-loader.gif"/></td></tr></table>
					</div>

			</div>

		</div>
	</section>
			
			


    <!-- Footer
    ================================================== -->
    <footer class="footer">
      <div class="container">
        <p>&copy; Troll d'idées 2013</p>
      </div>
    </footer>


    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="assets/js/jquery.js"></script>
   	<script src="assets/js/moment.min.js"></script>	

<script type="text/javascript">
	var currentOrder = '';
	function refresh() {
		var q = $.getJSON('stats.php');
		q.done(function(data){
			$("#cmd_nb").html("<strong>Nombre de commandes : <big style=\"color:#fff\">"+data.nb+'</big></strong>');
			$("#cmd_ca").html("<strong>Chiffre d'affaire HT : <big style=\"color:#fff\">"+data.ca.toFixed(2)+' '+data.currency+'</big></strong>');
			
			var table = '';
			if(data.pending.length) {
				for(var i=0; i<data.pending.length; i++) {
					var order = data.pending[i];
					table += '<tr id="CO'+order.id+'" ref="'+order.ref+'"><td>'+moment(order.date, 'YYYY-MM-DD HH:mm:ss').fromNow()+'</td>'+
								 '<td>'+order.ref+' : '+order.total+'</td>'+
								 '<td>'+order.client+'</td>'+
								 '</tr>';
				}
			} else {
				table = '<tr><td>Aucune commande en attente</td></tr>';
			}
			$("#cmd_todo").html(table);	
			
			var table = '';
			if(data.waiting.length) {
				for(var i=0; i<data.waiting.length; i++) {
					var order = data.waiting[i];
					table += '<tr id="CO'+order.id+'" ref="'+order.ref+'">'+
								 '<td>'+order.ref+'</td>'+
								 '<td>'+order.client+'</td>'+
								 '<td>'+order.total+'</td>'+
								 '</tr>';
				}
			} else {
				table = '<tr><td>Aucun client en attente</td></tr>';
			}
			$("#cmd_done").html(table);	
			
			highlightCurrentOrder();
		});
	}
	
	function handleOrder(ref) {
		highlightCurrentOrder();
		if($("tr[ref='"+ref+"']").length) {
			var q = $.getJSON('confirm_commande.php?id='+$("tr[ref='"+ref+"']").attr('id').substr(2));
			q.done(function(data){
				refresh();
				showOrderDetail(data);
			});
		} else {
			currentOrder = '';
			var q = $.getJSON('confirm_commande.php?ref='+ref);
			q.done(function(data){
				showOrderDetail(data);
			});
		}
	}
	
	function highlightCurrentOrder() {		
		$("tr[ref='"+currentOrder+"']").css('background-color', '#fff').css('color', '#000');
	}
	
	function showOrderDetail(data) {
		if(!data) return;
		var col1, col2;
		col1  = '<div class="span6">';
		col1 += '<h4>Commande '+data.order.reference+'</h4>';
		for(var p=0; p<data.order.items.length; p++) {
			col1 += '<p>'+data.order.items[p].product_name+' <span class="pull-right">x '+data.order.items[p].quantity+'</span></p>';
		}
		col1 += '<div class="well">Total TTC : <strong class="pull-right" style="color:#fff">'+data.order.price_total_inc_vat_label+'</strong></div>';
		col1 += '</div>';
		
		col2  = '<div class="span6">';
		col2 += '<h4>'+data.customer.firstname+' '+data.customer.lastname+'</h4>';
		col2 += '<p>Téléphone : <span class="pull-right">'+data.order.billing_address.phone+'</span></p>';
		col2 += '<div class="well">';
		col2 += '<div class="span2">Nb commandes : <strong style="color:#fff">'+data.customer.order_count+'</strong></div>';
		col2 += '<div class="span3">Chiffre d\'affaire : <strong style="color:#fff">'+data.customer.order_revenues_label+'</strong></div>';
		col2 += '</div>';
		col2 += '</div>';
		
		$("#detail").attr('ref', data.order.reference).html(col1+col2).show(500);
		setTimeout(function(){
			$("#detail[ref='"+data.order.reference+"']").hide(500, function(){$(this).html('');});
		}, 20*1000);
	}

	refresh();
	setInterval(refresh, 15*1000);
	
	$(function(){
		var pressed = false; 
		var chars = []; 
		$(window).keypress(function(e) {
			chars.push(String.fromCharCode(e.which));
			if (pressed == false) {
				setTimeout(function(){
					if (chars.length > 6) {
						var barcode = $.trim(chars.join(""));
						if(barcode.substr(0, 2)=='CO') {
							var ref = barcode.substr(3);
							currentOrder = ref;
							handleOrder(ref);
						}
					}
					chars = [];
					pressed = false;
				},500);
			}
			pressed = true;
		});
	});
		
	</script>
	
  </body>
</html>
