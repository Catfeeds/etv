mBuffer(function() {
(function floorLoader() {
	if($.browser.msie && $.browser.version == '6.0') {
		$('img[data-original]').each(function(i) {
            $(this).attr('src', $(this).data('original'))
        });
	return;
	}
    var domTime = new Date(), imgTime;
	function fl_1() {
		var floorEl = $('#floor_1');
		floorEl.find('img[data-original]').imgLoader(floorEl);
		floorEl.one('loaded', function() {
            // fl_2();
		    $('#mini_nav .fl-1').addClass('online');
		});
	}
	
	
	fl_1();
})();
});

