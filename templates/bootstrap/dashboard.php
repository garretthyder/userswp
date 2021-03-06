<?php
global $uwp_widget_args;
$user_id = get_current_user_id();
$user_info = get_userdata($user_id);
$display_name = $user_info->data->display_name;
$profile_link = uwp_build_profile_tab_url($user_id);
$hello_text = !empty($uwp_widget_args['dashboard_text']) ? esc_attr__($uwp_widget_args['dashboard_text'],'userswp') : __( 'Hello, %s', 'userswp' );
$display_name = "<a href='$profile_link' >".esc_attr($display_name)."</a>";
$hello_text = sprintf($hello_text,$display_name);
$dashboard_links = !empty($uwp_widget_args['template_args']['dashboard_links']) ? $uwp_widget_args['template_args']['dashboard_links'] : '';
?>

<div class="card text-center border-0">
    <a href="<?php echo $profile_link;?>">
    <img src="<?php echo get_avatar_url( get_current_user_id(), 100 ); ?>" class="rounded-circle shadow border border-white border-width-4" alt="<?php echo esc_attr($display_name);?>">
    </a>
    <div class="card-body">
        <?php do_action('uwp_template_form_title_before', 'dashboard'); ?>
        <h5 class="card-title"><?php echo apply_filters('uwp_template_form_title',  $hello_text, 'dashboard'); ?></h5>
        <?php do_action('uwp_template_form_title_after', 'dashboard'); ?>
        <?php do_action('uwp_template_display_notices', 'dashboard'); ?>

        <div class="uwp-dashboard-links">
            <?php
            do_action('uwp_dashboard_links_before',$uwp_widget_args);

            global $userswp;
            $userswp->forms->output_dashboard_links( $dashboard_links );

            do_action('uwp_dashboard_links_after',$uwp_widget_args);
            ?>
        </div>

        <div class="uwp-logout-link pt-3">
            <a href="<?php echo wp_logout_url();?>" class="btn btn-sm btn-outline-primary"><?php _e("Logout","userswp");?></a>
        </div>

    </div>
</div>