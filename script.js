String.prototype.repeat = function(num){
    return new Array(num + 1).join(this);
}
Array.prototype.has = function(value) {
    if (value instanceof Array){
		var n = value.length;
		for (var i=0; i<n; i++){
			if (!this.has(value[i])){
				return false;
			}
		}
		return true;
	} else{
		var n = this.length;
		for (var i=0; i<n; i++) {
			if(this[i] == value) {
				return true;
			}
		}
		return false;
	}
};

Raphael.fn.line = function(startX, startY, endX, endY){
	return this.path('M' + startX + ' ' + startY + ' L' + endX + ' ' + endY);
};
Raphael.fn.toJSON = function(callback){
	var data;
	var elements = new Array();
	var paper = this;

	for ( var el = paper.bottom; el != null; el = el.next ) {
		data = callback ? callback(el, new Object) : new Object;

		if (data){
			elements.push
			({
			data:      data,
			type:      el.type,
			attrs:     el.attrs,
			transform: el.matrix.toTransformString(),
			id:        el.id
			});
		}
	}

	return elements;
};
Raphael.fn.fromJSON = function(json, callback) {
	var el;
	var paper = this;

	if ( typeof json === 'string' ) json = JSON.parse(json);

	for ( var i in json ) {
		if ( json.hasOwnProperty(i) ) {
			el = paper[json[i].type]()
				.attr(json[i].attrs)
				.transform(json[i].transform);

			el.id = json[i].id;

			if ( callback ) el = callback(el, json[i].data);

			if ( el ) paper.set().push(el);
		}
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
$.dateUS = function(year, month, day, hours, minutes, seconds, milliseconds){
	var date;
	if (typeof(year) == 'object'){
		date = year;
	} else if (typeof(month) == 'undefined'){
		date = new Date(year);
	} else{
		date = new Date(year, month, day, hours, minutes, seconds, milliseconds);
	}
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

$.rgb2hsl = function(r, g, b){
    r /= 255, g /= 255, b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, l = (max + min) / 2;
    if(max == min){
        h = s = 0; // achromatic
    }else{
        var d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch(max){
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }
    return [h, s, l];
};
$.hsv2rgb = function(h, s, v) {
	var r, g, b;
	var i;
	var f, p, q, t;
 
	// Make sure our arguments stay in-range
	h = Math.max(0, Math.min(360, h));
	s = Math.max(0, Math.min(100, s));
	v = Math.max(0, Math.min(100, v));
 
	// We accept saturation and value arguments from 0 to 100 because that's
	// how Photoshop represents those values. Internally, however, the
	// saturation and value are calculated from a range of 0 to 1. We make
	// That conversion here.
	s /= 100;
	v /= 100;
 
	if(s == 0) {
		// Achromatic (grey)
		r = g = b = v;
		return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
	}
 
	h /= 60; // sector 0 to 5
	i = Math.floor(h);
	f = h - i; // factorial part of h
	p = v * (1 - s);
	q = v * (1 - s * f);
	t = v * (1 - s * (1 - f));
 
	switch(i) {
		case 0:
			r = v;
			g = t;
			b = p;
			break;
 
		case 1:
			r = q;
			g = v;
			b = p;
			break;
 
		case 2:
			r = p;
			g = v;
			b = t;
			break;
 
		case 3:
			r = p;
			g = q;
			b = v;
			break;
 
		case 4:
			r = t;
			g = p;
			b = v;
			break;
 
		default: // case 5:
			r = v;
			g = p;
			b = q;
	}
 
	return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
};
$.ajaxSetup({
	headers	:	{"X-Requested-With": "XMLHttpRequest"}
});
$.loadJSON = function(url, noCache){
	var json = $.ajax
	({	url		:	('/' + url + '.json')
	,	type	:	'GET'
	,	async	:	false
	,	cache	:	(noCache ? false : true)
    ,	success	:	function(response){
						json = response;
					}
    }).responseText;
	return $.parseJSON(json);
};
$.redirect = function(url){
	location.href = (typeof(url) == 'object')  ?  url.url  :  url;
};
$.random = function(min, max){
	return min + Math.round((max - min) * Math.random());
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

$.fn.menu = function(menu){
	var mainUL = $(this).empty();
	var zIndex = 0;
	$.each(menu, function(subMenuTitle, subMenu){
		var li = $('<li>').prependTo(mainUL).css({zIndex: zIndex++});
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

$.fn.reverse = [].reverse;
$.fn.raphael = function(width, height){
	var container = $(this);
	if (container.length){
		var id = container.attr('id');
		if (!id){
			id = 'id-' + (new Date()).getTime() + '-' + Math.round(1e10 * Math.random());
			container.attr('id', id);
		}
		width = width ? width : container.width();
		height = height ? height : container.height();
		var paper = Raphael(id, width, height);
		return paper;
	} else{
		return undefined;
	}
};
$.fn.niceForms = function(){
	//	Titles for input & textarea
	this.find('input:text,input:password,textarea').blur(function(){
		if (!this.value){
			$(this).addClass('empty').val(this.title);
		}
	}).focus(function(){
		if (this.value == this.title){
			$(this).removeClass('empty').removeClass('wrong').val('');		
		}
	}).change(function(){
		$(this).removeClass('wrong');
	}).blur();
	//  Email fields
    this.find('input.email').change(function(){
        var input = $(this);
        var value = input.val();
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (!re.test(value)) {
            input.addClass('wrong');
        }
    });
    //	Auto-resizing textarea
	this.find('textarea').filter(function(){
			return $(this).css('resize') != 'none';
		}).autosize();
	//	Star ranking
	this.find('input.niveau').each(function(){
		var names = ['faible', 'moyen', 'fort'];
		var input = $(this);
		var label = input.parent();
		var text = label.text();
		var inputName = input.attr('name');
		var inputValue = input.val();
		label.empty();
		$('<input type="hidden" />').attr('name', inputName).val(inputValue).appendTo(label);
		$.each(names, function(i, name){
			$('<button/>').html('&#9733;').addClass('star').prependTo(label).attr('name', name).click(function(){
				var label = $(this).parent();
				var buttons = label.find('button');
				var b = buttons.index(this);
				buttons.each(function(i, button){
					if (i >= b){
						$(this).addClass('on', 50);
					} else{
						$(this).removeClass('on', 150);
					}
				});
				label.find('input[type=hidden]').val(this.name);
				return false;
			});
		});
		label.find('button').eq(names.indexOf(inputValue)).click();
		label.append(text);
		label.click(function(){
			return false;
		});
	});
	//	Calendar
	this.find('input.date').datepicker({
		dateFormat		:	'dd/mm/yy'
	,	yearRange		:	'1950:' + ((new Date()).getFullYear() - 15)
	,	monthNames		:	['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
	,	monthNamesShort	:	['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
	,	dayNames		:	['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
	,	dayNamesMin		:	['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa']
	,	prevText		:	'Précédent'
	,	nextText		:	'Suivant'
	,	changeYear		:	true
	,	changeMonth		:	true
	,	gotoCurrent		:	true
	});
	//	Number
	this.find('input.number').change(function(){
		var value = this.value.replace(/[^\d]+/g, '');
		this.value = (value == '') ? 0 : parseInt(value);
	});
	//	Focus on filled fields
	this.find('input:text,input:password,textarea').each(function(){
		var value = this.value;
		if (value != this.title  &&  value != ''){
			$(this).focus();
			return false;
		}
	});
	//	On/Off buttons
	this.find('button.on,button.off').click(function(){
		var button = $(this);
		if (button.hasClass('on')){
			button.removeClass('on').addClass('off')
				.text('Off');
		} else{
			button.removeClass('off').addClass('on')
				.text('On');
		}
	});
	//	Color buttons
	var hue = Math.round(360 * Math.random());
	var emptyColors = this.find('input.color[value=]');
	emptyColors.each(function(){
		hue = Math.round(hue + 360 / emptyColors.length) % 360;
		var rgbColor = $.hsv2rgb(hue, 100, 100);
		var hexColor = 0x010000 * rgbColor[0] + 0x000100 * rgbColor[1] + 0x000001 * rgbColor[2];
		var stringColor = hexColor.toString(16);
		stringColor = '#' + '0'.repeat(6 - stringColor.length) + stringColor;
		this.value = stringColor;
	});
	this.find('input.color').each(function(){
		$(this).miniColors();
	});
	//	Done!
	return this;
};

$.window = function(options){
	//	Windows container
	var windows = $('.windows').remove();
	windows = $('<div/>').addClass('windows').appendTo('body');
	if (!options.blocking){
		$('<button/>').text('x').addClass('close').appendTo(w).attr('title', 'Close this window').click(function(){
			windows.remove();
		});
		windows.click(function(e){
			if ($(e.target).hasClass('windows')){
				windows.remove();
				return false;
			}
			return true;
		});
		$(document).keyup(function(e){
			if (e.which == 27){
				windows.remove();
			}
		});
	}
	//	Window frame
	var w = $('<div/>').addClass('window').appendTo(windows);
	if (options.name){
		w.addClass(options.name);
	}
	//	Contents
	if (options.html){
		w.append(options.html);
	}
	//	Title
	if (options.title){
		$('<h1>').text(options.title).prependTo(w);
	}
	//	Window size
	if (!options.width  ||  options.width == 'fit'){
		options.width = w.outerWidth();
	} else {
		w.width(options.width);
	}
	//	Window height
	if (!options.height  ||  options.height == 'fit'){
		options.height = w.outerHeight();
	} else{
		w.height(options.height);
	}
	//	Window position
	w.css(
	{	left	:	Math.round(($(window).width() - w.outerWidth(true)) / 2) + 'px'
	,	top		:	Math.round(($(window).height() - w.outerHeight(true)) / 2) + 'px'
	});
	//	Return the resulting container
	return w;
};
$.log = function(o){
	console.log(o);
};


//	Ajax
$.mySend = function(options){
	var post = {};
	if (options.name){
		post.form = options.name;
	}
	if (options.data){
		post.data = $.toJSON(options.data);
	}
	if (options.source){
		options.source = $(options.source);
		options.source.stop().animate({opacity: 0.5}, 50);
	}
	var url = options.url ? options.url : '';
	//
	if (options.synchronous === true){
		var response = $.ajax
		({	type	:	'POST'
		,	url		:	url
		,	data	:	post
		,	async	:	false
		});
		(function(response){
			if (options.source){
				options.source.stop().animate({opacity: 1}, 100);
			}
			response = $.parseJSON(response);
			if ($.isFunction(options.callback)){
				options.callback(response);
			} else{
				if (response){
					$.each(response, function(){
						var object = this.selector ? $(this.selector) : $;
						if (this.argument != null){
							object[this.method](this.argument);
						} else{
							object[this.method]();
						}
					});
				}
			}
		})(response);
	} else{
		$.post(url, post, function(response){
			if (options.source){
				options.source.stop().animate({opacity: 1}, 100);
			}
			response = $.parseJSON(response);
			if ($.isFunction(options.callback)){
				options.callback(response);
			} else{
				$.each(response, function(){
					var object = this.selector ? $(this.selector) : $;
					if (this.argument != null){
						object[this.method](this.argument);
					} else{
						object[this.method]();
					}
				});
			}
		}, 'text');
	}
};

//	Formulaires
$(document).niceForms();
$('form[method=get] select').change(function(){
	for (var element = $(this); true; element = element.parent()){
		if (element.get(0).nodeName == 'FORM'){
			element.submit();
			return false;
		}
	}
});
$('form[method=get]').submit(function(){
	$(this).find('input,textarea').each(function(){
		if (this.value == this.title){
			this.value = '';
		}
	});
	return true;
});
$('form[method=post]').submit(function(){
	var form = $(this);
	var data = {};
	form.find('.empty').val('');
	$.each(form.serializeArray(), function(){
		if (match = /^(.+)\[\]$/.exec(this.name)){
			if (!data[match[1]]){
				data[match[1]] = [];
			}
			data[match[1]].push(this.value);
		} else{
			data[this.name] = this.value;
		}
	});
	form.find('.empty').addClass('wrong').blur();
	if (form.find('.wrong').length == 0){
		$.mySend
		({	data	:	data
		,	name	:	form.attr('name')
		,	source	:	form
		});
	}
	return false;
});
$('.template button').click(function(){
	var fieldset = $(this).parent();
	var form = fieldset.parent();
	var div = $(this).next();
	fieldset
		.clone().insertAfter(fieldset).removeClass('template').niceForms()
		.find('button.add').remove();
	return false;
});

//	Session
session = new (function(){
	
	var session = this;
	var data = this.data = {};
	
	
	var init = function(){
		$.each(['u','s','e','r','t'], function(k, key){
			var re = new RegExp('\\b' + key + '\\s*\\=\\s*([^;]+)');
			var match;
			if (match = document.cookie.match(re)){
				var value = match[1].trim();
				if (value.match(/^\d+$/)){
					data[key] = parseInt(value);
				} else{
					data[key] = (decodeURIComponent ? decodeURIComponent : unescape)(value).replace(/\+/g, '%20');
				}
			} else{
				data[key] = false;
			}
		});
	}
	
	this.login = function(email, password, loginCallback){
		$.mySend
		({	name	:	'session'
		,	data	:	
			{	action	:	'login'
			,	email	:	email
			,	password:	password
			}
		,	callback:	function(response){
				init();
				if ($.isFunction(loginCallback)){
					loginCallback(response);
				}
			}
		});
	};
	this.create = function(email, password1, password2, createCallback){
		$.mySend
		({	name	:	'session'
		,	data	:	
			{	action	:	'create'
			,	email	:	email
			,	password1:	password1
			,	password2:	password2
			}
		,	callback:	function(response){
				init();
				if ($.isFunction(createCallback)){
					createCallback(response);
				}
			}
		});
	}
	this.logout = function(logoutCallback){
		$.mySend
		({	name	:	'session'
		,	data	:	
			{	action	:	'logout'
			}
		,	callback:	function(response){
				init();
				if ($.isFunction(logoutCallback)){
					logoutCallback(response);
				}
			}
		});
	};
	this.resetPassword = function(email, resetPasswordCallback){
		
		$.mySend
		({	name	:	'session'
		,	data	:	
			{	action	:	'resetPassword'
			,	email:		email
			}
		,	callback:	function(response){
				init();
				if ($.isFunction(resetPasswordCallback)){
					resetPasswordCallback(response);
				}
			}
		});
	};
	this.changePassword = function(oldPassword, newPassword1, newPassword2, changePasswordCallback){		
		$.mySend
		({	name	:	'session'
		,	data	:	
			{	action	:		'changePassword'
			,	oldPassword:	oldPassword
			,	newPassword1:	newPassword1
			,	newPassword2:	newPassword2
			}
		,	callback:	function(response){
				init();
				if ($.isFunction(changePasswordCallback)){
					changePasswordCallback(response);
				}
			}
		});
	};
	
	this.loginUI = function(UICallback){
	
		var container = $('<div>').addClass('container');
		var menu = $('<ul>').addClass('menu').appendTo(container);
		var contents = $('<div>').addClass('contents').appendTo(container);
		
		$.window
		({	title	:	'Connect with your account...'
		,	width	:	'80%'
		,	height	:	'60%'
		,	html	:	container
		});
		
		$('<li>').text('Login to your account').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on', action:'/blank', method:'post'});
			//	Add fields
			var inputEmail = $('<input type="text">').attr({name:'username',autocomplete:'on'});
			$('<label>').text('Email address:').prepend(inputEmail).appendTo(form);
			var inputPassword = $('<input type="password">').attr({name:'password',autocomplete:'on'});
			$('<label>').text('Password:').prepend(inputPassword).appendTo(form);
			//	Message 
			var message = $('<label>').appendTo(form).text(' ').addClass('warning').attr({opacity:0});
			//	Validation...
			$('<input type="submit">').appendTo(form).val('Login').addClass('button').click(function(e){
				contents.addClass('loading');
				//	Validate
				session.login(inputEmail.val(), inputPassword.val(), function(response){
					contents.removeClass('loading');
					if (response.status){
						if ($.isFunction(UICallback)){
							UICallback();
						}
						//	Nice window
						var w = $.window
						({	title	:	'You are now successfully authenticated!'
						});
						setTimeout(function(){
							w.parent().animate({opacity:0}, 1000, function(){
								w.parent().hide();
							});
						}, 1000);
						//	Update values
						init();
						//	Remember fields
						var iframe = $('<iframe>').width(1).height(1).css({display:'none'}).appendTo('body');
						console.log(iframe.get(0).contentWindow.document.body);
						var clonedForm = form.clone().appendTo(
							iframe.get(0).contentWindow.document.body
						).submit();
					} else{
						message.text(response.data).css({opacity:0}).animate({opacity:1}, 250);
					}
				});
				form.removeClass('loading');
				e.preventDefault();
				return false;
			});
			form.submit(function(e){
				e.preventDefault();
				return false;
			});
			inputEmail.focus();
		}).click();
		
		$('<li>').text('Create a new account').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on'});
			//	Add fields
			var inputEmail = $('<input type="text">');
			$('<label>').text('Email address:').prepend(inputEmail).appendTo(form);
			var inputPassword1 = $('<input type="password">');
			$('<label>').text('Password:').prepend(inputPassword1).appendTo(form);
			var inputPassword2 = $('<input type="password">');
			$('<label>').text('Confirm password:').prepend(inputPassword2).appendTo(form);
			//	Message 
			var message = $('<label>').appendTo(form).text(' ').addClass('warning').attr({opacity:0});
			//	Validation...
			$('<input type="submit">').appendTo(form).text('Create account').addClass('button').click(function(e){
				contents.addClass('loading');
				session.create(inputEmail.val(), inputPassword1.val(), inputPassword2.val(), function(response){
					contents.removeClass('loading');
					if (response.status){
						if ($.isFunction(UICallback)){
							UICallback();
						}
						var w = $.window
						({	title	:	'Your account has been successfully created!'
						});
						init();
						setTimeout(function(){
							w.parent().animate({opacity:0}, 1000, function(){
								w.parent().hide();
							});
						}, 1000);
					} else{
						message.text(response.data).css({opacity:0}).animate({opacity:1}, 250);
					}
				});
				form.removeClass('loading');
				e.preventDefault();
				return false;
			});
			inputEmail.focus();
		});
		
		$('<li>').text('Forgotten password').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on'});
			//	Add fields
			var inputEmail = $('<input>');
			$('<label>').text('Email address:').prepend(inputEmail).appendTo(form);
			//	Message 
			var message = $('<label>').appendTo(form).text(' ').addClass('warning').attr({opacity:0});
			$('<input type="submit">').appendTo(form).text('Send me a new password').addClass('button').click(function(e){
				contents.addClass('loading');
				session.resetPassword(inputEmail.val(), function(response){
					contents.removeClass('loading');
					if (response.status){
						var w = $.window
						({	title	:	'Your new password has been sent to you by email.'
						});
						init();
						setTimeout(function(){
							w.parent().animate({opacity:0}, 1000, function(){
								w.parent().hide();
							});
						}, 1000);
					} else{
						message.text(response.data).css({opacity:0}).animate({opacity:1}, 250);
					}
				});
				form.removeClass('loading');
				e.preventDefault();
				return false;
			});
			inputEmail.focus();
		});
	
	};
	this.accountUI = function(UICallback){
	
		var container = $('<div>').addClass('container');
		var menu = $('<ul>').addClass('menu').appendTo(container);
		var contents = $('<div>').addClass('contents').appendTo(container);
		
		$.window
		({	title	:	'Your account'
		,	width	:	'80%'
		,	height	:	'60%'
		,	html	:	container
		});
		
		$('<li>').text('Details').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on'});
			//	Add fields
			var inputEmail = $('<input type="text">').attr({disabled:true}).val(data.e);
			$('<label>').text('Email address:').prepend(inputEmail).appendTo(form);
			var date = new Date();
			date.setTime(1000 * data.t);
			var inputDate = $('<input type="text">').attr({disabled:true}).val(date.toLocaleString());
			$('<label>').text('Logged in since:').prepend(inputDate).appendTo(form);
		}).click();
		
		$('<li>').text('Change password').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on'});
			//	Add fields
			var inputOldPassword = $('<input type="password">');
			$('<label>').text('Old password:').prepend(inputOldPassword).appendTo(form);
			var inputNewPassword1 = $('<input type="password">');
			$('<label>').text('New password:').prepend(inputNewPassword1).appendTo(form);
			var inputNewPassword2 = $('<input type="password">');
			$('<label>').text('Confirm new password:').prepend(inputNewPassword2).appendTo(form);
			//	Message 
			var message = $('<label>').appendTo(form).text(' ').addClass('warning').attr({opacity:0});
			//	Validation...
			$('<input type="submit">').appendTo(form).text('Create account').addClass('button').click(function(e){
				contents.addClass('loading');
				session.changePassword(inputOldPassword.val(), inputNewPassword1.val(), inputNewPassword2.val(), function(response){
					contents.removeClass('loading');
					if (response.status){
						if ($.isFunction(UICallback)){
							UICallback();
						}
						var w = $.window
						({	title	:	'Your password has been updated with success!'
						});
						init();
						setTimeout(function(){
							w.parent().animate({opacity:0}, 1000, function(){
								w.parent().hide();
							});
						}, 1000);
					} else{
						message.text(response.data).css({opacity:0}).animate({opacity:1}, 250);
					}
				});
				form.removeClass('loading');
				e.preventDefault();
				return false;
			});
			inputOldPassword.focus();
		});
		
		$('<li>').text('Logout').appendTo(menu).click(function(){
			//	Containers stuff
			menu.children('li').removeClass('selected');
			$(this).addClass('selected');
			contents.empty();
			var form = $('<form>').appendTo(contents).attr({autocomplete:'on'});
			var label = $('<label>').appendTo(form).text('Click here to log out from your account:');
			//	Validation...
			$('<input type="submit">').appendTo(form).val('Logout').addClass('button').click(function(e){
				var w = $.window
				({	title	:	'Disconnecting...'
				,	blocking:	true
				});
				session.logout(function(){
					if ($.isFunction(UICallback)){
						UICallback();
					}
					w.parent().animate({opacity:0}, 1000, function(){
						w.parent().hide();
					});
				});
				e.preventDefault();
				return false;
			});
		});
	
	};
	
	this.UI = function(UICallback){		
		if (session.data.u){
			session.accountUI(UICallback);
		} else{
			session.loginUI(UICallback);
		}
	}
	
	init();
	
})();

