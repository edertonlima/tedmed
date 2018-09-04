<?php
//Header File
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<?php
    wp_head();
?>

<script type="text/javascript">
    jQuery(document).ready(function(){
        scroll_body = jQuery(window).scrollTop();
        if(scroll_body > 400){
            jQuery('.header-fixed').addClass('scroll_menu');
        }else{
            jQuery('.header-fixed').removeClass('scroll_menu');
        }
    });
    
    jQuery(window).scroll(function(){
        scroll_body = jQuery(window).scrollTop();
        if(scroll_body > 400){
            jQuery('.header-fixed').addClass('scroll_menu');
        }else{
            jQuery('.header-fixed').removeClass('scroll_menu');
        }
    });
</script>

<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/assets/css/new-tema.css" media="screen" />

</head>
<body <?php body_class(); ?>>
<div id="global" class="global">
    <div class="pagesidebar">
        <div class="sidebarcontent">    
            <h2 id="sidelogo">
            <a href="<?php echo vibe_site_url(); ?>">
                <img src="<?php  echo apply_filters('wplms_logo_url',VIBE_URL.'/assets/images/logo.png','pagesidebar'); ?>" alt="<?php echo get_bloginfo('name'); ?>"  class="logo-home" />
            </a>
            </h2>
            <?php
                $args = apply_filters('wplms-mobile-menu',array(
                    'theme_location'  => 'mobile-menu',
                    'container'       => '',
                    'menu_class'      => 'sidemenu',
                    'fallback_cb'     => 'vibe_set_menu',
                ));

                wp_nav_menu( $args );
            ?>
        </div>
        <a class="sidebarclose"><span></span></a>
    </div>  
    <div class="pusher">
        <?php
            $fix=vibe_get_option('header_fix');
        ?>


        <div class="header-fixed">

            <div id="headertop" class="<?php if(isset($fix) && $fix){echo 'fix';} ?>">
                <div class="<?php echo vibe_get_container(); ?>">
                    <div class="row">
                        <div class="col-md-4 col-sm-3 col-xs-4">
                           <a href="<?php echo vibe_site_url(); ?>" class="homeicon"><img src="<?php  echo apply_filters('wplms_logo_url',VIBE_URL.'/assets/images/logo.png','headertop'); ?>" width="100" height="48" alt="<?php echo get_bloginfo('name'); ?>" /></a> 
                        </div>
                        <div class="col-md-8 col-sm-9 col-xs-8">
                        <?php
                        if ( function_exists('bp_loggedin_user_link') && is_user_logged_in() ) :
                            ?>
                            <ul class="topmenu">
                                <li><a href="<?php bp_loggedin_user_link(); ?>" class="smallimg vbplogin"><?php $n=vbp_current_user_notification_count(); echo ((isset($n) && $n)?'<em></em>':''); bp_loggedin_user_avatar( 'type=full' ); ?><?php bp_loggedin_user_fullname(); ?></a></li>
                                <?php do_action('wplms_header_top_login'); ?>
                            </ul>
                        <?php
                        else :
                            ?>
                            <ul class="topmenu">
                                <li><a href="#login" class="smallimg vbplogin"><?php _e('Login','vibe'); ?></a></li>
                                <li><?php if ( function_exists('bp_get_signup_allowed') && bp_get_signup_allowed() ) :
                                    $registration_link = apply_filters('wplms_buddypress_registration_link',site_url( BP_REGISTER_SLUG . '/' ));
                                    printf( __( '<a href="%s" class="vbpregister" title="'.__('Create an account','vibe').'">'.__('Sign Up','vibe').'</a> ', 'vibe' ), $registration_link );
                                endif; ?>
                                </li>
                            </ul>
                        <?php
                        endif;
                                $args = apply_filters('wplms-top-menu',array(
                                    'theme_location'  => 'top-menu',
                                    'container'       => '',
                                    'menu_class'      => 'topmenu',
                                    'fallback_cb'     => 'vibe_set_menu',
                                ));

                            wp_nav_menu( $args );
                            ?>
                        </div>
                        <?php
                             $style = vibe_get_login_style();
                            if(empty($style)){
                                $style='default_login';
                            }
                        ?>
                        <div id="vibe_bp_login" class="<?php echo $style; ?>">
                        <?php
                            vibe_include_template("login/$style.php");
                         ?>
                       </div>
                    </div>
                </div>
            </div>
            <header class="header-princ">
                <div class="<?php echo vibe_get_container(); ?>">
                    <div class="row">
                        <div class="col-md-3 col-sm-3 col-xs-4">

                            <?php

                                if(is_home()){
                                    echo '<h1 id="logo">';
                                }else{
                                    echo '<h2 id="logo">';
                                }
                            ?>
                                <a href="<?php echo vibe_site_url(); ?>">

                                    <?php /*<img src="<?php  echo apply_filters('wplms_logo_url',VIBE_URL.'/assets/images/logo.png','header'); ?>" width="100" height="48" alt="<?php echo get_bloginfo('name'); ?>" /> */ ?>

                                    <img src="<?php echo vibe_site_url(); ?>/wp-content/uploads/2018/09/logo_tedmed_br2.png" alt="<?php echo get_bloginfo('name'); ?>" class="logo-home" />
                                    <img src="<?php echo vibe_site_url(); ?>/wp-content/uploads/2018/09/logo_tedmed.png" alt="<?php echo get_bloginfo('name'); ?>" class="logo-default" />

                                </a>
                            <?php
                                if(is_home()){
                                    echo '</h1>';
                                }else{
                                    echo '</h2>';
                                }
                            ?>
                        </div>
                        <div class="col-md-9 col-sm-9 col-xs-8">
                            <div id="searchicon"><i class="icon-search-2"></i></div>
                            <div id="searchdiv">
                                <form role="search" method="get" id="searchform" action="<?php echo home_url( '/' ); ?>">
                                    <div><label class="screen-reader-text" for="s">Search for:</label>
                                        <input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php _e('Hit enter to search...','vibe'); ?>" />
                                        <?php 
                                            $course_search=vibe_get_option('course_search');
                                            if(isset($course_search) && $course_search)
                                                echo '<input type="hidden" value="course" name="post_type" />';
                                        ?>
                                        <input type="submit" id="searchsubmit" value="Search" />
                                    </div>
                                </form>
                            </div>
                            <?php
                                $args = apply_filters('wplms-main-menu',array(
                                     'theme_location'  => 'main-menu',
                                     'container'       => 'nav',
                                     'menu_class'      => 'menu',
                                     'walker'          => new vibe_walker,
                                     'fallback_cb'     => 'vibe_set_menu'
                                 ));
                                wp_nav_menu( $args ); 
                            ?>
                            <a id="trigger">
                                <span class="lines"></span>
                            </a> 
                        </div>
                    </div>
                </div>
            </header>

        </div>