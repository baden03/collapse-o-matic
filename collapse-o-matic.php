<?php
/*
Plugin Name: Collapse-O-Matic
Text Domain: jquery-collapse-o-matic
Plugin URI: https://pluginoven.com/plugins/collapse-o-matic/
Description: Collapse-O-Matic adds an [expand] shortcode that wraps content into a lovely, jQuery collapsible div.
Version: 1.8.3
Author: twinpictures, baden03
Author URI: https://twinpictures.de/
License: GPL2
*/

/**
 * Class WP_Collapse_O_Matic
 * @package WP_Collapse_O_Matic
 * @category WordPress Plugins
 */

if(!defined('PLUGIN_OVEN_URL')){
	define( 'PLUGIN_OVEN_URL', 'https://pluginoven.com/' );
}
if(!defined('PLUGIN_OVEN_CC')){
	define( 'PLUGIN_OVEN_CC', 'Collapse Commander' );
}

class WP_Collapse_O_Matic {

	/**
	 * Current version
	 * @var string
	 */
	var $version = '1.8.3';

	/**
	 * Used as prefix for options entry
	 * @var string
	 */
	var $domain = 'colomat';

	/**
	 * Name of the options
	 * @var string
	 */
	var $options_name = 'WP_Collapse_O_Matic_options';

	/**
	 * @var array
	 */
	var $options = array(
		'style' => 'light',
		'cid' => '',
		'tag' => 'span',
		'trigclass' => '',
		'targtag' => 'div',
		'targclass' => '',
		'notitle' => '',
		'duration' => 'fast',
		'tabindex' => '0',
		'slideEffect' => 'slideFade',
		'custom_css' => '',
		'script_check' => '',
		'css_check' => '',
		'script_location' => 'footer',
		'cc_download_key' => '',
		'cc_email' => '',
		'filter_content' => '',
		'pauseinit' => '',
		'cc_display_id' => '',
		'cc_display_title' => '',
		'touch_start' => '',
	);

	var $license_group = 'colomat_licenseing';

    var $license_name = 'WP_Collapse_O_Matic_license';

    var $license_options = array(
		'collapse_commander_license_key' => '',
		'collapse_commander_license_status' => ''
	);

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		// set option values
		$this->_set_options();

		// load text domain for translations
		load_plugin_textdomain( 'jquery-collapse-o-matic' );

		//load the script and style if viewing the front-end
		add_action('wp_enqueue_scripts', array( $this, 'collapsTronicInit' ) );
		add_action('admin_enqueue_scripts', array( $this, 'codemirror_enqueue_scripts') );

		// add actions
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_actions' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_shortcode('expand', array($this, 'shortcode'));
		add_shortcode('colomat', array($this, 'shortcode'));

		//add expandsub shortcodes
		for ($i=1; $i<30; $i++) {
			add_shortcode('expandsub'.$i, array($this, 'shortcode'));
		}

