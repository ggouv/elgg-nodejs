
/**
 * Helper that return a compiled handlebar object
 * @param  {[type]}     template      Name of the template
 * @return {[type]}                   Compiled handlebar object
 */
elgg.handlebars = function(template) {
	return Handlebars.compile($.trim($('#'+template).html()));
};



/**
 * Return an unique identifier
 * @return {string}     unique token
 */
elgg.getToken = function() {
	return new Date().getTime() + (Math.random()+"").replace('0.', '_');
};



/**
 * helper to know if user is on the page or another tab/application.
 * Use HTML5 PageVisibilityAPI.
 * @return {[boolean]} false, window is hidden. Else, true : user is on elgg !
 */
elgg.provide('elgg.visibility');

elgg.visibility.active = false;

$(window)
.blur(function(){
	elgg.visibility.active = false;
	elgg.trigger_hook('elgg_visibility_change', 'hidden');
})
.focus(function(){
	elgg.visibility.active = true;
	elgg.trigger_hook('elgg_visibility_change', 'visible');
});
/*
// helper to get visibility property
elgg.visibility.getHiddenProp = function() {
	var prefixes = ['webkit','moz','ms','o'];

	// if 'hidden' is natively supported just return it
	if ('hidden' in document) return 'hidden';

	// otherwise loop over all the known prefixes until we find one
	for (var i = 0; i < prefixes.length; i++){
		if ((prefixes[i] + 'Hidden') in document) return prefixes[i] + 'Hidden';
	}

	// otherwise it's not supported
	return null;
};

// check if window is hidden.
elgg.visibility.isWindowHidden = function() {
	var prop = elgg.visibility.getHiddenProp();

	if (!prop) return false;
	return document[prop];
};

// trigger event when visibility change
elgg.visibility.change = function() {
	var prop = elgg.visibility.getHiddenProp();

	if (!prop) return false;
	$(document).bind(prop.replace(/[H|h]idden/,'') + 'visibilitychange', function(evt) {
		if (elgg.visibility.active) {
			elgg.trigger_hook('elgg_visibility_change', 'hidden');
		} else {
			elgg.trigger_hook('elgg_visibility_change', 'visible');
		}
	});
};
elgg.register_hook_handler('init', 'system', elgg.visibility.change);
/*
$([window, document]).focus(function(){
	console.log('focus');
}).blur(function(){
	console.log('hidden');
});*/



/**
 * Notification system in browser. Add count in favicon and play sound if window is hidden.
 */
elgg.notify = function() {
	var beep = $('#beep-audio')[0];

	if (!elgg.visibility.active) {
		beep.play();
		elgg.favicon.increase(true);
	} else if (window.console) {
		console.log('elgg.notify triggered ! Favicon and beep only work if window is hidden.');
	}
};



/**
 * Favicon notification
 */
elgg.provide('elgg.favicon');

elgg.favicon.change = function(href) {
	var $fav = $('#favicon'),
		clone = $fav.clone(true, true).attr('href', href);

	$fav.remove();
	$('head').append(clone);
};

elgg.favicon.notify = function(num, blink) {
	var blink = blink || false;
	if (num > 99) num = 99;

	var canvas = $('<canvas>')[0],
		ctx,
		img = new Image(),
		$fav = $('#favicon').data('num', num),
		favLink = $fav.data('original_favicon') ? $fav.data('original_favicon') : $fav.data('original_favicon', $fav.attr('href')).attr('href');

	if (canvas.getContext && num > 0) {
		canvas.height = canvas.width = 48;
		ctx = canvas.getContext('2d');
		img.src = favLink;

		img.onload = function () {
			// write image
			ctx.drawImage(this, 0, 0);

			// write circle //rounded rectangle
			ctx.fillStyle = '#F90';
			ctx.arc(24, 24, 16, 0, 2 * Math.PI, false);
			ctx.fill();
			//ctx.fillRect(num>9?0:8, 24, num>9?48:32, 24);

			// write num
			ctx.font = 'bold 26px "helvetica", sans-serif';
			ctx.textAlign = 'center';
			ctx.fillStyle = '#fff';
			if (num < 10) {
				ctx.fillText(num, 24, 33); // circle
			} else {
				ctx.fillText(num, 23, 33);
			}
			//ctx.fillText(num, 24, 45); // rect

			elgg.favicon.change(canvas.toDataURL('image/png'));

			if (blink) elgg.favicon.blink();
		};
	}
};

// clear favicon to restore original.
elgg.favicon.clear = function() {
	var $fav = $('#favicon'),
		favLink = $fav.data('original_favicon');

	$fav.removeData();
	elgg.favicon.change(favLink);
	clearInterval(elgg.favicon.interval);
};

// Increase count in favicon
elgg.favicon.increase = function(blink) {
	var num = $('#favicon').data('num') || 0;
	elgg.favicon.notify(num+1, blink);
};

// Blink favicon
elgg.favicon.blink = function(stop) {
	var stop = stop || false,
		$fav = $('#favicon');

	if (!stop && !$fav.data('blinked') && $fav.data('original_favicon')) {
		elgg.favicon.interval = setInterval(function() {
			var $fav = $('#favicon'),
				href = $fav.data('blinked', true).attr('href');

			if (/^d/.test(href)) {
				$fav.data('notify_favicon', href);
				elgg.favicon.change($fav.data('original_favicon'));
			} else {
				elgg.favicon.change($fav.data('notify_favicon'));
			}
		}, 800);
	} else if (stop) {
		$fav.removeData('blinked');
		elgg.favicon.change($fav.data('notify_favicon'));
		clearInterval(elgg.favicon.interval);
	}
};

// register hook that clear favicon when window return visible
elgg.register_hook_handler('elgg_visibility_change', 'visible', elgg.favicon.clear);

