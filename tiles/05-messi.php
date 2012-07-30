<article id="messi" class="social changing loading" data-width="2" data-height="2">
	<script>
		$.ajax('messi.json').done(function(data){
			$(document).ready(function(){
				function build_contact_list(data, hide_offline_contacts){
					var group_list = $('<ul>');
					var contact_count = 0;
					var contact_count_online = 0;
					
					// Sort the groups alphabetically by converting them to an array
					// of pairs (object properties are unordered)
					var sorted_groups = [];
					for(var name in data.groups)
						sorted_groups.push([name, data.groups[name]]);
					sorted_groups.sort(function(a, b){
						return (a[0] == b[0]) ? 0 : ( (a[0] > b[0]) ? 1 : -1 );
					});
					
					// Create the group list entries
					for(var i = 0; i < sorted_groups.length; i++){
						var group_name = sorted_groups[i][0];
						var member_jids = sorted_groups[i][1];
						var member_list = $('<ul>');
						
						for(var j = 0; j < member_jids.length; j++){
							var jid = member_jids[j];
							var contact = data.contacts[jid];
							
							contact_count++;
							if (contact.status == 'unknown' || contact.status == 'unavailable') {
								if (hide_offline_contacts)
									continue;
							} else {
								contact_count_online++;
							}
							
							$('<li>').attr('class', contact.status).append(
								$('<span>').attr('title', jid).text(contact.name),
								(contact.message) ? $('<small>').text(contact.message) : undefined
							).appendTo(member_list);
						}
						
						if (member_list.children().size() > 0)
							$('<li>').append( document.createTextNode(group_name), member_list ).appendTo(group_list);
					}
					
					// Insert the new contact list, update the stats and display them
					$('#messi').removeClass('loading').find('> ul')
						.replaceWith(group_list)
					.end().find('#messi-online')
						.text(function(){ return contact_count_online + $(this).data('suffix'); })
						.attr('class', hide_offline_contacts ? 'active' : null)
					.end().find('#messi-contacts')
						.text(function(){ return contact_count + $(this).data('suffix'); })
						.attr('class', hide_offline_contacts ? null : 'active')
					.end().find('> p')
						.show();
					
					return contact_count_online;
				}
				
				$('#messi').find('a#messi-contacts').click(function(){
					build_contact_list(data, false);
					return false;
				}).end().find('a#messi-online').click(function(){
					build_contact_list(data, true);
					return false;
				});
				
				var online_count = build_contact_list(data, true);
				if (online_count == 0)
					build_contact_list(data, false);
			});
		}).fail(function(){
			$(document).ready(function(){
				$('#messi').removeClass('loading').addClass('failed');
			});
		});
	</script>
	<h2><a title="Was ist Messi?" href="https://wiki.mi.hdm-stuttgart.de/wiki/messi">Messi</a></h2>
	<p>
		<a href="#" title="Zeige nur Kontakte die online sind" id="messi-online" data-suffix=" online"></a>
		<a href="#" title="Zeige alle Kontakte" id="messi-contacts" data-suffix=" insgesamt"></a>
	</p>
	<ul>
	</ul>
</article>