<?php

if ( ! defined( 'CSS_DEBUG' ) ) {
	define( 'CSS_DEBUG', false );
}

// Register sidebars.
add_action( 'widgets_init', 'openlab_register_sidebars' );

// Install widgets.
add_action( 'admin_init', 'openlab_maybe_install', 5 );

function openlab_core_setup() {
	add_theme_support( 'post-thumbnails' );
	global $content_width;
	register_nav_menus(array(
		'main' => __( 'Main Menu', 'openlab' ),
		'aboutmenu' => __( 'About Menu', 'openlab' ),
		'helpmenu' => __( 'Help Menu', 'openlab' ),
		'helpmenusec' => __( 'Help Menu Secondary', 'openlab' ),
	));
}

// test
add_action( 'after_setup_theme', 'openlab_core_setup' );

function openlab_maybe_install() {
	if ( get_option( 'openlab_theme_installed' ) ) {
		return;
	}

	// Set flag early to prevent dupes.
	update_option( 'openlab_theme_installed', time() );

	require dirname( __FILE__ ) . '/lib/cbox-widget-setter.php';

	// Group Type widgets.
	if ( ! CBox_Widget_Setter::is_sidebar_populated( 'home-main' ) ) {
		$group_types = cboxol_get_group_types();
		foreach ( $group_types as $group_type ) {
			CBox_Widget_Setter::set_widget( array(
				'id_base'    => 'openlab_group_type',
				'sidebar_id' => 'home-main',
				'settings'   => array(
					'title' => $group_type->get_label( 'plural' ),
					'group_type' => $group_type->get_slug(),
				),
			) );
		}
	}

	// Home sidebar.
	if ( ! CBox_Widget_Setter::is_sidebar_populated( 'home-sidebar' ) ) {
		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'cac_featured_content_widget',
			'sidebar_id' => 'home-sidebar',
			'settings'   => array(
				'crop_length' => 300,
				'custom_description' => __( 'Use this space to highlight content from around your network.', 'openlab-theme' ),
				'display_images' => true,
				'featured_content_type' => 'resource',
				'featured_resource_title' => __( 'Featured Item', 'openlab-theme' ),
				'featured_resource_link' => home_url(),
				'image_url' => bp_core_avatar_default(),
				'image_height' => 50,
				'image_width' => 50,
				'read_more' => '',
				'title' => __( 'In The Spotlight', 'openlab-theme' ),
				'title_element' => 'h2',
			),
		) );

		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'openlab-whats-happening',
			'sidebar_id' => 'home-sidebar',
		) );

		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'openlab-whos-online',
			'sidebar_id' => 'home-sidebar',
		) );

		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'openlab-new-members',
			'sidebar_id' => 'home-sidebar',
		) );
	}

	// Footer sidebar.
	if ( ! CBox_Widget_Setter::is_sidebar_populated( 'footer' ) ) {
		$welcome_text = __( 'The footer areas can be used to display general information about your site, such as contact information and links to terms of service.', 'openlab-theme' );

		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'text',
			'sidebar_id' => 'footer',
			'settings'   => array(
				'title' => __( 'Footer area 1', 'openlab-theme' ),
				'text'  => $welcome_text,
				'filter' => false,
			),
		) );

		$welcome_text = sprintf( __( 'Modify the text of this and other widgets using the <a href="%s">Customizer</a>.', 'openlab-theme' ), admin_url( 'customize.php?autofocus[section]=sidebar-widgets-footer' ) );

		CBox_Widget_Setter::set_widget( array(
			'id_base'    => 'text',
			'sidebar_id' => 'footer',
			'settings'   => array(
				'title' => __( 'Footer area 2', 'openlab-theme' ),
				'text'  => $welcome_text,
				'filter' => false,
			),
		) );
	}

	// Nav menu.
	openlab_create_default_nav_menu();

	// Slider.
	$slides = array(
		array(
			'title' => __( 'Your Second Sample Slide', 'openlab-theme' ),
			'content' => 'Ex consequatur ipsam iusto id impedit nesciunt. Velit perspiciatis laborum et culpa rem earum. Beatae fugit perspiciatis dolorum. Incidunt voluptate officia cupiditate ipsum. Officiis eius quo incidunt voluptatem vitae deleniti aut. Non dolorem iste qui voluptates id ratione unde accusantium.',
			'image' => get_template_directory() . '/images/default-slide-1.jpeg',
		),
		array(
			'title' => __( 'Your First Sample Slide', 'openlab-theme' ),
			'content' => 'Ipsam et voluptas sed qui vel voluptatem quam. Qui pariatur occaecati consequatur quibusdam reiciendis aut asperiores nam. Esse et et id amet et quis. Beatae quaerat a ea expedita blanditiis quia. Doloremque ad nemo culpa. Quia at qui et.',
			'image' => get_template_directory() . '/images/default-slide-2.jpeg',
		),
	);

	// only need these if performing outside of admin environment
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	foreach ( $slides as $slide ) {
		$slide_id = wp_insert_post( array(
			'post_type' => 'slider',
			'post_status' => 'publish',
			'post_title' => $slide['title'],
			'post_content' => $slide['content'],
		) );

		$file_path = $slide['image'];

		// Generate attachment and set as featured post.
		$tmpfname = wp_tempnam( $file_path );
		copy( $file_path, $tmpfname );

		$file = array(
			'error' => null,
			'tmp_name' => $tmpfname,
			'size' => filesize( $file_path ),
			'name' => basename( $file_path ),
		);

		$overrides = array(
			'test_form' => false,
			'test_size' => false,
		);

		$sideloaded = wp_handle_sideload( $file, $overrides );

		$attachment = array(
			'post_mime_type' => $sideloaded['type'],
			'post_title' => basename( $tmpfname ),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_parent' => $slide_id,
		);

		$attachment_id = wp_insert_attachment( $attachment, $sideloaded['file'] );
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $sideloaded );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		set_post_thumbnail( $slide_id, $attachment_id );
	}

	remove_action( 'after_switch_theme', '_wp_sidebars_changed' );
}

