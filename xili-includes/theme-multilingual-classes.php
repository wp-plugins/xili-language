<?php

// xili-options class
// part of xili-language plugin

if ( ! class_exists ( 'xili_language_theme_options' )  ) {
	
	class xili_language_theme_options  {
		
		var $settings_name;
		var $theme_name;
		var $theme_domain;
		var $child_version;
		var $capability;
		
		function __construct( $xili_args ) {
			$xili_args_default = array (
 				'settings_name' => 'xili_theme_options', // name of array saved in options table
 				'theme_name' => 'Twenty Twelve',
 				'theme_domain' => 'twentytwelve',
 				'child_version' => '0.0.1',
 				'capability' => 'edit_theme_options',
 				'customize_adds' => true, // add settings in customize page only if called by developer
 				'customize_add_menu' => false,
 				'customize_clone_widget_containers' => false // set during instancing by developer
			);
			
			$xili_args = wp_parse_args( $xili_args, $xili_args_default );
			
			$this->settings_name = $xili_args ['settings_name'];
			$this->theme_name = $xili_args ['theme_name'];
			$this->theme_domain = $xili_args ['theme_domain'];
			$this->child_version = $xili_args ['child_version'];
			$this->capability = $xili_args ['capability'];
			$this->customize_adds = $xili_args ['customize_adds'];
			$this->customize_clone_widget_containers = $xili_args ['customize_clone_widget_containers'];
			
			if ( $this->customize_adds ) {
				add_action( 'customize_register', array( $this, 'customize_registering' ), 1 ); 
			}

			add_action( 'wp_head', array( $this, 'special_head' ), 11 );
			
			add_action ( 'init', array( $this, 'xili_create_menu_locations'), 100 );
			add_filter ( 'wp_nav_menu_args', array( $this, 'xili_wp_nav_menu_args') ); // called at line #145 in nav-menu-template.php
			
			if ( $this->customize_clone_widget_containers ) {
				add_action ( 'init', array( $this, 'xili_clone_sidebar_container'), 101);
			}
		}
		
		function get_theme_xili_options() {
			return get_option( $this->settings_name, $this->get_default_theme_xili_options() );
		}

		function get_default_theme_xili_options() {
			return array( 'no_flags' => '' , 'linked_posts' => '1', 'linked_title' => 'Read this post in',  'nav_menus' => 0) ;
		}
		
		// create options in customize theme screen to be refreshed
		function customize_registering ( $wp_customize ) {
			global $xili_language;
			
			// in_nav_menu
		
			$locations      = get_registered_nav_menus();
			$menus          = wp_get_nav_menus();
			$menu_locations = get_nav_menu_locations();
			$num_locations  = count( array_keys( $locations ) );
			
			if ( $num_locations >= 1 && count ( $menu_locations ) >= 1 ) {
				
				$wp_customize->add_section( 'xili_options_section' , array(
		    	'title'      => __('Multilingual Options ©xili', 'xili-language' ),
		    	'priority'   => 300,
		    	'description'    => sprintf( _n('Your theme supports %s menu. Customize style.', 'Your theme supports %s menus. Customize style.', $num_locations, 'xili-language'), number_format_i18n( $num_locations ) ) . "\n\n" . __('You can edit your menu content on the Menus screen in the Appearance section.'),
			) );
		
				$location_slugs = array_keys( $locations );
				if ( 0 == $xili_language->has_languages_list_menu ( $location_slugs[0] ) ) { // only test one menu during transition
		
					$wp_customize->add_setting( 'xili_language_settings[in_nav_menu]' , array(
				    	'default'     => '',
				    	'transport'   => 'refresh',	
				    	'type'     => 'option',
				    	'capability'  => $this->capability,
				    	
					) );
				
					$wp_customize->add_control( 'in_nav_menu', array(
				    	'settings' => 'xili_language_settings[in_nav_menu]',
				    	'label'    => __( 'Append the languages', 'xili-language' ),
				    	'section'  => 'xili_options_section',
				    	'type'     => 'radio',
				    	'choices'    => array(
						'disable'	 => __('No languages menu-items', 'xili-language' ),
						'enable'     => __('Show languages menu-items', 'xili-language' )
					)
					) );
					
					$wp_customize->add_setting( 'xili_language_settings[nav_menu_separator]' , array(
				    	'default'     => '|',
				    	'transport'   => 'refresh',	
				    	'type'     => 'option',
				    	'capability'  => $this->capability
					) );
				
					$wp_customize->add_control( 'nav_menu_separator', array(
				    	'settings' => 'xili_language_settings[nav_menu_separator]',
				    	'label'    => __( 'Separator before language list (Character or Entity Number or Entity Name)', 'xili-language' ), // xl domain not
				    	'section'  => 'xili_options_section',
				    	'type'     => 'text'
					
					) );
				}
			$wp_customize->add_setting( $this->settings_name.'[no_flags]' , array(
		    	'default'     => '',
		    	'transport'   => 'refresh',	
		    	'type'     => 'option',
		    	'capability'  => $this->capability
			) );
		
			$wp_customize->add_control( 'no_flags', array(
		    	'settings' => $this->settings_name.'[no_flags]',
		    	'label'    => __( 'Hide the flags', 'xili-language' ),
		    	'section'  => 'xili_options_section',
		    	'type'     => 'checkbox', 
			) );
		
		} else {
			
			$wp_customize->add_section( 'xili_options_section', array(
			'title'          => __('Multilingual Options ©xili', 'xili-language' ),
			
			'priority'       => 300,
			'description'    => __( 'None nav menus location seems active', 'xili-language' )
		) );
			
		}
			$wp_customize->add_setting( $this->settings_name.'[linked_posts]' , array(
		    	'default'     => '',
		    	'transport'   => 'refresh',
		    	'type'     => 'option',
		    	'capability'  => $this->capability
			) );
		
			$wp_customize->add_control( 'linked_posts', array(
		    	'settings' => $this->settings_name.'[linked_posts]',
		    	'label'    => __('Show linked posts', 'xili-language' ),
		    	'section'  => 'xili_options_section',
		    	'type'     => 'checkbox',
			) );

	
		}
		
		function special_head ( ) {
		
			printf ("<!-- Website powered by child-theme %s-xili v. %s of dev.xiligroup.com -->\n", $this->theme_name, $this->child_version ) ;
			echo '<link rel="shortcut icon" href="' . get_stylesheet_directory_uri() . '/images/favicon.ico" type="image/x-icon"/>'."\n";
			echo '<link rel="apple-touch-icon" href="' . get_stylesheet_directory_uri() . '/images/apple-touch-icon.png"/>'."\n";
	
		}
		
		
		
		/**
		 * filter to create one menu per language for dashboard and front-end
		 * detect the default one created by theme ($menu_locations_keys[0])
		 * @since 0.9.7
		 * @updated 1.0.2
		 */
		
		function xili_create_menu_locations () {
			
			$xili_theme_options = $this->get_theme_xili_options() ;
			 
			if ( isset ( $xili_theme_options['nav_menus'] ) && $xili_theme_options['nav_menus'] == 'nav_menus' ) {  // ok for automatic insertion of one menu per lang...
				$menu_locations = get_registered_nav_menus() ; 
				$menu_locations_keys =  array_keys( $menu_locations );
				$navmenu_count = count ( $menu_locations_keys ) ;
				global $xili_language ;
				$default = 'en_us'; // currently the default language of theme in core WP
				$language_xili_settings = get_option('xili_language_settings');
				$language_slugs_list =  array_keys ( $language_xili_settings['langs_ids_array'] ) ;
				if ( $menu_locations_keys ) {
					foreach ( $menu_locations_keys as $oneloc ) {
						foreach ( $language_slugs_list as $slug ) {
							$one_menu_location = $oneloc.'_'.$slug ;
							$indice = 'nav_menu_'.$oneloc ;
							
							$do_it = ( $navmenu_count == 1 )  ?  true : (isset( $xili_theme_options[$indice] ) &&  $xili_theme_options[$indice] == 'nav_menu') ;
							if ( $do_it && $slug != $default ) {
								register_nav_menu ( $one_menu_location,  sprintf( __( '%s for %s', $this->theme_domain ), $menu_locations[$oneloc], $slug ) );
							}
						}
					}
				}
				
			}
		}
		
		/**
		 * filter to avoid modifying theme's header and changes 'virtually' location for each language
		 * @since 0.9.7
		 */
		function xili_wp_nav_menu_args ( $args ) {
			
			$xili_theme_options = get_theme_xili_options() ; 
			$ok =  ( isset ( $xili_theme_options['nav_menus'] ) && $xili_theme_options['nav_menus'] == 'nav_menus' ) ? true : false ;
			
			global $xili_language ;
			$default = 'en_us'; // currently the default language of theme as in core WP
			$slug = the_curlang();
			if ( $default != $slug  && $ok ) { 
				$theme_location = $args['theme_location'];
				if ( has_nav_menu ( $theme_location.'_'.$slug ) ) { // only if a menu is set by webmaster in menus dashboard
					$args['theme_location'] = $theme_location .'_'.$slug ;
				}	
			}
				
			return $args;
		}
		
		


		/**
		 * create if option clone of sidebar container by language
		 *
		 *
		 */
		function xili_clone_sidebar_container () {
			global $wp_registered_sidebars;
			
			$xili_theme_options = get_theme_xili_options() ; // 1.1.2 
			
			$language_xili_settings = get_option('xili_language_settings');
			$language_slugs_list =  array_keys ( $language_xili_settings['langs_ids_array'] ) ;
			
			foreach ( $language_slugs_list as $slug) {
				
				if ( $slug != 'en_us'  ) {
		
					$language = get_term_by( 'slug', $slug, TAXONAME ); //$language = xiliml_get_language( $slug );
					
					foreach ( $wp_registered_sidebars as $one_key => $one_sidebar ) { 
						$indice = 'sidebar_'.$one_key ;
						if ( false === strpos( $one_key , '_'. $slug ) && isset ( $xili_theme_options[$indice] ) ) {	// don't use _xx_XX lang in root sidebar id 	
							register_sidebar( array(
								'name' => sprintf ( __('%1$s in %2$s', $this->theme_domain),  $one_sidebar['name'],  $language->description ),
								'id' => $one_sidebar['id'].'_'.$slug,
								'description' => $one_sidebar['description'],
								'before_widget' => $one_sidebar['before_widget'],
								'after_widget' => $one_sidebar['after_widget'],
								'before_title' => $one_sidebar['before_title'],
								'after_title' => $one_sidebar['after_title'],
							) );
						}	
					}
				}
			}
		}
		
		
	} // end class
}

