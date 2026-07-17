define(['jquery'], function($) {
    return {
        init: function() {
            const $zone = $('.local-exammanager-dropzone');
            const $input = $zone.find('input[type=file]');
            if (!$zone.length || !$input.length) return;
            $zone.on('dragover', function(e) { e.preventDefault(); e.stopPropagation(); $zone.addClass('dragover'); });
            $zone.on('dragleave drop', function(e) { e.preventDefault(); e.stopPropagation(); $zone.removeClass('dragover'); });
            $zone.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) $input[0].files = files;
            });
            $zone.on('click', function(e) {
                if (e.target.tagName.toLowerCase() !== 'input') $input.trigger('click');
            });
        }
    };
});