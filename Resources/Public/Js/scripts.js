$(function () {

	$('.rte').each( function () {
		
		var $me = $(this);
		
		$me.summernote({
			lang: 'de-DE',
			height: 300,
			styleWithSpan: false,
			toolbar: [
				//[groupname, [button list]]
				['style', ['bold', 'ul', 'link']]
			],
			onpaste: function () {
				setTimeout(function () {
					var txt = $('<div>'+$me.code()+'</div>');
					var whitelist = 'p,ul,li,b,strong,a';
					txt.find('*').not(whitelist).each(function() {
						var content = $(this).html();
						console.log(content);
						$(this).replaceWith(content);
					});
					txt.find(whitelist).each(function () {
						$(this).removeAttr('class').removeAttr('style');
					});
					$me.code( txt );
				}, 100);
			}
		});
		
	});


	$('.form-media, .media-delete-btn').click( function () {
		$(this).find('input').val('');
		$(this).hide();
		update_filenames();
	});
	
	$('.nnfesubmit form').submit( function () {
		$(this).find('.rte').each( function () {
			$(this).html( $(this).code() );
		});
	});
		
	$('.fileupload-box .filename').click( function () {
		$(this).closest('.fileupload-box').find('input[type="file"]').click();
	});
	
	
	$('.fileupload input[type="file"]').change( function () {
		update_filenames();
	});
	
	function update_filenames () {
		$('.fileupload input[type="file"]').each( function () {
			var $input = $(this);
			var $box = $input.closest('.fileupload-box');
			var $label = $box.find('.filename .text');
			var origName = $box.data().name;
			var filename = $.trim($input.val().split('/').pop().split('\\').pop());
			if (!filename) filename = $('#field-'+origName).val();
			if (!filename) filename = $label.data().placeholder;
			$label.text(filename.substr(0,30) + (filename.length > 30 ? '...' : ''));					
		});
	}
	
	update_filenames();
	

});