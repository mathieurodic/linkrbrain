var pointsetTypes =
[	{	title		:	'Presets'
	,	name		:	'presets'
	,	description	:	'Known mappings for cognitive tasks & genes'
	,	html		:	'<ul class="tabs"><li class="tab"></li><li class="tab"></li></ul><ul class="tabs-select"><li>Tasks</li><li>Genes</li></ul>'
	,	initalize	:	function(){
							var contents = $(this);
							var tabs = contents.find('ul.tabs li');
							var tabsSelect = contents.find('ul.tabs-select li');
							//	Tabs management
							tabsSelect.each(function(i){
								var tabSelect = $(this);
								var tab = tabs.eq(i);
								var div = $('<div>').addClass('list-search').appendTo(tab);
								var input = $('<input type="text">').prependTo(div);
								var detailsDiv = $('<div>').addClass('list-details').appendTo(tab);
								var pagesUl = $('<ul>').appendTo(detailsDiv);
								var span = $('<span>').appendTo(detailsDiv);
								var table = $('<table></table>').addClass('list').appendTo(tab).uniqueId();
								var tbody = $('<tbody></tbody>').appendTo(table);
								//	Callback function to display results
								var displayCallback = function(data){
									//	Display results
									tbody.empty();
									$.each(data.list, function(i){
										table.data('query', input.val());
										var tr = $('<tr>').appendTo(tbody).addClass(i%2 ? 'odd' : 'even');
										$('<input type="checkbox">').attr({value:this.id}).appendTo(
											$('<td>').appendTo(tr)
										);
										$('<span>').text(this.title).appendTo(
											$('<td>').appendTo(tr)
										);
										$('<strong>').text(this.path).appendTo(
											$('<td>').appendTo(tr)
										);
									});
									tbody.find('tr').click(function(e){
										if (e.target.tagName != 'INPUT'){
											$(this).find('input:checkbox').each(function(){
												this.checked = !this.checked;
											});
										}
									});
									//	Pagination
									var text = '';
									if (data.results > 0){
										text += 'Results ';
										text += data.page * data.maxResults + 1;
										text += ' to ';
										if (data.results > data.maxResults){
											text += data.page * data.maxResults + data.list.length;
											text += ' out of ';
											text += data.results;
										} else{
											text += data.results
										}
									} else{
										text += 'No results';
									}
									span.text(text);
									//
									pagesUl.empty();
									var maxPage = Math.ceil(data.results / data.maxResults);
									if (maxPage > 1){
										var previousShown = false;
										for (var page=0; page<maxPage; page++){
											if (page==0 || (page<=data.page+2 && page>=data.page-2) || page==maxPage-1){
												var li = $('<li>').appendTo(pagesUl);
												var button = $('<button>').text(page + 1).appendTo(li);
												if (page == data.page){
													button.addClass('active');
												} else{
													button.click(function(){
														var page = parseInt($(this).text()) - 1;
														tab.addClass('loading');
														$.mySend
														({	name	:	'presets'
														,	data	:	{	time	:	(new Date()).getTime()
																		,	type	:	tabSelect.text().toLowerCase()
																		,	query	:	input.val()
																		,	page	:	page
																		,	htmlId	:	table.attr('id')
																		}
														,	callback:	displayCallback
														});
													});
												}
												previousShown = true;
											} else if (previousShown){
												var li = $('<li>').appendTo(pagesUl).text('...');
												previousShown = false;
											} else{
												previousShown = false;
											}
										}
									}
									//	Display
									tab.removeClass('loading');
									table.width(input.width());
									input.focus();
								};
								//	Add & configure search field
								input.keyup(function(){
									var page = 0;
									if (input.val() == table.data('query')){
										return;
									}
									tab.addClass('loading');
									$.mySend
									({	name	:	'presets'
									,	data	:	{	time	:	(new Date()).getTime()
													,	type	:	tabSelect.text().toLowerCase()
													,	query	:	input.val()
													,	page	:	0
													,	htmlId	:	table.attr('id')
													}
									,	callback:	displayCallback
									});
								});
								//	Show the clicked tab
								tabSelect.click(function(){
									var tabIndex = tabsSelect.removeClass('selected').index(this);
									tabs.removeClass('selected').eq(tabIndex).addClass('selected')
										.find('input').keyup().focus();
									tabsSelect.eq(tabIndex).addClass('selected');
								});
							}).first().click();
						}
	,	getData		:	function(contents){
							var ids = [];
							var tab = contents.find('li.tab.selected');
							var tabSelect = contents.find('ul.tabs-select li.selected');
							tab.find('input:checked').each(function(i, input){
								ids.push($(input).val());
							});
							return {type:tabSelect.text().toLowerCase(), ids:ids};
						}
	}
,	{	title		:	'Input coordinates'
	,	name		:	'input'
	,	description	:	'User-specified 3D coordinates'
	,	html		:	'<textarea placeholder="Example of coordinates:  10 -8 4"></textarea>'
	,	getData		:	function(contents){
							var textarea = contents.find('textarea');
							return textarea.val();
						}
	}
,	{	title		:	'Text files'
	,	name		:	'text'
	,	description	:	'Text file consisting of a list of 3D coordinates'
	,	html		:	'<iframe src="/upload/"></iframe><ul class="uploads"></ul>'
	,	getData		:	function(contents){
							var iframe = contents.find('iframe');
							return iframe.data('files');
						}
	}
,	{	title		:	'NIfTI files'
	,	name		:	'nifti'
	,	description	:	'Neuroimaging Informatics Technology Initiative file'
	,	html		:	'<iframe src="/upload/"></iframe><ul class="uploads"></ul>'
	,	getData		:	function(contents){
							var iframe = contents.find('iframe');
							return iframe.data('files');
						}
	}
,	{	title		:	'Previous queries'
	,	name		:	'previous'
	,	description	:	'Points used in a previous query'
	,	html		:	'<span>(soon to come)</span>'
	,	getData		:	function(contents){
						}
	}
];

Raphael.fn.line = function(startX, startY, endX, endY){
	return this.path('M' + startX + ' ' + startY + ' L' + endX + ' ' + endY);
};

