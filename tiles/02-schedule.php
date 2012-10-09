<?php
require_once(ROOT_PATH . '/include/config.php');
?>
<article id="schedule" class="official changing" data-width="4" data-height="2">
	<h2><a href="<?= ha($_CONFIG['schedule']['url']) ?>">Persönlicher Stundenplan für diese Woche</a></h2>
	<p class="empty">Die Krake schlägt gerade deinen Stundenplan nach</p>
	<script>
		$.get('schedule.html').success(function(data){
			$('article#schedule > p').replaceWith(data);
		});
	</script>
</article>