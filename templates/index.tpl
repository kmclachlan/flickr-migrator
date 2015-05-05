{include file='common/header.inc.tpl'}

<div id="sidepanel">
	<h1>Flickr Migratr</h1>
	
	{if !$FLICKR_LOGGEDIN}
		<div id="step-flickr">
			<h3>Step 1 <small> Log in to Flickr</small></h3>

			<a href="flickr.php" class="btn btn-lg btn-danger btn-block" id="flickr-login">Login to Flickr</a>
			{*<a href="#" class="btn btn-lg btn-success btn-block disabled hidden" id="flickr-login-complete">Done! <i class="fa fa-check"></i></a>*}
		</div>
	{else}
		<div id="step-500px">
			<h3>Step 2 <small>Log in to 500px</small></h3>

			<a href="500px.php?do=authorize" class="btn btn-lg btn-primary btn-block" id="500px-login">Login to 500px</a>
			{*<a href="#" class="btn btn-lg btn-success btn-block disabled hidden" id="500px-login-complete">Done! <i class="fa fa-check"></i></a>*}
		</div>
		
		<div id="step-migrate" style="display:none">
			<h3>Step 3 <small>Migrate Photos</small></h3>
			<a class="btn btn-lg btn-success btn-block" id="btn-migrate-photos">Migrate Photos!</a>
		</div>
	{/if}
</div>

{if $FLICKR_LOGGEDIN}
	<div id="main" style="display:none">
		<div id="migration-process" style="display:none">
			<div class="progress" style="height:40px">
				<div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%;">
					
				</div>
			</div>
			
			<h4 id="process-text"></h4>
			<div id="photowindow">
				<img src="blank.png" style="max-height:500px;" />
			</div>
		</div>
	
		<div id="flickr-photo-selector">
			<div class="well" id="photostream-controls">
				<button class="btn btn-default btn-checkall" data-check="true">Check All</button>
				<button class="btn btn-default btn-checkall" data-check="false">Uncheck All</button> <br />
			</div>
			
			<ul id="flickr-photostream" class="clearfix">
		</div>
		</ul>
		<div id="500px-photos"></div>
	</div>
{/if}

<script type="text/javascript">
var totalPhotos = 0;
var migratedPhotos = 0;
var photoData = {};

function migratePhoto() {
	$photo = $('#flickr-photostream li.photo:first');
	if ($photo.size() == 0) {
		// @todo we're done!
		alert('All done!');
	} else {
		photo = photoData[$photo.data('id')];
		$('#photowindow img').attr('src', photo.url_m);
		$photo.fadeOut(500);
		$('#process-text').text('Fetching photo metadata');
		
		/*
		@todo get the tags here instead of from photostream call so we have the raw tag value
		$.getJSON('flickr.php?do=get_photo_metadata&id=' + $photo.data('id'), function(data) {
			
		});
		*/
		
		$('#process-text').text('Uploading Photo to 500px');
		
		$.get('500px.php?do=upload&id=' + $photo.data('id'), photo, function(response) {
			if (response == 'ok') {
				$('#flickr-photostream li.photo:first').remove();

				migratedPhotos++;

				$('#migration-process .progress-bar').width((migratedPhotos / totalPhotos * 100) + '%');

		 		migratePhoto();
			} else {
				alert('Something happened! Aborting\n' + response);
			}
		});
	}
}
		
$(function () {
	{if $FLICKR_LOGGEDIN}
		$('#step-flickr').hide();
		{if $500PX_LOGGEDIN}
			$('#step-500px').hide();
			$('#step-migrate').show();
			$('#main').show();
			
			lastYear = 9999;
			$.getJSON('flickr.php?do=get_photostream', function(data) {
				$.each(data.photos, function(id, photo) {
					year = new Date(photo.date_uploaded * 1000).getFullYear();
					if (year != lastYear) {
						$('#flickr-photostream').append('<li class="separator">' + year + '</li>');
						lastYear = year;
					}
					//$('#flickr-photostream').append('<li data-id="' + id + '"><input type="checkbox" checked><img src="' + url + '" /></li>');
					$('#flickr-photostream').append('<li class="photo" data-id="' + id + '"><span><i class="fa fa-check"></i></span><img src="' + photo.url_t + '" /></li>');
					
					photoData[id] = photo;
				});
			});
		{/if}
	{/if}

	/*$(document).on('click', '#flickr-photostream li input', function(event) {
		$(this).parents('li').toggleClass('unchecked');
	});*/
	
	function updateMigrateButton() {
		count = $('#flickr-photostream li').not('.unchecked').size();
		if (count > 0) {
			$('#btn-migrate-photos').removeClass('disabled').text('Migrate ' + count + ' Photos!');
		} else {
			$('#btn-migrate-photos').addClass('disabled').text('Select Photos');
		}
	}
	
	$('.btn-checkall').click(function() {
		checkall = ($(this).data('check')) ? true : false;
		
		$('#flickr-photostream li').each(function() {
			if (checkall) {
				$(this).removeClass('unchecked');
			} else {
				$(this).addClass('unchecked');
			}
		});
		
		updateMigrateButton();
	});
	$(document).on('click', '#flickr-photostream li', function(event) {
		$(this).toggleClass('unchecked');
		updateMigrateButton();
	});
	
	$('#btn-migrate-photos').click(function() {
		$('#flickr-photostream li.unchecked').fadeOut(500, function() { $(this).remove(); });
		$('#photostream-controls').fadeOut(500);
		$('#flickr-photostream li span').fadeOut(500);
		$('#flickr-photostream').width(1000000000);
		$('#sidepanel').animate({ marginLeft:-350 }, 500);
		$('#main').animate({ marginLeft:0 }, 500, function() {
			$('#migration-process').fadeIn(500);
			totalPhotos = $('#flickr-photostream li').not('.unchecked').size();
			setTimeout(function() { migratePhoto(); }, 1000);
		});
	});

	{*
	_500px.init({
		sdk_key: 'f3eed0432bdd45c041993a33f803afd625902d4b'
	});

	// When the user logs in we will pull their favorite photos
	_500px.on('authorization_obtained', function () {
		
		$('#step-500px').hide();
		$('#step-migrate').show();
		$('#main').show();
		
		$.getJSON('flickr.php?do=get_photostream', function(data) {
			$.each(data.photos, function(id, photo) {
				//$('#flickr-photostream').append('<li data-id="' + id + '"><input type="checkbox" checked><img src="' + url + '" /></li>');
				$('#flickr-photostream').append('<li data-id="' + id + '"><span><i class="fa fa-check"></i></span><img src="' + photo.url_t + '" /></li>');
				
				photoData[id] = photo;
			});
		});
		
		/*// Get my user id
		_500px.api('/users', function (response) {
			var me = response.data.user;

			// Get my favorites
			_500px.api('/photos', { feature: 'user_favorites', user_id: me.id }, function (response) {
				if (response.data.photos.length == 0) {
					alert('You have no favorite photos.');
				} else {
					$.each(response.data.photos, function () {
						$('#500px-photos').append('<img src="' + this.image_url + '" />');
					});
				}
			});
		});*/
	});

	_500px.on('logout', function () {
		$('#not_logged_in').show();
		$('#logged_in').hide();
		$('#logged_in').html('');
	});

	// If the user has already logged in & authorized your application, this will fire an 'authorization_obtained' event
	_500px.getAuthorizationStatus();

	// If the user clicks the login link, log them in
	$('#500px-login').click(_500px.login);*}
});
</script>

{include file='common/footer.inc.tpl'}