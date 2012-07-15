<?php

const ROOT_PATH = '..';

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
			$('section').grid({ 'cell-width': 145, 'cell-height': 200, 'cell-spacing': 10 });//.triggerHandler('debug');
		});
	</script>
</head>
<body>

<header>
	<h1><a href="/">HdM Sammelkrake</a></h1>
	<p>Eine kleine Karte der Informationsquellen rund um die HdM</p>
	
	<ul id="legend">
		<li class="official changing">Sich ändernde offizielle Infos</li>
		<li class="official">Sich selten ändernde offizielle Infos</li>
		<li class="social">Soziales</li>
		<li class="projects">Projekte und eigene Aktivitäten</li>
		<li class="events">Events, Veröffent&shy;lichungen, …</li>
	</ul>
</header>

<section>

<? foreach( glob('../tiles/*.php') as $tile ): ?>
<?	include($tile) ?> 
<?	flush() ?>
<? endforeach ?>

</section>

</body>
</html>