		// Add shortcode support for widgets
		add_filter('widget_text', 'do_shortcode');
	}

	/**
	 * Callback init
	 */
	function collapsTronicInit() {
		//collapse script
		$load_in_footer = false;
		if($this->options['script_location'] == 'footer' ){
			$load_in_footer = true;
		}
		wp_register_script('collapseomatic-js', plugins_url('js/collapse.js', __FILE__), array('jquery'), '1.7.2', $load_in_footer);
		
		//prep options for injection
		$com_options = [
			'colomatduration' => $this->options['duration'],
			'colomatslideEffect' => $this->options['slideEffect'],
			'colomatpauseInit' => $this->options['pauseinit'],
			'colomattouchstart' => $this->options['touch_start']
		];
		wp_add_inline_script( 'collapseomatic-js', 'const com_options = ' . json_encode( $com_options ), 'before' );

		if( empty($this->options['script_check']) ){
			wp_enqueue_script('collapseomatic-js');
		}

		//css
		wp_register_style( 'collapscore-css', plugins_url('css/core_style.css', __FILE__) , array (), '1.0' );
		wp_register_style( 'collapseomatic-css', plugins_url('css/'.$this->options['style'].'_style.css', __FILE__) , array (), '1.6' );
		if( !empty( $this->options['custom_css'] ) ){
			wp_add_inline_style( 'collapscore-css', $this->options['custom_css'] );
		}

		if( empty($this->options['css_check'])){
			wp_enqueue_style( 'collapscore-css' );
			if ($this->options['style'] !== 'none') {
				wp_enqueue_style( 'collapseomatic-css' );
			}
		}
	}

	function codemirror_enqueue_scripts($hook) {
		if($hook == 'settings_page_collapse-o-matic-options'){
			wp_register_script('cm_js', plugins_url('js/admin_codemirror.js', __FILE__), array('jquery'), '0.1.0', true);
			$cm_settings = wp_enqueue_code_editor(
				[
					'type' => 'text/css',
					'codemirror' => [
						'lineNumbers' => true,
						'autoRefresh' => true
					]
				]
			);
			wp_localize_script('cm_js', 'cm_settings', $cm_settings);
			wp_enqueue_script( 'cm_js' );
			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );
			wp_register_style( 'com-admin-css', plugins_url('css/admin_style.css', __FILE__) , array (), '1.0.0' );
			wp_enqueue_style( 'com-admin-css' );
		}
	}

	/**
	 * Callback admin_menu
	 */
	function admin_menu() {
		if ( function_exists( 'add_options_page' ) AND current_user_can( 'manage_options' ) ) {
			// add options page
			$page = add_options_page('Collapse-O-Matic Options', 'Collapse-O-Matic', 'manage_options', 'collapse-o-matic-options', array( $this, 'options_page' ));
		}
	}

	/**
	 * Callback admin_init
	 */
	function admin_init() {
		// register settings
		register_setting( $this->domain, $this->options_name );
		register_setting( $this->license_group, $this->license_name, array($this, 'edd_sanitize_license') );
	}

	/**
	 * Callback shortcode
	 */
	function shortcode($atts, $content = null){
		$options = $this->options;
		if( !empty($this->options['script_check']) ){
			wp_enqueue_script('collapseomatic-js');
		}

		if( !empty($this->options['css_check'])){
			wp_enqueue_style( 'collapscore-css' );
			if ($this->options['style'] !== 'none') {
				wp_enqueue_style( 'collapseomatic-css' );
			}
		}

		//find a random number, if no id is assigned
		$ran = uniqid();
		extract(shortcode_atts(array(
				'title' => '',
				'cid' => $options['cid'],
				'template_id' => '',
				'swaptitle' => '',
				'alt' => '',
				'swapalt' => '',
				'notitle' => $options['notitle'],
				'id' => 'id'.$ran,
				'tag' => $options['tag'],
				'trigclass' => $options['trigclass'],
				'targtag' => $options['targtag'],
				'targclass' => $options['targclass'],
				'targpos' => '',
				'trigpos' => 'above',
				'rel' => '',
				'group' => '',
				'togglegroup' => '',
				'expanded' => '',
				'excerpt' => '',
				'swapexcerpt' => false,
				'excerptpos' => 'below-trigger',
				'excerpttag' => 'div',
				'excerptclass' => '',
				'findme' => '',
				'scrollonclose' => '',
				'startwrap' => '',
				'endwrap' => '',
				'elwraptag' => '',
				'elwrapclass' => '',
				'filter' => $options['filter_content'],
				'tabindex' => $options['tabindex'],
				'animation_effect' => '',
				'duration' => '',
			), $atts, 'expand'));

		//collapse commander
		if( !empty($cid) && is_plugin_active( 'collapse-commander/collapse-commander.php') ){
			$meta_values = WP_CollapseCommander::meta_grabber($cid);
			extract(shortcode_atts($meta_values, $atts));
		}

		if(!empty($triggertext)){
			$title = $triggertext;
		}
		if(!empty($highlander) && !empty($rel)){
			$rel .= '-highlander';
		}

		//content filtering
		if(empty($filter) || $filter == 'false'){
			$content = do_shortcode($content);
		}
		else{
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
		}

		if( !empty($cid) && get_edit_post_link($cid) ){
			$content .= '<div class="com_edit_link"><a class="post-edit-link" href="'.get_edit_post_link($cid).'">'.__('Edit').'</a></div>';
		}

		if( !empty($sub_cids) ){
			foreach($sub_cids as $sub_cid){
				$args = array('cid' => $sub_cid);
				$content .= $this->shortcode($args);
			}
		}

		//id does not allow spaces
		$id = preg_replace('/\s+/', '_', $id);

		$ewo = '';
		$ewc = '';

		//id does not allow spaces
		$id = preg_replace('/\s+/', '_', $id);

		//placeholders
		$placeholder_arr = array('%(%', '%)%', '%{%', '%}%');
		$swapout_arr = array('<', '>', '[', ']');

		$title = do_shortcode(str_replace($placeholder_arr, $swapout_arr, $title));
		if($swaptitle){
			$swaptitle = do_shortcode(str_replace($placeholder_arr, $swapout_arr, $swaptitle));
		}
		if($startwrap){
			$startwrap = do_shortcode(str_replace($placeholder_arr, $swapout_arr, $startwrap));
		}
		if($endwrap){
			$endwrap = do_shortcode(str_replace($placeholder_arr, $swapout_arr, $endwrap));
		}
		//need to check for a few versions, because of new option setting. can be removed after a few revisiosn.
		if(empty($targtag)){
			$targtag = 'div';
		}
		
		if(!empty($elwraptag)){
			$ewclass = '';
			if($elwrapclass){
				$ewclass = 'class="'.esc_attr($elwrapclass).'"';
			}
			$ewo = '<'.$elwraptag.' '.$ewclass.'>';
			$ewc = '</'.$elwraptag.'>';
		}

		$eDiv = '';

		if($content){
			$inline_class = '';
			$collapse_class = 'collapseomatic_content ';
			if($targpos == 'inline'){
				$inline_class = 'colomat-inline ';
				$collapse_class = 'collapseomatic_content_inline ';
			}
			$eDiv = '<'.$targtag.' id="target-'.$id.'" class="'.esc_attr($collapse_class.$inline_class.$targclass).'">'.$content.'</'.$targtag.'>';
		}
		if($excerpt){
			$excerpt = str_replace($placeholder_arr, $swapout_arr, $excerpt);
			$excerpt = do_shortcode($excerpt);
			$excerpt = apply_filters( 'colomat_excerpt', $excerpt );

			if($targpos == 'inline'){
				$excerpt .= $eDiv;
				$eDiv = '';
			}
			if($excerptpos == 'above-trigger'){
				$nibble = '<'.$excerpttag.' id="excerpt-'.esc_attr($id).'" class="'.esc_attr($excerptclass).'">'.$excerpt.'</'.$excerpttag.'>';
			}
			else{
				$nibble = '<'.$excerpttag.' id="excerpt-'.esc_attr($id).'" class="collapseomatic_excerpt '.esc_attr($excerptclass).'">'.$excerpt.'</'.$excerpttag.'>';
			}
			//swapexcerpt
			if($swapexcerpt !== false){
				$swapexcerpt = str_replace($placeholder_arr, $swapout_arr, $swapexcerpt);
				$swapexcerpt = do_shortcode($swapexcerpt);
				$swapexcerpt = apply_filters( 'colomat_swapexcerpt', $swapexcerpt );
				$nibble .= '<'.$excerpttag.' id="swapexcerpt-'.esc_attr($id).'" style="display:none;">'.$swapexcerpt.'</'.$excerpttag.'>';
			}
		}
		$altatt = '';
		if(!empty($alt)){
			$altatt = 'alt="'.esc_attr($alt).'" title="'.esc_attr($alt).'"';
		}
		else if( empty($notitle) ){
			$altatt = 'title="'.esc_attr($title).'"';
		}
		$relatt = '';
		if(!empty($rel)){
			$relatt = 'rel="'.esc_attr($rel).'"';
		}

		$groupatt = '';
		//legacy
		if($group && !$togglegroup){
			$togglegroup = $group;
		}

		if($togglegroup){
			$groupatt = 'data-togglegroup="'.esc_attr($togglegroup).'"';
		}
		$inexatt = '';
		//var_dump($tabindex);
		if(!empty($tabindex) || $tabindex == 0 ){
			$inexatt = 'tabindex="'.esc_attr($tabindex).'"';
		}
		if($expanded && $expanded != 'false'){
			$trigclass .= ' colomat-close';
		}
		$anchor = '';
		if($findme){
			$trigclass .= ' find-me';
			$offset = '';
			if($findme != 'true' && $findme != 'auto'){
				$offset = $findme;
			}
			//$anchor = '<input type="hidden" id="find-'.$id.'" name="'.$offset.'"/>';
			$anchor = 'data-findme="'.$offset.'"';
		}

		//effect
		$effatt = '';
		if($animation_effect){
			$effatt = 'data-animation_effect="'.esc_attr($animation_effect).'"';
		}

		//duration
		$duratt = '';
		if($duration){
			$duratt = 'data-duration="'.esc_attr($duration).'"';
		}

		$closeanchor = '';
		if($scrollonclose && (is_numeric($scrollonclose) || $scrollonclose == 0)){
			$trigclass .= ' scroll-to-trigger';
			$closeanchor = '<input type="hidden" id="scrollonclose-'.esc_attr($id).'" name="'.esc_attr($scrollonclose).'"/>';
		}

		//deal with image from collapse-commander
		if( !empty($trigtype) && $trigtype == 'image' && !empty($triggerimage) && strtolower($tag) == 'img' ){
			$imageclass = 'collapseomatic noarrow' . esc_attr($trigclass);
			$image_atts = array( 'id' => $id, 'class' => $imageclass, 'alt' => $alt );
			if(!$notitle){
				$image_atts['title'] = $alt;
			}
			$link = $closeanchor.wp_get_attachment_image( $triggerimage, 'full', false, $image_atts );
		}
		else{
			if(!empty($trigtype) && $trigtype == 'image' && !empty($triggerimage)){
				$title =  wp_get_attachment_image( $triggerimage, 'full' );
			}
			$link = $closeanchor.'<'.$tag.' class="collapseomatic '.esc_attr($trigclass).'" id="'.esc_attr($id).'" '.$relatt.' '.$inexatt.' '.$altatt.' '.$anchor.' '.$groupatt.' '.$effatt.' '.$duratt.'>'.$startwrap.$title.$endwrap.'</'.$tag.'>';
		}

		//swap image
		if( !empty($trigtype) && $trigtype == 'image' && !empty($swapimage) && strtolower($tag) == 'img' ){
			$link .= wp_get_attachment_image( $swapimage, 'full', false, array( 'id' => 'swap-'.$id, 'class' => 'colomat-swap', 'alt' => $swapalt, 'style' => 'display:none;' ) );
		}
		else{
			if(!empty($trigtype) && $trigtype == 'image' && !empty($swapimage)){
				$swaptitle = wp_get_attachment_image( $swapimage, 'full' );
			}
		}
		//swap title
		if($swaptitle){
			$swapalt_attr = '';
			if(!empty($swapalt)){
				$swapalt_attr = "alt='".$swapalt."'";
			}
			$link .= "<".$tag." id='swap-".esc_attr($id)."' ".$swapalt_attr." class='colomat-swap' style='display:none;'>".$startwrap.$swaptitle.$endwrap."</".$tag.">";
		}

		if($excerpt){
			if($excerptpos == 'above-trigger'){
				if($trigpos == 'below'){
					$retStr = $ewo.$eDiv.$nibble.$link.$ewc;
				}
				else{
					$retStr = $ewo.$nibble.$link.$eDiv.$ewc;
				}
			}
			else if($excerptpos == 'below-trigger'){
				if($trigpos == 'below'){
					$retStr =  $ewo.$eDiv.$link.$nibble.$ewc;
				}
				else{
					$retStr = $ewo.$link.$nibble.$eDiv.$ewc;
				}
			}
			else{
				if($trigpos == 'below'){
					$retStr = $ewo.$eDiv.$link.$nibble.$ewc;
				}
				else{
					$retStr = $ewo.$link.$eDiv.$nibble.$ewc;
				}
			}
		}
		else{
			if($trigpos == 'below'){
				$retStr = $ewo.$eDiv.$link.$ewc;
			}
			else{
				$retStr = $ewo.$link.$eDiv.$ewc;
			}
		}
		return $retStr;
	}

	// Add link to options page from plugin list
	function plugin_actions($links) {
		$new_links = array();
		$new_links[] = '<a href="options-general.php?page=collapse-o-matic-options">' . __('Settings', 'jquery-collapse-o-matic') . '</a>';
		return array_merge($new_links, $links);
	}

	/**
	 * Admin options page
	 */
	function options_page() {
		$like_it_arr = array(
			__('really tied the room together', 'jquery-collapse-o-matic'),
			__('made you feel all warm and fuzzy on the inside', 'jquery-collapse-o-matic'),
			__('restored your faith in humanity... even if only for a fleeting second', 'jquery-collapse-o-matic'),
			__('rocked your world', 'provided a positive vision of future living', 'jquery-collapse-o-matic'),
			__('inspired you to commit a random act of kindness', 'jquery-collapse-o-matic'),
			__('encouraged more regular flossing of the teeth', 'jquery-collapse-o-matic'),
			__('helped organize your life in the small ways that matter', 'jquery-collapse-o-matic'),
			__('saved your minutes--if not tens of minutes--writing your own solution', 'jquery-collapse-o-matic'),
			__('brightened your day... or darkened if if you are trying to sleep in', 'jquery-collapse-o-matic'),
			__('caused you to dance a little jig of joy and joyousness', 'jquery-collapse-o-matic'),
			__('inspired you to tweet a little @twinpictues social love', 'jquery-collapse-o-matic'),
			__('tasted great, while also being less filling', 'jquery-collapse-o-matic'),
			__('caused you to shout: "everybody spread love, give me some mo!"', 'jquery-collapse-o-matic'),
			__('helped you keep the funk alive', 'jquery-collapse-o-matic'),
			__('<a href="https://www.youtube.com/watch?v=dvQ28F5fOdU" target="_blank">soften hands while you do dishes</a>', 'jquery-collapse-o-matic'),
			__('helped that little old lady <a href="https://www.youtube.com/watch?v=Ug75diEyiA0" target="_blank">find the beef</a>', 'jquery-collapse-o-matic')
		);
		$rand_key = array_rand($like_it_arr);
		$like_it = $like_it_arr[$rand_key];
	?>
		<div class="wrap">
			<h2>Collapse-O-Matic</h2>
		</div>

		<div class="postbox-container metabox-holder meta-box-sortables" style="width: 69%">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle', 'jquery-collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle"><?php _e( 'Default Collapse-O-Matic Settings', 'jquery-collapse-o-matic' ) ?></h3>
					<div class="inside">
						<form method="post" action="options.php">
							<?php
								settings_fields( $this->domain );
								$options = $this->options;
							?>
							<fieldset class="options">
								<table class="form-table">
								<tr>
									<th><?php _e( 'Style', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><select id="style" name="<?php echo esc_attr($this->options_name); ?>[style]">
										<?php
											if(empty($options['style'])){
												$options['style'] = 'light';
											}
											$st_array = array(
												__('Light', 'jquery-collapse-o-matic') => 'light',
												__('Dark', 'jquery-collapse-o-matic') => 'dark',
												__('None', 'jquery-collapse-o-matic') => 'none'
											);
											foreach( $st_array as $key => $value){
												$selected = '';
												if($options['style'] == $value){
													$selected = 'SELECTED';
												}
												echo '<option value="'.esc_attr($value).'" '.$selected.'>'.esc_attr($key).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php _e('Select Light for sites with lighter backgrounds. Select Dark for sites with darker backgrounds. Select None to handle styling yourself.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<?php if( is_plugin_active( 'collapse-commander/collapse-commander.php' ) ) : ?>
								<tr>
									<th><?php _e( 'CID Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="cid" name="<?php echo esc_attr($this->options_name); ?>[cid]" value="<?php echo esc_attr($options['cid']); ?>" />
										<br /><span class="description"><?php printf( __('Default %sCollapse Commander%s ID', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/premium-plugins/collapse-commander/" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>
								<?php else: ?>
								<tr>
									<th><?php _e( 'Collapse Management', 'colpromat' ) ?></th>
									<td><?php printf(__( '%sCollapse Commander%s is an add-on plugin that introduces an advanced management interface to better organize expand elements and simplify expand shortcodes.', 'colpromat' ), '<a href="https://pluginoven.com/premium-plugins/collapse-commander/">', '</a>'); ?>
									</td>
								</tr>
								<?php endif; ?>

								<tr>
									<th><?php _e( 'Tag Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="tag" name="<?php echo esc_attr($this->options_name); ?>[tag]" value="<?php echo esc_attr($options['tag']); ?>" />
										<br /><span class="description"><?php printf(__('HTML tag use to wrap the trigger text. See %sTag Attribute%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#tag-attribute" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Trigclass Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="trigclass" name="<?php echo esc_attr($this->options_name); ?>[trigclass]" value="<?php echo esc_attr($options['trigclass']); ?>" />
										<br /><span class="description"><?php printf(__('Default class assigned to the trigger element. See %sTrigclass Attribute%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#trigclass-attribute" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Tabindex Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="tabindex" name="<?php echo esc_attr($this->options_name); ?>[tabindex]" value="<?php echo esc_attr($options['tabindex']); ?>" />
										<br /><span class="description"><?php printf(__('Default tabindex value to be assigned to the trigger element. See %sTabindex Attribute%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#tabindex-attribute" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Targtag Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="targtag" name="<?php echo esc_attr($this->options_name); ?>[targtag]" value="<?php echo esc_attr($options['targtag']); ?>" />
										<br /><span class="description"><?php printf(__('HTML tag use for the target element. See %sTargtag Attribute%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#targtag-attribute" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Targclass Attribute', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="targclass" name="<?php echo esc_attr($this->options_name); ?>[targclass]" value="<?php echo esc_attr($options['targclass']); ?>" />
										<br /><span class="description"><?php printf(__('Default class assigned to the target element. See %sTargclass Attribute%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#targclass-attribute" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'No Title', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="notitle" name="<?php echo esc_attr($this->options_name); ?>[notitle]" value="1"  <?php echo checked( $options['notitle'], 1 ); ?> /> <?php _e('No Title', 'jquery-collapse-o-matic'); ?>
										<br /><span class="description"><?php _e('Do not use title tags by default.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Add touchstart', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="touch_start" name="<?php echo esc_attr($this->options_name); ?>[touch_start]" value="1"  <?php echo checked( $options['touch_start'], 1 ); ?> /> <?php _e('Add touchstart', 'jquery-collapse-o-matic'); ?>
										<br /><span class="description"><?php _e('Add jQuery touchstart binding to triggers.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Initial Pause', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="number" id="pauseinit" name="<?php echo esc_attr($this->options_name); ?>[pauseinit]" value="<?php echo esc_attr($options['pauseinit']); ?>" />
										<br /><span class="description"><?php _e('Amount of time in milliseconds to pause before the initial collapse is triggered on page load.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<?php
										if(empty($options['duration'])){
												$options['duration'] = 'fast';
										}
									?>
									<th><?php _e( 'Animation Duration', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="duration" name="<?php echo esc_attr($this->options_name); ?>[duration]" value="<?php echo esc_attr($options['duration']); ?>" />
										<br /><span class="description"><?php printf(__('A string or number determining how long the animation will run. See %sDuration%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://plugins.twinpictures.de/plugins/collapse-o-matic/documentation/#duration" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Animation Effect', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><select id="slideEffect" name="<?php echo esc_attr($this->options_name); ?>[slideEffect]">
										<?php
											if(empty($options['slideEffect'])){
												$options['slideEffect'] = 'slideFade';
											}
											$se_array = array(
												__('Slide Only', 'jquery-collapse-o-matic') => 'slideToggle',
												__('Slide & Fade', 'jquery-collapse-o-matic') => 'slideFade',
												__('Fade Only', 'jquery-collapse-o-matic') => 'fadeOnly'
											);
											foreach( $se_array as $key => $value){
												$selected = '';
												if($options['slideEffect'] == $value){
													$selected = 'SELECTED';
												}
												echo '<option value="'.esc_attr($value).'" '.$selected.'>'.esc_attr($key).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php printf(__('Animation effect to use while collapsing and expanding. See %sAnimation Effect%s in the documentation for more info.', 'jquery-collapse-o-matic'), '<a href="https://plugins.twinpictures.de/plugins/collapse-o-matic/documentation/#animation-effect" target="_blank">', '</a>'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Custom Style', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><textarea id="custom_css" name="<?php echo esc_attr($this->options_name); ?>[custom_css]"><?php echo esc_textarea($options['custom_css']); ?></textarea>
										<br /><span class="description"><?php _e( 'Custom CSS style for <em>ultimate flexibility</em>', 'jquery-collapse-o-matic' ) ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Content Filter', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="filter_content" name="<?php echo esc_attr($this->options_name); ?>[filter_content]" value="1"  <?php echo checked( $options['filter_content'], 1 ); ?> /> <?php _e('Apply filter', 'jquery-collapse-o-matic'); ?>
										<br /><span class="description"><?php _e('Apply the_content filter to target content.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<?php
									//if collapse-commander is installed, display options for displaying id and text in shortocdes
									if( is_plugin_active( 'collapse-commander/collapse-commander.php' ) ) :
								?>
								<tr>
									<th><?php _e( 'Display ID', 'colpromat' ) ?>:</th>
									<td><label><input type="checkbox" id="cc_display_id" name="<?php echo esc_attr($this->options_name); ?>[cc_display_id]" value="1"  <?php echo checked( $options['cc_display_id'], 1 ); ?> /> <?php _e('Display ID', 'colpromat'); ?>
										<br /><span class="description"><?php _e('Display custom ID attribute in shortcodes if set for easier shortcode managment.', 'colpromat'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Display Title', 'colpromat' ) ?>:</th>
									<td><label><input type="checkbox" id="cc_display_title" name="<?php echo esc_attr($this->options_name); ?>[cc_display_title]" value="1"  <?php echo checked( $options['cc_display_title'], 1 ); ?> /> <?php _e('Display Title', 'colpromat'); ?>
										<br /><span class="description"><?php _e('Display custom eT attribute in shortcodes that shows expand title for easier shortcode managment.', 'colpromat'); ?></span></label>
									</td>
								</tr>
								<?php endif; ?>

								<tr>
									<th><?php _e( 'Shortcode Scripts', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="script_check" name="<?php echo esc_attr($this->options_name); ?>[script_check]" value="1"  <?php echo checked( $options['script_check'], 1 ); ?> /> <?php _e('Only load scripts with shortcode.', 'jquery-collapse-o-matic'); ?>
										<br /><span class="description"><?php _e('Only load Collapse-O-Matic scripts if [expand] shortcode is used.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Shortcode CSS', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="css_check" name="<?php echo esc_attr($this->options_name); ?>[css_check]" value="1"  <?php echo checked( $options['css_check'], 1 ); ?> /> <?php _e('Only load CSS with shortcode.', 'jquery-collapse-o-matic'); ?>
										<br /><span class="description"><?php _e('Only load Collapse-O-Matic CSS if [expand] shortcode is used.', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Script Load Location', 'jquery-collapse-o-matic' ) ?>:</th>
									<td><label><select id="script_location" name="<?php echo esc_attr($this->options_name); ?>[script_location]">
										<?php
											if(empty($options['script_location'])){
												$options['script_location'] = 'footer';
											}
											$sl_array = array(
												__('Header', 'jquery-collapse-o-matic') => 'header',
												__('Footer', 'jquery-collapse-o-matic') => 'footer'
											);
											foreach( $sl_array as $key => $value){
												$selected = '';
												if($options['script_location'] == $value){
													$selected = 'SELECTED';
												}
												echo '<option value="'.esc_attr($value).'" '.$selected.'>'.esc_attr($key).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php _e('Where should the script be loaded, in the Header or the Footer?', 'jquery-collapse-o-matic'); ?></span></label>
									</td>
								</tr>
								<?php if( !is_plugin_active( 'collapse-commander/collapse-commander.php' ) ) : ?>
								<tr>
									<th><strong><?php _e( 'Take Command!', 'jquery-collapse-o-matic' ) ?></strong></th>
									<td><?php printf(__( '%sCollapse Commander%s is an add-on plugin that introduces an advanced management interface to better organize expand elements and simplify expand shortcodes.', 'jquery-collapse-o-matic' ), '<a href="https://pluginoven.com/premium-plugins/collapse-commander/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-commander&utm_campaign=collapse-o-matic-commander">', '</a>'); ?>
									</td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><strong><?php _e( 'Level Up!', 'jquery-collapse-o-matic' ) ?></strong></th>
									<td><?php printf(__( '%sCollapse-Pro-Matic%s is our premium plugin that offers additional attributes and features for <i>ultimate</i> flexibility, in addition to a very %shigh level of personal support%s.', 'jquery-collapse-o-matic' ), '<a href="https://pluginoven.com/premium-plugins/collapse-pro-matic/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-pro-matic&utm_campaign=collapse-o-matic-pro">', '</a>', '<a href="https://pluginoven.com/premium-plugins/collapse-pro-matic/testimonials/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-pro-matic&utm_campaign=collapse-o-matic-support">', '</a>'); ?>
									</td>
								</tr>
								</table>
							</fieldset>

							<p class="submit">
								<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle', 'jquery-collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle"><?php _e( 'About' ) ?></h3>
					<div class="inside">
						<h4><img src="<?php echo plugins_url( 'images/collapse-o-matic-icon.png', __FILE__ ) ?>" width="16" height="16"/> Collapse-O-Matic Version <?php echo esc_attr($this->version); ?></h4>
						<p><?php _e( 'Remove clutter, save space. Display and hide additional content in a SEO friendly way. Wrap any content&mdash;including other shortcodes&mdash;into a lovely jQuery expanding and collapsing element.', 'jquery-collapse-o-matic') ?></p>
						<ul>
							<li><?php printf( __( '%sDetailed documentation%s, complete with working demonstrations of all shortcode attributes, is available for your instructional enjoyment.', 'jquery-collapse-o-matic'), '<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/" target="_blank">', '</a>'); ?></li>
							<li><?php printf( __( '%sFree Opensource Support%s', 'jquery-collapse-o-matic'), '<a href="https://wordpress.org/support/plugin/jquery-collapse-o-matic" target="_blank">', '</a>'); ?></li>
							<li><?php printf( __( 'If this plugin %s, please consider %sreviewing it at WordPress.org%s to help others.', 'jquery-collapse-o-matic'), $like_it, '<a href="https://wordpress.org/support/view/plugin-reviews/jquery-collapse-o-matic" target="_blank">', '</a>' ) ?></li>
							<li><a href="https://wordpress.org/plugins/jquery-collapse-o-matic/" target="_blank">WordPress.org</a> | <a href="https://pluginoven.com/plugins/collapse-o-matic/" target="_blank">Twinpictues Plugin Oven</a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<?php if( is_plugin_active( 'collapse-commander/collapse-commander.php' ) ) : ?>

		<div class="postbox-container side metabox-holder" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<h3 class="handle"><?php _e( 'Register Collapse Commander', 'jquery-collapse-o-matic') ?></h3>
					<div class="inside">
                                            <p><?php printf( __('To receive plugin updates you must register your plugin. Enter your Collapse Commander licence key below. Licence keys may be viewed and manged by logging into %syour account%s.', 'colpromat'), '<a href="https://pluginoven.com/my-account/" target="_blank">', '</a>'); ?></p>
						<form method="post" action="options.php">
                            <?php
                                settings_fields( $this->license_group );
                                $options = get_option($this->license_name);
                                $cc_licence = ( !isset( $options['collapse_commander_license_key'] ) ) ? '' : $options['collapse_commander_license_key'];
						    ?>
							<fieldset>
								<table style="width: 100%">
									<tbody>
										<tr>
											<th><?php _e( 'License Key', 'colpromat' ) ?>:</th>
											<td><label for="collapse_commander_license_key"><input type="text" id="collapse_commander_license_key" name="<?php echo esc_attr($this->license_name); ?>[collapse_commander_license_key]" value="<?php echo esc_attr( $cc_licence ); ?>" style="width: 100%" />
												<br /><span class="description"><?php _e('Enter your license key', 'jquery-collapse-o-matic'); ?></span></label>
											</td>

										</tr>

										<?php if( isset($options['collapse_commander_license_key']) ) { ?>
										    <tr valign="top">
											<th><?php _e('License Status', 'colpromat'); ?>:</th>
											<td>
											    <?php if( isset($options['collapse_commander_license_status']) && $options['collapse_commander_license_status'] == 'valid' ) { ?>
												<span style="color:green;"><?php _e('active'); ?></span><br/>
												<input type="submit" class="button-secondary" name="edd_cc_license_deactivate" value="<?php _e('Deactivate License', 'jquery-collapse-o-matic'); ?>"/>
											    <?php } else {
												    if( isset($options['collapse_commander_license_status']) ){ ?>
													<span style="color: red"><?php echo esc_attr($options['collapse_commander_license_status']); ?></span><br/>
												<?php } else { ?>
													<span style="color: grey"><?php _e('inactive', 'jquery-collapse-o-matic'); ?></span><br/>
												<?php } ?>
												    <input type="submit" class="button-secondary" name="edd_cc_license_activate" value="<?php _e('Activate License', 'jquery-collapse-o-matic'); ?>"/>
											    <?php } ?>
											    </td>
										    </tr>
										<?php } ?>
									</tbody>
								</table>
							</fieldset>
							<?php submit_button( __( 'Register', 'jquery-collapse-o-matic') ); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle', 'jquery-collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle">Collapse Commander</h3>
						<div class="inside">
							<p>A brief and not-exactly-sober overview of <a href="https://pluginoven.com/premium-plugins/collapse-commander/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-commander&utm_campaign=collapse-o-matic-commander">Collapse Commander</a>, a new add-on plugin for Collapse-O-Matic and Collapse-Pro-Matic that adds and advanded expand shortcode management system.</p>
							<iframe width="100%" height="300" src="//www.youtube.com/embed/w9X4nXpAEfo" frameborder="0" allowfullscreen></iframe>
						</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<?php endif; ?>
	<?php
	}

	/**
	 * Set options from save values or defaults
	 */
	function _set_options() {
		// set options
		$saved_options = get_option( $this->options_name );

		// backwards compatible (old values)
		if ( empty( $saved_options ) ) {
			$saved_options = get_option( $this->domain . 'options' );
		}
		// set all options
		if ( !empty( $saved_options ) ) {
			foreach ( $this->options AS $key => $option ) {
				if($key == 'tabindex'){
					$this->options[ $key ] = in_array( $key, $saved_options ) ? $saved_options[ $key ] : 0;
				}
				else{
					$this->options[ $key ] = ( empty( $saved_options[ $key ] ) ) ? '' : $saved_options[ $key ];
				}
			}
		}
	}

	function edd_sanitize_license( $new ) {
            //collapse commander
            $options = get_option($this->license_name);
            $old_cc = ( !isset( $options['collapse_commander_license_key'] ) ) ? '' : $options['collapse_commander_license_key'];
            $old_cc_status = ( !isset( $options['collapse_commander_license_status'] ) ) ? '' : $options['collapse_commander_license_status'];

            if( !empty($old_cc) && $old_cc != $new['collapse_commander_license_key'] ) {
                    $new['collapse_commander_license_status'] = '';
            }
            else{
                $new['collapse_commander_license_status'] = $old_cc_status;
            }

            if( isset( $_POST['edd_cc_license_activate'] ) ) {
                $new['collapse_commander_license_status'] = $this->plugin_oven_activate_license( urlencode( PLUGIN_OVEN_CC ), $new['collapse_commander_license_key'], 'activate_license');
            }

            if( isset( $_POST['edd_cc_license_deactivate'] ) ) {
                $new['collapse_commander_license_status'] = $this->plugin_oven_activate_license( urlencode( PLUGIN_OVEN_CC ), $new['collapse_commander_license_key'], 'deactivate_license');
            }
            return $new;
        }


	/************************************
	* this illustrates how to activate
	* a license key
	*************************************/

	function plugin_oven_activate_license($plugin_name, $license_key, $edd_action) {
            // data to send in our API request
            $api_params = array(
                    'edd_action'    => $edd_action,
                    'license' 	    => $license_key,
                    'item_name'     => $plugin_name,
                    'url'           => home_url()
            );

            // Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, PLUGIN_OVEN_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

            // make sure the response came back okay
            if ( is_wp_error( $response ) )
                    return false;

            // decode the license data
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            // $license_data->license will be either "valid" or "invalid"
            return $license_data->license;
	}

} // end class WP_Collapse_O_Matic


/**
 * Create instance
 */
$WP_Collapse_O_Matic = new WP_Collapse_O_Matic;

//clean unwanted p and br tags from shortcodes
//https://www.wpexplorer.com/clean-up-wordpress-shortcode-formatting
if (!function_exists('tp_clean_shortcodes')) {
	function tp_clean_shortcodes($content){
		$array = array (
		    '<p>[' => '[',
		    ']</p>' => ']',
		    ']<br />' => ']'
		);
		$content = strtr($content, $array);
		return $content;
	}
	add_filter('the_content', 'tp_clean_shortcodes');
}

?>
