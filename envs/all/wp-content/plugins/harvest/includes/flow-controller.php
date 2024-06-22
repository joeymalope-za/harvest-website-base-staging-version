<?php

function redirect_to_right_step()
{
    $user_id = get_current_user_id();
    // the user is unregistered and on the sign up flow page - assuming the intent is to sign up
    if ($user_id == 0) {
        return redirect_if_not_same("registration");
    }
    // get unique id
    $user_id = get_current_user_id();
    $user_uuid = get_user_meta($user_id, 'uuid', true);
    if (empty($user_uuid)) {
        // something is wrong
        debug_log("$user_id does not have an uuid");
        return redirect_if_not_same("");
    }
    
    $is_verified = get_user_meta($user_id, 'is_verified', true);
    if (!metadata_exists('user', $user_id, "is_verified"))
        return redirect_if_not_same(VERIFICATION_PAGE_NAME);
    if (!$is_verified) {
        return redirect_if_not_same("not-verified");
    }

    // no zoho id at this point - cannot proceed
    if (!metadata_exists('user', $user_id, "zoho_id")) {
        debug_log("User $user_id doesn't have Zoho id, cannot proceed");
        return redirect_if_not_same("error");
    }

    // no bot result - go to bot
    if (!metadata_exists('user', $user_id, "doctor_approval_required"))
        return redirect_if_not_same("patient-reception");
    $doctor_approval_required = get_user_meta($user_id, 'doctor_approval_required', true);
    // false means the patient is not eligible for the shop
    if (!$doctor_approval_required)
        return redirect_if_not_same("not-approved");

    $prescription_approved = get_user_meta($user_id, 'prescription_approved', true);
    if (metadata_exists('user', $user_id, "prescription_approved") && !$prescription_approved)
        return redirect_if_not_same("not-approved");
    
    // go to consent, if consent is not recorded
    // if (!metadata_exists('user', $user_id, "consent"))
    //     return redirect_if_not_same("consent");
    
    // go to capture delivery address, if it's not present
    $delivery_address = get_user_meta($user_id, 'delivery_address_obj', true);
    if (!$delivery_address)
        redirect_if_not_same("address");
    
    // for doctor queue and chat, redirect to /shop page
    return redirect_if_not_same("shop");
}

function should_do_redirects() {
    try {
        $elementor_preview_active = \Elementor\Plugin::$instance->preview->is_preview_mode();
    } catch (Exception) {
        $elementor_preview_active = false;
    }
    if (class_exists('\Elementor\Plugin')) {
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $elementor_preview_active = true;
        }
    }
    return !is_super_admin() && !$elementor_preview_active;
}

function redirect_if_not_same($page)
{
    global $wp;
    $cur_page = $wp->request;
    debug_log("current page is $cur_page");
    if ($cur_page != $page) {
        if (should_do_redirects()) {
            wp_redirect("/" . $page);
            debug_log("redirect to $page");
        } else {
            debug_log("would redirect user to $page");
        }
        return true;
    }
    return false;
}