<article id="kürzelkai" class="social unused" data-width="2" data-height="1">
	<script>
		$(document).ready(function(){
			$('article#kürzelkai input').keypress(function(e){
				// We're only interested in the enter key, skip the rest
				if (e.which != 13)
					return true;
				
				var search = $(this).val().trim();
				// Abort instead of searching for an empty string (would return the entire database...)
				if (search == '')
					return false;
				
				$.ajax('ldap.json', {
					data: {search: search}
				}).done(function(data){
					var list = $('<ul>');
					
					for(var i = 0; i < data.length; i++){
						var user = data[i];
						// Don't use the real mail but always the HdM mail. This way we show the mail and the id
						// at the same time. This avoids displaying the id twice in a row... kind of akward.
						var mail = user.id + '@hdm-stuttgart.de';
						$('<li>').append(
							$('<span class="name">').text(user.name).attr('title', user.full_name),
							' <', $('<a>').attr('href', 'mailto:' + user.name + ' <' + mail + '>').text(mail), '>'
						).appendTo(list);
					}
					
					var tile = $('article#kürzelkai');
					if (data.length == 0)
						tile.addClass('empty');
					else
						tile.removeClass('empty');
					
					tile.removeClass('unused');
					tile.find('ul').replaceWith(list);
				});
				
				return false;
			});
		});
	</script>
	<h2>Kürzel Kai</h2>
	<input id="query" placeholder="Kürzel oder Name eingeben">
	<p class="description">Über das Kürzel oder den Vor- oder Nachnamen kannst du nach Studenten, Mitarbeiter und Professoren suchen.</p>
	<p class="description">Beim drücken der Enter-Taste starktet Kürzel Kai die Suche.</p>
	<p class="empty">Sorry, leider hat Kai nichts gefunden.</p>
	<ul></ul>
</article>