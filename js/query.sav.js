
$('div.frame.groups').each(function(){

	//	Containers
	var groupsFrame = $(this);
	var groups = groupsFrame.find('dl.group');
	
	//	Number of groups
	var nbGroupsInput = groupsFrame.find('.title input');
	var nbGroupsLast = nbGroupsInput.val();
	var nbGroupsMax = groups.length;
	nbGroupsInput.change(function(){
		var nbGroups = parseInt(nbGroupsInput.val());
		if (nbGroups < 1){
			nbGroups = 1;
		} else if (nbGroups > nbGroupsMax){
			nbGroups = nbGroupsMax;
		} else if (isNaN(nbGroups)){
			nbGroups = nbGroupsLast;
		}
		nbGroupsInput.val(nbGroupsLast = nbGroups);
		//	Only show columns of interest
		groups.each(function(i){
			if (i < nbGroups){
				$(this).show();
			} else{
				$(this).hide();
			}
		});
		//	Send data to the server
		groupsFrame.resize();
		$.mySend
		({	name	:	'query'
		,	source	:	nbGroupsInput
		,	data	:	{queryId:queryId, groups:nbGroups}
		});
	});
	//	Change number of inputs by clicking on buttons
	groupsFrame.find('.title button').click(function(){
		var value = nbGroupsInput.val();
		switch ($(this).text()){
			case '+':
				value++;
				break;
			case '-':
				value--;
				break;
		}
		nbGroupsInput.val(value).change();
	});
	//	Initialize by hiding hidden groups
	var nbGroups = parseInt(nbGroupsInput.val());
	groups.each(function(i){
		if (i < nbGroups){
			$(this).show();
		} else{
			$(this).hide();
		}
	});
	
	//	Colors initialization
	var groupInitColor = function(){
		var dl = $(this);
		var hue = dl.data('hue');
		color = Raphael.hsb(dl.data('hue'), 0.8, 0.8);
		dl.css({outlineColor: color});
		dl.find('dt').css({backgroundColor: color, borderColor: color});
		dl.find('dd.add').css({color: color});
	};
	groups.each(groupInitColor);

	//	Add data to a group
	groups.find('dd.add').click(function(){
		var dd = $(this);
		var dl = dd.parent();
		var dt = dl.find('dt');
		var data = dl.data();
		var color = dl.css('outlineColor');
		var container = $('<div>').addClass('container');
		var groupSubid = groups.index(dl);
		//	Menu list
		var menu = $('<ul>').addClass('menu').appendTo(container);
		$.each(pointsetTypes, function(){
			$('<li>').text(this.title).attr('title', this.description).appendTo(menu);
		});
		//	Contents
		var contents = $('<div>').addClass('contents loading').appendTo(container);
		//	Float blocker
		$('<div>').addClass('clear').appendTo(container);
		//	Window rendering
		var w = $.window(
		{	title	:	'Add points to "' + data.title + '"'
		,	width	:	'75%'
		,	height	:	'75%'
		,	html	:	container
		});
		w.find('h1,li,button').css({color:color, outlineColor:color});
		contents.height(0.8 * (w.height() - w.find('h1').outerHeight() - parseInt(contents.css('marginTop'))));
		//	Menu list event
		menu.find('li').mousedown(function(){
			var li = $(this);
			if (!li.hasClass('selected')){
				menu.find('li').removeClass('selected').css({backgroundColor:'#FFF', color:color});
				li.addClass('selected').css({backgroundColor:color, color:'#FFF'});
				$.each(pointsetTypes, function(p, pointsetType){
					if (li.text() == pointsetType.title){
						contents.html(pointsetType.html).append(
							$('<button>').addClass('add').text('Add these points to the group').click(function(){
								contents.addClass('loading');
								$.mySend
								({	name	:	'query'
								,	callback:	function(){$('.windows').remove();}
								,	data	:
									{	queryId		:	queryId
									,	groupSubid	:	groupSubid
									,	add		:	true
									,	type		:	pointsetType.name
									,	data		:	pointsetType.getData(contents)
									}
								});
								$('.windows').remove();
								$.window
								({	title	:	'Loading data...'
								,	width	:	'50%'
								,	height	:	'25%'
								,	blocking:	true	
								});
							})
						).removeClass().addClass('contents').addClass(pointsetType.name);
						if ($.isFunction(pointsetType.initalize)){
							($.proxy(pointsetType.initalize, contents.get(0)))();
						}
					}
				});
			}
		}).first().mousedown();
		//
		contents.removeClass('loading');
	});
	
	//	Delete a pointset
	groups.each(function(groupSubid, dl){
		$(dl).find('dd button').click(function(){
			if (confirm('Are you sure you want to remove these points?')){
				button = $(this);
				var dd = button.parent();
				var dl = dd.parent();
				dd.css({opacity:0.5});
				$.mySend
				({	name	:	'query'
				,	callback:	function(){
						dd.remove();					
					}
				,	data	:
					{	queryId		:	queryId
					,	groupSubid	:	groupSubid
					,	pointsetId	:	dd.data('id')
					,	delete		:	true
					}
				});
			}
		});
	});
	
	//	Edit a group's properties
	groups.find('dt').each(function(groupSubid){
		var dt = $(this);
		var dl = dt.parent();
		var data = dl.data();
		$('<button>').text('edit').prependTo(dt).attr({title:'Click here to edit the settings for this group...'}).click(function(){
			var label;
			var form = $('<form>');
			var w = $.window
			({	title	:	'Edit group settings'
			,	width	:	600
			,	html	:	form
			});
			label = $('<label>').appendTo(form).text('Title');
			$('<input type="text">').prependTo(label).val(data.title).attr({placeholder:'Title of this group', name: 'title'});
			label = $('<label>').appendTo(form).text('Description');
			$('<input type="text">').prependTo(label).val(data.description).attr({placeholder:'Short description for this group', name: 'description'});
			label = $('<label>').appendTo(form).text('Color');
			$('<input type="text">').prependTo(label).val(data.hue).attr({name: 'hue'}).hue();
			label = $('<label>').appendTo(form);
			$('<input type="submit">').prependTo(label).addClass('submit').val('Save changes').click(function(){
				var data = {};
				form.find('input,select').each(function(){
					var field = $(this);
					var name = field.attr('name');
					if (name){
						data[name] = field.val();
					}				
					$.mySend
					({	name	:	'query'
					,	source	:	w
					,	data	:	{queryId:queryId, groupSubid:groupSubid, update:true, update:data}
					,	callback:	function(){
										dl.data(data).each(groupInitColor)
											.find('dt span').text(data.title);
										w.parent().hide();
										$('div.frame.view div.title button.selected').click();
										$('div.frame.correlations div.title button.selected').click();
									}
					});
				});
				return false;
			});
		});
	});
	
	//	Change the title of the whole query
	$('h1.query').click(function(){
		var h1 = $(this);
		var title = prompt('Prompt a new title for the query:', h1.text());
		if (title){
			h1.text(title);
			$.mySend
			({	name	:	'query'
			,	source	:	h1
			,	data	:	{queryId:queryId, title:title}
			});
		}
	});
	
});


