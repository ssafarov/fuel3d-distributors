<div style="  margin: 30px 15px 0 0; background-color: white; padding: 20px; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
	<div id="box_status">
		<img src="/wp-content/themes/storefront/images/ajax-loader.gif"> Checking if box is authorized...
	</div>

	<br><br>
	<a href="https://www.box.com/api/oauth2/authorize?response_type=code&client_id=<?=apply_filters('get_custom_option', '', 'box_client_id')?>&redirect_uri=https://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>" class="button authorize-box" disabled>Authorize</a>
</div>

<script>
	(function($) {
		$(document).ready(function() {
			$.get('<?php echo plugins_url('fuel3d-distributors/get-media-downloads.php'); ?><?= isset($_GET['code']) ? '?' . $_GET['code'] : '' ?>', function(r) {
				if (r && typeof r == "object" && ! r.error) {
					$('#box_status').html('<span class="dashicons dashicons-yes"></span> Box seems to be authorized').css('color', 'green');
				} else {
					$('#box_status').html('<span class="dashicons dashicons-no"></span> Box doesn\'t seem to be authorized').css('color', 'red');
					$('.authorize-box').removeAttr('disabled');
				}
			});
			$('.authorize-box').click(function() {
				if ($(this).attr('disabled')) {
					return false;
				}
			});
		});
	})(jQuery);
</script>