function openlab_create_default_nav_menu() {
	$menu_name = wp_slash( __( 'Main Menu', 'cbox-openlab-core' ) );
	$menu_id = wp_create_nav_menu( $menu_name );

	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$brand_pages = cboxol_get_brand_pages();
	if ( isset( $brand_pages['about'] ) ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title' => $brand_pages['about']['title'],
				'menu-item-classes' => 'about',
				'menu-item-url' => $brand_pages['about']['preview_url'],
				'menu-item-status' => 'publish',
			)
		);
	}

	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title' => bp_get_directory_title( 'members' ),
			'menu-item-classes' => 'home',
			'menu-item-url' => bp_get_members_directory_permalink(),
			'menu-item-status' => 'publish',
		)
	);

	$group_types = cboxol_get_group_types();
	foreach ( $group_types as $group_type ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title' => $group_type->get_label( 'plural' ),
				'menu-item-classes' => 'group-type ' . $group_type->get_slug(),
				'menu-item-url' => bp_get_group_type_directory_permalink( $group_type->get_slug() ),
				'menu-item-status' => 'publish',
			)
		);
	}

	if ( isset( $brand_pages['help'] ) ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title' => $brand_pages['help']['title'],
				'menu-item-classes' => 'help',
				'menu-item-url' => $brand_pages['help']['preview_url'],
				'menu-item-status' => 'publish',
			)
		);
	}

	$locations = get_theme_mod( 'nav_menu_locations' );
	$locations['main'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/*
 * creating a library to organize functions* */
/* * core* */
require_once( get_template_directory() . '/lib/core/page-control.php' );
require_once( get_template_directory() . '/lib/core/frontend-admin.php' );
require_once( get_template_directory() . '/lib/core/backend-admin.php' );

require_once( get_template_directory() . '/lib/course-clone.php' );
require_once( get_template_directory() . '/lib/header-funcs.php' );
require_once( get_template_directory() . '/lib/post-types.php' );
require_once( get_template_directory() . '/lib/menus.php' );
require_once( get_template_directory() . '/lib/content-processing.php' );
require_once( get_template_directory() . '/lib/nav.php' );
require_once( get_template_directory() . '/lib/breadcrumbs.php' );
require_once( get_template_directory() . '/lib/shortcodes.php' );
require_once( get_template_directory() . '/lib/media-funcs.php' );
require_once( get_template_directory() . '/lib/group-funcs.php' );
require_once( get_template_directory() . '/lib/ajax-funcs.php' );
require_once( get_template_directory() . '/lib/help-funcs.php' );
require_once( get_template_directory() . '/lib/member-funcs.php' );
require_once( get_template_directory() . '/lib/page-funcs.php' );
require_once( get_template_directory() . '/lib/sidebar-funcs.php' );
require_once( get_template_directory() . '/lib/plugin-hooks.php' );
require_once( get_template_directory() . '/lib/theme-hooks.php' );
require_once( get_template_directory() . '/lib/widgets.php' );

require_once( get_template_directory() . '/lib/customizer.php' );
require_once( get_template_directory() . '/lib/buddypress.php' );

function openlab_load_scripts() {
	$stylesheet_dir_uri = get_template_directory_uri();

	/**
	 * scripts, additional functionality
	 */
	if ( ! is_admin() ) {

		// google fonts
		wp_register_style( 'google-open-sans', set_url_scheme( 'http://fonts.googleapis.com/css?family=Open+Sans:400,400italic,600,600italic,700,700italic' ), array(), '2014', 'all' );
		wp_enqueue_style( 'google-open-sans' );

		wp_register_style( 'camera-js-styles', $stylesheet_dir_uri . '/css/camera.css', array(), '20130604', 'all' );
		wp_enqueue_style( 'camera-js-styles' );

		// less compliation via js so we can check styles in firebug via fireless - local dev only
		if ( CSS_DEBUG ) {
			wp_register_script( 'less-config-js', $stylesheet_dir_uri . '/js/less.config.js', array( 'jquery' ) );
			wp_enqueue_script( 'less-config-js' );
			wp_register_script( 'less-js', $stylesheet_dir_uri . '/js/less-1.7.4.js', array( 'jquery' ) );
			wp_enqueue_script( 'less-js' );
		}

		wp_register_script( 'vendor-js', $stylesheet_dir_uri . '/js/dist/vendor.js', array( 'jquery' ), '1.6.8', true );
		wp_enqueue_script( 'vendor-js' );

		wp_register_script( 'select2', $stylesheet_dir_uri . '/js/select2.min.js', array( 'jquery' ) );

		$utility_deps = array( 'jquery', 'select2' );
		/*
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$utility_deps[] = 'hyphenator-js';
		} else {
			$utility_deps[] = 'openlab-smoothscroll';
		}
		*/
		wp_register_script( 'utility', $stylesheet_dir_uri . '/js/utility.js', $utility_deps, '1.6.9.8', true );

		wp_enqueue_script( 'utility' );
		wp_localize_script( 'utility', 'localVars', array(
			'nonce' => wp_create_nonce( 'request-nonce' ),
		) );

		wp_register_script( 'parsley', $stylesheet_dir_uri . '/js/parsley.min.js', array( 'jquery' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'openlab_load_scripts' );

function openlab_admin_scripts() {
	wp_register_script( 'utility-admin', get_template_directory_uri() . '/js/utility.admin.js', array( 'jquery', 'jquery-ui-autocomplete' ), '1.6.9.7', true );
	wp_enqueue_script( 'utility-admin' );
	wp_localize_script('utility-admin', 'localVars', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'request-nonce' ),
	));
}

add_action( 'admin_enqueue_scripts', 'openlab_admin_scripts' );

/**
 * Giving the main stylesheet the highest priority among stylesheets to make sure it loads last
 */
function openlab_load_scripts_high_priority() {
	$stylesheet_dir_uri = get_template_directory_uri();

	global $post;

	$color_scheme = openlab_get_color_scheme();

	// less compliation via js so we can check styles in firebug via fireless - local dev only
	// @to-do: way to enqueue as last item?
	if ( CSS_DEBUG ) {
		wp_register_style( 'main-styles', $stylesheet_dir_uri . '/style.less', array(), '1.6.9.5', 'all' );
		wp_enqueue_style( 'main-styles' );
	} else {

		wp_register_style( 'main-styles', $stylesheet_dir_uri . '/css/color-schemes/' . $color_scheme . '.css', array(), '1.6.9.5', 'all' );
		wp_enqueue_style( 'main-styles' );
	}

	if ( isset( $post->post_type ) && 'help' === $post->post_type ) {
		wp_register_style( 'print-styles', $stylesheet_dir_uri . '/css/print.css', array(), '2015', 'print' );
		wp_enqueue_style( 'print-styles' );
	}
}

add_action( 'wp_enqueue_scripts', 'openlab_load_scripts_high_priority', 999 );

/**
 * Custom image sizes
 */
// front page slider
add_image_size( 'front-page-slider', 735, 295, true );

/**
 * Register sidebar widget areas.
 *
 * @since 1.0.0
 */
function openlab_register_sidebars() {

	// Home sidebar.
	register_sidebar( array(
		'name' => __( 'Home Sidebar', 'openlab-theme' ),
		'description' => __( 'The sidebar at the left side of the home page.', 'openlab-theme' ),
		'id' => 'home-sidebar',
		'before_widget' => '<div id="%1$s" class="box-1 left-box widget %2$s">',
		'after_widget' => '</div>',
	) );

	// Home main (group type columns).
	register_sidebar( array(
		'name' => __( 'Home Main', 'openlab-theme' ),
		'description' => __( 'The main section of the home page. Generally used for group type widgets.', 'openlab-theme' ),
		'id' => 'home-main',
	) );

	// Sitewide footer.
	register_sidebar( array(
		'name' => __( 'Footer', 'openlab-theme' ),
		'description' => __( 'The footer that appears across the network.', 'openlab-theme' ),
		'id' => 'footer',
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget' => '</div>',
	) );
}

/**
 * Modify the body class
 *
 * Invite New Members and Your Email Options fall under "Settings", so need
 * an appropriate body class.
 */
function openlab_group_admin_body_classes( $classes ) {
	if ( ! bp_is_group() ) {
		return $classes;
	}

	if ( in_array( bp_current_action(), array( 'invite-anyone', 'notifications' ) ) ) {
		$classes[] = 'group-admin';
	}

	return $classes;
}

add_filter( 'bp_get_the_body_class', 'openlab_group_admin_body_classes' );

// for less js - local dev only
function enqueue_less_styles( $tag, $handle ) {
	global $wp_styles;
	$match_pattern = '/\.less$/U';
	if ( preg_match( $match_pattern, $wp_styles->registered[ $handle ]->src ) ) {
		$handle = $wp_styles->registered[ $handle ]->handle;
		$media = $wp_styles->registered[ $handle ]->args;
		$href = $wp_styles->registered[ $handle ]->src;
		$rel = isset( $wp_styles->registered[ $handle ]->extra['alt'] ) && $wp_styles->registered[ $handle ]->extra['alt'] ? 'alternate stylesheet' : 'stylesheet';
		$title = isset( $wp_styles->registered[ $handle ]->extra['title'] ) ? "title='" . esc_attr( $wp_styles->registered[ $handle ]->extra['title'] ) . "'" : '';

		$tag = "<link rel='stylesheet/less' $title href='$href' type='text/css' media='$media' />";
	}
	return $tag;
}

add_filter( 'style_loader_tag', 'enqueue_less_styles', 5, 2 );

/**
 * Get content with formatting in place
 *
 * @param type $more_link_text
 * @param type $stripteaser
 * @param type $more_file
 * @return type
 */
function get_the_content_with_formatting( $more_link_text = '(more...)', $stripteaser = 0, $more_file = '' ) {
	$content = get_the_content( $more_link_text, $stripteaser, $more_file );
	$content = apply_filters( 'the_content', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );
	return $content;
}

/**
 * Get a value from a failed POST request, especially during registration.
 */
function openlab_post_value( $key ) {
	$value = '';
	if ( ! empty( $_POST[ $key ] ) ) {
		$value = wp_unslash( $_POST[ $key ] );
	}
	return $value;
}

/**
 * Disable the new avatar upload interface introduced in BP 2.3.
 */
add_filter( 'bp_avatar_is_front_edit', '__return_false' );

/**
 * Generate data attributes for xprofile 'input' fields.
 *
 * Used for Parsely validation.
 */
function openlab_profile_field_input_attributes() {
	$attributes = array();

	switch ( bp_get_the_profile_field_name() ) {
		case 'Name' :
			$attributes[] = 'data-parsley-required';
			$attributes[] = 'data-parsley-required';
			break;

		case 'First Name' :
			$attributes[] = 'data-parsley-required';
			break;

		case 'Last Name' :
			$attributes[] = 'data-parsley-required';
			break;

		case 'Account Type' :
			$attributes[] = 'data-parsley-required';
			break;
	}

	if ( $attributes ) {
		return ' ' . implode( ' ', $attributes ) . ' ';
	}
}

/**
 * Fetch the sitewide footer markup, storing in a transient if necessary.
 */
function openlab_site_footer() {
	$footer = get_site_transient( 'cboxol_network_footer' );

	if ( $footer ) {
		echo $footer;
	}

	ob_start(); ?>

<div id="openlab-footer" class="oplb-bs page-table-row">
	<div class="oplb-bs">
		<div class="footer-wrapper">
			<div class="container-fluid footer-desktop">
				<div class="row row-footer">
					<?php dynamic_sidebar( 'footer' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>

	<?php
	$footer = ob_get_contents();
	ob_end_clean();

	set_site_transient( 'cboxol_network_footer', $footer, DAY_IN_SECONDS );

	echo $footer;
}