if ( ! class_exists ( 'xili_language_theme_options_admin' )  ) {
	
	class xili_language_theme_options_admin extends xili_language_theme_options  {
		
		var $customize_adds;
		
		var $capability;
		
		
		var $xili_theme_page; // set in menu creation - used by help
		
		function __construct( $xili_admin_args ) {
			
			$xili_admin_args_default = array (
 				
 				'settings_name' => 'xili_theme_options', // name of array saved in options table
 				'theme_name' => 'Twenty Twelve',
 				'theme_domain' => 'twentytwelve',	 
 				'capability' => 'edit_theme_options',
 				'child_version' => '0.0.1',
 				'customize_adds' => true,
 				'customize_add_menu' => false,
 				'customize_clone_widget_containers' => false
			);
			
			$xili_admin_args = wp_parse_args( $xili_admin_args, $xili_admin_args_default );
			
			$xili_args = array ( 
				
				'settings_name' => $xili_admin_args ['settings_name'],
				'theme_name' => $xili_admin_args ['theme_name'],
				'theme_domain' => $xili_admin_args ['theme_domain'],
				'child_version' => $xili_admin_args ['child_version'],
				'capability' => $xili_admin_args ['capability'],
				'customize_adds' => $xili_admin_args ['customize_adds'],
				'customize_add_menu' => $xili_admin_args ['customize_add_menu'],
				'customize_clone_widget_containers' => $xili_admin_args ['customize_clone_widget_containers']
			);
			
			parent::__construct( $xili_args );
			
			$this->customize_adds = $xili_admin_args ['customize_adds'];
			$this->customize_addmenu = $xili_admin_args ['customize_add_menu'];
			$this->customize_clone_widget_containers = $xili_admin_args ['customize_clone_widget_containers'];
			$this->settings_name = $xili_admin_args ['settings_name'];
			$this->theme_name = $xili_admin_args ['theme_name'];
			$this->theme_domain = $xili_admin_args ['theme_domain'];
			$this->capability = $xili_admin_args ['capability'];
			$this->child_version = $xili_admin_args ['child_version'];
			
			add_action( 'admin_menu', array( $this, 'xili_options_theme_menu' ) );
			
			add_action( 'admin_init', array( $this, 'xili_register_settings' ) );
			
			add_action( 'admin_print_styles', array(&$this, 'print_styles_xili_options') );
			
			
				
			//add_action( 'customize_preview_init', array( $this, 'customize_js_footer' ) );	
		
		}
		
		// create appareance sub-menus
		function xili_options_theme_menu() {
			
			$this->xili_theme_page = add_theme_page( sprintf(__('%s Theme Options', 'xili-language'), $this->theme_name ) , 'Xili Options', 'manage_options', $this->settings_name, array( $this,'xili_options_theme_page' ) );
			
			if ( $this->customize_addmenu )
				add_theme_page( __('Customize'), __('Customize'), 'edit_theme_options', 'customize.php' );
				
			add_action('load-'.$this->xili_theme_page, array( $this, 'xili_theme_options_help_page' ) );
		}
		
		function xili_options_theme_page() {
		?>
		<div class="section panel">
		<h1><?php printf( __('Multilingual options for %s theme ', $this->theme_domain ), $this->theme_name ); ?></h1>
		<form method="post" enctype="multipart/form-data" action="options.php">
		        <?php
		          settings_fields( $this->settings_name ); 
		          do_settings_sections( $this->settings_name );
		        ?>
		<p class="submit">
		                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		            
		</form>
		<p><small><?php echo $this->theme_domain ?>-xili v. <?php echo $this->child_version; ?> , a multilingual child by <a href="http://dev.xiligroup.com" target="_blank" >dev.xiligroup.com</a> (©2013) <?php echo '(xl v.'.XILILANGUAGE_VER.')' ?></small></p>
		
		</div>
		    <?php
		}
		
		
		/**
		 * Function to register the settings
		 */
		function xili_register_settings()
		{
			
			global $wp_registered_sidebars;
			if ( false === get_option( $this->settings_name, false ) )
				add_option( $this->settings_name, $this->get_default_theme_xili_options() );
				
		    // Register the settings with Validation callback
		    
		    $options = $this->get_theme_xili_options();
		    
		    register_setting( $this->settings_name, $this->settings_name, array( $this,'xili_validate_settings' ) );
		    // Add settings section
		    add_settings_section( 'xili_option_section_1', __('Look and feel of theme on visitors side', 'xili-language'), array( $this, 'xili_display_one_section' ), $this->settings_name );
		    
		    $field_args = array(
		      'option_name' => $this->settings_name,
		      'title'	  => __('Hide Flags', 'xili-language'),
		      'type'      => 'checkbox',
		      'id'        => 'no_flags',
		      'name'      => 'no_flags',
		      'desc'      => __('If checked, default flags are hidden...', 'xili-language'),
		      'std'       => '1', // like via customizer
		      'label_for' => 'no_flags',
		      'class'     => 'css_class'
		    );
		    add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting') , $this->settings_name, 'xili_option_section_1', $field_args );
		    
		    $field_args = array(
		      'option_name' => $this->settings_name,
		      'title'	  => __('Show linked posts', 'xili-language'),
		      'type'      => 'checkbox',
		      'id'        => 'linked_posts',
		      'name'      => 'linked_posts',
		      'desc'      => __('Show Other Posts links in meta in single (even if menu or widget languages list).', 'xili-language'),
		      'std'       => '1',
		      'label_for' => 'linked_posts',
		      'class'     => 'css_class'
		    );
		    
		    add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting'), $this->settings_name, 'xili_option_section_1', $field_args );
		    
		    $field_args = array(
		      'option_name' => $this->settings_name,
		      'title'	  => __('Text of link title', 'xili-language' ),
		      'type'      => 'text',
		      'id'        => 'linked_title',
		      'name'      => 'linked_title',
		      'desc'      => __('The text before the links of linked posts in other language', 'xili-language'),
		      'std'       => 'Read this post in',
		      'label_for' => 'linked_title',
		      'class'     => 'css_class'
		    );
		    
		    add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting'), $this->settings_name, 'xili_option_section_1', $field_args );
		    
		    $menu_locations = get_registered_nav_menus() ;
			$navmenu_count =  0 ;
			foreach ( $menu_locations as $one_key => $one_location ) { 
					if ( false === strpos( $one_key , '_' ) ) $navmenu_count ++ ; // only core nav menu
			}
			
			if ( $navmenu_count > 0 )  {
				
				add_settings_section( 'xili_option_section_2', __('Navigation Menus', 'xili-language'), array( $this, 'xili_display_one_section'), $this->settings_name );	
				
				$field_args = array(
		      'option_name' => $this->settings_name,
		      'title'	  => __('Instancing nav menus', 'xili-language'),
		      'type'      => 'checkbox',
		      'id'        => 'nav_menus',
		      'name'      => 'nav_menus',
		      'desc'      => __('Instantiation of nav menu for each language.', 'xili-language'),
		      'std'       => 'nav_menus',
		      'label_for' => 'nav_menus',
		      'class'     => 'css_class menus_instancing'
		    );
		    add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting'), $this->settings_name, 'xili_option_section_2', $field_args );
			
		    	if ( $navmenu_count > 1 )  {
		    		
		    		foreach ( $menu_locations as $one_key => $one_location ) { 
						if ( false === strpos( $one_key , '_' ) ) {
							$indice = 'nav_menu_'.$one_key ;
							$nav_value = isset( $options[$indice] ) ? $options[$indice] : "";
				
							$field_args = array(
		      'option_name' => $this->settings_name,
		      'title'	  => sprintf( '-&nbsp;'.__('Instancing menu named %s:', 'xili-language') , '<strong> '.$one_location.'</strong>' ),
		      'type'      => 'checkbox',
		      'id'        => $indice,
		      'name'      => $indice,
		      'desc'      => sprintf( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __('Instantiation nav menu named %s  for each language.', 'xili-language') , '<strong> '.$one_location.'</strong>' ),
		      'std'       => 'nav_menu',
		      'label_for' => $indice,
		      'class'     => 'css_class menu_locations'
		    				);
		    				
		    				add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting'), $this->settings_name, 'xili_option_section_2', $field_args );
		    
		    			}
					}
		    	}
			}
			
			if ( $this->customize_clone_widget_containers ) {
			
				add_settings_section( 'xili_option_section_3', __('Sidebars', 'xili-language'), array( $this, 'xili_display_one_section'), $this->settings_name );
				
				foreach ( $wp_registered_sidebars as $one_key => $one_sidebar ) { 
					if ( false === strpos( $one_key , '_' ) ) {
						$indice = 'sidebar_'.$one_key ;
						
						
						$field_args = array(
			      'option_name' => $this->settings_name,
			      'title'	  => sprintf( __('Instancing widget named %s:', 'xili-language') , '<strong> '.$one_sidebar['name'].'</strong>' ),
			      'type'      => 'checkbox',
			      'id'        => $indice,
			      'name'      => $indice,
			      'desc'      => sprintf( __('Instantiation widget named %s  for each language.', 'xili-language') , '<strong> '.$one_sidebar['name'].'</strong>' ),
			      'std'       => 'sidebar_clone',
			      'label_for' => $indice,
			      'class'     => 'css_class'
			    				);
			    				
			    				add_settings_field( $field_args['id'], $field_args['title'] , array( $this, 'xili_display_one_setting'), $this->settings_name, 'xili_option_section_3', $field_args );
						
					}
				}
			}
			
		}


		function xili_display_one_section( $section ){ 
			switch ( $section['id'] ) {
		        case 'xili_option_section_1':
						echo '<p>'. __('Choices...', 'xili-language') .'</p>';
					break;
				case 'xili_option_section_2':
						echo '<p>'. sprintf (__( 'Enable (or not) instantiation of the registered menu locations.<br /> After changes saved, <a href="%s" >go to Menus settings</a> and fill menus for each language.', 'xili-language' ) , 'nav-menus.php') .'</p>';
					break;
				case 'xili_option_section_3':
						echo '<p>'. sprintf (__( 'Enable (or not) instantiation of the registered sidebars.<br /> After changes saved, <a href="%s" >go to Widget Menus</a> and fill sidebar for each language.', 'xili-language' ) , 'widgets.php') .'</p>';
					break;
			}
				
		}

		/**
		 * one line in section
		 */
		function xili_display_one_setting( $args )
		{
		    extract( $args );
		    
		    $options = $this->get_theme_xili_options();
		    
		    switch ( $type ) {
		          case 'text':
		          	$options[$id] = stripslashes($options[$id]);
		    		$options[$id] = esc_attr( $options[$id]);	
		              
		              echo "<input class='regular-text$class' type='text' id='$id' name='" . $option_name . "[$id]' value='$options[$id]' />";
		              echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		          break;
		          
		          case 'checkbox':
		          	  $set = ( isset ( $options[$id] ) ) ? $options[$id] : false;
		          	  $checked = checked ( $set, $std, false ); 
		          	  echo "<input $checked class='$class' type='checkbox' id='$id' name='" . $option_name . "[$id]' value='$std' />";
		              echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		          
		          break;
		    }
		}
		
		function xili_validate_settings ($input) {
			
		  	
		  	$default = 'en_us'; // currently the default language of theme in core WP
			$language_xili_settings = get_option('xili_language_settings');
			$language_slugs_list =  array_keys ( $language_xili_settings['langs_ids_array'] ) ;
			
		  	$checked_locations = array();
		  	foreach($input as $k => $v)
		  	{
		    	$newinput[$k] = trim($v);
		    	
		    	if ( false !== strpos ( $k , 'nav_menu_' ) ) {
		    		 $location = str_replace ( 'nav_menu_', '', $k);
		    		 $checked_locations[] = $location;
		    	}		    	
		  	}
		  	
			
			$theme_mod_locations = get_theme_mod( 'nav_menu_locations' );
			if ( $theme_mod_locations ) { // if new theme not started
				$menu_locations_keys = array_keys ( $theme_mod_locations );
				
				foreach ( $menu_locations_keys as $oneloc ) {
					// multiple locations or one only location
					if (  ( $checked_locations && !in_array ( $oneloc, $checked_locations ) ) || ( $checked_locations == array() && !isset ( $input['nav_menus'] ) ) ) {
						foreach ( $language_slugs_list as $slug ) {
							$one_menu_location = $oneloc.'_'.$slug ;
							unset ( $theme_mod_locations[$one_menu_location] ); // previous menu location set when menu content attached to location
						}
					}
				}
				set_theme_mod( 'nav_menu_locations',  $theme_mod_locations ); 
			}
		  
		  	// 'no_flags' => false , 'linked_posts' => 'show_linked',  'nav_menus' => false
		  	if ( !isset ( $input['no_flags'] ) ) $newinput['no_flags'] = '';
		  	if ( !isset ( $input['linked_posts'] ) ) $newinput['linked_posts'] = '';
		  	if ( !isset ( $input['nav_menus'] ) ) $newinput['nav_menus'] = '';
		  
		  	return $newinput;
		}
		
		
		function print_styles_xili_options ( $params ) {
		
			$screen = get_current_screen();
			
			if ( $screen->id == $this->xili_theme_page ) { 
				echo "<!---- xl css --->\n";
	   			echo '<style type="text/css" media="screen">'."\n";
				echo ".menu_locations { display:block; margin-left:20px !important; } \n";
				echo '</style>'."\n";
			}
		
		}
		
		function xili_theme_options_help_page () {
			
			$screen = get_current_screen();
			
			if ( $screen->id != $this->xili_theme_page )
		        return;
			$help = '<p>' . sprintf (__( 'Some themes provide customization options that are grouped together on a Theme Options screen. If you change themes, options may change or disappear, as they are theme-specific. Your current theme, %s, provides the following Theme Options:', 'xili-language' ), $this->theme_name ) . '</p>' .
					'<ol>' .
						'<li>' . __( '<strong>Multilingual Flags style</strong>: Check if you want to hidden flags and see only language names. (no style generated)...', 'xili-language' ) . '</li>' .
						'<li>' . __( '<strong>Other posts in other languages links in singular (page or post)</strong>: Check if you want to show links of posts in other languages.', 'xili-language' ) . '</li>' .
						'<li>' . __( '<strong>Instancing nav menu for each language</strong>: Check if you want to clone menu location.', 'xili-language' ) . '</li>' .
						'<li>' . __( '<strong>Enable instantiation for the registered sidebars</strong>: Check if you want to clone one the sidebars for each language.', 'xili-language' ) . '</li>' .
						
					'</ol>' .
					'<p>' . __( 'Remember to click "Save Changes" to save any changes you have made to the theme options.', 'xili-language' ) . '</p>' .
					'<p><strong>' . __( 'For more information:', 'xili-language' ) . '</strong></p>' .
					'<p>' . __( '<a href="http://codex.wordpress.org/Appearance_Theme_Options_Screen" target="_blank">WP Documentation on Theme Options</a>', 'xili-language' ) . '</p>' .
					'<p>' . __( '<a href="http://wiki.xiligroup.org" target="_blank">Xili Wiki</a>', 'xili-language' ) . '</p>'.
					'<p>' . __( '<a href="http://dev.xiligroup.com/?post_type=forum" target="_blank">Xili Support Forums</a>', 'xili-language' ) . '</p>';
		
			$screen->add_help_tab(  array(
		        'id'	=> $this->xili_theme_page,
		        'title'	=> __('Help'),
		        'content'	=>	$help	));
		}

		
		
		
		
		
		
	} // end class
	
}

/**
 * use it to get xili-options of the current multilingual child theme
 *
 */
function get_theme_xili_options() {
	global $xili_language_theme_options ;
	return get_option( $xili_language_theme_options->settings_name, $xili_language_theme_options->get_default_theme_xili_options() );
}

?>