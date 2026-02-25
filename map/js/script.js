$(function() {
	if(typeof(window.parent.hide_layout) != 'undefined') {
		setTimeout(function() {window.parent.hide_layout();},200);
	}

	$('.typo-effect').each(function() {
		var items = $(this).attr('title');
		$(this).empty().attr('title', '').teletype({
			text:$.map(items.split( ';' ), $.trim),
			typeDelay:10,
			backDelay:20,
			cursor: '', 
			delay:3000,
			preserve: false,
			prefix: '',
			loop:0
		});
	});
});