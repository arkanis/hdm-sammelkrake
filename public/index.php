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
	<article>
		<h2>Game-Praktikum / Advanced Game Development (clarification about the limited number of participants)</h2>
		<div>
			<p>Er hörte leise Schritte hinter sich. Das bedeutete nichts Gutes. Wer würde ihm schon folgen, spät in der Nacht und dazu noch in dieser engen Gasse mitten im übel beleumundeten Hafenviertel? Gerade jetzt, wo er das Ding seines Lebens gedreht hatte und mit der Beute verschwinden wollte! Hatte einer seiner zahllosen Kollegen dieselbe Idee gehabt, ihn beobachtet und abgewartet, um ihn nun um die Früchte seiner Arbeit zu erleichtern?</p>
			<p>Oder gehörten die Schritte hinter ihm zu einem der unzähligen Gesetzeshüter dieser Stadt, und die stählerne Acht um seine Handgelenke würde gleich zuschnappen?</p>
			<p>Er konnte die Aufforderung stehen zu bleiben schon hören. Gehetzt sah er sich um. Plötzlich erblickte er den schmalen Durchgang.</p>
		</div>
		<p>
			<a href="#">fertig gelesen</a>
		</p>
	</article>
</div>

</body>
</html>