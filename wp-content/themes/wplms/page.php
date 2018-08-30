<?php

get_header(vibe_get_header());

if ( have_posts() ) : while ( have_posts() ) : the_post();

$title=get_post_meta(get_the_ID(),'vibe_title',true);
if(vibe_validate($title) || empty($title)){
?>
<section id="title">
    <div class="<?php echo vibe_get_container(); ?>">
        <div class="row">
            <div class="col-md-12">
                <div class="pagetitle">
                    <?php
                        $breadcrumbs=get_post_meta(get_the_ID(),'vibe_breadcrumbs',true);
                        if(vibe_validate($breadcrumbs) || empty($breadcrumbs))
                            vibe_breadcrumbs(); 
                    ?>
                    <h1><?php the_title(); ?></h1>
                    <?php the_sub_title(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
}

    $v_add_content = get_post_meta( $post->ID, '_add_content', true );
 
?>
<section id="content">



    <div class="<?php echo vibe_get_container(); ?>">
        <div class="row">
            <div class="col-md-12">
            
            <?php
$current_user = wp_get_current_user();
if ( 0 == $current_user->ID ) {
    //echo 'NÃO';
} else { ?>
<!-- Gabriel -->
<div lass="vc_row wpb_row vc_row-fluid vc_custom_1471500987236 vc_row-has-fill vc_row-o-equal-height vc_row-flex" style="position:relative; box-sizing: border-box; padding-left:0px; padding-right:0px; padding-top:50px; z-index:10000;">

  <div class="wpb_column vc_column_container vc_col-sm-12">
    <div class="vc_column-inner">
      <div class="wpb_wrapper">
        <div class="wpb_text_column wpb_content_element vc_custom_1471500967089">
          <div class="wpb_wrapper">
            <a class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-rounded vc_btn3-style-modern vc_btn3-icon-left vc_btn3-color-warning" href="http://tedmed.com.br/members/tedadmin/course/instructor-courses/" title="" target="_blank"><i class="vc_btn3-icon fa fa-check"></i> Administrar Curso</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</div> 
<!-- Gabriel -->

<?php
}
?>

                <div class="<?php echo $v_add_content;?> content">
                    <?php
                        the_content();
                        $page_comments = vibe_get_option('page_comments');
                        if(!empty($page_comments))
                            comments_template();
                     ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
endwhile;
endif; 
?>




<?php
get_footer( vibe_get_footer() );