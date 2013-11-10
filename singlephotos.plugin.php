<?php
class SinglePhotos extends Plugin
{
	public function action_plugin_activation( $plugin_file )
	{
		Post::add_new_type( 'photo' );
	}

	public function action_plugin_deactivation( $plugin_file )
	{
		Post::deactivate_post_type( 'photo' );
	}
	
	/*
	 * Hook when the publish form is displayed: Display our image
	 */
	public function action_form_publish( $form, $post )
	{
		if( $form->content_type->value == Post::type( 'photo' ) ) {
			$singlephoto = $form->append('hidden', 'singlephoto');
			$singlephoto->value = (isset($post->info->singlephoto)) ? $post->info->singlephoto : '';
			$singlephoto->id = 'singlephoto';
			if(isset($post->info->singlephoto) && !empty($post->info->singlephoto)) {
				$asset = Media::get($post->info->singlephoto);
				$image = '<img class="container" id="photopreview" style="display:block;" src="' . $asset->url . '" alt="' . basename($asset->url) . '">';
			}
			else {
				$image = '<img class="container" id="photopreview" style="display:block;" src="" alt="empty">';
			}
			$imagepreview = $form->insert('content', 'static', 'imagepreview', $image);
			$script = <<< CAPTION_SCRIPT
<script type="text/javascript">
function change_photo(fileindex, fileobj) {
	$("#photopreview").attr("src", fileobj.url);
	$("#singlephoto").val(fileobj.path);
}
$(function(){
	$.extend(habari.media.output.image_jpeg, {
		insert_image: change_photo
	});
	$.extend(habari.media.output.image_png, {
		insert_image: change_photo
	});
	$.extend(habari.media.output.image_gif, {
		insert_image: change_photo
	});
	$.extend(habari.media.output.flickr, {
		embed_photo: change_photo
	});
});
</script>
CAPTION_SCRIPT;
			$form->append('static', 'singlephotojs', $script);
		}
	}
	
	/**
	 * Save our data to the database on post publish form submit
	 */
	public function action_publish_post( $post, $form )
	{
		if ($post->content_type == Post::type('photo')) {
			$post->info->singlephoto = $form->singlephoto->value;
		}
	}
	
	/*
	 * Make usable URL available through $post->singlephoto
	 */
	public function filter_post_singlephoto($singlephoto, $post)
	{
		return str_replace(" ", "%20", Media::get($post->info->singlephoto)->url);
	}
	
	/**
	 * Make usable thumb URL available through $post->singlephoto_thumb
	 */
	public function filter_post_singlephoto_thumb($thumb, $post)
	{
		$url = Media::get($post->info->singlephoto)->url;
		$url = dirname($url) . "/thumbs/" . basename($url);
		return str_replace(" ", "%20", $url);
	}
	
	/**
	 * Add photosets to the output (0.9 method)
	 */
	public function filter_template_user_filters( $filters ) 
	{
		// Cater for the home page which uses presets as of d918a831
		if ( isset( $filters['preset'] ) ) {
			if(isset($filters['content_type'])) {
				$filters['content_type'] = Utils::single_array( $filters['content_type'] );
			}
			$filters['content_type'][] = Post::type( 'photo' );
			$filters['content_type'][] = Post::type( 'entry' );
		} else {		
			// Cater for other pages like /page/1 which don't use presets yet
			if ( isset( $filters['content_type'] ) ) {
				$filters['content_type'] = Utils::single_array( $filters['content_type'] );
				$filters['content_type'][] = Post::type( 'photo' );
			}
		}
		return $filters;
	}
}
?>