$.dateUS = function(year, month, day, hours, minutes, seconds, milliseconds){
	var date;
	if (typeof(year) == 'object'){
		date = year;
	} else if (typeof(month) == 'undefined'){
		date = new Date(year);
	} else{
		date = new Date(year, month, day, hours, minutes, seconds, milliseconds);
	}
	console.log(date);
	var dayLength = 24 * 60 * 60 * 1000;
	var z = function(n){
		return (n<10 ? '0' : '') + n;
	};
	var d = function(){ 
		return z(date.getMonth() + 1) + '/' + z(date.getDate()) + '/' + date.getFullYear();
	};
	var t = function(){ 
		return z(date.getHours()) + ':' + z(date.getMinutes());
	};
	var today = new Date();
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	dt = date.getTime() - today.getTime();
	if (dt > 2*dayLength){
		return d();
	} else if (dt > dayLength){
		return 'tomorrow at ' + t();
	} else if (dt > 0){
		return 'today at ' + t();
	} else if (dt > -dayLength){
		return 'yesterday at ' + t();
	} else{
		return d();
	}
};

$.fn.title = function(title){
	if (typeof(title) == 'string'){
		this.attr('title', title);
		return this;
	} else{
		return this.attr('title');
	}
};

$.fn.getCSS = function(){
	var dom = this.get(0);
    var dest = {};
    var style, prop;
    if (window.getComputedStyle){
        var camelize = function(a, b){
			return b.toUpperCase();
		};
		if (style = window.getComputedStyle(dom, null)){
			var camel, val;
			if (style.length) {
				for (var i=0, l=style.length; i<l; i++){
					prop = style[i];
					camel = prop.replace(/\-([a-z])/, camelize);
					val = style.getPropertyValue(prop);
					dest[camel] = val;
				}
			} else{
				for (prop in style){
					camel = prop.replace(/\-([a-z])/, camelize);
					val = style.getPropertyValue(prop) || style[prop];
					dest[camel] = val;
				}
			}
			return dest;
		}
	}
	if (style = dom.currentStyle){
		for (prop in style){
			dest[prop] = style[prop];
		}
		return dest;
	}
	if (style = dom.style){
		for (prop in style){
			if (typeof style[prop] != 'function'){
				dest[prop] = style[prop];
			}
		}
	}
	return dest;
};
$.fn.cloneWithStyle = function(withDataAndEvents, deepWithDataAndEvents){
	var original = $(this);
	var clone = original.clone(withDataAndEvents, deepWithDataAndEvents);
	//	Retrieve original CSS
	var css = [];
	original.add(original.find('*')).each(function(){
		css.push($(this).getCSS());
	});
	//	Apply CSS to clone
	clone.add(clone.find('*')).each(function(i){
		$(this).css(css[i]);		
	});
	//	Return clone
	return clone;
};

$.fn.menu = function(menu){
	var mainUL = $(this).empty();
	$.each(menu, function(subMenuTitle, subMenu){
		var li = $('<li>').prependTo(mainUL);
		var dl = $('<dl>').appendTo(li);
		var dt = $('<dt>').appendTo(dl).text(subMenuTitle);
		var dd = $('<dd>').appendTo(dl);
		var ul = $('<ul>').appendTo(dd);
		$.each(subMenu, function(){
			var item = this;
			var li = $('<li>').appendTo(ul);
			switch (item.type){
				case 'separator':
					li.addClass('separator');
					break;
				case 'bool':
					li.text(item.title).click(function(){
						var selected = (item.context[item.key] = !item.context[item.key]);
						item.action();
						li.removeClass().addClass(selected ? 'selected' : 'grayed');
					}).addClass(item.context[item.key] ? 'selected' : 'grayed');
					break;
				case 'click':
					li.text(item.title).click(item.action);
					break;
				case 'numeric':
					var valueSpan = $('<span>').text(item.context[item.key]);
					var decreaseButton = $('<button>').text('-').click(function(){
						item.context[item.key] -= item.step;
						item.context[item.key] = item.step * Math.round(item.context[item.key] / item.step);
						if (item.context[item.key] < item.min){
							item.context[item.key] = item.min;
						}
						valueSpan.text(item.context[item.key]);
						item.action();
					});
					var increaseButton = $('<button>').text('+').click(function(){
						item.context[item.key] += item.step;
						item.context[item.key] = item.step * Math.round(item.context[item.key] / item.step);
						if (item.context[item.key] > item.max){
							item.context[item.key] = item.max;
						}
						valueSpan.text(item.context[item.key]);
						item.action();
					});
					var control = $('<div>');
					control.append(decreaseButton).append(valueSpan).append(increaseButton);
					li.text(item.title).prepend(control);
					break;
				case 'slider':
					var slider = $('<div>').addClass('slider');
					var cursor = $('<div>').appendTo(slider);
					slider.click(function(e){
						var x = e.pageX - slider.offset().left;
						var value = item.min + x / slider.width() * (item.max - item.min);
						if (value > item.max){
							value = item.max;
						} else if (value < item.min){
							value = item.min;
						}
						var left = Math.round(value / (item.max - item.min) * (slider.width() - cursor.width()));
						cursor.css({left: left+'px'});
						item.context[item.key] = value;
						item.action();
					});
					li.text(item.title).prepend(slider);
					var left = Math.round(item.context[item.key] / (item.max - item.min) * (slider.width() - cursor.width()));
					cursor.css({left: left+'px'});
					break;
			}
		});
	});
};
$.fn.sidebar = function(list){
	var ul = this.addClass('sidebar').empty();
	$.each(list, function(i, item){
		var li = $('<li>').title(item.description).appendTo(ul).click(item.action);
		var div = $('<div>').appendTo(li);
		$('<img>').attr({src: '/icons.png', style: 'top:-'+(3*item.icon)+'em'}).appendTo(div);
		$('<p>').text(item.title).appendTo(li);
	});
	return ul;
};

