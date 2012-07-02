jQuery.fn.grid = function(options){
	var opts = jQuery.extend({
		'cell-width': 100,
		'cell-height': 100,
		'cell-spacing': 10,
		weights: { edge: 0.25, left: 1, right: 1, top: 0.5, bottom: 0.5 }
	});
	var context = this;
	
	var cells = [];
	var grid_width = 0, grid_height = 0;
	
	// Returns the grid value for the specified cell
	var at = function(x, y){
		return cells[y * grid_width + x];
	};
	
	// Increments the value at the specified cell if it contains nothing (undefined)
	// or a number. In any other case (e.g. string IDs) the cell is left unchanged.
	var inc_at = function(x, y, val){
		if (x < 0 || x > grid_width - 1 || y < 0 )
			return;
		var weight = at(x, y);
		if ( typeof weight == 'undefined' )
			weight = val;
		else if ( typeof weight == 'number' )
			weight += val;
		cells[y * grid_width + x] = weight;
	};
	
	// Sets the value for each cell in the specified rectangle
	var set_rect = function(x, y, w, h, val){
		if (x < 0) x = 0;
		if (y < 0) y = 0;
		if (x + w > grid_width) w = grid_width - x;
		
		for(var i = 0; i < w; i++){
			for(var j = 0; j < h; j++){
				cells[(y+j) * grid_width + (x+i)] = val;
			}
		}
	};
	
	// Searches a free rectangle near these x and y coords.
	var refine_pos = function(x, y, w, h){
		var check_rect = function(x1, y1, x2, y2){
			if (x1 < 0 || y1 < 0 || x2 >= grid_width)
				return false;
			
			for(var x = x1; x <= x2; x++){
				for(var y = y1; y <= y2; y++){
					var type = typeof at(x, y);
					if ( x >= grid_width || !(type == 'number' || type == 'undefined') )
						return false;
				}
			}
			return true;
		};
		
		var dx = w-1, dy = h-1;
		if ( check_rect(x-dx, y-dy, x, y) )
			return {x: x-dx, y: y-dy};
		else if ( check_rect(x-dx, y, x, y+dy) )
			return {x: x-dx, y: y};
		else if ( check_rect(x, y-dy, x+dx, y) )
			return {x: x, y: y-dy};
		else if ( check_rect(x, y, x+dx, y+dy) )
			return {x: x, y: y};
		return false;
	};
	
	// Updates the weights around the rectangle. You can configure the
	// weights via the `weights` variable above.
	var update_weights = function(x, y, w, h){
		inc_at(x - 1, y - 1, opts.weights.edge);
		inc_at(x + w, y - 1, opts.weights.edge);
		inc_at(x + w, y + h, opts.weights.edge);
		inc_at(x - 1, y + h, opts.weights.edge);
		
		for(var i = x; i < x + w; i++){
			inc_at(i, y - 1, opts.weights.top);
			inc_at(i, y + h, opts.weights.bottom);
		}
		
		for(var i = y; i < y + h; i++){
			inc_at(x - 1, i, opts.weights.left);
			inc_at(x + w, i, opts.weights.right);
		}
	};
	
	// Inserts a new box into the grid
	var insert = function(id, width, height){
		if ( at(0, 0) == undefined ) {
			var coords = refine_pos(0, 0, width, height);
			update_weights(0, 0, width, height);
			set_rect(coords.x, coords.y, width, height, id);
			grid_height = height;
			return {x: 0, y: 0};
		} else {
			var sorted_cells = [];
			for(var i = 0; i < cells.length; i++)
				sorted_cells.push({x: i % grid_width, y: Math.floor(i / grid_width), val: cells[i]});
			
			sorted_cells.sort(function(a, b){
				if (typeof a.val == 'number') {
					if (typeof b.val == 'number')
						return (a.val == b.val) ? 0 : ( (a.val < b.val) ? 1 : -1 );
					else
						return -1;
				} else {
					return (typeof b.val == 'number') ? 1 : 0;
				}
			});
			
			for(var i = 0; i < sorted_cells.length; i++){
				var coords = refine_pos(sorted_cells[i].x, sorted_cells[i].y, width, height);
				if (coords != false){
					update_weights(coords.x, coords.y, width, height);
					set_rect(coords.x, coords.y, width, height, id);
					grid_height = Math.max(grid_height, coords.y + height);
					return {x: coords.x, y: coords.y};
				}
			}
		}
	};
	
	this.on('layout', function(){
		grid_width = Math.floor( (context.innerWidth() + opts['cell-spacing']) / (opts['cell-width'] + opts['cell-spacing']) );
		context.children().each(function(){
			var elem = $(this);
			var w = elem.data('width'), h = elem.data('height');
			var coords = insert(elem.attr('id'), w, h);
			elem.css({
				position: 'absolute',
				left: coords.x * (opts['cell-width'] + opts['cell-spacing']) + 'px',
				top: coords.y * (opts['cell-height'] + opts['cell-spacing']) + 'px',
				width: opts['cell-width'] + ( (w - 1) * (opts['cell-width'] + opts['cell-spacing']) ) + 'px',
				height: opts['cell-height'] + ( (h - 1) * (opts['cell-height'] + opts['cell-spacing']) ) + 'px'
			});
		});
		context.css('min-height', (grid_height * (opts['cell-height'] + opts['cell-spacing'])) - opts['cell-spacing'] + 'px');
		return false;
	});
	
	this.on('debug', function(){
		context.children().hide().find('> div.grid-debug').remove();
		for(var i = 0; i < cells.length; i++){
			var x = i % grid_width, y = Math.floor(i / grid_width);
			$('<div class="grid-debug">').text(cells[i]).css({
				left: x * (opts['cell-width'] + opts['cell-spacing']) +  'px',
				top: y * (opts['cell-height'] + opts['cell-spacing']) + 'px'
			}).appendTo(context);
		}
		return false;
	});
	
	this.triggerHandler('layout');
	return this;
};