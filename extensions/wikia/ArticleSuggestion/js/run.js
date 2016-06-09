/*global require*/
require([
	'jquery',
	'wikia.window'
], function ($, win) {
	'use strict';

	var slotName = 'WikiaArticleSuggestion',
	    $footer  = $('#WikiaFooter'),
	    $slot	 = $('<section></section>').attr('id', slotName),
		$h2 = $('<h3 class="suggestions-header">Similar Articles</h3>'),
		$ol = $('<ol></ol>'),
	    data = win.articlesuggestions.context.data,
		defaultImgPath = 'http://fandom.wikia.com/wp-content/themes/upstream/dist/svg/logo-fandom-tagline.svg';

	data.forEach(function(pageInfo) {
		var imgPath = pageInfo[0] || defaultImgPath,
			url = pageInfo[1],
			title = pageInfo[2];

		$ol.append($('<li class="suggestions-item"><img src="' + imgPath + '"><div class="suggestions-title"><a href="' + url + '" >' + title + '</a></div></li>'));
	});

	$slot.append($h2);
	$slot.append($ol);

	$footer.prepend($slot);
});