$.fn.groups = function(groups){
	var container = this.empty();
	//	Events
	var deletePointset = function(pointset, dd){
		dd = $(dd).css({opacity:0.5});
		var dl = dd.parent();
		$.mySend
		({	name	:	'query'
		,	source	:	dd
		,	callback:	function(){
				dd.remove();
				$('.frame.view .container').view(groups, query.settings.view);
			}
		,	data	:
			{	pointsetId	:	pointset.id
			,	delete		:	true
			}
		});
		dd.remove();
	};
	var deletePointsetUI = function(pointset, dd){
		if (confirm('Are you sure you want to remove these points?')){
			deletePointset(pointset, dd);
		}
	};
	var insertPointset = function(pointset, ddAdd){
		var dd = $('<dd>').insertBefore(ddAdd).text(pointset.title);
		$('<button>').text('x').appendTo(dd).attr({title:'Click here to remove these points from the group...'}).click(function(){
			deletePointsetUI(pointset, dd);
		});
	};
	var insertPointsetUI = function(group, ddAdd){
		var color = ddAdd.css('color');
		var container = $('<div>').addClass('container');
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
		{	title	:	'Add points to "' + group.title + '"'
		,	width	:	'75%'
		,	height	:	'75%'
		,	html	:	container
		});
		w.find('h1,li,button').css({color:color, outlineColor:color, borderColor:color});
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
								,	callback:	function(data){
										var pointset = data.pointset;
										var group = data.group;
										insertPointset(pointset, ddAdd);
										$('.windows').remove();
									}
								,	data	:
									{	queryId		:	group.queryId
									,	groupSubid	:	group.subId
									,	add			:	true
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
	};
	var updateGroup = function(group, dl){
		var color = Raphael.hsb(group.hue, 0.8, 0.8);
		dl = $(dl).css({outlineColor:color});
		dl.find('dt').css({backgroundColor: color, borderColor: color})
		  .find('span').text(group.title)
		dl.find('dd.add').css({color: color});
	};
	var updateGroupUI = function(group, dl){var label;
		var form = $('<form>');
		var w = $.window
		({	title	:	'Edit group settings'
		,	width	:	600
		,	html	:	form
		});
		label = $('<label>').appendTo(form).text('Title');
		$('<input type="text">').prependTo(label).val(group.title).attr({placeholder:'Title of this group', name: 'title'});
		label = $('<label>').appendTo(form).text('Description');
		$('<input type="text">').prependTo(label).val(group.description).attr({placeholder:'Short description for this group', name: 'description'});
		label = $('<label>').appendTo(form).text('Color');
		$('<input type="text">').prependTo(label).val(group.hue).attr({name: 'hue'}).hue();
		label = $('<label>').appendTo(form);
		$('<input type="submit">').prependTo(label).addClass('submit').val('Save changes').click(function(){
			var groupProperties = {};
			form.find('input,select').each(function(){
				var field = $(this);
				var name = field.attr('name');
				if (name){
					groupProperties[name] = field.val();
				}
			});
			$.mySend
			({	name	:	'query'
			,	source	:	w
			,	data	:	{queryId:group.queryId, groupSubid:group.subId, update:groupProperties}
			,	callback:	function(response){
								$.each(groupProperties, function(key, value){
									group[key] = value;
								});
								updateGroup(group, dl);
								w.parent().remove();
							}
			});
			return false;
		});
	};
	//	Building each group
	$.each(groups.list, function(g, group){
		//	Group's frame
		var dl = $('<dl>').appendTo(container);
		if (g >= groups.number){
			dl.hide();
		}
		//	Title bar
		var dt = $('<dt>').appendTo(dl);
		$('<button>').text('edit').title('Click here to edit the settings for this group...').appendTo(dt).click(function(){
			updateGroupUI(group, dl);
		});
		$('<span>').appendTo(dt);
		//	Below contents
		var dd = $('<dd>').addClass('add').text('Add data to this group...').appendTo(dl);
		dd.click(function(){
			insertPointsetUI(group, dd);
		});
		$.each(group.pointsets, function(p, pointset){
			insertPointset(pointset, dd);
		});
		updateGroup(group, dl);
	});
};
$.fn.correlations = function(data){
	
};
$.fn.graph = function(data, settings){
	var container = $(this);
	var threshold = settings.threshold;
	var nodes = data.nodes;
	var edges = data.edges;
	if (!nodes || !edges || !threshold){
		return container;
	}
	container.removeClass('loading').empty();
	var width = container.width();
	var height = container.height();
	var paper = Raphael(container[0], width, height);
	p = paper;
	var extrema = {xMin:+99,xMax:-99,yMin:+99,yMax:-99};
	//	Calculates on-screen coordinates
	$.each(nodes, function(){
		if (extrema.xMin > this.x){
			extrema.xMin = this.x;
		} else if (extrema.xMax < this.x){
			extrema.xMax = this.x;
		}
		if (extrema.yMin > this.y){
			extrema.yMin = this.y;
		} else if (extrema.yMax < this.y){
			extrema.yMax = this.y;
		}
	});
	var r = Math.min(width, height) / 20;
	var scale = Math.min
	(	(width  - 3 * r) / (extrema.xMax - extrema.xMin)
	,	(height - 3 * r) / (extrema.yMax - extrema.yMin)
	);
	var x0 = 0.5 * (width  - scale * (extrema.xMax + extrema.xMin));
	var y0 = 0.5 * (height - scale * (extrema.yMax + extrema.yMin));
	var onscreenCoordinates = function(point){
		var x = Math.round(x0 + scale * point.x);
		var y = Math.round(y0 + scale * point.y);
		return {x:x, y:y};
	};
	//	Draw edges
	$.each(edges, function(i, row){
		var centerI = onscreenCoordinates(nodes[i]);
		var color = '#000';
		var isGroup = nodes[i].hue != undefined;
		if (isGroup){
			color = Raphael.hsb(nodes[i].hue, 0.8, 0.8);
		}
		$.each(row, function(j, score){
			score -= threshold;
			if (score > 0){
				var centerJ = onscreenCoordinates(nodes[j]);
				paper.line(centerI.x, centerI.y, centerJ.x, centerJ.y).attr
				({	opacity		:	Math.min(1, score/2)
				,	stroke		:	color
				,	'stroke-width'	:	(isGroup  ?  1.5  :  0.75) * (1 + score)
				});
			}
		});
	});
	//	Draw nodes
	$.each(nodes, function(i){
		var center = onscreenCoordinates(this);
		var backgroundColor = '#EEE';
		var foregroundColor = '#000';
		var title = this.title;
		if (this.type == 'group'){
			backgroundColor = Raphael.hsb(this.hue, 0.8, 0.8);
			foregroundColor = '#FFF';
			title = this.title;
		}
		this.disc = paper.circle(center.x, center.y, r).attr
		({	fill	:	backgroundColor
		,	stroke	:	'rgba(0,0,0,0.5)'
		,	opacity	:	0.85
		,	cursor	:	'pointer'
		});
		this.text = paper.text(center.x, center.y, title.replace(/ /g, '\n')).attr
		({	fill	:	foregroundColor
		,	title	:	title
		,	fontSize:	r/2.5
		,	cursor	:	'pointer'
		});
	});
	//	Adjust font
	container.find('*').css
	({	fontSize:	(r/2) + 'px'
	,	lineHeight:	(r/2) + 'px'
	});
	//	The end!
	return container;
};
$.fn.view2D = function(groups, settings, repaint){
	
	//	Parameters initialization
	var container = $(this);
	if (repaint){
		container.empty();
	}
	var frame = container.parent();
	var view;
	if (!(view = container.find('div.view')).length){
		view = $('<div>').addClass('view').appendTo(container);
	}
	var controlsMenu;
	if (!(controlsMenu = container.find('ul.controls')).length){
		controlsMenu = $('<ul>').addClass('controls').appendTo(container);
	}
	
	
	var box = settings.box;
	if (repaint){
		//	Prepare the background
		frame.css({backgroundColor:'#000'});
		view.empty();
		//	Prepare the slices
		$.each(box, function(coordinate, values){
			this.total = this.max - this.min;
		});
		var scale = Math.min
		(	container.width() / (box.x.total + box.y.total)
		,	(container.height() - controlsMenu.height()) / (box.y.total + box.z.total)
		);
		view.width(scale * (box.x.total + box.y.total))
			.height(scale * (box.y.total + box.z.total))
			.css({marginTop:controlsMenu.height()+'px'});
		//	Display slices
		var slices = {};
		$.each(['xyz', 'yzx', 'xzy'], function(s, sliceName){
			var c0 = sliceName.charAt(0);
			var c1 = sliceName.charAt(1);
			var width = scale * box[c0].total;
			var height = scale * box[c1].total;
			var wrapper = $('<div>').addClass('view-slice').addClass('view-slice-' + sliceName)
				.appendTo(view).width(width).height(height);
			var image = $('<img>').attr({src: '/brain/'+sliceName+'.png'}).load(function(){
				$(this).data({loaded: true})
			}).appendTo(wrapper);
			var paperContainer = $('<div>').addClass('paper').appendTo(wrapper);
			var paper = Raphael(paperContainer.get(0), width, height);
			paper.setViewBox
			(	box[c0].min
			,	box[c1].min
			,	1 + box[c0].total
			,	1 + box[c1].total
			);
			var overlay = $('<div>').addClass('overlay').appendTo(wrapper);
			//	Add to slices list
			slices[sliceName] =
			{	wrapper	:	wrapper
			,	image	:	image
			,	paper	:	paper
			,	overlay	:	overlay
			};
		});
		slices.xyz.wrapper.css({right:0, top:0});
		slices.yzx.wrapper.css({left: 0, bottom:0});
		slices.xzy.wrapper.css({right:0, bottom:0});
		//	Store important stuff
		container.data('view-slices', slices);
	}
	var position = settings.position;
	
	//	Adjust position of pictures...
	var movePictures = function(){
		var slices = container.data('view-slices');
		$.each(container.data('view-slices'), function(sliceName, slice){
			var c1 = sliceName.charAt(1);
			var c2 = sliceName.charAt(2);
			var top = (slice.image.height() - slice.wrapper.height()) * (position[c2] - box[c2].min) / box[c2].total;
			slice.image.css({top: -top + 'px'});
		});
	};
	//	...but before that, wait until they're loaded!
	var movePicturesWhenLoaded = function(){
		var unloadedPictures = 0;
		$.each(container.data('view-slices'), function(sliceName, slice){
			if (!slice.image.data('loaded')){
				unloadedPictures++;
			}
		});
		if (unloadedPictures > 0){
			setTimeout(arguments.callee, 100);				
		} else{
			movePictures();
		}
	};
	movePicturesWhenLoaded();
	//	Draw points
	var drawPoints = function(){
		var position = settings.position;
		var opacity = settings.points.opacity;
		var radius = settings.points.radius;
		var radius2 = radius * radius;
		var box = settings.box;
		$.each(container.data('view-slices'), function(sliceName, slice){
			//	Initialize view
			var c0 = sliceName.charAt(0);
			var c1 = sliceName.charAt(1);
			var c2 = sliceName.charAt(2);
			var paper = slice.paper;
			paper.clear();
			//	Horizontal line
			paper.line
			(	box[c0].min
			,	box[c1].min + box[c1].max - position[c1]
			,	box[c0].max
			,	box[c1].min + box[c1].max - position[c1]
			).attr({stroke:'#888'});
			//	Vertical line
			paper.line
			(	position[c0]
			,	box[c1].min
			,	position[c0]
			,	box[c1].max
			).attr({stroke:'#888'});
			//	Draw points
			for (var g=0; g<groups.number; g++){
				var group = groups.list[g];
				var color = 'hsba(' + group.hue + ', 1, 0.8, ' + opacity + ')';
				$.each(group.points, function(p, point){
					var h = Math.abs(position[c2] - point[c2]);
					if (h <= radius){
						paper.circle
						(	point[c0]
						,	box[c1].min + box[c1].max - point[c1]
						,	Math.sqrt(radius2 - h*h)
						).attr
						({	fill	:	color
						,	stroke	:	0
						});
					}
				});
			}
		});
	};
	drawPoints();
	//	Position coordinates
	var coordinatesSlice = $('<div>').addClass('view-slice').css({left:0,top:0}).appendTo(view);
	var updatePosition;
	$.each(['x','y','z'], function(i, c){
		var label = $('<label>').text(c + ' = ').appendTo(coordinatesSlice);
		$('<input>').attr({name:c}).val(position[c]).appendTo(label).change(function(){
			var newPosition = {};
			newPosition[this.name] = this.value;
			updatePosition(newPosition);
		});
	});
	updatePosition = function(newPosition){
		var box = settings.box;
		$.each(newPosition, function(c, value){
			value = parseFloat(value);
			value = isNaN(value) ? 0 : (2*Math.round(value/2));
			if (value < box[c].min){
				value = box[c].min;
			}
			if (value > box[c].max){
				value = box[c].max;
			}
			view.find('input[name=' + c + ']').val(settings.position[c] = value);
		});
		movePictures();
		drawPoints();
	};
	
	if (!repaint){
		return container.removeClass('loading');
	}
		
	//	Mouse events
	$.each(slices, function(sliceName, slice){
		var overlay = slice.overlay;
		var size0 = overlay.width();
		var size1 = overlay.height();
		var mouse = function(e, overlay){
			var c0 = sliceName.charAt(0);
			var c1 = sliceName.charAt(1);
			var newPosition = {};
			newPosition[c0] = e.offsetX / size0 * box[c0].total + box[c0].min;
			newPosition[c1] = (1 - e.offsetY / size1) * box[c1].total + box[c1].min;				
			updatePosition(newPosition);
		};
		overlay.mousedown(function(e){
			overlay.data({viewDragging:true});
			mouse(e, overlay);
			return false;
		}).mousemove(function(e){
			if (overlay.data('viewDragging')){
				mouse(e, overlay);
			}
			return false;
		}).mouseout(function(){
			overlay.data({viewDragging:false});
		}).mouseup(function(){
			overlay.data({viewDragging:false});
		});
	});
	
	//
	//	Settings menu
	//
	controlsMenu.menu
	({	'Representative points':
		[	{	title:		'Radius'
			,	type:		'slider'
			,	context:	settings.points
			,	key:		'radius'
			,	min:		0.1
			,	max:		9.9
			,	action:		function(){
					drawPoints();
				}
			}
		,	{	title:		'Opacity'
			,	type:		'slider'
			,	context:	settings.points
			,	min:		0
			,	max:		1
			,	key:		'opacity'
			,	action:		function(){
					drawPoints();
				}
			}
		,	
		]
	});
	
	//
	//	On resize event
	//
	return container.off('resize').resize(function(e){
		container.view2D(query.groups, query.settings.view, true);
		return false;
	}).removeClass('loading');
};
$.fn.view3D = function(groups, settings, repaint){
	
	//	Parameters initialization
	var container = $(this);
	var frame = container.parent().css({background: ''});
	var view;
	if (!(view = container.find('div.view')).length){
		view = $('<div>').addClass('view').appendTo(container);
	}
	var controlsMenu;
	if (!(controlsMenu = container.find('ul.controls')).length){
		controlsMenu = $('<ul>').addClass('controls').appendTo(container);
	}
	
	//	3D variables
	var distanceFromCamera = 300;
	var camera, scene, renderer, controls, directionalLight;
	var mouseX, mouseY;
	var windowX, windowY;
	var windowHalfX, windowHalfY;
	
	//	View parameters
	container.addClass('loading');
	frame.css({backgroundColor:''});
	view.width(
		container.width()
	).height(
		container.height()
	).css({margin:0});
	//	Draw brains
	var brainObject  =
	{	gray	:
		{	cerebellum	:
			{	left	:	undefined
			,	right	:	undefined
			}
		,	cerebrum	:
			{	left	:	undefined
			,	right	:	undefined
			}
		}
	,	white	:
		{	cerebellum	:
			{	left	:	undefined
			,	right	:	undefined
			}
		,	cerebrum	:
			{	left	:	undefined
			,	right	:	undefined
			}
		}
	};
	var brainColor = new THREE.Color(0xFFFFFF);
	var brainPosition = new THREE.Vector3(0, 0, 0);
	var mapSpheres = function(brainSubObject){
		//	Groups & spheres stuff
		var radius = settings.points.radius;
		var radius2 = radius * radius;
		var groupColors = [];
		for (var g=0; g<groups.number; g++){
			var groupRGBColor = Raphael.hsb2rgb(groups.list[g].hue, 1, 0.8);
			var groupColor = new THREE.Color();
			groupColor.r = groupRGBColor.r / 255;
			groupColor.g = groupRGBColor.g / 255;
			groupColor.b = groupRGBColor.b / 255;
			groupColors.push(groupColor);
		}
		//	Initialize values
		var mesh = brainSubObject.children[0];
		var geometry = mesh.geometry;
		var faces = geometry.faces;
		var vertices = geometry.vertices;
		//	Determine each vertex' color
		var vertexIndex = vertices.length;
		var vertexColors = new Array(vertexIndex);
		while (vertexIndex--){
			var isMapped = 0;
			var mappingColor = brainColor;
			var vertex = vertices[vertexIndex];
			var x = vertex.x;
			var y = vertex.y;
			var z = vertex.z;
			//	Check if inside one of the spheres
			var groupIndex = groups.number;
			while (groupIndex--){
				var group = groups.list[groupIndex];
				var points = group.points;
				var pointIndex = points.length;
				while (pointIndex--){
					var point = points[pointIndex];
					var dx = Math.abs(point.x - x);
					if (dx <= radius){
						var dy = Math.abs(point.y - y);
						if (dy <= radius){
							var dz = Math.abs(point.z - z);
							if (dz <= radius){
								if (dx*dx + dy*dy + dz*dz <= radius2){
									var groupColor = groupColors[groupIndex];
									// mappingColor = groupColor;
									if (isMapped == 0){
										mappingColor = groupColor;
									} else{
										mappingColor = new THREE.Color(mappingColor);
										mappingColor.r = (isMapped * mappingColor.r + groupColor.r) / (isMapped + 1);
										mappingColor.g = (isMapped * mappingColor.g + groupColor.g) / (isMapped + 1);
										mappingColor.b = (isMapped * mappingColor.b + groupColor.b) / (isMapped + 1);
									}
									isMapped++;
									break;
								}
							}
						}
					}
				}
			}
			vertexColors[vertexIndex] = mappingColor;
		}
		//	Color faces
		var faceIndex = faces.length;
		while (faceIndex--){
			var face = faces[faceIndex];
			face.vertexColors =
			[	vertexColors[face.a]
			,	vertexColors[face.b]
			,	vertexColors[face.c]
			];
		}
		//	Repaint
		geometry.verticesNeedUpdate = true;
		geometry.elementsNeedUpdate = true;
		geometry.morphTargetsNeedUpdate = true;
		geometry.uvsNeedUpdate = true;
		geometry.normalsNeedUpdate = true;
		geometry.colorsNeedUpdate = true;
		geometry.tangentsNeedUpdate = true;
	};
	var unMapSpheres = function(brainSubObject){
		//	Initialize values
		var mesh = brainSubObject.children[0];
		var geometry = mesh.geometry;
		var faces = geometry.faces;
		//	Color faces
		var faceIndex = faces.length;
		var vertexColors = [brainColor, brainColor, brainColor];
		while (faceIndex--){
			faces[faceIndex].vertexColors = vertexColors;
		}
		//	Repaint
		geometry.verticesNeedUpdate = true;
		geometry.elementsNeedUpdate = true;
		geometry.morphTargetsNeedUpdate = true;
		geometry.uvsNeedUpdate = true;
		geometry.normalsNeedUpdate = true;
		geometry.colorsNeedUpdate = true;
		geometry.tangentsNeedUpdate = true;
	};
	var drawBrain = function(){
		var brainMaterials =
		[	new THREE.MeshLambertMaterial({color: 0x888888, emissive:0x888888, shading: THREE.SmoothShading, vertexColors: THREE.VertexColors, side:THREE.DoubleSide, depthTest:true, depthWrite:true})
		,	new THREE.MeshBasicMaterial({color: 0xFFFFFF, shading: THREE.FlatShading, wireframe: false, transparent: true, opacity:0})
		];
		$.each(brainObject, function(matter){
			$.each(brainObject[matter], function(part){
				$.each(brainObject[matter][part], function(hemisphere){
					var visible = (settings.matters[matter]  &&  settings.hemispheres[hemisphere]);
					if (visible  &&  brainObject[matter][part][hemisphere] == undefined){
						var loader = new THREE.OBJLoader();
						loader.load('/' + matter + '-' + hemisphere + '-' + part + '.obj', function(object){
							var clonedObject = THREE.SceneUtils.createMultiMaterialObject(object.children[0].geometry, brainMaterials);
							scene.add(clonedObject);
							clonedObject.children[0].traverse(function(node){
								if (node.material){
									node.material.opacity = settings.opacity;
									node.material.transparent = true;
								}
							});
							if (settings.zones.surfaces){
								mapSpheres(clonedObject);
							}
							brainObject[matter][part][hemisphere] = clonedObject;
						});
					} else if (brainObject[matter][part][hemisphere] != undefined){
						brainObject[matter][part][hemisphere].traverse(function(node){
							node.visible = visible;
							if (node.material){
								node.material.opacity = settings.opacity;
							}
						});
						if (visible){
							if (settings.zones.surfaces){
								mapSpheres(brainObject[matter][part][hemisphere]);
							} else{
								unMapSpheres(brainObject[matter][part][hemisphere]);
							}
						}
					}
				});
			});
		});
	};
	//	Draw spheres
	var spheres = [];
	var drawZones = function(){
		//	Delete previous spheres
		if (spheres){
			$.each(spheres, function(s, sphere){
				scene.remove(sphere);
				sphere.geometry.dispose();
				sphere.material.dispose();
				// sphere.texture.dispose();
			});
		}
		spheres = [];
		//	Options
		var left = settings.hemispheres.left;
		var right = settings.hemispheres.right;
		//	Represent points as spheres
		if (settings.zones.centers){
			var radius = settings.points.radius;
			var opacity = settings.points.opacity;
			var sphereGeometry = new THREE.SphereGeometry(radius, 16, 16);
			for (var g=0; g<groups.number; g++){
				var group = groups.list[g];
				var rgb = Raphael.hsb2rgb(group.hue, 0.8, 0.5);
				var color = new THREE.Color();
				color.r = rgb.r / 255;
				color.g = rgb.g / 255;
				color.b = rgb.b / 255;
				var sphereMaterial = new THREE.MeshLambertMaterial({color:color, transparent:true, emissive:color, opacity:0.75, overdraw: true});
				$.each(group.points, function(p, point){
					if ((point.x>-radius || left)  &&  (point.x<+radius || right)){
						var sphere = new THREE.Mesh(sphereGeometry, sphereMaterial);
						sphere.position.x = point.x; 
						sphere.position.y = point.y;
						sphere.position.z = point.z;
						sphere.traverse(function(node){
							 if (node.material){
								node.material.opacity = opacity * point.value;
								node.material.transparent = true;
							}
						});
						scene.add(sphere);
						spheres.push(sphere);
					}
				});
			}
		}
	};
	//	Initialize
	var init = function(){
		//	Dimensions stuff
		mouseX = mouseY = 0;
		windowX = view.width();
		windowY = view.height();
		windowHalfX = windowX / 2;
		windowHalfY = windowY / 2;
		var container = view.empty().get(0);
		camera = new THREE.PerspectiveCamera(45, 1, 1, 1000);
		scene = new THREE.Scene();			
		//	Lights
		directionalLight1 = new THREE.DirectionalLight(0xFFFFFF);
		scene.add(directionalLight1);
		directionalLight2 = new THREE.DirectionalLight(0xFFFFFF);
		scene.add(directionalLight2);
		//	Camera
		camera.position.x = distanceFromCamera;
		camera.position.y = 0;
		camera.position.z = 0;
		camera.aspect = windowX / windowY;
		camera.updateProjectionMatrix();
		camera.up = new THREE.Vector3(0, 0, 1);
		//	Renderer
		try {
			renderer = new THREE.WebGLRenderer({preserveDrawingBuffer: true});
		} catch (e) {
			try {
				renderer = new THREE.CanvasRenderer({preserveDrawingBuffer: true});
			} catch (e) {
				view.html
				(	'<div style="padding:16px"><h3>Error Detected!</h3><br/>For Mac Safari users, WebGL is disabled by Default (the Windows version of Safari does not yet support WebGL). To enable it you should follow these instructions:<br/><br/>'
				+	'(1) Open Safari and in the Safari menu select Preferences<br/>'
				+	'(2) Click Advanced tab in the Preferences window<br/>'
				+	'(3) At the bottom of the window check the "Show Develop menu in menu bar" checkbox<br/>'
				+	'(4) Open the Develop menu in the menu bar and select Enable WebGL<br/><br/>'
				+	'Please let us know if you have any questions.</div>'
				);
				return container;
			}
		}
		renderer.setSize(windowX, windowY);
		container.appendChild(renderer.domElement);
		//	Draw objects
		drawBrain();
		drawZones();
		//	Axis
		var showAxis = function(axisLength){
			function v(x,y,z){ 
				return new THREE.Vector3(x,y,z); 
			}
			function drawLine(p1, p2, color){
				var line, lineGeometry = new THREE.Geometry(),
				lineMat = new THREE.LineBasicMaterial({color: color, lineWidth: 1, opacity:0.5});
				lineGeometry.vertices.push(p1, p2);
				line = new THREE.Line(lineGeometry, lineMat);
				scene.add(line);
			}
			drawLine(v(-axisLength, 0, 0), v(axisLength, 0, 0), 0x000000);
			drawLine(v(0, -axisLength, 0), v(0, axisLength, 0), 0x000000);
			drawLine(v(0, 0, -axisLength), v(0, 0, axisLength), 0x000000);
		};
		showAxis(1000);
		//	Controls
		controls = new THREE.TrackballControls(camera, container.parentNode);394
	};
	var render = function(){
		controls.update();
		directionalLight1.position.set(camera.position.x, camera.position.y, camera.position.z).normalize();
		directionalLight2.position.set(-camera.position.x, -camera.position.y, -camera.position.z).normalize();
		renderer.render(scene, camera);
	};
	var animate = function(){
		requestAnimationFrame(animate);
		render();
	};
	container.off('resize').resize(function(e){
		var windowX = container.width();
		var windowY = container.height();
		view.width(windowX).height(windowY);
		windowHalfX = windowX / 2;
		windowHalfY = windowY / 2;
		camera.aspect = windowX / windowY;
		camera.updateProjectionMatrix();
		renderer.setSize(windowX, windowY);
		e.preventDefault();
		e.stopPropagation()
		return false;
	});
	
	//	
	if (!repaint){
		drawBrain();
		drawZones();
		return container.removeClass('loading');
	}
	init();
	animate();
	
	
	//
	//	Make a menu
	//
	
	controlsMenu.menu
	({	'Cortex surface':
		[	{	title:		'Opacity'
			,	type:		'slider'
			,	context:	settings
			,	min:		0
			,	max:		1
			,	key:		'opacity'
			,	action:		function(){
					drawBrain();
				}
			}
		,	{	type:		'separator'
			}
		,	{	title:		'Left hemisphere'
			,	type:		'bool'
			,	context:	settings.hemispheres
			,	key:		'left'
			,	action:		function(){
					drawBrain();
					drawZones();
				}
			}
		,	{	title:		'Right hemisphere'
			,	type:		'bool'
			,	context:	settings.hemispheres
			,	key:		'right'
			,	action:		function(){
					drawBrain();
					drawZones();
				}
			}
		,	{	type:		'separator'
			}
		,	{	title:		'White matter'
			,	type:		'bool'
			,	context:	settings.matters
			,	key:		'white'
			,	action:		function(){
					drawBrain();
					drawZones();
				}
			}
		,	{	title:		'Gray matter'
			,	type:		'bool'
			,	context:	settings.matters
			,	key:		'gray'
			,	action:		function(){
					drawBrain();
					drawZones();
				}
			}
		]
	,	'Representative points':
		[	{	title:		'Spheres'
			,	type:		'bool'
			,	context:	settings.zones
			,	key:		'centers'
			,	action:		function(){
					drawZones();
				}
			}
		,	{	title:		'Spheres projections on cortex'
			,	type:		'bool'
			,	context:	settings.zones
			,	key:		'surfaces'
			,	action:		function(){
					drawBrain();
				}
			}
		,	{	title:		'Original volumes'
			,	type:		'bool'
			,	context:	settings.zones
			,	key:		'volumes'
			,	action:		function(){
					drawZones();
				}
			}
		]
	,	'Standard views':
		[	{	title:		'Lateral (left)'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(-distanceFromCamera, 0, 0);
					camera.up = new THREE.Vector3(0, 0, 1);
					camera.updateProjectionMatrix();
				}
			}
		,	{	title:		'Lateral (right)'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(+distanceFromCamera, 0, 0);
					camera.up = new THREE.Vector3(0, 0, 1);
					camera.updateProjectionMatrix();
				}
			}
		,	{	title:		'Rostral'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(0, +distanceFromCamera, 0);
					camera.up = new THREE.Vector3(0, 0, 1);
					camera.updateProjectionMatrix();
				}
			}
		,	{	title:		'Caudal'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(0, -distanceFromCamera, 0);
					camera.up = new THREE.Vector3(0, 0, 1);
					camera.updateProjectionMatrix();
				}
			}
		,	{	title:		'Dorsal'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(0, 0, +distanceFromCamera);
					camera.up = new THREE.Vector3(0, 1, 0);
					camera.updateProjectionMatrix();
				}
			}
		,	{	title:		'Ventral'
			,	type:		'click'
			,	action:		function(){
					controls.target = new THREE.Vector3(0, 0, 0);
					camera.position = new THREE.Vector3(0, 0, -distanceFromCamera);
					camera.up = new THREE.Vector3(0, 1, 0);
					camera.updateProjectionMatrix();
				}
			}
		]
	,	'Spheres representation':
		[	{	title:		'Radius'
			,	type:		'slider'
			,	context:	settings.points
			,	key:		'radius'
			,	min:		0.1
			,	max:		9.9
			,	action:		function(){
					drawZones();
					drawBrain();
				}
			}
		,	{	title:		'Opacity'
			,	type:		'slider'
			,	context:	settings.points
			,	min:		0
			,	max:		1
			,	key:		'opacity'
			,	action:		function(){
					drawZones();
				}
			}
		,	
		]
	});
	
	
	return container.removeClass('loading');
};

