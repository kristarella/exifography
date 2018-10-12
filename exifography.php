<?php
/*
Plugin Name: Exifography
Plugin URI: http://kristarella.blog/exifography
Description: Displays EXIF data for images uploaded with WordPress and enables import of latitude and longitude EXIF to the database upon image upload.
Version: 1.3.1
Author URI: http://kristarella.blog
Author: kristarella
License: GPL2+
Text Domain: exifography
Domain Path: languages
*/

/*
	Exifography is Copyright 2011 Kristen Symonds

	Exifography is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	any later version.

	Exifography is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists("exifography")) {
	class exifography {
		var $exif_options = 'exifography_options';
		public $fields = array();
		public $html_options = array();

		public function __construct() {
			load_plugin_textdomain( 'exifography', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

			$this->fields = array(
				'aperture' => __('Aperture', 'exifography'),
				'credit' => __('Credit', 'exifography'),
				'camera' => __('Camera', 'exifography'),
				'caption' => __('Caption', 'exifography'),
				'created_timestamp' => __('Taken', 'exifography'),
				'copyright' => __('Copyright', 'exifography'),
				'exposure_bias' => __('Exposure bias', 'exifography'),
				'flash' => __('Flash fired', 'exifography'),
				'focal_length' => __('Focal length', 'exifography'),
				'iso' => __('ISO', 'exifography'),
				//'lens' => __('Lens', 'exifography'),
				'keywords' => __('Keywords', 'exifography'),
				'location' => __('Location', 'exifography'),
				'shutter_speed' => __('Shutter speed', 'exifography'),
				'title' => __('Title', 'exifography'),
			);
			$this->html_options = array(
				'before_block' => __('Before EXIF block', 'exifography'),
				'before_item' => __('Before EXIF item', 'exifography'),
				'after_item' => __('After EXIF item', 'exifography'),
				'after_block' => __('After EXIF block', 'exifography'),
				'sep' => __('Separator for EXIF label', 'exifography'),
			);

			register_activation_hook(__FILE__,array($this, 'activate'));
			$this->init();
		}

		private function init() {
			// actions
			add_action('admin_menu', array($this, 'admin_page'));
			add_action('admin_init', array($this, 'options_init'));
			add_action('add_meta_boxes', array($this, 'add_post_box'));
			add_action('save_post', array($this, 'save_postdata'));
			add_shortcode('exif',array($this, 'shortcode'));
			// filters
			add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'plugin_links'));
			add_filter('wp_read_image_metadata', array($this, 'add_exif'),'',3);
			add_filter( 'attachment_fields_to_edit', array($this,'shortcode_id'), 10, 2 );
			add_filter('the_content', array($this, 'auto_insert'),2);
		}

		// === ADD EXTRA EXIF TO DATABASE === //
		function add_exif($meta,$file,$sourceImageType) {
			if ( is_callable( 'iptcparse' ) ) {
				getimagesize( $file, $info );
				if ( ! empty( $info['APP13'] ) ) {
					$iptc = iptcparse( $info['APP13'] );
					if (!empty($iptc["2#025"]))
						$meta['keywords'] = implode(', ',$iptc["2#025"]);
				}
			}
			if ( is_callable('exif_read_data') && in_array($sourceImageType, apply_filters('wp_read_image_metadata_types', array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM)) ) ) {
				$exif = @exif_read_data( $file );
				if (!empty($exif['GPSLatitude']))
					$meta['latitude'] = $exif['GPSLatitude'] ;
				if (!empty($exif['GPSLatitudeRef']))
					$meta['latitude_ref'] = trim( $exif['GPSLatitudeRef'] );
				if (!empty($exif['GPSLongitude']))
					$meta['longitude'] = $exif['GPSLongitude'] ;
				if (!empty($exif['GPSLongitudeRef']))
					$meta['longitude_ref'] = trim( $exif['GPSLongitudeRef'] );
				if (!empty($exif['ExposureBiasValue']))
					$meta['exposure_bias'] = trim( $exif['ExposureBiasValue'] );
				if (!empty($exif['Flash']))
					$meta['flash'] = trim( $exif['Flash'] );
				/*if (!empty($exif['LensMake']))
					$meta['lens'] = trim( $exif['LensMake'] );
				if (!empty($exif['LensModel']))
					$meta['lens'] .= ' '.trim( $exif['LensModel'] );*/

			}
			array_walk($meta, function(&$value, $index){
				if (is_string($value))
					$value = sanitize_text_field($value);
			});
			return $meta;
		}

		//Returns an array of admin options
		function get_options() {
			$current_options = get_option($this->exif_options);
			if (!empty($current_options))
				return $current_options;
		}

		function activate() {
			$defaults = array(
				'before_block' => '<ul id="%s" class="exif">',
				'before_item' => '<li class="%s">',
				'after_item' => '</li>',
				'after_block' => '</ul>',
				'sep' => ': ',
				'timestamp' => 'j F, Y',
				'geo_zoom' => '2',
				'geo_width' => '100',
				'geo_height' => '100'
			);

			$old_options = get_option('thesography_options');
			$current_options = get_option($this->exif_options);
			if (!empty($old_options)) {
				$options = $defaults;
				foreach ($old_options as $key => $option)
					$options[$key] = $option;
				delete_option('thesography_options');
			}
			elseif (!empty($current_options)) {
				$options = $defaults;
				foreach ($current_options as $key => $option)
					$options[$key] = $option;
			}
			else
				$options = $defaults;
			update_option($this->exif_options, $options);

			if (!empty($options))
				return true;
			else
				wp_redirect(get_admin_url() . 'options-general.php?page=exifography');
		}

		// add settings link on plugin page
		function plugin_links($links) {
			$settings_link = '<a href="options-general.php?page=exifography">'.__('Settings').'</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}


		// === DISPLAY EXIF === //
		function img_meta($imgID=null) {
			global $post;
			if (!$imgID) {
				if (get_post_thumbnail_id($post->ID))
					$imgID = get_post_thumbnail_id($post->ID);
				else {
					$images = get_children(array(
						'post_parent' => $post->ID,
						'post_type' => 'attachment',
						'numberposts' => 1,
						'post_mime_type' => 'image',
						'orderby' => 'ID',
						'order' => 'ASC'
						));
					if ($images) {
						foreach ($images as $image) {
							$imgID = $image->ID;
						}
					}
				}
			}

			return wp_get_attachment_metadata($imgID);
		}
		// return geo exif in a nice form
		function geo_frac2dec($str) {
			@list( $n, $d ) = explode( '/', $str );
			if ( !empty($d) )
				return $n / $d;
			return $str;
		}
		function geo_pretty_fracs2dec($fracs) {
			return	$this->geo_frac2dec($fracs[0]) . '&deg; ' .
					$this->geo_frac2dec($fracs[1]) . '&prime; ' .
					$this->geo_frac2dec($fracs[2]) . '&Prime; ';
		}
		function geo_single_fracs2dec($fracs) {
			return	$this->geo_frac2dec($fracs[0]) +
					$this->geo_frac2dec($fracs[1]) / 60 +
					$this->geo_frac2dec($fracs[2]) / 3600;
		}
		function display_geo($imgID=null,$show=false) {
			$imgmeta = $this->img_meta($imgID);
			$options = $this->get_options();
			if (isset($imgmeta['image_meta']['latitude'])) {
				if ($imgmeta['image_meta']['latitude'])
					$latitude = $imgmeta['image_meta']['latitude'];
				if ($imgmeta['image_meta']['longitude'])
					$longitude = $imgmeta['image_meta']['longitude'];
				if ($imgmeta['image_meta']['latitude_ref'])
					$lat_ref = $imgmeta['image_meta']['latitude_ref'];
				if ($imgmeta['image_meta']['longitude_ref'])
					$lng_ref = $imgmeta['image_meta']['longitude_ref'];

				$lat = $this->geo_single_fracs2dec($latitude);
				$lng = $this->geo_single_fracs2dec($longitude);
				if ($lat_ref == 'S') { $neg_lat = '-'; } else { $neg_lat = ''; }
				if ($lng_ref == 'W') { $neg_lng = '-'; } else { $neg_lng = ''; }

				// all the formats we might want
				$geo_coords = $neg_lat . number_format($lat,6) . ',' . $neg_lng . number_format($lng, 6);
				$geo_pretty_coords = $this->geo_pretty_fracs2dec($latitude) . $lat_ref . ' ' . $this->geo_pretty_fracs2dec($longitude) . $lng_ref;
				$gmap_url = '//maps.google.com/maps?q=' . $geo_coords . '&ll=' . $geo_coords . '&z=11';
				$geo_key = !empty($options['geo_key']) ? '&key=' . $options['geo_key'] : '';
				$geo_img_url = '//maps.googleapis.com/maps/api/staticmap?zoom='.$options['geo_zoom'].'&size='.$options['geo_width'].'x'.$options['geo_height'].'&maptype=roadmap
&markers=color:blue%7Clabel:S%7C'.$geo_coords.'&sensor=false'.$geo_key;
				$geo_img_html = '<img src="'.$geo_img_url.'" alt="'.$geo_pretty_coords.'" title="'.$geo_pretty_coords.'" width="'.$options['geo_width'].'" height="'.$options['geo_height'].'" style="vertical-align:top;" />';

				// all the things you can manually output with this function
				if (false !== $show) {
					if ('dec' == $show)
						return $geo_coords;

					if ('coords' == $show)
						return $geo_pretty_coords;

					if ('url' == $show)
						return $gmap_url;

					if ('map' == $show)
						return $geo_img_url;
				} else { // the things automatically output in display_exif()

					if (array_key_exists('geo_link',$options) && array_key_exists('geo_img',$options))
						$show_geo = '<a href="'.$gmap_url.'">'.$geo_img_html.'</a>';

					elseif (array_key_exists('geo_img',$options) && !array_key_exists('geo_link',$options))
						$show_geo = $geo_img_html;

					elseif (array_key_exists('geo_link',$options) && !array_key_exists('geo_img',$options))
						$show_geo = '<a href="'.$gmap_url.'">'.$geo_pretty_coords.'</a>';

					else
						$show_geo = $geo_pretty_coords;

					return $show_geo;
				}

				return $show;
			}
		}
		function flash_fired($imgmeta) {
			if (isset($imgmeta['image_meta']['flash'])) {
				$value = $imgmeta['image_meta']['flash'];
				if ($value & 1)
					return __('yes','exifography');
				else
					return __('no','exifography');
			}
		}
		function pretty_shutter_speed($imgmeta) {
			if (isset($imgmeta['image_meta']['shutter_speed']) && $imgmeta['image_meta']['shutter_speed'] > 0) {
				if ((1 / $imgmeta['image_meta']['shutter_speed']) > 1) {
					$speed = "1/";
					if ((number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1)) == 1.3
					or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 1.5
					or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 1.6
					or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 2.5) {
						$speed .= number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1, '.', '') . "s";
					}
					else
						$speed .= number_format((1 / $imgmeta['image_meta']['shutter_speed']), 0, '.', '') . "s";
				}
				else
					$speed = $imgmeta['image_meta']['shutter_speed']."s";

				return $speed;
			}
		}


		// render exif data in posts
		public function display_exif($display=null,$imgID=null) {
			global $post;
			$options = $this->get_options();
			$post_options = get_post_meta($post->ID, '_use_exif', true);
			// use specified options
			if (!(is_null($display) || $display == '')) {
				if (isset($options['exif_fields']))
					$options['exif_fields'] = array();
				$user_defined = explode(',',$display);
				foreach ($user_defined as $field)
					$options['exif_fields'][] = $field;
			}
			// or use post options
			elseif ((is_null($display) || $display == '') && $post_options) {
				if (isset($options['exif_fields']))
					$options['exif_fields'] = array();
				$post_options = explode(',',$post_options);
				foreach ($post_options as $field)
					$options['exif_fields'][] = $field;
			}

			// in case there are thesograhy format options
			$old_fields = array(
				'time' => 'created_timestamp',
				'copy' => 'copyright',
				'focus' => 'focal_length',
				'shutter' => 'shutter_speed',
			);
			foreach ($old_fields as $key=>$value) {
				if (in_array($key,$options['exif_fields']))
					$options['exif_fields'][] = $value;
			}

			$imgmeta = $this->img_meta($imgID);
			if (!empty($imgmeta['image_meta'])) :

			$output = array();
			if (!empty($options['order']))
				$order = $options['order'];
			else
				$order = array_keys($this->fields);

			foreach ($order as $key) {
				$value = $this->fields[$key];
				if (empty($options['item_label']))
					$value = $value;
				else
					$value = '';
				if ( !(array_key_exists($key, $imgmeta['image_meta']) || $key == 'location' ) )
					continue;
				if (in_array($key,$options['exif_fields']) || $display == 'all') {
					if ($key == 'aperture' && !$imgmeta['image_meta'][$key] == 0)
						$exif = '&#402;/'.$imgmeta['image_meta'][$key];
					elseif ($key == 'created_timestamp' && !$imgmeta['image_meta'][$key] == 0)
						$exif = date_i18n($options['timestamp'],$imgmeta['image_meta']['created_timestamp']);
					elseif ($key == 'exposure_bias' && !$imgmeta['image_meta'][$key] == 0) {
					 	$exposure_bias_parts = explode("/", $imgmeta['image_meta'][$key]);
					 	if ($exposure_bias_parts[0] == "0")
					 		$exif = '';
					 	else {
					 		$float = intval($exposure_bias_parts[0]) / intval($exposure_bias_parts[1]);
					 		if (is_int($float))
					 			$exif = sprintf("%+d%s", $float, __('EV','exifography'));
					 		elseif ($float <= -1 || $float >= 1)
					 			$exif = sprintf("%+.1f%s", $float, __('EV','exifography'));
					 		else
					 			$exif = sprintf("%+d%s%d%s", intval($exposure_bias_parts[0]), "/", intval($exposure_bias_parts[1]), __('EV','exifography'));
					 	}
					}
					elseif ($key == 'flash')
						$exif = $this->flash_fired($imgmeta);
					elseif ($key == 'focal_length' && !$imgmeta['image_meta'][$key] == 0)
						$exif = $imgmeta['image_meta'][$key] . __('mm','exifography');
					elseif ($key == 'location')
						$exif = $this->display_geo($imgID);
					elseif ($key == 'shutter_speed' && !$imgmeta['image_meta'][$key] == 0)
						$exif = $this->pretty_shutter_speed($imgmeta);
					else
						$exif = $imgmeta['image_meta'][$key];

					if ($exif)
						$output[$key] = sprintf(stripslashes($options['before_item']),$key) . $value . stripslashes($options['sep']) . $exif . stripslashes($options['after_item']);
				}
			}

			$output = apply_filters('exifography_display_exif',$output,$post->ID,$imgID);
			endif;
			if (!empty($output)) {
				$output = sprintf(stripslashes($options['before_block']),'wp-image-'.$imgID) . implode('',$output) . stripslashes($options['after_block']);

				return $output;
			}
		}

		//render shortcode
		function shortcode($atts, $content = null) {
			global $post;
			$post_options = get_post_meta($post->ID, '_use_exif', true);
			if ("none" == $post_options || empty($post_options))
				$post_options = "all";

			extract(shortcode_atts(array(
				'show' => $post_options,
				'id' => '',
			), $atts));

			$images = get_children(array(
				'post_parent' => $post->ID,
				'post_type' => 'attachment',
				'numberposts' => 1,
				'post_mime_type' => 'image',
				'orderby' => 'ID',
				'order' => 'ASC'
				));
			if ($images) {
				foreach ($images as $image) {
					$imageID = $image->ID;
				}
			}

			if ($id == '')
				$imgID = $imageID;
			else
				$imgID = $id;


			return $this->display_exif($show,$imgID);
		}

		//auto insert
		function auto_insert($content) {
			$options = $this->get_options();
			if (isset($options['auto_insert']) && (!is_page()))
				return $content . $this->display_exif();
			else
				return $content;
		}

		// === ADMIN OPTIONS === //
		// add the options page under Settings
		function admin_page() {
			add_options_page(
				'Exifography Options',
				'Exifography',
				'manage_options',
				'exifography',
				array($this,'options_page')
			);
		}

		// render the admin page
		function options_page() {
		?>
<div>
	<?php if ($_POST) print_r($_POST); ?>
	<h1><?php _e('Exifography Options', 'exifography'); ?></h1>
	<p><?php _e('For instructions and support please visit the <a target="_blank" href="http://kristarella.blog/exifography">Exifography plugin page</a>.', 'exifography'); ?></p>
	<form action="options.php" method="post" class="exifography">
	<?php settings_fields($this->exif_options); ?>
	<?php do_settings_sections('plugin_options'); ?>

	<p><input class="button-primary" name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
	</form>
</div>
		<?php
		}

		// add the admin settings to the database and page
		function options_init(){
			register_setting( $this->exif_options, $this->exif_options, array($this,'options_validate') );
			add_settings_section('default_display', __('Default EXIF', 'exifography'), array($this,'defaults'), 'plugin_options');
			add_settings_section('auto_display', __('Auto insert into post', 'exifography'), array($this,'auto'), 'plugin_options');
			add_settings_section('custom_html', __('Custom HTML', 'exifography'), array($this,'html'), 'plugin_options');
			// exif fields settings inputs
			$options = $this->get_options();
			if (!empty($options['order']))
				$order = $options['order'];
			else
				$order = array_keys($this->fields);

			foreach ($order as $key) {
				$value = $this->fields[$key];
				add_settings_field('exif-field-'.$key, $value, array($this,'default_fields'), 'plugin_options', 'default_display', $key);
			}
			// auto insert settings fields
			add_settings_field('auto_insert', __('Automatically display exif','exifography'), array($this,'auto_field'), 'plugin_options', 'auto_display');
			// custom HTML settings fields
			foreach ($this->html_options as $key => $value) {
				add_settings_field($key, $value, array($this,'html_fields'), 'plugin_options', 'custom_html', $key);
			}
			add_settings_field('timestamp',__('Timestamp format', 'exifography'),array($this,'timestamp'),'plugin_options','custom_html');
			add_settings_field('item_label',__('Turn off item label', 'exifography'),array($this,'label'),'plugin_options','custom_html');
			add_settings_field('geo_link',__('Link GEO EXIF to Google Maps', 'exifography'),array($this,'geo_link'),'plugin_options','custom_html');
			add_settings_field('geo_img',__('Display map thumbnail instead of location coords', 'exifography'),array($this,'geo_img'),'plugin_options','custom_html');
			add_settings_field('geo_zoom',__('Map zoom (0 is the widest, 21 is close)', 'exifography'),array($this,'geo_zoom'),'plugin_options','custom_html');
			add_settings_field('geo_width',__('Map width', 'exifography'),array($this,'geo_width'),'plugin_options','custom_html');
			add_settings_field('geo_height',__('Map height', 'exifography'),array($this,'geo_height'),'plugin_options','custom_html');
			add_settings_field('geo_key',__('Google Maps API key', 'exifography'),array($this,'geo_key'),'plugin_options','custom_html');

			wp_enqueue_style( 'exif_admin_style', WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/admin.css' );
			wp_enqueue_script('exif_admin_js',WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'js/admin.js',array('jquery'));
			wp_enqueue_script('jquery-ui-sortable');
		}

		// render options sections
		function defaults() {
?>
<p><?php _e("Set these to create default display options. In the absence of any other settings, these will be used when EXIF is displayed. You can override these within an individual post, shortcode, or function.", 'exifography'); ?> <em><?php _e("Drag and drop to reorder the fields.", 'exifography'); ?></em></p>
<?php
		}
		function auto() {
?>
<p><?php _e("Use this option to automatically insert the EXIF for the first image attached to your post. Only use this when most of your posts will need EXIF, you can override this by deselecting all the post display options when editing a single post.", 'exifography'); ?></p>
<?php
		}
		function html() {
?>
<p><?php _e('This is the HTML used to display your exif data. IDs and classes can be used for styling.', 'exifography'); ?></p>
<?php
		}

		// render inputs
		function default_fields($key) {
			$options = $this->get_options();
			$checked = '';
			if (!empty($options['exif_fields']))
				$checked = checked( in_array($key,$options['exif_fields'] ), true, false);
			echo '<input id="exif-field-'.$key.'" value="'.$key.'" type="checkbox" name="'.$this->exif_options.'[exif_fields][]" '.$checked.' />';
		}
		function auto_field() {
			$options = $this->get_options();
			$checked = isset($options['auto_insert']) ? checked( $options['auto_insert'], true, false) : false;
			echo '<input id="auto_insert" type="checkbox" name="'.$this->exif_options.'[auto_insert]" '.$checked.' />';
		}
		function html_fields($key) {
			$options = $this->get_options();
			echo "<input type='text' id='".$key."' name='$this->exif_options[$key]' value='".esc_attr(stripslashes($options[$key]))."' class='regular-text code' />";
		}
		function timestamp() {
			$options = $this->get_options();
			echo '<input type="text" id="timestamp" name="'.$this->exif_options.'[timestamp]" value="'.$options['timestamp'].'" class="regular-text code" />';
		}
		function label() {
			$options = $this->get_options();
			$checked = isset($options['item_label']) ? checked( $options['item_label'], true, false) : false;
			echo '<input id="item_label" type="checkbox" name="'.$this->exif_options.'[item_label]" '.$checked.' />';
		}
		function geo_link() {
			$options = $this->get_options();
			$checked = isset($options['geo_link']) ? checked( $options['geo_link'], true, false) : false;
			echo '<input id="geo_link" type="checkbox" name="'.$this->exif_options.'[geo_link]" '.$checked.' />';
		}
		function geo_img() {
			$options = $this->get_options();
			$checked = isset($options['geo_img']) ? checked( $options['geo_img'], true, false) : false;
			echo '<input id="geo_img" type="checkbox" name="'.$this->exif_options.'[geo_img]" '.$checked.' />';
		}
		function geo_zoom() {
			$options = $this->get_options();
			echo '<input type="text" id="geo_zoom" name="'.$this->exif_options.'[geo_zoom]" value="'.$options['geo_zoom'].'" class="regular-text code" />';
		}
		function geo_width() {
			$options = $this->get_options();
			echo '<input type="text" id="geo_width" name="'.$this->exif_options.'[geo_width]" value="'.$options['geo_width'].'" class="regular-text code" />';
		}
		function geo_height() {
			$options = $this->get_options();
			echo '<input type="text" id="geo_height" name="'.$this->exif_options.'[geo_height]" value="'.$options['geo_height'].'" class="regular-text code" />';
		}
		function geo_key() {
			$options = $this->get_options();
			$key_info = __('Google Maps is free for 25,000 views per 24 hours, add your API key for higher usage','exifography');
			echo '<input type="text" id="geo_key" name="'.$this->exif_options.'[geo_key]" value="'.$options['geo_key'].'" class="regular-text code" /> <a href="https://developers.google.com/maps/documentation/static-maps/usage-limits" target="_blank" title="'.$key_info.'">&#9432;</a>';
		}

		// validate options
		function options_validate($input) {
			$output = array();
			foreach ($input as $key => $value) {
				//validate checkboxes
				if ($key == 'exif_fields') {
					foreach ($value as $field) {
						if (array_key_exists( $field, $this->fields) )
							$output['exif_fields'][] = $field;
					}
				}
				elseif ($key == 'auto_insert' || $key == 'item_label' || $key == 'geo_link' || $key == 'geo_img') {
					$output[$key] = 1;
				}
				//validate numbers
				elseif ($key == 'geo_zoom' || $key == 'geo_width' || $key == 'geo_height') {
					if(preg_match('/^[0-9]*$/i',trim($value)))
						$output[$key] = $value;
				}
				//validate order
				elseif ($key == 'order') {
					$order = array();
					$fields = explode( ',', $value );
					foreach ($fields as $field) {
						if (array_key_exists( $field, $this->fields ))
							$order[] = $field;
					}
						$output[$key] = $order;
				}
				// everything else
				else
					$output[$key] = addslashes($value);
			}
			return $output;
		}

		// === EDIT POST OPTIONS === //
		// registers the meta box for the post edit page
		function add_post_box() {
			if( function_exists('add_meta_box'))
				add_meta_box('exifography_add_meta', __( 'Add EXIF to post', 'exifography' ), array($this,'edit_post_exif'), 'post', 'side', 'low');
		}

		// creates the content of the post edit meta box
		function edit_post_exif($post) {
			wp_nonce_field( plugin_basename( __FILE__ ), 'exifography_noncename' );
			echo '<input type="hidden" name="exif_saved" value="1">';

			// fetching Exifography options
			$options = $this->get_options();

			if (get_post_meta($post->ID, '_use_exif'))
				$set_exif = get_post_meta($post->ID, '_use_exif', true);
			elseif (isset($options['exif_fields']))
				$set_exif = implode(',',$options['exif_fields']);
			else
				$set_exif = '';
			$set_exif = explode(',', $set_exif);
			?>

			<p><?php _e('If there is a photo attached to this post, the following details may be added to the end of the post.', 'exifography'); ?></p>
			<ul style="padding:0 0.9em;">
<?php
			foreach ($this->fields as $key => $value) {
				echo '<li><input id="exif-field-'.$key.'" value="'.$key.'" type="checkbox" name="exif_fields[]" '.checked( in_array($key, $set_exif), true, false ).' /> <label for="'.$key.'">'.$value.'</label></li>';
			}
?>
			</ul>
			<?php
		}

		//saves the meta box options as a custom field called _use_exif
		function save_postdata($post_id) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['exifography_noncename'] ) )
				return $post_id;

			// verify this came from our screen and with proper authorization,
			// because save_post can be triggered at other times
			if (!wp_verify_nonce($_POST['exifography_noncename'], plugin_basename( __FILE__ ) ))
				return $post_id;
			if (!current_user_can( 'edit_post', $post_id ))
				return $post_id;
			// OK, we're authenticated

			if (isset($_POST['exif_fields']))
				$use_exif = implode(',',$_POST['exif_fields']);
			elseif (isset($_POST['exif_saved']) && !(isset($_POST['exif_fields'])))
				$use_exif = 'none';
			else
				$use_exif = '';

			$current_data = get_post_meta($post_id, '_use_exif', true);

			if ($use_exif == '')
				delete_post_meta($post_id, '_use_exif');
			elseif ($use_exif == 'none')
				update_post_meta($post_id, '_use_exif', $use_exif);
			elseif ($use_exif !== $current_data)
				update_post_meta($post_id, '_use_exif', $use_exif);
			else
				update_post_meta($post_id, '_use_exif', $current_data);
		}

		//shows shortcode with ID for current image
		function shortcode_id( $form_fields, $post ) {
			$form_fields['exif_shortcode'] = array(
				'label' => 'Exifography shortcode',
				'input' => 'html',
				'html' => "<input type='text' class='text' readonly='readonly' name='exif_shortcode' value='[exif id=\"".$post->ID."\"]' /><br />",
				'helps' => 'Copy and paste into your post to show EXIF for this image',
			);

			return $form_fields;
		}
	}
}

if (class_exists('exifography'))
	$exif = new exifography();

// use this to manually insert the exif output in your theme
function exifography_display_exif($fields=null,$imgID=null) {
	if (class_exists('exifography')) {
		$exif = new exifography();
		return $exif->display_exif($fields,$imgID);
	}
}
// this is deprecated don't use it anymore, use exifography_display_exif() instead
if (!function_exists('display_exif')) {
	function display_exif($fields=null,$imgID=null) {
		return '<!-- Please replace your instances of "display_exif" with "exifography_display_exif". -->';
	}
}
