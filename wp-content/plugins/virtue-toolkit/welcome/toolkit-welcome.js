jQuery(document).ready(function ($) {
$('.kad-panel-left .nav-tab').click(function (event) {
		event.preventDefault();

		var panel = $(this);
		var active = $(this).closest('.nav-tab-wrapper').find('.nav-tab-active');
		var opencontent = $(this).closest('.kad-panel-contain').find('.nav-tab-content.panel_open');
		var contentid = $(this).data('tab-id');

		if (panel.hasClass('nav-tab-active')) {
			//leave
		} else {
			panel.addClass('nav-tab-active');
			active.removeClass('nav-tab-active');
			opencontent.removeClass('panel_open');
			$('#'+contentid).addClass('panel_open');	
		}

		return false;

	});
});