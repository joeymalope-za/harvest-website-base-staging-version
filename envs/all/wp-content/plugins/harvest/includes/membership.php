<?php
define('STANDARD_MEMBER_ROLE', "standard_member");
define('PREMIUM_MEMBER_ROLE', "high_rollers_club");
define('STANDARD_MEMBER_SECRET_MENU_ROLE', "standard_member_secret_menu");
define('PREMIUM_MEMBER_SECRET_MENU_ROLE', "high_rollers_club_secret_menu");
define('EXPIRED_ROLE', "expired");

if (function_exists('add_action')) {
    add_action('template_redirect', function () {
        if (should_do_redirects() &&
            (
                is_page( wc_get_page_id( 'cart' )) ||
                is_page( wc_get_page_id( 'checkout' ))
            ) &&
            !is_member(get_current_user_id())
        ) {
            wp_redirect("/my-account");
        }
        return true;
    });
}

function is_member($user_id) {
    $user = get_user_by('id', $user_id);
    $user_roles = (array) $user->roles;
    $member_roles = [
        STANDARD_MEMBER_ROLE, 
        PREMIUM_MEMBER_ROLE, 
        STANDARD_MEMBER_SECRET_MENU_ROLE, 
        PREMIUM_MEMBER_SECRET_MENU_ROLE
    ];

    return (!in_array(EXPIRED_ROLE, $user_roles) && 
            array_intersect($member_roles, $user_roles));
}

function is_premium_member($user_id) {
    $user = get_user_by('id', $user_id);
    $user_roles = (array) $user->roles;

    return !in_array(EXPIRED_ROLE, $user_roles) && 
            (in_array(PREMIUM_MEMBER_ROLE, $user_roles) || 
            in_array(PREMIUM_MEMBER_SECRET_MENU_ROLE, $user_roles));
}

function make_member($user_id) {
    set_member_role($user_id, STANDARD_MEMBER_ROLE);
}

function make_premium_member($user_id) {
    set_member_role($user_id, PREMIUM_MEMBER_ROLE);
}

function set_member_role($user_id, $role) {
    $user = get_user_by('id', $user_id);
    $user -> set_role($role);
    if (!metadata_exists('user', $user_id, "member_since"))
        update_user_meta($user_id, 'member_since', time());
}