$.fn.hue = function(){
	$(this).each(function(){
		if (this.nodeName.toLowerCase() == 'input'){
			var input = $(this);
			var width = input.outerWidth();
			var height = input.outerHeight();
			var container = $('<div>').addClass('input').insertBefore(input).width(width).height(height);
			var paper = Raphael(container.get(0), width, height);
			var gradientString = '0';
			for (var hue=0; hue<1; hue+=1/6){
				gradientString += '-hsb(' + hue + ',0.8,0.8):' + (100*hue);
			}
			gradientString += '-hsb(1, 0.8, 0.8):100';
			paper.rect(0, 0.25*height, width, 0.5*height).attr({stroke: 0, fill: gradientString});
			var hue = parseFloat(input.val());
			var line = paper.rect(Math.round(hue*width), 0, 1, height).attr({stroke: 0, fill: 'hsb('+hue+',0.8,0.8)'});
			container.mousedown(function(e){
				var x = e.pageX - container.offset().left;
				var hue = x / container.width();
				input.val(hue);
				line.attr
				({	x		:	Math.round(x)
				,	fill	:	'hsb('+hue+',0.8,0.8)'
				})
			});
			input.hide();
		}
	});
};




//	Resizing the window affects the elements size
$('.frameset').frameset();
$('.query').resize(function(e){
	if (!e.isTrigger){
		return false;
	}
	console.log('Resize query');
	var query = $(this);
	var title = query.find('h1');
	var frameset = query.find('div.frameset');
	//	Update height
	frameset.width(
		query.width()
	).height(
		query.height() - title.outerHeight(true)
	).resize();
	//	Allow children resizing events
	frameset.resize().find('.frame .container').resize();
	return false;
});
$(window).resize(function(){
	console.log('Resize window');
	var query = $('.query');
	var screen = $(window);
	//	Calculate query size
	var height = screen.height() - query.outerHeight(true) + query.height();
	var width = screen.width() - query.outerWidth(true) + query.width();
	var fontSize = (Math.sqrt(width * height) / 80);
	//	Apply
	query.height(height).width(width).css({fontSize : fontSize+'px'}).resize();
	return false;
}).resize();


