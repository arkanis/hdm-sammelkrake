<?php

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/view_helpers.php');

?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<title>HdM Sammelkrake</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script src="scripts/jquery.js"></script>
	<script src="scripts/jquery.grid.js"></script>
	<script>
		$(document).ready(function(){
			$(window).resize(function(){
				$('section').grid({ 'cell-width': 145, 'cell-height': 200, 'cell-spacing': 10 });//.triggerHandler('debug');
			});
			$(window).resize();
		});
	</script>
</head>
<body>

<header>
	<h1><a href="/">HdM Sammelkrake</a></h1>
	<p>Eine kleine Karte der Informationsquellen rund um die HdM</p>
	
	<ul id="legend">
		<li class="official changing" title="Teilweise tägliche Meldungen aus offiziellen Informationskanälen der HdM"><span>Aktuelle offizielle Infos</span></li>
		<li class="official" title="Informationsquellen für dein Studium"><span>Offizielle Informationsquellen</span></li>
		<li class="social"><span>Soziales</span></li>
		<li class="projects" title="z.B. Projekte"><span>Eigene Aktivitäten</span></li>
		<li class="events"><span>Events, Veröffentlichungen, …</span></li>
	</ul>
</header>

<section id="tiles">

<? foreach( glob('../tiles/*.php') as $tile ): ?>
<?	include($tile) ?> 
<? endforeach ?>

</section>

<div id="details" class="inactive">
	<article></article>
</div>

</body>
</html>