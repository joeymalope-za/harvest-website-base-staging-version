<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

$zoho_flow_services_config = array (
  array (
    'name' => esc_html__('WordPress.org'),
    'api_path' => 'wordpress-org',
    'class_name' => 'Zoho_Flow_WordPress_org',
    'gallery_app_link' => 'wordpress-org',
    'description' => esc_html__('Connect WordPress.org to Zoho Flow to automatically post your new WordPress posts on your social media handles such as Twitter, get notifications in your team chat for new posts, and create posts for new events scheduled in your event management app.', 'zoho-flow'),
    'icon_file' => 'wordpress.png',
    'class_test' => 'WP_Comment',
    'app_documentation_link' => 'wordpress-org',
    'embed_link' => 'wordpress_org',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/users',
        'method' => 'get_users',
        'capability' => 'list_users',
        'schema_method' => 'get_user_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/posts',
        'method' => 'get_posts',
        'capability' => 'read',
        'schema_method' => 'get_post_schema',
      ),
      array(
        'type' => 'list',
        'path' => '/posts/(?\'post_id\'[\\d]+)',
        'method' => 'get_post',
        'capability' => 'read',
        'schema_method' => 'get_post_schema',
      ),
      array(
        'type' => 'list',
        'path' => '/posts/(?\'post_type\'[a-zA-Z_-]+)/(?\'post_id\'[\\d]+)',
        'method' => 'fetch_post_with_posttype',
        'capability' => 'read',
        'schema_method' => 'get_post_schema',
      ),
      array(
        'type' => 'list',
        'path' => '/media',
        'method' => 'get_media_files',
        'capability' => 'upload_files',
      ),
      array(
        'type' => 'create',
        'path' => '/media/new',
        'method' => 'upload_media',
        'capability' => 'upload_files',
      ),
      array(
        'type' => 'create',
        'path' => '/media',
        'method' => 'upload_media_multipart',
        'capability' => 'upload_files',
      ),
      array(
        'type' => 'delete',
        'path' => '/media/(?\'attachment_id\'[\\d]+)',
        'method' => 'remove_media',
        'capability' => 'delete_files',
      ),
      array(
        'type' => 'list',
        'path' => '/comments',
        'method' => 'get_comments',
        'capability' => 'read',
        'schema_method' => 'get_comment_schema'
      ),
      array (
        'type' => 'list',
        'path' => '/posts/(?\'post_id\'[\\d]+)/comments/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'read',
      ),
      array(
          'type' => 'create',
          'path' => '/posts/(?\'post_id\'[\\d]+)/comments/webhooks',
          'method' => 'create_post_comments_webhook',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'delete',
          'path' => '/posts/(?\'post_id\'[\\d]+)/comments/webhooks/(?\'webhook_id\'[\\d]+)',
          'method' => 'delete_webhook_deprecated',
          'capability' => 'delete_posts',
      ),
      array(
          'type' => 'list',
          'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks',
          'method' => 'get_webhooks_for_post',
          'capability' => 'read',
      ),
      array(
          'type' => 'create',
          'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks',
          'method' => 'create_webhook_for_post',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'delete',
          'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks/(?\'webhook_id\'[\\d]+)',
          'method' => 'delete_webhook_deprecated',
          'capability' => 'delete_posts',
      ),
      array(
          'type' => 'list',
          'path' => '/comments/webhooks',
          'method' => 'get_comments_webhooks',
          'capability' => 'read',
      ),
      array(
          'type' => 'create',
          'path' => '/comments/webhooks',
          'method' => 'create_comments_webhooks',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'delete',
          'path' => '/comments/webhooks/(?\'webhook_id\'[\\d]+)',
          'method' => 'delete_webhook_deprecated',
          'capability' => 'delete_posts',
      ),
      array(
          'type' => 'create',
          'path' => '/users',
          'method' => 'create_user',
          'capability' => 'create_users',
      ),
      array(
          'type' => 'update',
          'path' => '/users/(?\'user_id\'[\\d]+)',
          'method' => 'update_user',
          'capability' => 'edit_users',
      ),
      array(
          'type' => 'create',
          'path' => '/posts/upsert',
          'method' => 'create_post_insert',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'create',
          'path' => '/posts',
          'method' => 'create_post',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'update',
          'path' => '/posts/(?\'post_id\'[\\d]+)',
          'method' => 'update_post',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'update',
          'path' => '/posts/(?\'post_id\'[\\d]+)/tags',
          'method' => 'update_post_tag',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'update',
          'path' => '/posts/(?\'post_id\'[\\d]+)/categories',
          'method' => 'update_post_categories',
          'capability' => 'edit_posts',
      ),
      array(
          'type' => 'list',
          'path' => '/me',
          'method' => 'get_self',
          'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/getuser/(?P<user_id>\d+)',
        'method' => 'get_user_by',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/getuser/(?P<login>\S+)',
        'method' => 'get_user_by',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/getresetpasswordlink/(?\'user_login\'[a-zA-Z0-9_\@]\S+)',
        'method' => 'get_resetpassword_link',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/user_meta/(?\'user_id\'[\\d]+)',
        'method' => 'get_userinfo_meta',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/post_types',
        'method' => 'get_post_types',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/post_statuses',
        'method' => 'get_post_statuses',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/post_types/(?\'post_type\'[a-zA-Z_\@]\S+)/meta_keys',
        'method' => 'get_post_type_meta_keys',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/users/meta_keys',
        'method' => 'get_user_meta_keys',
        'capability' => 'list_users',
      ),
      array(
        'type' => 'list',
        'path' => '/comments/meta_keys',
        'method' => 'get_comment_meta_keys',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/categories',
        'method' => 'get_categories',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/tags',
        'method' => 'get_tags',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/roles',
        'method' => 'get_roles',
        'capability' => 'list_users',
      ),
      array(
        'type' => 'list',
        'path' => '/getuser',
        'method' => 'fetch_user',
        'capability' => 'list_users',
      ),
      array(
        'type' => 'update',
        'path' => '/posts/(?\'post_id\'[\\d]+)/meta',
        'method' => 'update_post_meta',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'update',
        'path' => '/users/(?\'user_id\'[\\d]+)/meta',
        'method' => 'update_user_meta',
        'capability' => 'edit_users',
      ),
      array(
        'type' => 'create',
        'path' => '/comments',
        'method' => 'create_comment',
        'capability' => 'moderate_comments',
      ),
      array(
        'type' => 'create',
        'path' => '/mail/send',
        'method' => 'send_mail',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/siteinfo',
        'method' => 'get_site_details',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array (
      array (
        'action' => 'comment_post',
        'method' => 'process_comment_post',
        'args_count' => 3,
      ),
      array (
        'action' => 'spammed_comment',
        'method' => 'process_spammed_comment',
        'args_count' => 2,
      ),
      array (
        'action' => 'edit_comment',
        'method' => 'process_edit_comment',
        'args_count' => 2,
      ),
      array (
        'action' => 'wp_set_comment_status',
        'method' => 'process_set_comment_status',
        'args_count' => 2,
      ),
      array (
        'action' => 'register_new_user',
        'method' => 'process_user_register',
        'args_count' => 1,
      ),
      array(
          'action' => 'profile_update',
          'method' => 'process_profile_update',
          'args_count' => 2
      ),
      array(
          'action' => 'save_post',
          'method' => 'process_save_post',
          'args_count' => 3
      ),
      array(
          'action' => 'wp_login',
          'method' => 'process_wp_login',
          'args_count' => 2,
      ),
      array(
          'action' => 'transition_post_status',
          'method' => 'payload_post_created',
          'args_count' => 3
      ),
      array(
          'action' => 'transition_post_status',
          'method' => 'payload_post_created_or_updated',
          'args_count' => 3
      ),
      array(
          'action' => 'transition_post_status',
          'method' => 'payload_post_status_changed',
          'args_count' => 3
      ),
      array(
          'action' => 'transition_post_status',
          'method' => 'payload_post_updated',
          'args_count' => 3
      ),
      array(
          'action' => 'added_post_meta',
          'method' => 'payload_post_meta_created_or_updated_for_post_update',
          'args_count' => 4
      ),
      array(
          'action' => 'updated_post_meta',
          'method' => 'payload_post_meta_created_or_updated_for_post_update',
          'args_count' => 4
      ),
      array(
          'action' => 'added_post_meta',
          'method' => 'payload_post_meta_created_or_updated_for_post_created_or_update',
          'args_count' => 4
      ),
      array(
          'action' => 'updated_post_meta',
          'method' => 'payload_post_meta_created_or_updated_for_post_created_or_update',
          'args_count' => 4
      ),
      array(
          'action' => 'user_register',
          'method' => 'payload_user_created',
          'args_count' => 2
      ),
      array(
          'action' => 'user_register',
          'method' => 'payload_user_for_created_or_updated',
          'args_count' => 2
      ),
      array(
          'action' => 'profile_update',
          'method' => 'payload_user_created_or_updated',
          'args_count' => 3
      ),
      array(
          'action' => 'added_user_meta',
          'method' => 'payload_user_meta_added_or_updated',
          'args_count' => 4
      ),
      array(
          'action' => 'updated_user_meta',
          'method' => 'payload_user_meta_added_or_updated',
          'args_count' => 4
      ),
      array(
          'action' => 'comment_post',
          'method' => 'payload_comment_created',
          'args_count' => 3
      ),
      array(
          'action' => 'edit_comment',
          'method' => 'payload_comment_edited',
          'args_count' => 2
      ),
      array(
          'action' => 'transition_comment_status',
          'method' => 'payload_comment_status_transition',
          'args_count' => 3
      ),
      array(
          'action' => 'wp_login',
          'method' => 'payload_user_login',
          'args_count' => 2,
      ),
      array(
          'action' => 'add_attachment',
          'method' => 'payload_attachment_added',
          'args_count' => 1,
      ),
      array(
          'action' => 'wp_mail_succeeded',
          'method' => 'payload_mail_succeeded',
          'args_count' => 1,
      ),
      array(
          'action' => 'wp_mail_failed',
          'method' => 'payload_mail_failed',
          'args_count' => 1,
      ),
    ),
  ),
  array (
    'name' => esc_html__('Contact Form 7'),
    'api_path' => 'contact-form-7',
    'class_name' => 'Zoho_Flow_Contact_Form_7',
    'gallery_app_link' => 'contact-form-7',
    'description' => esc_html__('Create forms in Contact Form 7 to collect contacts, feedback, or orders. Then integrate Contact Form 7 with other apps using Zoho Flow to store, share, and analyze your form submissions automatically.', 'zoho-flow'),
    'icon_file' => 'contact-form-7.png',
    'class_test' => 'WPCF7_ContactForm',
    'app_documentation_link' => 'contact-form-7',
    'embed_link' => 'contact_form_7',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'wpcf7_read_contact_forms',
        'schema_method' => 'get_form_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_fields',
        'capability' => 'wpcf7_read_contact_forms',
        'schema_method' => 'get_field_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'wpcf7_read_contact_forms',
        'schema_method' => 'get_form_webhook_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'wpcf7_edit_contact_form',
      ),
      array (
        'type' => 'delete',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'wpcf7_delete_contact_form',
      ),
      array (
        'type' => 'get',
        'path' => '/files/(?\'filename\'.+)',
        'method' => 'get_file',
        'capability' => 'wpcf7_edit_contact_form',
      ),
    ),
    'hooks' => array (
      array (
        'action' => 'wpcf7_before_send_mail',
        'method' => 'process_form_submission',
        'args_count' => 1,
      ),
    ),
  ),
  array (
    'name' => esc_html__('WPForms'),
    'api_path' => 'wpforms',
    'class_name' => 'Zoho_Flow_WPForms',
    'gallery_app_link' => 'wpforms',
    'description' => esc_html__('Utilize WPForms’s drag-and-drop form builder to create customizable forms for subscriptions, payments, and lead generation. Connect it to Zoho Flow to automatically add your subscriber information to your email marketing platform, add your payment data as new rows in your spreadsheet, and much more.', 'zoho-flow'),
    'icon_file' => 'wpforms.png',
    'class_test' => 'WPForms',
    'app_documentation_link' => 'wpforms',
    'embed_link' => 'wpforms',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_options',
        'schema_method' => 'get_form_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_fields',
        'capability' => 'manage_options',
        'schema_method' => 'get_field_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'manage_options',
        'schema_method' => 'get_form_webhook_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'delete',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'get',
        'path' => '/files/(?\'filename\'.+)',
        'method' => 'get_file',
        'capability' => 'manage_options',
      ),
    ),
    'hooks' => array (
      array (
        'action' => 'wpforms_process_complete',
        'method' => 'process_form_submission',
        'args_count' => 4,
      ),
    ),
  ),
  array (
    'name' => esc_html__('Elementor Pro'),
    'api_path' => 'elementor',
    'class_name' => 'Zoho_Flow_Elementor',
    'gallery_app_link' => 'elementor-pro',
    'description' => esc_html__('Elementor’s intuitive website builder and 300+ predesigned templates make it easy to build great websites. Integrate it with your favorite apps, and you can automatically send, store, and analyze form responses, contacts, and feedback.', 'zoho-flow'),
    'icon_file' => 'elementor.png',
    'class_test' => 'ElementorPro\Plugin',
    'app_documentation_link' => '',
    'embed_link' => 'elementor',
    'version' => 'v1',
    'rest_apis' => array (
        array (
            'type' => 'list',
            'path' => '/forms',
            'method' => 'get_forms',
            'capability' => 'manage_options',
            'schema_method' => 'get_form_schema',
        ),
        array (
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[a-zA-Z0-9_]+)/fields',
            'method' => 'get_fields',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[a-zA-Z0-9_]+)/webhooks',
            'method' => 'get_webhooks',
            'capability' => 'manage_options',
            'schema_method' => 'get_form_webhook_schema',
        ),
        array (
            'type' => 'create',
            'path' => '/forms/(?\'form_id\'[a-zA-Z0-9_]+)/webhooks',
            'method' => 'create_webhook',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'delete',
            'path' => '/forms/(?\'form_id\'[a-zA-Z0-9_]+)/webhooks/(?\'webhook_id\'[\\d]+)',
            'method' => 'delete_webhook',
            'capability' => 'manage_options',
        ),
    ),
    'hooks' => array (
            array (
                'action' => 'elementor_pro/forms/new_record',
                'method' => 'process_form_submission',
                'args_count' => 2,
            ),
        ),
  ),
  array(
    'name' => esc_html__("Akismet"),
    'api_path' => 'akismet',
    'class_name' => 'Zoho_Flow_Akismet',
    'gallery_app_link' => 'akismet',
    'description' => esc_html__('Akismet is a spam protection application that can identify and filter spam comments, trackbacks, and contract form messages. By integrating Akismet with other applications, you will be able to get notified when you get spam comments.', 'zoho-flow'),
    'icon_file' => 'akismet.png',
    'class_test' => 'Akismet',
    'app_documentation_link' => '',
    'embed_link' => 'akismet',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'create',
        'path' => '/comment/(?\'comment_id\'[\\d]+)/recheck',
        'method' => 'recheck_comment',
        'capability' => 'moderate_comments',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'comment_post',
        'method' => 'payload_spam_comment',
        'args_count' => 3,
      ),
      array (
        'action' => 'akismet_submit_spam_comment',
        'method' => 'payload_submit_spam',
        'args_count' => 2,
      ),
      array (
        'action' => 'akismet_submit_nonspam_comment',
        'method' => 'payload_submit_nonspam',
        'args_count' => 2,
      ),
    )
  ),
  array(
    'name' => esc_html__("WP Mail SMTP"),
    'api_path' => 'wp-mail-smtp',
    'class_name' => 'Zoho_Flow_WPMailSMTP',
    'gallery_app_link' => 'wp-mail-smtp',
    'description' => esc_html__('WP Mail SMTP is an STMP mailer WordPress plugin that improves email deliverability and enhances email authentication. Integrate WP Mail SMTP with other applications to ensure that you get notified every time your mail has been delivered successfully.', 'zoho-flow'),
    'icon_file' => 'wp-mail-smtp.png',
    'class_test' => 'WPMailSMTP\Reports\Reports',
    'app_documentation_link' => '',
    'embed_link' => 'wp_mail_smtp',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/summary',
        'method' => 'get_summary',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/summary/sendtoadmin',
        'method' => 'send_summary_to_admin',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'wp_mail_smtp_mailcatcher_send_after',
        'method' => 'payload_send_after',
        'args_count' => 2,
      ),
    )
  ),
  array (
    'name' => esc_html__("Advanced Custom Fields"),
    'api_path' => 'advanced-custom-fields',
    'class_name' => 'Zoho_Flow_Advanced_Custom_Fields',
    'gallery_app_link' => 'advanced-custom-fields',
    'description' => esc_html__('Enhance WordPress with extra content fields like text, images, and more using Advanced Custom Fields. Connect it with Zoho Flow to dynamically update custom field content based on triggers from other apps, or save new content additions to your cloud storage.', 'zoho-flow'),
    'icon_file' => 'acf.png',
    'class_test' => 'ACF',
    'app_documentation_link' => '',
    'embed_link' => 'advanced_custom_fields',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/fieldgroups',
        'method' => 'get_field_groups',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/allfields',
        'method' => 'get_all_fields',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/fieldsbygroup/(?\'post_parent\'[\\d]+)',
        'method' => 'get_fields_by_group',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/fieldgroup_by_id/(?\'field_group_id\'[\\d]+)',
        'method' => 'get_field_group_by_id',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/fetchfields',
        'method' => 'fetch_fields',
        'capability' => 'manage_options',
      ),
      array(
          'type' => 'update',
          'path' => '/updatefields',
          'method' => 'update_fields',
          'capability' => 'manage_options',
      ),
    ),
    'hooks' => array(
      array(
        'action' => 'acf/save_post',
        'method' => 'process_save_post',
        'args_count' => 1,
      )
    )
  ),
  array (
    'name' => esc_html__('Ninja Forms'),
    'api_path' => 'ninja-forms',
    'class_name' => 'Zoho_Flow_Ninja_Forms',
    'gallery_app_link' => 'ninja-forms',
    'description' => esc_html__('Ninja Forms is a beginner-friendly form builder plugin that also provides conditional logic, multistep forms, and file uploads. Integrate Ninja Forms with your favorite apps to get instant notifications in your team chat app, create tasks in your project management application, or add leads to your CRM for new form submissions.', 'zoho-flow'),
    'icon_file' => 'ninja-forms.png',
    'class_test' => 'Ninja_Forms',
    'app_documentation_link' => 'ninja-forms',
    'embed_link' => 'ninja_forms',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_options',
        'schema_method' => 'get_form_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_fields',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'manage_options',
        'schema_method' => 'get_form_webhook_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'delete',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'manage_options',
      ),
    ),
    'hooks' => array (
      array (
        'action' => 'ninja_forms_after_submission',
        'method' => 'process_form_submission',
        'args_count' => 4,
      ),
    ),
  ),
  array(
    'name' => esc_html__("TablePress"),
    'api_path' => 'tablepress',
    'class_name' => 'Zoho_Flow_TablePress',
    'gallery_app_link' => 'tablepress',
    'description' => esc_html__('TablePress is a table plugin with which you can create and manage tables on your website. By integrating TablePress with your applications, you\'ll be able to transfer data into your tables instantly, letting you store, view, and organize data in multiple formats.', 'zoho-flow'),
    'icon_file' => 'tablepress.png',
    'class_test' => 'TablePress',
    'app_documentation_link' => '',
    'embed_link' => 'tablepress',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/tables',
        'method' => 'list_tables',
        'capability' => 'tablepress_list_tables',
      ),
      array(
        'type' => 'list',
        'path' => '/tables/(?\'table_id\'[\\d]+)',
        'method' => 'get_table_details',
        'capability' => 'tablepress_list_tables',
      ),
      array(
        'type' => 'create',
        'path' => '/tables/import',
        'method' => 'import_table',
        'capability' => 'tablepress_import_tables',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'tablepress_list_tables',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'tablepress_list_tables',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'tablepress_event_saved_table',
        'method' => 'payload_table_save',
        'args_count' => 1,
      )
    )
  ),
  array(
    'name' => esc_html__("MailPoet"),
    'api_path' => 'mailpoet',
    'class_name' => 'Zoho_Flow_MailPoet',
    'gallery_app_link' => 'mailpoet',
    'description' => esc_html__('MailPoet is a newsletter plugin that can help you compose and design emails, maintain a subscriber list, and more. By integrating MailPoet with other applications, you\'ll be able to automate sending newsletters to your subscribers.', 'zoho-flow'),
    'icon_file' => 'mailpoet.png',
    'class_test' => 'MailPoet\API\MP\v1\API',
    'app_documentation_link' => '',
    'embed_link' => 'mailpoet',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/lists',
        'method' => 'get_lists',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'list',
        'path' => '/fields',
        'method' => 'get_fields',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'list',
        'path' => '/subscribers',
        'method' => 'get_subscribers',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'list',
        'path' => '/subscriber',
        'method' => 'get_subscriber',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/lists',
        'method' => 'create_list',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/subscriber',
        'method' => 'create_subscriber',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/subscriber/(?\'subscriber_id\'[\\d]+)/unsubscribe',
        'method' => 'unsubscribe_subscriber',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/subscriber/(?\'subscriber_id\'[\\d]+)/subscribetolists',
        'method' => 'subscriber_subscribetolists',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/subscriber/(?\'subscriber_id\'[\\d]+)/unsubscribefromlists',
        'method' => 'subscriber_unsubscribefromlists',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'mailpoet_manage_subscribers',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'mailpoet_subscriber_created',
        'method' => 'payload_subscriber_created',
        'args_count' => 1,
      ),
      array (
        'action' => 'mailpoet_subscriber_updated',
        'method' => 'payload_subscriber_updated',
        'args_count' => 1,
      ),
      array (
        'action' => 'mailpoet_subscriber_status_changed',
        'method' => 'payload_subscriber_status_changed',
        'args_count' => 1,
      ),

    ),
  ),
  array (
    'name' => esc_html__("Forminator"),
    'api_path' => 'forminator',
    'class_name' => 'Zoho_Flow_Forminator',
    'gallery_app_link' => 'forminator',
    'description' => esc_html__('Use Forminator to create quizzes, polls, and forms on your WordPress site. When connected with Zoho Flow, you can create workflows that automatically compile quiz results in a spreadsheet, inform your team of new poll responses, or create tasks based on form feedback.', 'zoho-flow'),
    'icon_file' => 'forminator.png',
    'class_test' => 'Forminator_API',
    'app_documentation_link' => '',
    'embed_link' => 'forminator',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_form_fields',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'list',
        'path' => '/polls',
        'method' => 'get_polls',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'list',
        'path' => '/quizzes',
        'method' => 'get_quizzes',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'list',
        'path' => '/quizzes/(?\'quiz_id\'[\\d]+)/fields',
        'method' => 'get_quiz_fields',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'manage_forminator',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'forminator_custom_form_mail_before_send_mail',
        'method' => 'payload_form_entry_added',
        'args_count' => 4,
      ),
      array (
        'action' => 'forminator_poll_mail_before_send_mail',
        'method' => 'payload_poll_added',
        'args_count' => 4,
      ),
      array (
        'action' => 'forminator_quiz_mail_before_send_mail',
        'method' => 'payload_quiz_added',
        'args_count' => 4,
      )
    )
  ),
  array (
    'name' => esc_html__('Formidable Forms'),
    'api_path' => 'formidable-forms',
    'class_name' => 'Zoho_Flow_Formidable_Forms',
    'gallery_app_link' => 'formidable-forms',
    'description' => esc_html__('Build a simple contact form or complex multipage form with conditional logic, calculations, file uploads, and more, using Formidable Forms. You can then integrate it with other apps to automatically upload the forms to your team’s cloud drive, send new submissions to your team’s chat channel, or add contacts to your CRM.', 'zoho-flow'),
    'icon_file' => 'formidable-forms.png',
    'class_test' => 'FrmSettings',
    'app_documentation_link' => 'formidable-forms',
    'embed_link' => 'formidable_forms',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'frm_view_forms',
        'schema_method' => 'get_form_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_fields',
        'capability' => 'frm_view_forms',
        'schema_method' => 'get_field_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'frm_view_forms',
        'schema_method' => 'get_form_webhook_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook_deprecated',
        'capability' => 'frm_edit_forms',
      ),
      array (
        'type' => 'delete',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook_deprecated',
        'capability' => 'frm_delete_forms',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array (
      array (
        'action' => 'frm_after_create_entry',
        'method' => 'process_form_submission',
        'args_count' => 3,
      ),
      array (
        'action' => 'frm_after_create_entry',
        'method' => 'payload_entry_created',
        'args_count' => 3,
      ),
      array (
        'action' => 'frm_after_update_entry',
        'method' => 'payload_entry_updated',
        'args_count' => 2,
      ),
    ),
  ),
  array (
    'name' => esc_html__("Fluent Forms"),
    'api_path' => 'fluent-forms',
    'class_name' => 'Zoho_Flow_Fluent_Forms',
    'gallery_app_link' => 'fluent-forms',
    'description' => esc_html__('Fluent Forms is a form builder plugin that can help you build many types of forms. Integrate Fluent Forms with your favorite applications using Zoho Flow to automatically add prospects who fill out your forms as contacts in your CRM.', 'zoho-flow'),
    'icon_file' => 'fluent-forms.png',
    'class_test' => 'FluentForm\App\Api\Form',
    'app_documentation_link' => '',
    'embed_link' => 'fluent_forms',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_all_forms',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_all_form_fields',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'fluentform/submission_inserted',
        'method' => 'payload_submission_inserted',
        'args_count' => 3,
      )
    )
  ),
  array (
    'name' => esc_html__('LearnDash'),
    'api_path' => 'learndash',
    'class_name' => 'Zoho_Flow_LearnDash',
    'gallery_app_link' => 'learndash',
    'description' => esc_html__('LearnDash helps you better sell your online courses by providing multiple pricing models, payment gateways, and automatic renewal notifications. Use Zoho Flow to automatically add new users enrolled in your course to your CRM, send customized emails to users who’ve completed quizzes, add users to a specific group, and more.', 'zoho-flow'),
    'icon_file' => 'learndash.png',
    'class_test' => 'Sfwd_Lms',
    'app_documentation_link' => '',
    'embed_link' => 'learndash_lms',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/courses',
        'method' => 'get_courses',
        'capability' => 'manage_options',
        'schema_method' => 'get_course_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/groups',
        'method' => 'get_groups',
        'capability' => 'manage_options',
        'schema_method' => 'get_group_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/course/(?\'course_id\'[\\d]+)/enroll',
        'method' => 'enroll_user_to_course',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'create',
        'path' => '/group/(?\'group_id\'[\\d]+)/add_users',
        'method' => 'add_users_to_group',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'update',
        'path' => '/group/(?\'group_id\'[\\d]+)/remove_users',
        'method' => 'remove_users_from_group',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'update',
        'path' => '/course/(?\'course_id\'[\\d]+)/remove_user',
        'method' => 'remove_users_from_course',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/user/(?\'user_id\'[\\d]+)/courses',
        'method' => 'user_courses',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/course/(?\'course_id\'[\\d]+)/quizzes',
        'method' => 'get_quizzes',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/group/(?\'group_id\'[\\d]+)/users',
        'method' => 'group_users',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/users',
        'method' => 'get_users',
        'capability' => 'read',
        'schema_method' => 'get_user_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/course/(?\'course_id\'[\\d]+)',
        'method' => 'get_courses',
        'capability' => 'read',
      ),
      array (
        'type' => 'list',
        'path' => '/course/(?\'course_id\'[\\d]+)/lessons',
        'method' => 'get_lessons',
        'capability' => 'read',
      ),
      array (
        'type' => 'list',
        'path' => '/lesson/(?\'lesson_id\'[\\d]+)/topics',
        'method' => 'get_topics',
        'capability' => 'read',
      ),
      array (
        'type' => 'list',
        'path' => '/post_types',
        'method' => 'list_post_types',
        'capability' => 'read',
      ),
      array (
        'type' => 'list',
        'path' => '/questions',
        'method' => 'get_ldquestions',
        'capability' => 'read',
      ),
      array (
        'type' => 'list',
        'path' => '/essay_submissions',
        'method' => 'get_essay_submissions',
        'capability' => 'read',
      ),
      array (
        'type' => 'create',
        'path' => '/(?\'action\'.+)/(?\'form_id\'[\\d]+)/webhook',
        'method' => 'create_webhook',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'delete',
        'path' => '/webhook/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/(?\'action\'.+)/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/webhooks',
        'method' => 'get_all_webhooks',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array (
        array (
        'action' => 'learndash_course_completed',
        'method' => 'process_course_completed',
        'args_count' => 1,
      ),
      array(
        'action' => 'learndash_topic_completed',
        'method' => 'process_topic_completed',
        'args_count' => 1
      ),
      array(
        'action' => 'learndash_lesson_completed',
        'method' => 'process_lesson_completed',
        'args_count' => 1
      ),
      array(
        'action' => 'learndash_quiz_completed',
        'method' => 'process_quiz_completed',
        "args_count" => 2
      ),
      array(
        'action' => 'learndash_new_essay_submitted',
        'method' => 'process_essay_submitted',
        "args_count" => 2
      ),
      array(
        'action' => 'learndash_update_course_access',
        'method' => 'process_enrolled_into_course',
        "args_count" => 4

      ),
      array(
        'action' => 'ld_added_group_access',
        'method' => 'process_group_enrolled',
        'args_count'=> 2
      )
    ),
  ),
  array(
    'name' => esc_html__("Post SMTP"),
    'api_path' => 'post-smtp',
    'class_name' => 'Zoho_Flow_Post_SMTP',
    'gallery_app_link' => 'post-smtp',
    'description' => esc_html__('Post SMTP is an SMTP mailer WordPress plugin that enhances email deliverability, logging, authentication, and more. Integrate Post SMTP with other applications to track your email logs for marketing purposes.', 'zoho-flow'),
    'icon_file' => 'post-smtp.png',
    'class_test' => 'PostmanWpMail',
    'app_documentation_link' => '',
    'embed_link' => 'post_smtp',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/stats',
        'method' => 'get_stats',
        'capability' => 'manage_postman_logs',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_postman_logs',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'post_smtp_on_success',
        'method' => 'payload_email_success',
        'args_count' => 4,
      ),
      array (
        'action' => 'post_smtp_on_failed',
        'method' => 'payload_email_failure',
        'args_count' => 5,
      ),
    )
  ),
  array (
    'name' => esc_html__('Ultimate Member'),
    'api_path' => 'ultimate-member',
    'class_name' => 'Zoho_Flow_Ultimate_Member',
    'gallery_app_link' => 'ultimate-member',
    'description' => esc_html__('Ultimate Member’s WordPress plugin makes it a breeze for users to sign up and become members of your website. Easily manage your forms by automating follow-ups for form submissions, contact management, cloud storage, and more, using Zoho Flow.', 'zoho-flow'),
    'icon_file' => 'ultimate-member.png',
    'class_test' => 'UM',
    'app_documentation_link' => '',
    'embed_link' => 'ultimate_member',
    'version' => 'v1',
    'rest_apis' => array (
        array (
            'type' => 'list',
            'path' => '/forms',
            'method' => 'get_forms',
            'capability' => 'manage_options',
            'schema_method' => 'get_form_schema',
        ),
        array (
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
            'method' => 'get_fields',
            'capability' => 'manage_options',
            'schema_method' => 'get_field_schema',
        ),
        array (
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
            'method' => 'get_webhooks',
            'capability' => 'manage_options',
            'schema_method' => 'get_form_webhook_schema',
        ),
        array (
            'type' => 'create',
            'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
            'method' => 'create_webhook',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'delete',
            'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
            'method' => 'delete_webhook',
            'capability' => 'manage_options',
        ),
    ),
    'hooks' => array (
          array (
              'action' => 'um_after_save_registration_details',
              'method' => 'process_form_submission',
              'args_count' => 2,
          ),
          array(
              'action' => 'um_after_user_updated',
              'method' => 'um_user_updated',
              'args_count' => 3,
          ),
      ),
  ),
  array(
    'name' => esc_html__("FluentSMTP"),
    'api_path' => 'fluentsmtp',
    'class_name' => 'Zoho_Flow_FluentSMTP',
    'gallery_app_link' => 'fluentsmtp',
    'description' => esc_html__('FluentSMTP is an SMTP WordPress plugin that lets you send transactional and marketing emails without delivery issues. By integrating Fluent SMTP with other applications, you will get notified when a mail has been delivered successfully.', 'zoho-flow'),
    'icon_file' => 'fluentsmtp.png',
    'class_test' => 'FluentMail\App\Models\Logger',
    'app_documentation_link' => '',
    'embed_link' => 'fluentsmtp',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/stats/all',
        'method' => 'get_overall_stats',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/stats/period',
        'method' => 'get_periodic_stats',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/mail/resend/(?\'log_id\'[\\d]+)',
        'method' => 'resend_mail_from_logger',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'fluentmail_email_sending_failed',
        'method' => 'payload_send_failure',
        'args_count' => 2,
      ),
    ),
  ),
  array (
    'name' => esc_html__('Everest Forms'),
    'api_path' => 'everest-forms',
    'class_name' => 'Zoho_Flow_Everest_Forms',
    'gallery_app_link' => 'everest-forms',
    'description' => esc_html__('Everest Forms is a drag-and-drop form builder plugin that’s lightweight, fast, and mobile responsive. Automatically create calendar events from new form submissions, add subscribers to your mailing list, create tickets for complaints received, and more, using Zoho Flow.', 'zoho-flow'),
    'icon_file' => 'everest-forms.png',
    'class_test' => 'EverestForms',
    'app_documentation_link' => 'everest-forms',
    'embed_link' => 'everest_forms',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_everest_forms',
        'schema_method' => 'get_form_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_fields',
        'capability' => 'manage_everest_forms',
        'schema_method' => 'get_field_schema',
      ),
      array (
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'manage_everest_forms',
        'schema_method' => 'get_form_webhook_schema',
      ),
      array (
        'type' => 'create',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_everest_forms',
      ),
      array (
        'type' => 'delete',
        'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'manage_everest_forms',
      ),
    ),
    'hooks' => array (
      array (
        'action' => 'everest_forms_process_complete',
        'method' => 'process_form_submission',
        'args_count' => 4,
      ),
    ),
  ),
  array (
    'name' => esc_html__("GiveWP"),
    'api_path' => 'givewp',
    'class_name' => 'Zoho_Flow_GiveWP',
    'gallery_app_link' => 'givewp',
    'description' => esc_html__('GiveWP is an online donation and fundraising platform for your WordPress website. Build GiveWP integrations on Zoho Flow to instantly notify your team of new donations, generate automated thank-you notes, or even update donor information in your CRM.', 'zoho-flow'),
    'icon_file' => 'givewp.png',
    'class_test' => 'Give_Donate_Form',
    'app_documentation_link' => '',
    'embed_link' => 'givewp',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'read_private_give_forms',
      ),
      array(
        'type' => 'list',
        'path' => '/donor',
        'method' => 'get_donor',
        'capability' => 'read_private_give_forms',
      ),
      array(
        'type' => 'create',
        'path' => '/donors/(?\'donor_id\'[\\d]+)/notes',
        'method' => 'add_donor_note',
        'capability' => 'edit_give_forms',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'read_private_give_forms',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'give_donor_post_create',
        'method' => 'payload_donar_added',
        'args_count' => 2,
      ),
      array (
        'action' => 'give_complete_form_donation',
        'method' => 'payload_donation_form_complete',
        'args_count' => 3,
      ),
    ),
  ),
  array (
    'name' => esc_html__("Mailster"),
    'api_path' => 'mailster',
    'class_name' => 'Zoho_Flow_Mailster',
    'gallery_app_link' => 'mailster',
    'description' => esc_html__('Mailster is a comprehensive email newsletter plugin for WordPress. With Mailster integrations, you can automate list management by adding new subscribers from different sources, sending customized follow-up emails based on user behavior, or even updating subscriber info from other platforms.', 'zoho-flow'),
    'icon_file' => 'mailster.png',
    'class_test' => 'MailsterCampaigns',
    'app_documentation_link' => '',
    'embed_link' => 'mailster',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/campaigns',
        'method' => 'get_campaigns',
        'capability' => 'mailster_dashboard',
      ),
      array(
        'type' => 'list',
        'path' => '/fields',
        'method' => 'get_custom_fields',
        'capability' => 'mailster_dashboard',
      ),
      array(
        'type' => 'list',
        'path' => '/lists',
        'method' => 'get_lists',
        'capability' => 'mailster_dashboard',
      ),
      array(
        'type' => 'list',
        'path' => '/statuses',
        'method' => 'get_statuses',
        'capability' => 'mailster_dashboard',
      ),
      array(
        'type' => 'list',
        'path' => '/subscriber',
        'method' => 'get_subscriber',
        'capability' => 'mailster_manage_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/subscriber',
        'method' => 'add_subscriber',
        'capability' => 'mailster_add_subscribers',
      ),
      array(
        'type' => 'update',
        'path' => '/subscriber/(?\'subscriber_id\'[\\d]+)',
        'method' => 'update_subscriber',
        'capability' => 'mailster_edit_subscribers',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'mailster_manage_subscribers',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'mailster_manage_subscribers',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array (
      array (
        'action' => 'mailster_add_subscriber',
        'method' => 'payload_add_subscriber',
        'args_count' => 1,
      ),
      array (
        'action' => 'mailster_update_subscriber',
        'method' => 'payload_update_subscriber',
        'args_count' => 1,
      ),
      array (
        'action' => 'mailster_tag_added',
        'method' => 'payload_subscriber_tag',
        'args_count' => 3,
      ),
      array (
        'action' => 'mailster_list_added',
        'method' => 'payload_add_subscriber_to_list',
        'args_count' => 3,
      ),

    ),
  ),
  array (
    'name' => esc_html__("Paid Memberships Pro"),
    'api_path' => 'paid-memberships-pro',
    'class_name' => 'Zoho_Flow_Paid_Memberships_Pro',
    'gallery_app_link' => 'paid-memberships-pro',
    'description' => esc_html__('Elevate your membership site with Paid Memberships Pro, offering levels, subscription packages, and more. Connect it with Zoho Flow, to easily handle member renewals, send out reminder emails before expiration, or even promote upgrades based on user activities.', 'zoho-flow'),
    'icon_file' => 'paid-memberships-pro.png',
    'class_test' => 'MemberOrder',
    'app_documentation_link' => '',
    'embed_link' => 'paid_memberships_pro',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/memberfields',
        'method' => 'get_fields',
        'capability' => 'pmpro_userfields',
      ),
      array(
        'type' => 'list',
        'path' => '/membershiplevels',
        'method' => 'get_levels',
        'capability' => 'pmpro_membershiplevels',
      ),
      array(
        'type' => 'list',
        'path' => '/member',
        'method' => 'get_user',
        'capability' => 'pmpro_memberslist',
      ),
      array(
        'type' => 'create',
        'path' => '/membershiplevelchange',
        'method' => 'change_user_membership_level',
        'capability' => 'pmpro_membershiplevels',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'pmpro_memberslist',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'pmpro_added_order',
        'method' => 'payload_order_added',
        'args_count' => 1,
      ),
      array (
        'action' => 'pmpro_updated_order',
        'method' => 'payload_order_updated',
        'args_count' => 1,
      ),
      array (
        'action' => 'pmpro_after_change_membership_level',
        'method' => 'payload_membership_level_changed',
        'args_count' => 3,
      ),
    )
  ),
  array(
    'name' => esc_html__("Ninja Tables"),
    'api_path' => 'ninja-tables',
    'class_name' => 'Zoho_Flow_NinjaTables',
    'gallery_app_link' => 'ninja-tables',
    'description' => esc_html__('Ninja Tables a table builder plugin you can use to create and manage tables and view data in multiple formats. Integrate Ninja Tables with your favorite applications to ensure an instant and seamless flow of data transfer to your tables.', 'zoho-flow'),
    'icon_file' => 'ninja-tables.png',
    'class_test' => 'NinjaTables\App\Models\NinjaTableItem',
    'app_documentation_link' => '',
    'embed_link' => 'ninja_tables',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/tables',
        'method' => 'list_tables',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/tables/(?\'table_id\'[\\d]+)',
        'method' => 'get_table_details',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/tables/(?\'table_id\'[\\d]+)/row/(?\'row_id\'[\\d]+)',
        'method' => 'fetch_table_row',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/tables/(?\'table_id\'[\\d]+)/row',
        'method' => 'add_table_row',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'update',
        'path' => '/tables/(?\'table_id\'[\\d]+)/row/(?\'row_id\'[\\d]+)',
        'method' => 'update_table_row',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'ninja_table_after_add_item',
        'method' => 'payload_added_item',
        'args_count' => 3,
      ),
      array (
        'action' => 'ninja_table_after_update_item',
        'method' => 'payload_updated_item',
        'args_count' => 3,
      ),
    )
  ),
  array (
    'name' => esc_html__("User Registration"),
    'api_path' => 'user-registration',
    'class_name' => 'Zoho_Flow_User_Registration',
    'gallery_app_link' => 'user-registration',
    'description' => esc_html__('Simplify user signups on your WordPress site with the User Registration plugin. When connected to Zoho Flow, automate onboarding processes by sending welcome emails, adding users to specific groups, or initiating a new member journey in your marketing app.', 'zoho-flow'),
    'icon_file' => 'user-registration.png',
    'class_test' => 'UR_Form_Handler',
    'app_documentation_link' => '',
    'embed_link' => 'user_registration',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_user_registration',
      ),
      array(
        'type' => 'list',
        'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
        'method' => 'get_form_fields',
        'capability' => 'manage_user_registration',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'manage_user_registration',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'user_registration_after_register_user_action',
        'method' => 'payload_after_register_user_action',
        'args_count' => 3,
      )
    )
  ),
  array (
    'name' => esc_html__('Simple Membership'),
    'api_path' => 'simple-membership',
    'class_name' => 'Zoho_Flow_Simple_Membership',
    'gallery_app_link' => 'simple-membership',
    'description' => esc_html__('With Simple Membership, protect your WordPress posts and pages, restricting access only to members. Integrate Simple Membership with the other apps you use to automatically update membership levels in your CRM, notify members about expiration through various platforms, or even send out personalized member-only offers.', 'zoho-flow'),
    'icon_file' => 'simple-membership.png',
    'class_test' => 'SimpleWpMembership',
    'app_documentation_link' => '',
    'embed_link' => 'simple_membership',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/members',
        'method' => 'get_members',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/membershiplevels',
        'method' => 'get_membership_levels',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/create_membership_level',
        'method' => 'create_membership',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'update',
        'path' => '/update_membership_level',
        'method' => 'update_membership',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'create',
        'path' => '/createmember',
        'method' => 'create_member',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'update',
        'path' => '/updatemember',
        'method' => 'update_member',
        'capability' => 'manage_options',
      ),
      array (
        'type' => 'list',
        'path' => '/getmember/(?\'member_id\'[\\d]+)',
        'method' => 'get_member',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/getmember/(?P<login>\S+)',
        'method' => 'get_member',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_posts',
      ),
      array(
        'type' => 'update',
        'path' => '/updatemembershiplevel',
        'method' => 'update_membership_level_of_member',
        'capability' => 'manage_options',
      ),
    ),
    'hooks' => array (
      array(
        'action' => 'swpm_admin_end_registration_complete_user_data',
        'method' => 'process_swpm_registration_user_data',
        'args_count' => 1,
      ),
      array(
        'action' => 'swpm_admin_end_edit_complete_user_data',
        'method' => 'process_swpm_registration_user_data',
        'args_count' => 1,
      )
    )
  ),
  array(
    'name' => esc_html__("BuddyBoss"),
    'api_path' => 'buddyboss',
    'class_name' => 'Zoho_Flow_BuddyBoss',
    'gallery_app_link' => 'buddyboss',
    'description' => esc_html__('BuddyBoss is a WordPress community platform that enables users to create online communities, online forums, private groups, and more. Integrate BuddyBoss with other applications to create a centralized marketing hub.', 'zoho-flow'),
    'icon_file' => 'buddyboss.png',
    'class_test' => 'BP_Activity_Activity',
    'app_documentation_link' => '',
    'embed_link' => 'buddyboss',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/activities',
        'method' => 'get_activities',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/creategroup',
        'method' => 'create_group',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/activity_post',
        'method' => 'activity_post_update',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/invite_member',
        'method' => 'invite_member_to_group',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/follow_unfollow_member',
        'method' => 'follow_request',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/friendship',
        'method' => 'create_friendship',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/sendinvite',
        'method' => 'send_invite',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/topic',
        'method' => 'create_topic',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/forums',
        'method' => 'get_forums',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/groups',
        'method' => 'get_groups',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/members',
        'method' => 'get_members',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
       )
    ),
    'hooks' => array(
      array(
        'action' => 'bp_member_invite_submit',//in rest
        'method' => 'trigger_new_invite',
        'args_count' => 2,
      ),
      array(
        'action' => 'bp_notification_after_save',
        'method' => 'trigger_new_notification',
        'args_count' => 1,
      ),
      array(
        'action' => 'bp_activity_after_save',
        'method' => 'trigger_new_activity',
        'args_count' => 1,
      ),
      array(
        'action' => 'bp_core_signup_user',
        'method' => 'trigger_new_member',
        'args_count' => 5,
      ),
      array(
        'action' => 'bbp_publicized_forum',
        'method' => 'trigger_new_forum',
        'args_count' => 1,
      ),
    ),
  ),
  array (
    'name' => esc_html__('Login/Signup Popup'),
    'api_path' => 'login-signup-popup',
    'class_name' => 'Zoho_Flow_Login_Signup_Popup',
    'gallery_app_link' => 'login-signup-popup',
    'description' => esc_html__('Login/Signup Popup is a lightweight WordPress plugin that can make registration, login, password reset, and other login-related actions easier.', 'zoho-flow'),
    'icon_file' => 'login-signup-popup.png',
    'class_test' => 'Xoo_Aff',
    'app_documentation_link' => '',
    'embed_link' => 'login_signup_popup',
    'version' => 'v1',
    'rest_apis' => array (
      array (
        'type' => 'list',
        'path' => '/fields',
        'method' => 'get_user_meta_keys',
        'capability' => 'list_users',
      ),
      array (
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'list_users',
      ),
      array (
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array (
      array (
        'action' => 'xoo_el_login_success',
        'method' => 'payload_login_success',
        'args_count' => 1,
      ),
      array (
        'action' => 'xoo_el_registration_success',
        'method' => 'payload_registration_success',
        'args_count' => 1,
      ),
      array (
        'action' => 'xoo_el_created_customer',
        'method' => 'payload_customer_created',
        'args_count' => 2,
      ),
      array (
        'action' => 'xoo_el_reset_password_success',
        'method' => 'payload_password_reset_success',
        'args_count' => 1,
      ),
    ),
  ),
  array (
    'name' => esc_html__("PlanSo Forms"),
    'api_path' => 'planso-forms',
    'class_name' => 'Zoho_Flow_Planso_Forms',
    'gallery_app_link' => 'planso-forms',
    'description' => esc_html__('PlanSo Forms’s intuitive and user-friendly interface, added with features like auto-responder emails and integrated spam protection, makes it easy to build amazing forms for your WordPress site. Manage form submissions and analyze data efficiently by automatically moving data between your apps using Zoho Flow.', 'zoho-flow'),
    'icon_file' => 'planso-forms.png',
    'class_test' => 'Recursive_ArrayAccess',
    'app_documentation_link' => '',
    'embed_link' => 'planso_forms',
    'version' => 'v1',
    'rest_apis' => array(
        array(
            'type' => 'list',
            'path' => '/forms',
            'method' => 'get_forms',
            'capability' => 'manage_options',
        ),
        array(
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[\\d]+)/fields',
            'method' => 'get_fields',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'list',
            'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
            'method' => 'get_webhooks',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'create',
            'path' => '/forms/(?\'form_id\'[\\d]+)/webhooks',
            'method' => 'create_webhook',
            'capability' => 'manage_options',
        ),
        array(
            'type' => 'delete',
            'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
            'method' => 'delete_webhook',
            'capability' => 'delete_posts',
        ),
    ),
    'hooks' => array(
          array(
              'action' => 'psfb_submit_after_error_check_success',
              'method' => 'process_form_submission',
              'args_count' => 1,
          ),
      ),
  ),
  array(
    'name' => esc_html__("FluentCRM"),
    'api_path' => 'fluentcrm',
    'class_name' => 'Zoho_Flow_FluentCRM',
    'gallery_app_link' => 'fluentcrm',
    'description' => esc_html__('FluentCRM is an email marketing automation plugin where you can manage your email campaigns and other email marketing activities. By integrating FluentCRM with other applications using Zoho Flow, you\'ll be able to automate your email campaigns.', 'zoho-flow'),
    'icon_file' => 'fluentcrm.png',
    'class_test' => 'FluentCrm\App\Models\Subscriber',
    'app_documentation_link' => '',
    'embed_link' => 'fluentcrm',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/forms',
        'method' => 'get_forms',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/allcontacts',
        'method' => 'get_contacts',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/fetchcontact/(?\'id\'[\\d]+)',
        'method' => 'fetch_contact',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/tags',
        'method' => 'fetch_tags',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/lists',
        'method' => 'fetch_lists',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/createcontact',
        'method' => 'create_contact',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'update',
        'path' => '/updatecontact',
        'method' => 'update_contact',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/tags/create',
        'method' => 'create_tags',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'create',
        'path' => '/lists/create',
        'method' => 'create_lists',
        'capability' => 'manage_options',
      ),
      array(
        'type' => 'list',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'get_webhooks',
        'capability' => 'read',
      ),
      array(
        'type' => 'create',
        'path' => '/(?\'type\'[a-zA-Z_]+)/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_posts',
      ),
     array(
       'type' => 'list',
       'path' => '/systeminfo',
       'method' => 'get_system_info',
       'capability' => 'read',
      )
    ),
    'hooks' => array(
      array(
        'action' => 'fluent_crm/contact_created',
        'method' => 'process_form_submission',
        'args_count' => 1,
      ),
      array(
        'action' => 'fluent_crm/contact_updated',
        'method' => 'process_contact_updated',
        'args_count' => 1,
      )
    ),
  ),
  array (
    'name' => esc_html__('DigiMember'),
    'api_path' => 'digi-member',
    'class_name' => 'Zoho_Flow_Digi_Member',
    'gallery_app_link' => 'digimember',
    'description' => esc_html__('This easy-to-use membership plugin for WordPress lets you build your own automated membership site. Let Zoho Flow automatically add new orders to your spreadsheet, notify you by chat when a new order is made, create new orders from emails received, and more.', 'zoho-flow'),
    'icon_file' => 'digi-member.png',
    'class_test' => 'ncore_Class',
    'app_documentation_link' => '',
    'embed_link' => 'digimember',
    'version' => 'v1',
    'rest_apis' => array (
        array (
            'type' => 'list',
            'path' => '/products',
            'method' => 'get_all_products',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'list',
            'path' => '/users/(?\'user_id\'[\\d]+)/products',
            'method' => 'get_products_of_user',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'list',
            'path' => '/orders/(?\'user_id\'[\\d]+)',
            'method' => 'get_user_orders',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'create',
            'path' => '/orders',
            'method' => 'create_orders',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'list',
            'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks',
            'method' => 'get_webhook_for_order',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'create',
            'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks',
            'method' => 'create_webhook_for_order',
            'capability' => 'manage_options',
        ),
        array (
            'type' => 'delete',
            'path' => '/(?\'post_type\'[a-zA-Z_]+)/webhooks/(?\'webhook_id\'[\\d]+)',
            'method' => 'delete_webhook',
            'capability' => 'manage_options',
        ),
    ),
    'hooks' => array (
          array (
              'action' => 'digimember_purchase',
              'method' => 'digi_purchase',
              'args_count' => 4,
          ),
      ),
  ),
  array (
    'name' => esc_html__("WP Travel Engine"),
    'api_path' => 'wp-travel-engine',
    'class_name' => 'Zoho_Flow_WP_Travel_Engine',
    'gallery_app_link' => 'wp-travel-engine',
    'description' => esc_html__('WP Travel Engine is a travel booking plugin that can help you build SEO-friendly travel booking websites. By integrating WP Travel Engine with your favorite applications, you\'ll be able to automate making bookings and payments, and sending invoices.', 'zoho-flow'),
    'icon_file' => 'wp-travel-engine.png',
    'class_test' => 'Wp_Travel_Engine_Admin',
    'app_documentation_link' => '',
    'embed_link' => 'wp_travel_engine',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/trips',
        'method' => 'get_trips',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'wp_travel_engine_after_enquiry_sent',
        'method' => 'payload_enquiry_created',
        'args_count' => 1,
      ),
      array (
        'action' => 'wp_travel_engine_after_booking_process_completed',
        'method' => 'payload_booking_created',
        'args_count' => 1,
      )
    )
  ),
  array(
    'name' => esc_html__("UsersWP"),
    'api_path' => 'userswp',
    'class_name' => 'Zoho_Flow_UsersWP',
    'gallery_app_link' => 'userswp',
    'description' => esc_html__('UsersWP is a user registration WordPress plugin that enables users to create and manage secure logins and registrations. Integrate UsersWP with other applications to ensure a safe and secure login.', 'zoho-flow'),
    'icon_file' => 'userswp.png',
    'class_test' => 'UsersWP_Forms',
    'app_documentation_link' => '',
    'embed_link' => 'userswp',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'list_users',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'read',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'uwp_after_process_login',
        'method' => 'payload_login_success',
        'args_count' => 1,
      ),
      array (
        'action' => 'wp_login_failed',
        'method' => 'payload_login_failed',
        'args_count' => 1,
      ),
      array (
        'action' => 'uwp_after_process_register',
        'method' => 'payload_register_success',
        'args_count' => 2,
      ),
      array (
        'action' => 'uwp_after_process_forgot',
        'method' => 'payload_forgot_password',
        'args_count' => 1,
      ),
    )
  ),
  array(
    'name' => esc_html__("Fluent Support"),
    'api_path' => 'fluent-support',
    'class_name' => 'Zoho_Flow_Fluent_Support',
    'gallery_app_link' => 'fluent-support',
    'description' => esc_html__('Fluent Support is a support ticket management system to manage support agents, customers, and tickets. Integrate Fluent Support with other applications using Zoho Flow to escalate or assign tickets to a support agent or get notified every time a new ticket is initiated.', 'zoho-flow'),
    'icon_file' => 'fluent-support.png',
    'class_test' => 'FluentSupport\App\Api\Classes\Tickets',
    'app_documentation_link' => '',
    'embed_link' => 'fluent_support',
    'version' => 'v1',
    'rest_apis' => array(
      array(
        'type' => 'list',
        'path' => '/products',
        'method' => 'get_all_products',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/customers',
        'method' => 'get_all_customers',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/tickets',
        'method' => 'get_all_tickets',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/mailbox',
        'method' => 'get_all_mailbox',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/agents',
        'method' => 'get_all_agents',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/getcustomers',
        'method' => 'get_customer',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/getagents',
        'method' => 'get_agent',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/gettickets',
        'method' => 'get_ticket',
        'capability' => 'read_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/customers',
        'method' => 'customer_create',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/tickets',
        'method' => 'ticket_create',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'create',
        'path' => '/webhooks',
        'method' => 'create_webhook',
        'capability' => 'edit_private_posts',
      ),
      array(
        'type' => 'delete',
        'path' => '/webhooks/(?\'webhook_id\'[\\d]+)',
        'method' => 'delete_webhook',
        'capability' => 'delete_private_posts',
      ),
      array(
        'type' => 'list',
        'path' => '/systeminfo',
        'method' => 'get_system_info',
        'capability' => 'read',
      )
    ),
    'hooks' => array(
      array (
        'action' => 'fluent_support/ticket_created',
        'method' => 'payload_ticket_created',
        'args_count' => 2,
      ),
      array (
        'action' => 'fluent_support/ticket_agent_change',
        'method' => 'payload_ticket_agent_changed',
        'args_count' => 2,
      ),
      array (
        'action' => 'fluent_support/tickets_moved',
        'method' => 'payload_tickets_moved',
        'args_count' => 3,
      ),
      array (
        'action' => 'fluent_support/ticket_closed',
        'method' => 'payload_ticket_closed',
        'args_count' => 2,
      ),
      array (
        'action' => 'fluent_support/ticket_reopen',
        'method' => 'payload_ticket_reopened',
        'args_count' => 2,
      ),
    )
  )
);