//	Define the behaviour for every frame

$('.frame.groups .container').groups(query.groups);

$('div.frame.view').each(function(){
	var frame = $(this);
	var title = frame.find('.title');
	var container = frame.find('div.container');
	var buttons = frame.find('div.title button');
	var controls;
	buttons.click(function(){
		buttons.removeClass('selected');
		$(this).addClass('selected');
		container['view' + $(this).text()](query.groups, query.settings.view, true);
		controls = container.find('.controls');
	}).each(function(){
		var button = $(this);
		if (button.text() == query.settings.view.type){
			button.click();
		}
	});
	frame.resize(function(e){
		container.width(
			frame.outerWidth()
		).height(
			frame.outerHeight() - title.outerHeight()
		).resize();
		controls.width(
			title.outerWidth() + controls.width() - controls.outerWidth()
		);
		e.preventDefault();
		e.stopPropagation();
		return false;
	}).resize();
});

$('div.frame.graph').resize(function(){
	$(this).find('.container').graph(query.graph, query.settings.graph);
	return false;
}).resize();



//	Fullscreen mode
/*
	http://stackoverflow.com/questions/12400912/exit-from-full-screen-not-working
	http://stackoverflow.com/questions/8546896/ios-javascript-video-webkitcancelfullscreen-does-not-work
	document.webkitCancelFullScreen
*/
if (document.body.mozRequestFullScreen){
	$.fullscreen = function(){
		document.body.mozRequestFullScreen();
	};
} else if (document.body.webkitRequestFullScreen){
	$.fullscreen = function(){
		document.body.webkitRequestFullScreen();
	};
} else if (document.body.msRequestFullScreen){
	$.fullscreen = function(){
		document.body.msRequestFullScreen();
	};
} else if (document.body.requestFullScreen){
	$.fullscreen = function(){
		document.body.requestFullScreen();
	};
} else if (typeof(window.ActiveXObject) != 'undefined'){
	$.fullscreen = function(){
		var wScript = new ActiveXObject("WScript.Shell");
		if (wScript != null) {
			wScript.SendKeys("{F11}");
		}
	};
}
var div = $('<div>').title('Fullscreen').prependTo('.query>h1').append(
	$('<img>').attr({src:'/icons.png'})
).click(function(){
	$.fullscreen();
	return false;
});