$('div.frame.graph').resize(function(e){
	if (!e.isTrigger){
		return false;
	}
	var frame = $(this);
	var container = frame.find('div.container');
	var framePaddingLeft = parseInt(frame.css('paddingLeft'));
	var framePaddingTop = parseInt(frame.css('paddingTop'));
	container.width(
		frame.width()  + framePaddingLeft + parseInt(frame.css('paddingRight'))
	).height(
		frame.height() + framePaddingTop  + parseInt(frame.css('paddingBottom')) - parseInt(frame.find('.title').outerHeight())
	);
	container.css
	({	position	:	'relative'
	,	left		:	-framePaddingLeft
	,	top			:	-framePaddingTop
	});
	container.graph(query.graph);
	return false;
});

$('div.frame.correlations').each(function(){
	var frame = $(this);
	var buttons = frame.find('div.title button');
	var title = frame.find('div.tile');
	var container = frame.find('div.container').empty();
	var graphContainer = $('div.frame.graph div.container');
	//	The table
	var table = $('<table>').appendTo(container);
	var thead = $('<thead>').appendTo(table);
	var tbody = $('<tbody>').appendTo(table);
	//	Resizing
	frame.resize(function(e){
		if (!e.isTrigger){
			return false;
		}
		var thList = thead.find('th');
		var width = [];
		var n = thList.length;
		thList.each(function(i){
			var th = $(this);
			var td = tbody.find('tr:first td').eq(i);
			if (i < 2){
				th.width(td.width() + parseInt(td.css('paddingLeft')) + parseInt(td.css('paddingRight')) - parseInt(th.css('paddingLeft')) - parseInt(th.css('paddingRight')));
			} else{
				td.width(th.width() + parseInt(th.css('paddingLeft')) + parseInt(th.css('paddingRight')) - parseInt(td.css('paddingLeft')) - parseInt(td.css('paddingRight')));
			}
		});
		container.height(frame.height() - thead.outerHeight() - title.outerHeight());
		tbody.height(container.height() - thead.outerHeight());
		return false;
	});
	//	Click it! Click it!
	buttons.click(function(){
		buttons.removeClass('selected').not(this).appendTo(this.parentNode);
		container.addClass('loading');
		graphContainer.empty().addClass('loading');
		var type = $(this).addClass('selected').text();
		$.mySend
		({	name	:	'query'
		,	data	:
			{	queryId		:	queryId
			,	correlate	:	true
			,	type		:	type
			}
		,	callback:	function(){
							//	Empty stuff
							thead.empty();
							tbody.empty();
							//	Fill header
							var tr = $('<tr>').appendTo(thead);
							$('<th>').appendTo(tr);
							$('<th>').appendTo(tr);
							$('<th>').text('Overall').appendTo(tr);
							$('div.frame.groups dt:visible').each(function(){
								var dt = $(this);
								var data = dt.parent().data();
								$('<th>').text(data.title).css({color: dt.css('backgroundColor')}).appendTo(tr);
							});
							//	Fill body
							var data = $('.frame.correlations').data('correlations');
							$.each(data, function(i){
								var tr = $('<tr>').appendTo(tbody);
								tr.addClass(i%2 ? 'even' : 'odd');
								$('<td>').text(i+1).appendTo(tr);
								$('<td>').text(this.title).appendTo(tr);
								$.each(this.scores, function(){
									$('<td>').text(Math.round(100 * this)).css({textAlign:'right'}).appendTo(tr);
								});
							});
							//	The end!
							table.tablesorter({onSorted: function(){
								$(this).find('tbody tr').removeClass('even odd').each(function(i){
									$(this).addClass(i%2 ? 'even' : 'odd').find('td:first').text(i+1);									
								});
							}});
							container.removeClass('loading');
							frame.resize();
							//	Draw graph
							$.mySend
							({	name	:	'query'
							,	data	:
								{	queryId	:	queryId
								,	graph	:	true
								,	type	:	type
								}
							,	callback:	function(){
												$('.frame.graph').resize();
											}
							});
						}
		});
	}).first().click();
	
});
// }).resize(function(e){
	// if (!e.isTrigger){
		// return false;
	// }
	// var container = $(this).find('div.container');
	// var thead = container.find('thead');
	// var tbody = container.find('tbody');
	// tbody.height
	// (	2 * container.height()
	// -	container.outerHeight()
	// -	thead.height()
	// );
// });

$('div.frame.view').each(function(){
	var frame = $(this);
	var buttons = frame.find('div.title button');
	var container = frame.find('div.container');
	var resizeContainer = function(){
		container.width(
			frame.width() + parseInt(frame.css('paddingLeft')) + parseInt(frame.css('paddingRight'))
		).height(
			frame.height() - parseInt(frame.find('.title').outerHeight()) + parseInt(frame.css('paddingTop')) + parseInt(frame.css('paddingBottom'))
		);
	};
	resizeContainer();
	frame.resize(function(e){
		if (!e.isTrigger){
			return false;
		}
		resizeContainer();
		var resizeView = container.data('view-resize');
		if ($.isFunction(resizeView)){
			resizeView();
		}
		return false;
	});
	buttons.click(function(){
		var button = $(this);
		buttons.removeClass('selected').not(button).prependTo(button.parent());
		button.addClass('selected');
		container.removeClass()
			.addClass('container view-' + button.text()).view(query.volumes);
	});
}).find('button.selected').click();
