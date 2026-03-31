jQuery(document).ready(function($) {
	var $panel = $('#Lukic-notices-panel');
	var $container = $('#Lukic-notices-container');
	var $badge = $('.Lukic-notice-count');
	var $noNotices = $('.Lukic-no-notices');
	var count = 0;
	var observer;

	if ($container.length === 0) {
		return;
	}

	var noticeSelectors = [
		'.notice-error',
		'.notice-warning',
		'.notice-success',
		'.notice-info',
		'.update-nag',
		'.updated',
		'.error',
		'.is-dismissible',
		'.notice',
		'#message'
	];

	var selectorString = noticeSelectors.join(', ');

	function processNotices() {
		if (observer) {
			observer.disconnect();
		}

		var $notices = $(selectorString).not('#Lukic-notices-panel *');

		if ($notices.length > 0) {
			$notices.each(function() {
				var $notice = $(this);

				if ($.trim($notice.text()) === '') {
					return;
				}

				var type = 'info';
				if ($notice.hasClass('notice-success') || $notice.hasClass('updated')) {
					type = 'success';
				}
				if ($notice.hasClass('notice-warning')) {
					type = 'warning';
				}
				if ($notice.hasClass('notice-error') || $notice.hasClass('error')) {
					type = 'error';
				}

				var $wrapper = $('<div class="lukic-notice-wrapper ' + type + '"></div>');
				var $content = $notice.detach();

				$content.removeClass('is-dismissible notice updated error update-nag notice-error notice-warning notice-success notice-info');
				$content.removeAttr('style');

				$wrapper.append($content);
				$container.append($wrapper);
				count++;
			});

			updateBadge();
		}

		if (observer) {
			observer.observe(document.body, { childList: true, subtree: true });
		}
	}

	function updateBadge() {
		$badge.text(count);
		if (count > 0) {
			$badge.removeClass('zero');
			$noNotices.hide();
		} else {
			$badge.addClass('zero');
			$noNotices.show();
		}
	}

	processNotices();

	observer = new MutationObserver(function(mutations) {
		var shouldProcess = false;
		mutations.forEach(function(mutation) {
			if ($(mutation.target).closest('#Lukic-notices-panel').length > 0) {
				return;
			}

			if (mutation.addedNodes.length) {
				shouldProcess = true;
			}
		});

		if (shouldProcess) {
			processNotices();
		}
	});

	observer.observe(document.body, { childList: true, subtree: true });

	$('.Lukic-toggle-notices').on('click', function(e) {
		e.preventDefault();
		$panel.toggleClass('is-open');
	});

	$('.Lukic-close-notices').on('click', function() {
		$panel.removeClass('is-open');
	});

	$(document).on('click', function(e) {
		if (!$(e.target).closest('#Lukic-notices-panel, .Lukic-toggle-notices').length) {
			$panel.removeClass('is-open');
		}
	});
});