//	Sidebar menu
$('<ul>').appendTo('.query').sidebar
([	{	"title"			:	"New"
	,	"description"	:	"Create an empty query"
	,	"icon"			:	0
	,	"action"		:	function(){
			alert('');
		}
	}
,	{	"title"			:	"Duplicate"
	,	"description"	:	"Copy this query into a new one"
	,	"icon"			:	1
	,	"action"		:	function(){
			alert('');
		}
	}
,	{	"title"			:	"Open"
	,	"description"	:	"Load an existing query from your collection"
	,	"icon"			:	2
	,	"action"		:	function(){
			var div = $('<div>').addClass('container');
			var ul = $('<ul>').appendTo(div);
			var w = $.window(
			{	title	:	'Load a query'
			,	width	:	'75%'
			,	height	:	'75%'
			,	html	:	div
			});
			
			div.height(
				w.height() - w.find('h1').outerHeight(true)
			).addClass('loading');
			
			$.mySend
			({	name:		'queries'
			,	callback:	function(response){
					div.empty();
					var table = $('<table>').appendTo(div);
					var tbody = $('<tbody>').appendTo(table);
					var timestamp = (new Date()).getTime();
					$.each(response, function(q, query){
						var tr = $('<tr>').appendTo(tbody).addClass(q%2 ? 'even' : 'odd');
						var td;
						//	Title
						$('<td>').text(query.title).appendTo(tr);
						//	Date
						$('<td>').text($.dateUS(1000 * query.lastUpdateTime)).appendTo(tr);
						//	Groups
						td = $('<td>').appendTo(tr);
						$.each(query.groups, function(g, group){
							$('<span>').title(group.title).addClass('disc')
							 .css({background: Raphael.hsb(group.hue, 0.8, 0.8)}).appendTo(td);
						});
					});
					div.removeClass('loading');
				}
			});
		}
	}
,	{	"title"			:	"PDF"
	,	"description"	:	"Save this query on your computer in the PDF format"
	,	"icon"			:	4
	,	"action"		:	function(){
			alert('');
		}
	}
,	{	"title"			:	"Help!"
	,	"description"	:	"How to use this application?"
	,	"icon"			:	5
	,	"action"		:	function(){
			alert('');
		}
	}
]);