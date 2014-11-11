var pointsetTypes =
[	{	title		:	'Presets'
	,	name		:	'presets'
	,	description	:	'Known mappings for cognitive tasks, genes and areas'
	,	html		:	'<ul class="tabs"><li class="tab"></li><li class="tab"></li><li class="tab"></li></ul><ul class="tabs-select"><li>Tasks</li><li>Genes</li><li>Areas</li></ul>'
	,	initalize	:	function(){
							var contents = $(this);
							var tabs = contents.find('ul.tabs li');
							var tabsSelect = contents.find('ul.tabs-select li');
							//	Tabs management
							tabsSelect.each(function(i){
								var tabSelect = $(this);
								console.log(tabSelect.text())
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
								var timer;
								input.keyup(function(){
									clearTimeout(timer);
									timer = setTimeout(function(){
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
									}, 500);
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
exportPdfSettings = 
{	groups:			true
,	correlations:	true
,	view:			true
,	viewSnapshots:	[]
,	graph:			true
,	graphSnapshot:	''
};


var distanceFromCamera = 300;
var standardViews =
[	{	title:		'Lateral (left)'
	,	position:	new THREE.Vector3(-distanceFromCamera, 0, 0)
	,	up:			new THREE.Vector3(0, 0, 1)
	}
,	{	title:		'Lateral (right)'
	,	position:	new THREE.Vector3(+distanceFromCamera, 0, 0)
	,	up:			new THREE.Vector3(0, 0, 1)
	}
,	{	title:		'Rostral'
	,	position:	new THREE.Vector3(0, +distanceFromCamera, 0)
	,	up:			new THREE.Vector3(0, 0, 1)
	}
,	{	title:		'Caudal'
	,	position:	new THREE.Vector3(0, -distanceFromCamera, 0)
	,	up:			new THREE.Vector3(0, 0, 1)
	}
,	{	title:		'Dorsal'
	,	position:	new THREE.Vector3(0, 0, +distanceFromCamera)
	,	up:			new THREE.Vector3(0, 1, 0)
	}
,	{	title:		'Ventral'
	,	position:	new THREE.Vector3(0, 0, -distanceFromCamera)
	,	up:			new THREE.Vector3(0, 1, 0)
	}
];



var query = new (function(selector){
	
	var query = this;
	var settings;
	var queryContainer = $(selector).first().empty().addClass('query');
	var queryTitle;
	var queryFrameset;
	
	queryData = undefined;
	var queryUpdate;

	//
	//	Client/server interaction
	//

	var querySend = function(action, data, dialogTitle){
		//	Entertain the user
		queryTitle.hide();
		queryFrameset.addClass('loading');
		var w = $.window
		({	title	:	(dialogTitle ? dialogTitle : 'Updating query data...')
		,	height	:	'25%'
		,	width	:	'50%'
		,	blocking:	true
		});
		//	Complete the data fields
		if (!data){
			data = {};
		}
		if (!data.queryId){
			data.queryId = queryData ? queryData.id : 0;
		}
		data.action = action;
		//	Send data to server
		$.mySend
		({	name	:	'query'
		,	data	:	data
		,	callback:	function(response){
				if (!response.id){
					queryNew();
				}
				queryData = response;
				location.hash = queryData.id;
				w.parent().remove();
				queryFrameset.removeClass('loading');
				queryTitle.show();
				queryUpdate();
			}
		});
	};
	var queryLoad = function(id){
		querySend('load', {queryId: id}, 'Loading query...');
	};
	var queryNew = function(){
		querySend('new', {}, 'Creating new query...');
	};
	var querySettings = function(synchronous){
		$.mySend
		({	name		:	'query'
		,	synchronous	:	(synchronous === true)
		,	data		:	
			{	queryId		:	queryData.id
			,	action		:	'settings'
			,	values		:	queryData.settings
			}
		});
	};
	var queryDelete = function(queryId){
		//	Delete query
		$.window
		({	title	:	'Deleting query...'
		,	height	:	'25%'
		,	width	:	'50%'
		,	blocking:	true
		});
		$.mySend
		({	name	:	'query'
		,	data	:
			{	action	:	'delete'
			,	queryId	:	queryId ? queryId : queryData.id
			}
		,	callback:	function(response){
				queryNew();
			}
		});
	};
	
	//
	//	Build the title
	//
	
	var titleInit = function(){
		queryTitle = $('<h1>').appendTo(queryContainer).click(function(){
			var newTitle;
			if (newTitle = prompt('Enter a new title for this query:', queryData.title)){
				querySend('update', {values:{title: newTitle}});
			}
		});
		$('<span>').appendTo(queryTitle);
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
		if ($.isFunction($.fullscreen)){
			var div = $('<div>').title('Fullscreen').prependTo(queryTitle).append(
				$('<img>').attr({src:'/icons.png'})
			).click(function(){
				$.fullscreen();
				return false;
			});
		}
	};
	var titleUpdate = function(){
		var span = queryTitle.find('span');
		if (!queryData.title){
			return;
		}
		var text = queryData.title.trim();
		var textDefault = 'Untitled query';
		text = text ? text : textDefault;
		span.text(text);
		if (text == textDefault){
			span.addClass('untitled');
		} else{
			span.removeClass('untitled');
		}
		$('title').text(text);
	};
	
	
	//	
	//	Build the frames
	//
	
	var frames = 
	{	"groups":
		{	"init":		function(){
				var frame = frames.groups;
				var frameTitle = frame.htmlTitle;
				$('<button>').text('-').appendTo(frameTitle);
				var input = $('<input>').appendTo(frameTitle);
				$('<button>').text('+').appendTo(frameTitle);
				$('<span>').text('groups').appendTo(frameTitle);
				//	Events
				var changeGroupNumber = function(number){
					if (number < 1  ||  number > 9  ||  number == queryData.groups.length){
						input.val(queryData.groups.length);
						return;
					}
					querySend('update', {values: {groups: number}});
				};
				input.change(function(){
					var number = $(this).val().replace(/[^\d]/g, '');
					number = (number == '') ? 0 : parseInt(number);
					changeGroupNumber(number);
				}).blur(function(){
					$(this).change();
				});
				frameTitle.children('button').click(function(){
					changeGroupNumber(queryData.groups.length + parseInt($(this).text() + '1'));
				});
			}
		,	"resize":	function(){
			}
		,	"update":	function(){
				var container = frames.groups.htmlContainer.empty();
				var groups = queryData.groups;
				if (!groups){
					return;
				}
				//	Title
				frames.groups.htmlTitle.children('input').val(queryData.groups.length);
				//	Events
				var deletePointsetUI = function(pointset){
					if (confirm('Are you sure you want to remove these points?')){
						querySend('delete', {pointsetId: pointset.id});
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
					var wContainer = $('<div>').addClass('container');
					//	Menu list
					var menu = $('<ul>').addClass('menu').appendTo(wContainer);
					$.each(pointsetTypes, function(){
						$('<li>').text(this.title).attr('title', this.description).appendTo(menu);
					});
					//	Contents
					var contents = $('<div>').addClass('contents loading').appendTo(wContainer);
					//	Float blocker
					$('<div>').addClass('clear').appendTo(wContainer);
					//	Window rendering
					var w = $.window(
					{	title	:	'Add points to "' + group.title + '"'
					,	width	:	'75%'
					,	height	:	'75%'
					,	html	:	wContainer
					});
					wContainer.height(
						w.height() - w.find('h1').outerHeight(true)
					).css({overflow:'visible'});
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
											querySend('insert',
											{	groupSubid:	group.subId
											,	type:		pointsetType.name
											,	data:		pointsetType.getData(contents)
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
					var form = $('<form>').css({boxShadow:''});
					label = $('<label>').appendTo(form).text('Title');
					$('<input type="text">').prependTo(label).val(group.title).attr({placeholder:'Title of this group', name: 'title'});
					label = $('<label>').appendTo(form).text('Description');
					$('<input type="text">').prependTo(label).val(group.description).attr({placeholder:'Short description for this group', name: 'description'});
					label = $('<label>').appendTo(form).text('Color');
					hueInput = $('<input type="text">').prependTo(label).val(group.hue).attr({name: 'hue'});
					label = $('<label>').appendTo(form);
					$('<input type="submit">').prependTo(label).addClass('button').val('Save changes').css({marginTop:'1em'}).click(function(){
						var groupProperties = {};
						form.find('input,select').each(function(){
							var field = $(this);
							var name = field.attr('name');
							if (name){
								groupProperties[name] = field.val();
							}
						});
						querySend('update', {groupSubid: group.subId, values: groupProperties});
						return false;
					});
					var w = $.window
					({	title	:	'Edit group settings'
					,	width	:	600
					,	html	:	form
					});
					hueInput.hue();
				};
				//	Building each group
				$.each(groups, function(g, group){
					//	Group's frame
					var dl = $('<dl>').appendTo(container);
					if (g >= groups.length){
						dl.hide();
					}
					//	Title bar
					var dt = $('<dt>').appendTo(dl);
					$('<button>').text('edit').attr({title: 'Click here to edit the settings for this group...'}).appendTo(dt).click(function(){
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
			}
		}
	,	"correlations":
		{	"init":		function(){
				var frameTitle = frames.correlations.htmlTitle;
				$('<span>').text('Correlation with ').appendTo(frameTitle);
				//	Create buttons
				$.each(['tasks','genes','areas'], function(){
					$('<button>').text(this).appendTo(frameTitle);
				});
				//	Add events to buttons
				var buttons = frameTitle.find('button');
				buttons.click(function(){
					if (queryData && queryData.settings){
						buttons.removeClass('selected');
						var type = $(this).addClass('selected').text().replace(/s$/, '');
						queryData.settings.correlation.type = type;
						setTimeout(function(){
							querySettings(true);
							querySend('refresh');
						}, 100);
					}
				});
			}
		,	"resize":	function(){
			}
		,	"update":	function(){
				//	Show the right type of correlation is selected
				frames.correlations.htmlTitle.find('button').each(function(){
					var button = $(this);
					if (button.text().replace(/s$/, '') == queryData.settings.correlation.type){
						button.addClass('selected');
					} else{
						button.removeClass('selected');
					}
				});
				//	Initialize stuff
				var groupColors = ['#888'];
				var groups = queryData.groups;
				if (!queryData){
					return;
				}
				var correlations = queryData.correlations;
				var frameContainer = frames.correlations.htmlContainer.empty();
				if (!groups || !correlations){
					frameContainer.empty();
					return;
				}
				var table = $('<table>').appendTo(frameContainer);
				var thead = $('<thead>').appendTo(table);
				var tbody = $('<tbody>').appendTo(table);
				//	Fill header
				var tr = $('<tr>').appendTo(thead);
				$('<th>').attr({colspan:2}).appendTo(tr);
				$('<th>').text('Overall').appendTo(tr);
				for (var g=0; g<groups.length; g++){
					var group = groups[g];
					var groupColor = Raphael.hsb(group.hue, 0.8, 0.8);
					groupColors.push(groupColor);
					$('<th>').text(group.title).css({color:groupColor}).appendTo(tr);
				}
				//	Fill body
				$.each(correlations, function(c, correlation){
					var tr = $('<tr>').appendTo(tbody).addClass(c%2 ? 'even' : 'odd');
					$('<td>').text(c + 1).appendTo(tr);
					$('<td>').text(correlation.title).appendTo(tr);
					$.each(correlation.scores, function(s, score){
						$('<td>').text(Math.round(100 * score))
						 .css({textAlign:'center', color:groupColors[s], fontWeight:'bold'}).appendTo(tr);
					});
				});
				//	The end!
				table.tablesorter({onSorted: function(){
					$(this).find('tbody tr').removeClass('even odd').each(function(i){
						$(this).addClass(i%2 ? 'even' : 'odd').find('td:first').text(i+1);									
					});
				}});
			}
		}
	,	"view":
		{	"init":		function(){
				var frame = frames.view;
				var frameTitle = frame.htmlTitle;
				frame.initialized = '';
				$('<span>').text('Brain mapping in ').appendTo(frameTitle);
				$.each(['2D','3D'], function(){
					$('<button>').text(this).appendTo(frameTitle);
				});
				frameTitle.find('button').click(function(){
					if (queryData && queryData.settings){
						var type = $(this).text();
						queryData.settings.view.type = type;
						frame.update();
						if (type == '3D'){
							frame.resize();
						}
						querySettings();
					}
				});
			}
		,	"init2D":	function(){
				var frame = frames.view;
				var container = frame.htmlContainer.empty();
				var settings = queryData.settings.view;
				var box = settings.box;
				frame.initialized = '2D';
				frame.html.css({backgroundColor:'#000'});
				$.each(['2D', '3D'], function(){
					delete frame['frame' + this];
				});
				frame.frame2D = {};
				//	Parameters initialization
				var view = $('<div>').addClass('view').appendTo(container);
				var controlsMenu = $('<ul>').addClass('controls').appendTo(container);
				var groups = queryData.groups;
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
				frame.frame2D.slices = slices;
				
				//	Adjust position of pictures...
				var position = settings.position;
				var movePictures = function(){
					var slices = frame.frame2D.slices
					$.each(slices, function(sliceName, slice){
						var c1 = sliceName.charAt(1);
						var c2 = sliceName.charAt(2);
						var top = (slice.image.height() - slice.wrapper.height()) * (position[c2] - box[c2].min) / box[c2].total;
						slice.image.css({top: -top + 'px'});
					});
				};
				//	...but before that, wait until they're loaded!
				var movePicturesWhenLoaded = function(){
					var unloadedPictures = 0;
					$.each(frame.frame2D.slices, function(sliceName, slice){
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
					$.each(frame.frame2D.slices, function(sliceName, slice){
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
						for (var g=0; g<groups.length; g++){
							var group = groups[g];
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
			}
		,	"init3D":	function(){
				var frame = frames.view;
				frame.initialized = '3D';
				frame.htmlContainer.empty();
				$.each(['2D', '3D'], function(){
					delete frame['frame' + this];
				});
				//
				var frame3D = frame['3D'];
				var settings = queryData.settings.view;
				frame.html.css({background: ''});
				frame.view3D = $('<div>').addClass('view').appendTo(frame.htmlContainer);
				frame3D.controlsMenu = $('<ul>').addClass('controls').appendTo(frame.htmlContainer);
				//	3D variables
				var camera, scene, renderer;
				var controls;
				var mouseX, mouseY;
				var windowX, windowY;
				var windowHalfX, windowHalfY;
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
					var groups = queryData.groups;
					var groupColors = [];
					for (var g=0; g<groups.length; g++){
						var groupRGBColor = Raphael.hsb2rgb(groups[g].hue, 1, 0.8);
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
						var groupIndex = groups.length;
						while (groupIndex--){
							var group = groups[groupIndex];
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
						var groups = queryData.groups;
						for (var g=0; g<groups.length; g++){
							var group = groups[g];
							var rgb = Raphael.hsb2rgb(group.hue, 0.8, 0.5);
							var color = new THREE.Color();
							color.r = rgb.r / 255;
							color.g = rgb.g / 255;
							color.b = rgb.b / 255;
							var sphereMaterial = new THREE.MeshLambertMaterial({color:color, transparent:true, emissive:color, opacity:0.75, overdraw: true});
							$.each(group.points, function(p, point){
								if ((point.x>-radius || left)  &&  (point.x<+radius || right)  &&  point.value){
									var sphereGeometry = new THREE.SphereGeometry(radius * (settings.points.byRadius ? point.value : 1), 16, 16);
									var sphere = new THREE.Mesh(sphereGeometry, sphereMaterial);
									sphere.position.x = point.x; 
									sphere.position.y = point.y;
									sphere.position.z = point.z;
									sphere.traverse(function(node){
										if (node.material){
											node.material.opacity = opacity * (settings.points.byOpacity ? point.value : 1);
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
					windowX = frame.view3D.width();
					windowY = frame.view3D.height();
					windowHalfX = windowX / 2;
					windowHalfY = windowY / 2;
					var container = frame.view3D.empty().get(0);
					//	Camera
					camera = new THREE.PerspectiveCamera(45, 1, 1, 1000);
					camera.position.x = distanceFromCamera;
					camera.position.y = 0;
					camera.position.z = 0;
					camera.aspect = windowX / windowY;
					camera.updateProjectionMatrix();
					camera.up = new THREE.Vector3(0, 0, 1);
					frame3D.camera = camera;
					//	Scene
					scene = new THREE.Scene();			
					frame3D.scene = scene;
					//	Lights
					directionalLight1 = new THREE.DirectionalLight(0xFFFFFF);
					scene.add(directionalLight1);
					directionalLight2 = new THREE.DirectionalLight(0xFFFFFF);
					scene.add(directionalLight2);
					//	Renderer
					try {
						renderer = new THREE.WebGLRenderer({preserveDrawingBuffer: true});
					} catch (e) {
						try {
							renderer = new THREE.CanvasRenderer({preserveDrawingBuffer: true});
						} catch (e) {
							frame.view3D.html
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
					frame3D.renderer = renderer;
					//	Draw objects
					frame3D.draw = function(){
						drawBrain();
						drawZones();
					};
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
					showAxis(2048);
					//	Controls
					controls = new THREE.TrackballControls(camera, container.parentNode);
					frame3D.controls = controls;
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
				//
				init();
				animate();
				//
				//	Make a menu
				//
				var menu =
				{	'Cortex surface':
					[	{	title:		'Opacity'
						,	type:		'slider'
						,	context:	settings
						,	min:		0
						,	max:		1
						,	key:		'opacity'
						,	action:		function(){
								drawBrain();
								querySettings();
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
								querySettings();
							}
						}
					,	{	title:		'Right hemisphere'
						,	type:		'bool'
						,	context:	settings.hemispheres
						,	key:		'right'
						,	action:		function(){
								drawBrain();
								drawZones();
								querySettings();
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
								querySettings();
							}
						}
					,	{	title:		'Gray matter'
						,	type:		'bool'
						,	context:	settings.matters
						,	key:		'gray'
						,	action:		function(){
								drawBrain();
								drawZones();
								querySettings();
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
								querySettings();
							}
						}
					,	{	title:		'Spheres projections on cortex'
						,	type:		'bool'
						,	context:	settings.zones
						,	key:		'surfaces'
						,	action:		function(){
								drawBrain();
								querySettings();
							}
						}
					,	{	title:		'Original volumes'
						,	type:		'bool'
						,	context:	settings.zones
						,	key:		'volumes'
						,	action:		function(){
								drawZones();
								querySettings();
							}
						}
					,	{	type:		'separator'
						}
					,	{	title:		'Intensity represented by radius'
						,	type:		'bool'
						,	context:	settings.points
						,	key:		'byRadius'
						,	action:		function(){
								drawZones();
								querySettings();
							}
						}
					,	{	title:		'Intensity represented by opacity'
						,	type:		'bool'
						,	context:	settings.points
						,	key:		'byOpacity'
						,	action:		function(){
								drawZones();
								querySettings();
							}
						}
					]
				,	'Standard views':
					[]
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
								querySettings();
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
								querySettings();
							}
						}
					,	
					]
				};
				$.each(standardViews, function(){
					var view = this;
					menu['Standard views'].push
					({	title:		view.title
					,	type:		'click'
					,	action:		function(){
							controls.target = new THREE.Vector3(0, 0, 0);
							$.each(['x','y','z'], function(i, c){
								camera.position[c] = view.position[c];
								camera.up[c] = view.up[c];
							});
							camera.updateProjectionMatrix();
						}
					});
				});
				frame3D.controlsMenu.menu(menu);
			}
		,	"resize2D":	function(){
				frames.view.init2D();
			}
		,	"resize3D":	function(windowX, windowY){
				var frame = frames.view;
				var frame3D = frame['3D'];
				var camera = frame3D.camera;
				var renderer = frame3D.renderer;
				if (!windowX){
					windowX = frame.htmlContainer.width();
				}
				if (!windowY){
					windowY = frame.htmlContainer.height();
				}
				frame.view3D.width(windowX).height(windowY);
				camera.aspect = windowX / windowY;
				camera.updateProjectionMatrix();
				renderer.setSize(windowX, windowY);
				frame3D.controlsMenu.css({width:frame.htmlTitle.outerWidth()})
			}
		,	"resize":	function(){
				if (!queryData){
					return;
				}
				var viewType = queryData.settings.view.type;
				var frame = frames.view;
				if (frame.initialized != viewType){
					frame[viewType] = {};
					frame['init' + viewType]();
					frame.initialized = viewType;
				}
				frames.view['resize' + viewType]();
			}
		,	"update2D":	function(){
				frames.view.init2D();
			}
		,	"update3D":	function(){
				var frame3D = frames.view['3D'];
				frame3D.draw();
			}
		,	"update":	function(){
				var viewType = queryData.settings.view.type;
				var frame = frames.view;
				//	Select the right type of visualization				
				frame.htmlTitle.find('button').each(function(){
					var button = $(this);
					if (button.text().replace(/s$/, '') == viewType){
						button.addClass('selected');
					} else{
						button.removeClass('selected');
					}
				});
				//	Initialize when necessary
				if (frame.initialized != viewType){
					frame[viewType] = {};
					frame['init' + viewType]();
					frame.initialized = viewType;
				}
				//	Update stuff
				frames.view['update' + viewType]();
			}
		}
	,	"graph":
		{	"init":		function(){
				$('<span>').text('Graph').appendTo(frames.graph.htmlTitle);
			}
		,	"resize":	function(width, height){
				frames.graph.update(width, height);
			}
		,	"update":	function(width, height){
				if (!queryData){
					return;
				}
				var settings = queryData.settings.graph;
				var container = frames.graph.htmlContainer;
				if (!queryData.graph){
					container.empty();
					return;
				}
				var threshold = settings.threshold;
				var nodes = queryData.graph.nodes;
				var edges = queryData.graph.edges;
				if (!nodes || !edges || !threshold){
					return;
				}
				container.removeClass('loading').empty();
				if (!width){
					width = container.width();
				}
				if (!height){
					height = container.height();
				}
				var paper = Raphael(container[0], width, height);
				var extrema = {xMin:+99,xMax:-99,yMin:+99,yMax:-99};
				frames.graph.paper = paper;
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
					,	stroke	:	'#000'
					,	'stroke-opacity'	:	0.5
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
			}
		}
	};
	
	var addRow = function(frameset, ratio){
		var row = $('<div>').addClass('row').appendTo(frameset);
		if (ratio){
			row.data('frameset-ratio', ratio);
		}
		return row;
	};
	var addFrame = function(row, name, ratio){
		var frame = frames[name];
		frame.html = $('<div>').addClass('frame').addClass(name).appendTo(row);
		frame.htmlTitle = $('<div>').addClass('title').appendTo(frame.html);
		frame.htmlContainer = $('<div>').addClass('container').appendTo(frame.html);
		frame.html.framesetResizeCallback(function(){
			frame.htmlContainer.height(
				frame.html.height() - frame.htmlTitle.outerHeight(true) - frame.htmlContainer.outerHeight(true) + frame.htmlContainer.height()
			);
			frame.resize();
		});
		if (ratio){
			frame.html.data('frameset-ratio', ratio);
		}
		frame.init();
	};
	
	var framesetInit = function(){
		//	Build the frameset
		queryFrameset = $('<div>').addClass('frameset').appendTo(queryContainer);
		var row;
		row = addRow(queryFrameset, 0.3);
		addFrame(row, 'groups', 0.5)
		addFrame(row, 'correlations', 0.5);
		row = addRow(queryFrameset, 0.7);
		addFrame(row, 'view', 0.5);
		addFrame(row, 'graph', 0.5);
		queryFrameset.frameset();
		var slimScrollOptions = {height:'auto', width:'auto', size:'1em', railVisible:true, railOpacity:0.5, railColor:'#888', color:'#000'};
		frames.groups.htmlContainer.slimScroll(slimScrollOptions);
		frames.correlations.htmlContainer.slimScroll(slimScrollOptions);
	};	

	//
	//	Exportation function
	//
	
	var exportPDF = function(){
		//	Show a window
		var div = $('<div>').addClass('container list');
		var w = $.window
		({	title	:	'Exporting to PDF'
		,	html	:	div
		,	width	:	'50%'
		,	height	:	'75%'
		});
		div.height(
			w.height() - w.find('h1').outerHeight(true)
		);
		//	Choices for user
		var pdfSections =
		[	{	title		:	'Groups'
			,	key			:	'groups'
			,	description	:	'If checked, the PDF will include a description of every group, along with all the point it contains.'
			}
		,	{	title		:	'Correlations'
			,	key			:	'correlations'
			,	description	:	'If checked, the PDF will include a table displaying the correlations between the groups and presets.'
			}
		,	{	title		:	'Graph'
			,	key			:	'graph'
			,	description	:	'If checked, the PDF will include a nodes graph showing the proximity between groups and presets.'
			}
		,	{	title		:	'View'
			,	key			:	'view'
			,	description	:	'If checked, the PDF will include the 3D representation of the brain, following the 6 standard views.'
			}
		];
		$('<p>').text('Check which sections you want to be displayed in the generated PDF:').appendTo(div);
		$.each(pdfSections, function(s, section){
			var label = $('<label>')
			 .text(section.title)
			 .addClass(s%2 ? 'odd' : 'even')
			 .attr({title: section.description})
			 .appendTo(div);
			var input = $('<input type="checkbox">')
			 .attr({name: section.key})
			 .prependTo(label);
			if (exportPdfSettings[section.key]){
				input.attr({checked: 'checked'});
			}
		});
		$('<button>').text('Generate PDF').appendTo(div).click(function(){
			//	Get query settings
			div.find(':checkbox').each(function(){
				exportPdfSettings[this.name] = $(this).prop('checked');
			});
			//	Initialize DOM
			div.empty();
			var ul = $('<ul>').addClass('list').appendTo(div);
			var li;
			var liNew = function(text){
				if (!text){
					text = '';
				}
				var l = ul.children('li').length;
				li = $('<li>').addClass(l%2 ? 'odd' : 'even').appendTo(ul);
				$('<b>').text(text).appendTo(li);
				$('<span>').text(' - ').css({color:'#888'}).appendTo(li);
			};
			var liUpdate = function(text){
				if (!text){
					text = '';
				}
				li.children('span').append(' ' + text);
			};
			//	Functions used
			var snapshotCreate, snapshotViewCallback, snapshotUploadCallback, snapshotGraph, snapshotGraphCallback, pdfCreate, pdfCreateCallback;
			snapshotViewCreate = function(){
				var frame = frames.view;
				var frame3D = frame['3D'];
				var snapshots = exportPdfSettings.viewSnapshots;
				var s = snapshots.length;
				if (exportPdfSettings.view  &&  standardViews.length > s){
					if (s == 0){
						frame.resize3D(256, 256);
					}
					var view = standardViews[s];
					liNew('3D view, ' + view.title);
					liUpdate('preparing view...');
					snapshots.push
					({	title	:	view.title
					,	path	:	''
					});
					frame3D.controls.target = new THREE.Vector3(0, 0, 0);
					frame3D.camera.position = view.position;
					frame3D.camera.up = view.up;
					frame3D.camera.updateProjectionMatrix();
					setTimeout(snapshotViewCallback, s ? 250 : 500);
				} else{
					frame.resize3D();
					if (exportPdfSettings.graph){
						snapshotGraph();
					} else{
						pdfCreate();
					}
				}
			};
			snapshotViewCallback = function(){
				var snapshots = exportPdfSettings.viewSnapshots;
				var s = snapshots.length - 1;
				var view = standardViews[s];
				var snapshot = snapshots[s];
				liUpdate('taking snapshot...');
				var png = frames.view['3D'].renderer.domElement.toDataURL('image/png');
				liUpdate('sending data...');
				$.mySend
				({	name	:	'pdf'
				,	callback:	snapshotViewUploadCallback
				,	data	:
					{	queryId	:	queryData.id
					,	action	:	'snapshot'
					,	png		:	png
					,	index	:	s
					}
				});
			};
			snapshotViewUploadCallback = function(response){
				var snapshots = exportPdfSettings.viewSnapshots;
				var s = snapshots.length - 1;
				var view = standardViews[s];
				var snapshot = snapshots[s];
				snapshot.path = response.path;
				liUpdate('done.');
				snapshotViewCreate();
			};
			snapshotGraph = function(){
				liNew('Graph');
				liUpdate('taking snapshot...');
				frames.graph.resize(1024, 1024);
				var svg = frames.graph.paper.toJSON();
				frames.graph.resize();
				liUpdate('sending data...');
				$.mySend
				({	name	:	'pdf'
				,	callback:	snapshotGraphCallback
				,	data	:
					{	queryId	:	queryData.id
					,	action	:	'graph'
					,	svg		:	svg
					}
				});
			};
			snapshotGraphCallback = function(response){
				liUpdate('done.');
				exportPdfSettings.graphSnapshot = response.path;
				pdfCreate();			
			};
			pdfCreate = function(){
				liNew('PDF');
				liUpdate('generating file...');
				$.mySend
				({	name	:	'pdf'
				,	callback:	pdfCreateCallback
				,	data	:
					{	queryId	:	queryData.id
					,	action	:	'pdf'
					,	settings:	exportPdfSettings
					}
				});
			};
			pdfCreateCallback = function(response){
				liUpdate('done.');
				$('<a>').addClass('button').text('Click here to view the generated PDF').attr
				({	href	:	'/data/cache/' + response.path
				,	target	:	'_blank'
				}).appendTo(div);
			};
			//	Start the process!
			exportPdfSettings.viewSnapshots = [];
			exportPdfSettings.graphSnapshot = '';
			snapshotViewCreate();
		});
	};
	
	//
	//	Build the sidebar
	//
	var sidebar =
	[	{	"title"			:	"New"
		,	"description"	:	"Create an empty query"
		,	"icon"			:	0
		,	"action"		:	queryNew
		}
	,	{	"title"			:	"Delete"
		,	"description"	:	"Delete this query"
		,	"icon"			:	1
		,	"action"		:	function(){
				if (confirm('Are you sure you want to delete this query?')){
					queryDelete();
				}
			}
		}
	,	{	"title"			:	"Open"
		,	"description"	:	"Load an existing query from your collection"
		,	"icon"			:	3
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
						if (!session.data.u){
							$('<p>').addClass('warning').html('You are not logged in.<br/><br/>To keep track of your queries, login or create an account!<br/><br/>Creating an account only takes a few seconds.').appendTo(div).css
							({	marginBottom	:	'2em'
							,	paddingBottom	:	'2em'
							,	borderBottom	:	'solid 1px #DDD'
							,	cursor			:	'pointer'
							}).click(function(){
								session.loginUI(function(){
									location.reload();
								});
							});
						}
						if (!response.length){
							$('<p>').text('You have no queries recorded for this session.').appendTo(div).css
							({	color		:	'#444'
							,	fontStyle	:	'italic'
							});
						}
						var table = $('<table>').appendTo(div);
						var tbody = $('<tbody>').appendTo(table);
						var timestamp = (new Date()).getTime();
						$.each(response, function(q, query){
							var tr = $('<tr>').appendTo(tbody).addClass(q%2 ? 'even' : 'odd').click(function(){
								queryLoad(query.id);
								return false;
							});
							var td;
							//	Title
							var td = $('<td>').appendTo(tr);
							$('<a>').attr('href', '#'+query.id).text(query.title).appendTo(td);
							//	Date
							$('<td>').text($.dateUS(1000 * query.lastUpdateTime)).appendTo(tr);
							//	Groups
							td = $('<td>').appendTo(tr);
							$.each(query.groups, function(g, group){
								$('<span>').text(group.title).addClass('group')
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
		,	"icon"			:	2
		,	"action"		:	exportPDF
		}
	,	{	"title"			:	"Feedback"
		,	"description"	:	"How can we make this application better for you?"
		,	"icon"			:	5
		,	"action"		:	function(){
				var container = $('<div>');
				var label;
				var w;
				
				$('<p>').text('What kind of feedback do you want to give us?').appendTo(container);
				
				label = $('<label>').text(' Bug report').appendTo(container);
				$('<input type="checkbox">').attr('name', 'bug').prependTo(label);
				$('<br>').appendTo(container);
				
				label = $('<label>').text(' Feature request').appendTo(container);
				$('<input type="checkbox">').attr('name', 'feature').prependTo(label);
				$('<br>').appendTo(container);
				
				label = $('<label>').text(' Other: ').appendTo(container);
				$('<input type="checkbox">').attr('name', 'other').prependTo(label);
				$('<input type="text">').attr('name', 'subject').insertAfter(label);
				$('<br>').appendTo(container);
				
				$('<br>').appendTo(container);
				$('<textarea>').attr({name:'message', placeholder: 'Type your message here'}).appendTo(container).css({width:'100%', border:'solid 1px #CCC', height:'8em', resize:'none'});
				
				$('<br>').appendTo(container);
				$('<br>').appendTo(container);
				label = $('<label>').text('How would you rate this application?').appendTo(container);
				var select = $('<select>').attr('name', 'rating').appendTo(label);
				$('<option>').val(0).appendTo(select);
				$.each(['Awful', 'Bad', 'OK', 'Good', 'Excellent'], function(i, name){
					$('<option>').val(i + 1).text((i+1) + ' - ' + name).appendTo(select);
				});
				$('<br>').appendTo(container);
				$('<br>').appendTo(container);
				
				$('<button>').text('Send').appendTo(container).click(function(){
					var data =
					{	types	:	[]
					,	subject	:	container.find('[name=subject]').val()
					,	message	:	container.find('[name=message]').val()
					,	rating	:	container.find('[name=rating]').val()
					};
					container.find('input:checkbox:checked').each(function(){
						data.types.push(this.name);
					});
					w.parent().remove();
					$.mySend
					({	name	:	'feedback'
					,	data	:	data
					});
				});
				
				w = $.window
				({	title	:	'Feedback'
				,	width	:	'50%'
				,	html	:	container
				});
			}
		}
	,	{	"title"			:	session.data.u ? "Account" : "Login"
		,	"description"	:	session.data.u ? "Manage my account" : "Login using your email address and password"
		,	"icon"			:	4
		,	"action"		:	function(li){
				session.UI(function(){
					var p = li.find('p');
					if (session.data.u){
						p.text('Account');
						li.attr({title: 'Manage my account'});
					} else{
						p.text('Login');
						li.attr({title: 'Login using your email address and password'});
						queryNew();
					}
				});
			}
		}
	];
	var sidebarInit = function(){
		var ul = $('<ul>').addClass('sidebar').appendTo(queryContainer);
		$.each(sidebar, function(i, item){
			var li = $('<li>').attr({title: item.description}).appendTo(ul);
			li.click(function(){
				item.action(li);
			});
			var div = $('<div>').appendTo(li);
			$('<img>').attr({src: '/icons.png', style: 'top:-'+(3*item.icon)+'em'}).appendTo(div);
			$('<p>').text(item.title).appendTo(li);
		});
		sidebarLoginIcon = ul.find('li p:contains(Log)').parent();
	};
	
	//
	//	Query events
	//
	
	var queryInit = function(){
		var $window = $(window);
		titleInit();
		framesetInit();
		sidebarInit();
		var windowResize = function(){
				// General stuff
			var width = $window.width();
			var height = $window.height();
				// Font size
			var fontSize = (Math.sqrt(width * height) / 80);
			$('body').css({fontSize: fontSize + 'px'});
				// Query container
			queryContainer.width(
				width + queryContainer.width() - queryContainer.outerWidth(true)
			).height(
				height + queryContainer.height() - queryContainer.outerHeight(true)
			);
				// Frameset
			queryFrameset.width(
				queryContainer.width() + queryFrameset.width() - queryFrameset.outerWidth(true)
			).height(
				queryContainer.height() + queryFrameset.height() - queryFrameset.outerHeight(true) - queryTitle.outerHeight(true)
			).framesetResize();
			return false;
		};
		windowResize();
		$window.resize(windowResize);
	};
	queryUpdate = function(){
		titleUpdate();
		$.each(frames, function(f, frame){
			frame.html.addClass('loading');
		});
		$.each(frames, function(f, frame){
			frame.update();
			frame.html.removeClass('loading');
		});
		$(window).resize();
		location.hash = '#' + queryData.id
	};
	
	//
	//	Get it all done!
	//
	
	queryInit();
	if (location.hash){
		queryLoad(location.hash.replace(/[^\d]+/g, ''));
	} else {
		queryNew();
	}
	
})('#contents');