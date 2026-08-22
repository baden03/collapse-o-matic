<?php
/*
Plugin Name: Collapse-O-Matic
Text Domain: collapse-o-matic
Domain Path: /languages
Plugin URI: https://pluginoven.com/plugins/collapse-o-matic/
Description: Collapse-O-Matic adds an [expand] shortcode that wraps content into a lovely, jQuery collapsible div.
Version: 1.8.6
Author: twinpictures, baden03
Author URI: https://twinpictures.de/
License: GPL2
*/

/**
 * Class WP_Collapse_O_Matic
 * @package WP_Collapse_O_Matic
 * @category WordPress Plugins
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	var $version = '1.8.6';

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
		// must not run before `init`, or WP 6.7+ raises a _load_textdomain_just_in_time notice
		add_action( 'init', array( $this, 'load_textdomain' ) );

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
	 * Callback init: load translations
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'collapse-o-matic', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * is_plugin_active() lives in wp-admin/includes/plugin.php, which is not loaded
	 * on the front end. Make sure it is available before asking it anything.
	 */
	function cc_is_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'collapse-commander/collapse-commander.php' );
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
		wp_register_script('collapseomatic-js', plugins_url('js/collapse.js', __FILE__), array('jquery'), '1.7.3', $load_in_footer);
		
		//prep options for injection
		$com_options = [
			'colomatduration' => $this->options['duration'],
			'colomatslideEffect' => $this->options['slideEffect'],
			'colomatpauseInit' => $this->options['pauseinit'],
			'colomattouchstart' => $this->options['touch_start']
		];
		wp_add_inline_script( 'collapseomatic-js', 'const com_options = ' . wp_json_encode( $com_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );

		if( empty($this->options['script_check']) ){
			wp_enqueue_script('collapseomatic-js');
		}

		//css
		wp_register_style( 'collapscore-css', plugins_url('css/core_style.css', __FILE__) , array (), '1.0' );
		wp_register_style( 'collapseomatic-css', plugins_url('css/'.$this->options['style'].'_style.css', __FILE__) , array (), '1.6' );
		if( !empty( $this->options['custom_css'] ) ){
			//strip tags so stored CSS can never close the <style> block and inject script
			wp_add_inline_style( 'collapscore-css', wp_strip_all_tags( $this->options['custom_css'] ) );
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
			$page = add_options_page(
				__( 'Collapse-O-Matic Options', 'collapse-o-matic' ),
				__( 'Collapse-O-Matic', 'collapse-o-matic' ),
				'manage_options',
				'collapse-o-matic-options',
				array( $this, 'options_page' )
			);
		}
	}

	/**
	 * Callback admin_init
	 */
	function admin_init() {
		// register settings
		register_setting( $this->domain, $this->options_name, array( 'sanitize_callback' => array( $this, 'sanitize_options' ) ) );
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
				'orderby' => '',
				'order' => ''
			), $atts, 'expand'));

		//collapse commander
		if( !empty($cid) && $this->cc_is_active() ){
			$meta_values = WP_CollapseCommander::meta_grabber($cid, $orderby, $order);
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
			$content .= '<div class="com_edit_link"><a class="post-edit-link" href="'.esc_url( get_edit_post_link($cid) ).'">'.esc_html__( 'Edit', 'collapse-o-matic' ).'</a></div>';
		}

		if( !empty($sub_cids) ){
			foreach($sub_cids as $sub_cid){
				$args = array('cid' => $sub_cid);
				$content .= $this->shortcode($args);
			}
		}

		$ewo = '';
		$ewc = '';

		//id does not allow spaces or any funny business
		$id = preg_replace('/\s+/', '_', esc_attr($id));

		//placeholders
		$placeholder_arr = array('%(%', '%)%', '%{%', '%}%');
		$swapout_arr = array('<', '>', '[', ']');

		$allowed_tags = [
			"div", "span", "p", "li", "ul", "ol", "strong", "b",
			"em", "i", "u", "h1", "h2", "h3", "h4", "h5", "h6",
			"blockquote", "a", "img", "button", "tr", "td", "th", "caption", "small", "cite", "q"
		];

		//the `tag` option is admin-supplied: only let it widen the allow-list if it is a bare tag name
		if( preg_match( '/\A[a-zA-Z][a-zA-Z0-9]*\Z/', (string) $options['tag'] ) ){
			$allowed_tags[] = $options['tag'];
		}

		if(!empty($tag)){
			$tag = $this->filter_allowed_tags( $tag, $allowed_tags );
		}
		if(empty($tag)){
			$tag = 'span';
		}

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
		if(!empty($targtag)){
			$targtag = $this->filter_allowed_tags( $targtag, $allowed_tags );
		}
		if(empty($targtag)){
			$targtag = 'div';
		}

		//excerpttag is used as a raw HTML tag name, so restrict it to the same allow-list as tag/targtag
		$excerpttag = $this->filter_allowed_tags( $excerpttag, $allowed_tags );
		if(empty($excerpttag)){
			$excerpttag = 'div';
		}

		
		if(!empty($elwraptag)){
			$ewclass = '';
			if($elwrapclass){
				$ewclass = 'class="'.esc_attr($elwrapclass).'"';
			}
			$elwraptag = $this->filter_allowed_tags( $elwraptag, $allowed_tags );
			if(empty($elwraptag)){
				$elwraptag = 'div';
			}

			$ewo = '<'. $elwraptag .' '.$ewclass.'>';
			$ewc = '</'. $elwraptag .'>';
		}

		$eDiv = '';

		if($content){
			$inline_class = '';
			$collapse_class = 'collapseomatic_content ';
			if($targpos == 'inline'){
				$inline_class = 'colomat-inline ';
				$collapse_class = 'collapseomatic_content_inline ';
			}
			$eDiv = '<'. esc_attr(  $targtag ) .' id="target-'.$id.'" class="'.esc_attr($collapse_class.$inline_class.$targclass).'">'. $content .'</'. esc_attr(  $targtag ) .'>';
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
				$nibble = '<'. esc_attr( $excerpttag ) .' id="excerpt-'.esc_attr($id).'" class="'.esc_attr($excerptclass).'">'. wp_kses_post( $excerpt ).'</'. esc_attr( $excerpttag ) .'>';
			}
			else{
				$nibble = '<'. esc_attr( $excerpttag ) .' id="excerpt-'.esc_attr($id).'" class="collapseomatic_excerpt '.esc_attr($excerptclass).'">'. wp_kses_post( $excerpt ) .'</'. esc_attr( $excerpttag ) .'>';
			}
			//swapexcerpt
			if($swapexcerpt !== false){
				$swapexcerpt = str_replace($placeholder_arr, $swapout_arr, $swapexcerpt);
				$swapexcerpt = do_shortcode($swapexcerpt);
				$swapexcerpt = apply_filters( 'colomat_swapexcerpt', $swapexcerpt );
				$nibble .= '<'. esc_attr( $excerpttag ) .' id="swapexcerpt-'.esc_attr($id).'" style="display:none;">'. wp_kses_post( $swapexcerpt ).'</'. esc_attr( $excerpttag ) .'>';
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
			$anchor = 'data-findme="'.esc_attr($offset).'"';
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
			$link = $closeanchor.'<'. esc_attr($tag) .' class="collapseomatic '.esc_attr($trigclass).'" id="'.esc_attr($id).'" '.$relatt.' '.$inexatt.' '.$altatt.' '.$anchor.' '.$groupatt.' '.$effatt.' '.$duratt.'>'.wp_kses_post($startwrap.$title.$endwrap).'</'. esc_attr($tag) .'>';
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
				$swapalt_attr = "alt='".esc_attr($swapalt)."'";
			}
			$link .= "<". esc_attr($tag) ." id='swap-".esc_attr($id)."' ".$swapalt_attr." class='colomat-swap' style='display:none;'>".wp_kses_post($startwrap.$swaptitle.$endwrap)."</". esc_attr($tag) .">";
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
		$new_links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=collapse-o-matic-options' ) ) . '">' . __('Settings', 'collapse-o-matic') . '</a>';
		return array_merge($new_links, $links);
	}

	/**
	 * Admin options page
	 */
	function options_page() {
	?>
		<div class="wrap">
			<h2>Collapse-O-Matic</h2>
		</div>

		<div class="postbox-container metabox-holder meta-box-sortables" style="width: 69%">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle"><?php esc_html_e( 'Default Collapse-O-Matic Settings', 'collapse-o-matic' ) ?></h3>
					<div class="inside">
						<form method="post" action="options.php">
							<?php
								settings_fields( $this->domain );
								$options = $this->options;
							?>
							<fieldset class="options">
								<table class="form-table">
								<tr>
									<th><?php esc_html_e( 'Style', 'collapse-o-matic' ) ?>:</th>
									<td><label><select id="style" name="<?php echo esc_attr($this->options_name); ?>[style]">
										<?php
											if(empty($options['style'])){
												$options['style'] = 'light';
											}
											$st_array = array(
												'light' => __('Light', 'collapse-o-matic'),
												'dark' => __('Dark', 'collapse-o-matic'),
												'none' => __('None', 'collapse-o-matic')
											);
											//keyed by stored value: a translation must never be able to collide with another option
											foreach( $st_array as $value => $label){
												echo '<option value="'.esc_attr($value).'" '.selected($options['style'], $value, false).'>'.esc_html($label).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php esc_html_e('Select Light for sites with lighter backgrounds. Select Dark for sites with darker backgrounds. Select None to handle styling yourself.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<?php if( $this->cc_is_active() ) : ?>
								<tr>
									<th><?php esc_html_e( 'CID Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="cid" name="<?php echo esc_attr($this->options_name); ?>[cid]" value="<?php echo esc_attr($options['cid']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'Default %1$sCollapse Commander%2$s ID', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/premium-plugins/collapse-commander/" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>
								<?php else: ?>
								<tr>
									<th><?php esc_html_e( 'Collapse Management', 'collapse-o-matic' ) ?></th>
									<td><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( '%1$sCollapse Commander%2$s is an add-on plugin that introduces an advanced management interface to better organize expand elements and simplify expand shortcodes.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/premium-plugins/collapse-commander/">', '</a>'
									) ); ?>
									</td>
								</tr>
								<?php endif; ?>

								<tr>
									<th><?php esc_html_e( 'Tag Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="tag" name="<?php echo esc_attr($this->options_name); ?>[tag]" value="<?php echo esc_attr($options['tag']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'HTML tag use to wrap the trigger text. See %1$sTag Attribute%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#tag-attribute" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Trigclass Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="trigclass" name="<?php echo esc_attr($this->options_name); ?>[trigclass]" value="<?php echo esc_attr($options['trigclass']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'Default class assigned to the trigger element. See %1$sTrigclass Attribute%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#trigclass-attribute" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Tabindex Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="tabindex" name="<?php echo esc_attr($this->options_name); ?>[tabindex]" value="<?php echo esc_attr($options['tabindex']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'Default tabindex value to be assigned to the trigger element. See %1$sTabindex Attribute%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#tabindex-attribute" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Targtag Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="targtag" name="<?php echo esc_attr($this->options_name); ?>[targtag]" value="<?php echo esc_attr($options['targtag']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'HTML tag use for the target element. See %1$sTargtag Attribute%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#targtag-attribute" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Targclass Attribute', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="targclass" name="<?php echo esc_attr($this->options_name); ?>[targclass]" value="<?php echo esc_attr($options['targclass']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'Default class assigned to the target element. See %1$sTargclass Attribute%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/shortcode/#targclass-attribute" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'No Title', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="notitle" name="<?php echo esc_attr($this->options_name); ?>[notitle]" value="1"  <?php echo checked( $options['notitle'], 1 ); ?> /> <?php esc_html_e('No Title', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Do not use title tags by default.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Add touchstart', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="touch_start" name="<?php echo esc_attr($this->options_name); ?>[touch_start]" value="1"  <?php echo checked( $options['touch_start'], 1 ); ?> /> <?php esc_html_e('Add touchstart', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Add jQuery touchstart binding to triggers.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Initial Pause', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="number" id="pauseinit" name="<?php echo esc_attr($this->options_name); ?>[pauseinit]" value="<?php echo esc_attr($options['pauseinit']); ?>" />
										<br /><span class="description"><?php esc_html_e('Amount of time in milliseconds to pause before the initial collapse is triggered on page load.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<?php
										if(empty($options['duration'])){
												$options['duration'] = 'fast';
										}
									?>
									<th><?php esc_html_e( 'Animation Duration', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="text" id="duration" name="<?php echo esc_attr($this->options_name); ?>[duration]" value="<?php echo esc_attr($options['duration']); ?>" />
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'A string or number determining how long the animation will run. See %1$sDuration%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/#duration" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Animation Effect', 'collapse-o-matic' ) ?>:</th>
									<td><label><select id="slideEffect" name="<?php echo esc_attr($this->options_name); ?>[slideEffect]">
										<?php
											if(empty($options['slideEffect'])){
												$options['slideEffect'] = 'slideFade';
											}
											$se_array = array(
												'slideToggle' => __('Slide Only', 'collapse-o-matic'),
												'slideFade' => __('Slide & Fade', 'collapse-o-matic'),
												'fadeOnly' => __('Fade Only', 'collapse-o-matic')
											);
											//keyed by stored value: a translation must never be able to collide with another option
											foreach( $se_array as $value => $label){
												echo '<option value="'.esc_attr($value).'" '.selected($options['slideEffect'], $value, false).'>'.esc_html($label).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'Animation effect to use while collapsing and expanding. See %1$sAnimation Effect%2$s in the documentation for more info.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/#animation-effect" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Custom Style', 'collapse-o-matic' ) ?>:</th>
									<td><label><textarea id="custom_css" name="<?php echo esc_attr($this->options_name); ?>[custom_css]"><?php echo esc_textarea($options['custom_css']); ?></textarea>
										<br /><span class="description"><?php echo wp_kses_post( __( 'Custom CSS style for <em>ultimate flexibility</em>', 'collapse-o-matic' ) ) ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Content Filter', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="filter_content" name="<?php echo esc_attr($this->options_name); ?>[filter_content]" value="1"  <?php echo checked( $options['filter_content'], 1 ); ?> /> <?php esc_html_e('Apply filter', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Apply the_content filter to target content.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<?php
									//if collapse-commander is installed, display options for displaying id and text in shortocdes
									if( $this->cc_is_active() ) :
								?>
								<tr>
									<th><?php esc_html_e( 'Display ID', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="cc_display_id" name="<?php echo esc_attr($this->options_name); ?>[cc_display_id]" value="1"  <?php echo checked( $options['cc_display_id'], 1 ); ?> /> <?php esc_html_e('Display ID', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Display custom ID attribute in shortcodes if set for easier shortcode managment.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Display Title', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="cc_display_title" name="<?php echo esc_attr($this->options_name); ?>[cc_display_title]" value="1"  <?php echo checked( $options['cc_display_title'], 1 ); ?> /> <?php esc_html_e('Display Title', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Display custom eT attribute in shortcodes that shows expand title for easier shortcode managment.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>
								<?php endif; ?>

								<tr>
									<th><?php esc_html_e( 'Shortcode Scripts', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="script_check" name="<?php echo esc_attr($this->options_name); ?>[script_check]" value="1"  <?php echo checked( $options['script_check'], 1 ); ?> /> <?php esc_html_e('Only load scripts with shortcode.', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Only load Collapse-O-Matic scripts if [expand] shortcode is used.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Shortcode CSS', 'collapse-o-matic' ) ?>:</th>
									<td><label><input type="checkbox" id="css_check" name="<?php echo esc_attr($this->options_name); ?>[css_check]" value="1"  <?php echo checked( $options['css_check'], 1 ); ?> /> <?php esc_html_e('Only load CSS with shortcode.', 'collapse-o-matic'); ?>
										<br /><span class="description"><?php esc_html_e('Only load Collapse-O-Matic CSS if [expand] shortcode is used.', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Script Load Location', 'collapse-o-matic' ) ?>:</th>
									<td><label><select id="script_location" name="<?php echo esc_attr($this->options_name); ?>[script_location]">
										<?php
											if(empty($options['script_location'])){
												$options['script_location'] = 'footer';
											}
											$sl_array = array(
												'header' => __('Header', 'collapse-o-matic'),
												'footer' => __('Footer', 'collapse-o-matic')
											);
											//keyed by stored value: a translation must never be able to collide with another option
											foreach( $sl_array as $value => $label){
												echo '<option value="'.esc_attr($value).'" '.selected($options['script_location'], $value, false).'>'.esc_html($label).'</option>';
											}
										?>
										</select>
										<br /><span class="description"><?php esc_html_e('Where should the script be loaded, in the Header or the Footer?', 'collapse-o-matic'); ?></span></label>
									</td>
								</tr>
								<?php if( !$this->cc_is_active() ) : ?>
								<tr>
									<th><strong><?php esc_html_e( 'Take Command!', 'collapse-o-matic' ) ?></strong></th>
									<td><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( '%1$sCollapse Commander%2$s is an add-on plugin that introduces an advanced management interface to better organize expand elements and simplify expand shortcodes.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/premium-plugins/collapse-commander/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-commander&utm_campaign=collapse-o-matic-commander" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><strong><?php esc_html_e( 'Level Up!', 'collapse-o-matic' ) ?></strong></th>
									<td><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag to the plugin page, %2$s: closing link tag, %3$s: opening link tag to the testimonials page, %4$s: closing link tag. */
										__( '%1$sCollapse-Pro-Matic%2$s is our premium plugin that offers additional attributes and features for <i>ultimate</i> flexibility, in addition to a very %3$shigh level of personal support%4$s.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/premium-plugins/collapse-pro-matic/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-pro-matic&utm_campaign=collapse-o-matic-pro" target="_blank" rel="noopener noreferrer">', '</a>', '<a href="https://pluginoven.com/premium-plugins/collapse-pro-matic/testimonials/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-pro-matic&utm_campaign=collapse-o-matic-support" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?>
									</td>
								</tr>
								</table>
							</fieldset>

							<p class="submit">
								<input class="button-primary" type="submit" value="<?php esc_attr_e( 'Save Changes', 'collapse-o-matic' ) ?>" />
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle"><?php esc_html_e( 'About', 'collapse-o-matic' ) ?></h3>
					<div class="inside">
						<h4><img src="<?php echo esc_url( plugins_url( 'css/images/collapse-o-matic-icon.png', __FILE__ ) ) ?>" width="16" height="16" alt=""/> <?php
							/* translators: %s: plugin version number. */
							printf( esc_html__( 'Collapse-O-Matic Version %s', 'collapse-o-matic' ), esc_html( $this->version ) );
						?></h4>
						<p><?php echo wp_kses_post( __( 'Remove clutter, save space. Display and hide additional content in a SEO friendly way. Wrap any content&mdash;including other shortcodes&mdash;into a lovely jQuery expanding and collapsing element.', 'collapse-o-matic') ) ?></p>
						<ul>
							<li><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( '%1$sDetailed documentation%2$s, complete with working demonstrations of all shortcode attributes, is available for your instructional enjoyment.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/plugins/collapse-o-matic/documentation/" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></li>
							<li><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( '%1$sGitHub Issues%2$s', 'collapse-o-matic' ),
										'<a href="https://github.com/baden03/collapse-o-matic/issues" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></li>
							<li><a href="https://github.com/baden03/collapse-o-matic" target="_blank" rel="noopener noreferrer">GitHub</a> | <a href="https://pluginoven.com/plugins/collapse-o-matic/" target="_blank" rel="noopener noreferrer">Twinpictures Plugin Oven</a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<?php if( $this->cc_is_active() ) : ?>

		<div class="postbox-container side metabox-holder" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<h3 class="handle"><?php esc_html_e( 'Register Collapse Commander', 'collapse-o-matic') ?></h3>
					<div class="inside">
                                            <p><?php echo wp_kses_post( sprintf(
										/* translators: %1$s: opening link tag, %2$s: closing link tag. */
										__( 'To receive plugin updates you must register your plugin. Enter your Collapse Commander licence key below. Licence keys may be viewed and managed by logging into %1$syour account%2$s.', 'collapse-o-matic' ),
										'<a href="https://pluginoven.com/my-account/" target="_blank" rel="noopener noreferrer">', '</a>'
									) ); ?></p>
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
											<th><?php esc_html_e( 'License Key', 'collapse-o-matic' ) ?>:</th>
											<td><label for="collapse_commander_license_key"><input type="text" id="collapse_commander_license_key" name="<?php echo esc_attr($this->license_name); ?>[collapse_commander_license_key]" value="<?php echo esc_attr( $cc_licence ); ?>" style="width: 100%" />
												<br /><span class="description"><?php esc_html_e('Enter your license key', 'collapse-o-matic'); ?></span></label>
											</td>

										</tr>

										<?php if( isset($options['collapse_commander_license_key']) ) { ?>
										    <tr valign="top">
											<th><?php esc_html_e('License Status', 'collapse-o-matic'); ?>:</th>
											<td>
											    <?php if( isset($options['collapse_commander_license_status']) && $options['collapse_commander_license_status'] == 'valid' ) { ?>
												<span style="color:green;"><?php esc_html_e( 'active', 'collapse-o-matic' ); ?></span><br/>
												<input type="submit" class="button-secondary" name="edd_cc_license_deactivate" value="<?php esc_attr_e('Deactivate License', 'collapse-o-matic'); ?>"/>
											    <?php } else {
												    if( isset($options['collapse_commander_license_status']) ){ ?>
													<span style="color: red"><?php echo esc_attr($options['collapse_commander_license_status']); ?></span><br/>
												<?php } else { ?>
													<span style="color: grey"><?php esc_html_e('inactive', 'collapse-o-matic'); ?></span><br/>
												<?php } ?>
												    <input type="submit" class="button-secondary" name="edd_cc_license_activate" value="<?php esc_attr_e('Activate License', 'collapse-o-matic'); ?>"/>
											    <?php } ?>
											    </td>
										    </tr>
										<?php } ?>
									</tbody>
								</table>
							</fieldset>
							<?php submit_button( __( 'Register', 'collapse-o-matic') ); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:29%;">
			<div style="margin:0 5px;">
				<div class="postbox">
					<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'collapse-o-matic' ) ?>"><br/></div>
					<h3 class="hndle">Collapse Commander</h3>
						<div class="inside">
							<p><?php
								/* translators: %1$s: opening link tag, %2$s: closing link tag. */
								echo wp_kses_post( sprintf(
									__( 'A brief and not-exactly-sober overview of %1$sCollapse Commander%2$s, a new add-on plugin for Collapse-O-Matic and Collapse-Pro-Matic that adds an advanced expand shortcode management system.', 'collapse-o-matic' ),
									'<a href="https://pluginoven.com/premium-plugins/collapse-commander/?utm_source=collapse-o-matic&utm_medium=plugin-settings-page&utm_content=collapse-commander&utm_campaign=collapse-o-matic-commander" target="_blank" rel="noopener noreferrer">',
									'</a>'
								) );
							?></p>
							<iframe width="100%" height="300" src="https://www.youtube.com/embed/w9X4nXpAEfo" title="<?php esc_attr_e( 'Collapse Commander overview', 'collapse-o-matic' ); ?>" loading="lazy" frameborder="0" allowfullscreen></iframe>
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
					$this->options[ $key ] = array_key_exists( $key, $saved_options ) ? $saved_options[ $key ] : 0;
				}
				else{
					$this->options[ $key ] = ( empty( $saved_options[ $key ] ) ) ? '' : $saved_options[ $key ];
				}
			}
		}

		// normalise values that get echoed into markup or built into a file path,
		// so options stored before sanitising was added are still safe to use
		$this->options['style']           = $this->sanitize_choice( $this->options['style'], array( 'light', 'dark', 'none' ), 'light' );
		$this->options['script_location'] = $this->sanitize_choice( $this->options['script_location'], array( 'header', 'footer' ), 'footer' );
		$this->options['slideEffect']     = $this->sanitize_choice( $this->options['slideEffect'], array( 'slideToggle', 'slideFade', 'fadeOnly' ), 'slideFade' );
	}

	/**
	 * Return $value if it is one of $choices, otherwise $default
	 */
	function sanitize_choice( $value, $choices, $default ) {
		return in_array( $value, $choices, true ) ? $value : $default;
	}

	/**
	 * Sanitize callback for the main options group
	 */
	function sanitize_options( $input ) {
		$clean = array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$checkboxes = array( 'notitle', 'script_check', 'css_check', 'filter_content', 'cc_display_id', 'cc_display_title', 'touch_start' );
		$plain_text = array( 'cid', 'trigclass', 'targclass', 'duration', 'cc_download_key' );

		foreach ( $this->options as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : '';

			if ( in_array( $key, $checkboxes, true ) ) {
				$clean[ $key ] = empty( $value ) ? '' : 1;
			}
			elseif ( in_array( $key, $plain_text, true ) ) {
				$clean[ $key ] = sanitize_text_field( $value );
			}
			elseif ( 'style' === $key ) {
				$clean[ $key ] = $this->sanitize_choice( $value, array( 'light', 'dark', 'none' ), 'light' );
			}
			elseif ( 'script_location' === $key ) {
				$clean[ $key ] = $this->sanitize_choice( $value, array( 'header', 'footer' ), 'footer' );
			}
			elseif ( 'slideEffect' === $key ) {
				$clean[ $key ] = $this->sanitize_choice( $value, array( 'slideToggle', 'slideFade', 'fadeOnly' ), 'slideFade' );
			}
			elseif ( 'tag' === $key || 'targtag' === $key ) {
				// bare HTML tag names only: these are written straight into markup
				$value = strtolower( trim( (string) $value ) );
				$clean[ $key ] = preg_match( '/\A[a-z][a-z0-9]*\Z/', $value ) ? $value : ( 'tag' === $key ? 'span' : 'div' );
			}
			elseif ( 'tabindex' === $key || 'pauseinit' === $key ) {
				$clean[ $key ] = ( '' === $value || ! is_numeric( $value ) ) ? '' : (string) intval( $value );
			}
			elseif ( 'custom_css' === $key ) {
				// no tags, so the value can never close the inline <style> block
				$clean[ $key ] = wp_strip_all_tags( (string) $value );
			}
			elseif ( 'cc_email' === $key ) {
				$clean[ $key ] = sanitize_email( $value );
			}
			else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}

		return $clean;
	}

	function edd_sanitize_license( $new ) {
            //collapse commander
            if ( ! is_array( $new ) ) {
                $new = array();
            }
            $new['collapse_commander_license_key'] = isset( $new['collapse_commander_license_key'] ) ? sanitize_text_field( $new['collapse_commander_license_key'] ) : '';

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
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, PLUGIN_OVEN_URL ) ), array( 'timeout' => 15 ) );

            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) )
                    return '';

            // decode the license data
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            // $license_data->license will be either "valid" or "invalid"
            if ( ! is_object( $license_data ) || ! isset( $license_data->license ) )
                    return '';

            return sanitize_text_field( $license_data->license );
	}

	/**
	 * Filter $input to allow only tags from $allowed_tags array
	 */
	function filter_allowed_tags( $input, $allowed_tags ) {
		//quote every entry: the list is interpolated into a pattern, and must not
		//be able to smuggle in regex metacharacters
		$quoted  = array_map( function( $tag ) { return preg_quote( (string) $tag, '~' ); }, $allowed_tags );
		$pattern = '~\A(' . implode( '|', $quoted ) . ')\Z~';
		if ( preg_match( $pattern, $input, $matches ) ) {
			$output = $matches[0];
		} else {
			$output = '';
		}

		return $output;
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
