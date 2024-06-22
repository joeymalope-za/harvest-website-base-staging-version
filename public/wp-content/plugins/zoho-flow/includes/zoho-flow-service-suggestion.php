<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Zoho_Flow_Service_Suggestion{
  private $id;
  private $name;
  private $icon_file;
  private $gallery_app_link;
  private $plugin_api_page_link;
  private $has_api_key;
  private $is_plugin_integration;

  //admin notice blocked services: wpforms,formidable-forms,everest-forms, mailster, bitform, ninja-tables, Akismet, Fluent SMTP
  public function __construct() {
    global $pagenow;
  	if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='wpcf7') || ($_REQUEST['page']=='wpcf7-integration')))){
      $this->set_service_meta('contact-form-7');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) &&(($_REQUEST['page']=='nf-submissions') || ($_REQUEST['page']=='nf-settings') || ($_REQUEST['page']=='nf-system-status') || ($_REQUEST['page']=='nf-import-export')))){
      $this->set_service_meta('ninja-forms');
    }
    //shows only in formidable-smtp
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='formidable') || ($_REQUEST['page']=='formidable-smtp') || ($_REQUEST['page']=='formidable-addons') || ($_REQUEST['page']=='formidable-import') || ($_REQUEST['page']=='formidable-entries')))){
      $this->set_service_meta('formidable-forms');
    }
    else if((($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='ultimatemember') || ($_REQUEST['page']=='ultimatemember-extensions')))) || (($pagenow=='edit.php') && ((!empty($_REQUEST['post_type'])) && ($_REQUEST['post_type']=='um_form')))){
      $this->set_service_meta('ultimate-member');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='digimember') || ($_REQUEST['page']=='digimember_orders')))){
      $this->set_service_meta('digi-member');
    }
    else if((($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='learndash_lms_settings') || ($_REQUEST['page']=='learndash-lms-reports')))) || (($pagenow=='edit.php') && ((!empty($_REQUEST['post_type'])) && (($_REQUEST['post_type']=='groups') || ($_REQUEST['post_type']=='sfwd-courses') || ($_REQUEST['post_type']=='sfwd-essays') || ($_REQUEST['post_type']=='sfwd-question'))))){
      $this->set_service_meta('learndash');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && ($_REQUEST['page']=='ps-form-builder'))){
      $this->set_service_meta('planso-forms');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='simple_wp_membership') || ($_REQUEST['page']=='simple_wp_membership_addons')))){
      $this->set_service_meta('simple-membership');
    }
    else if(($pagenow=='edit.php') && ((!empty($_REQUEST['post_type'])) && ($_REQUEST['post_type']=='acf-field-group'))){
      $this->set_service_meta('advanced-custom-fields');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='forminator') || ($_REQUEST['page']=='forminator-integrations') || ($_REQUEST['page']=='forminator-addons') || ($_REQUEST['page']=='forminator-entries')))){
      $this->set_service_meta('forminator');
    }
    else if((($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && ($_REQUEST['page']=='give-forms'))) || (($pagenow=='edit.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='give-add-ons') || ($_REQUEST['page']=='give-donors') || ($_REQUEST['page']=='give-payment-history'))))){
      $this->set_service_meta('givewp');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='user-registration') || ($_REQUEST['page']=='user-registration-addons')))){
      $this->set_service_meta('user-registration');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='pmpro-dashboard') || ($_REQUEST['page']=='pmpro-memberslist') || ($_REQUEST['page']=='pmpro-orders') || ($_REQUEST['page']=='pmpro-addons')))){
      $this->set_service_meta('paid-memberships-pro');
    }
    else if((($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='wc-addons') || ($_REQUEST['page']=='wc-admin') || ($_REQUEST['page']=='wc-orders') || ($_REQUEST['page']=='wc-reports') || ($_REQUEST['page']=='wc-settings') || ($_REQUEST['page']=='wc-status'))))){
      $this->set_service_meta('woocommerce');
    }
    else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='gf_edit_forms') || ($_REQUEST['page']=='gf_addons') || ($_REQUEST['page']=='gf_entries') || ($_REQUEST['page']=='gf_export')))){
      $this->set_service_meta('gravity-forms');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='mailpoet-homepage') || ($_REQUEST['page']=='mailpoet-forms') || ($_REQUEST['page']=='mailpoet-subscribers') || ($_REQUEST['page']=='mailpoet-lists') || ($_REQUEST['page']=='mailpoet-help') || ($_REQUEST['page']=='mailpoet-upgrade')))){
      $this->set_service_meta('mailpoet');
    }
		else if((($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='wptravelengine-admin-page')))) || (($pagenow=='edit.php') && ((!empty($_REQUEST['post_type'])) && (($_REQUEST['post_type']=='booking') || ($_REQUEST['post_type']=='enquiry'))))){
      $this->set_service_meta('wp-travel-engine');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='fluent_forms_smtp') || ($_REQUEST['page']=='fluent_forms')))){
      $this->set_service_meta('fluent-forms');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && ($_REQUEST['page']=='fluentcrm-admin'))){
      $this->set_service_meta('fluentcrm');
    }
		else if(($pagenow=='admin.php') && (!empty($_REQUEST['page'])) && ($_REQUEST['page']=='fluent-support')){
      $this->set_service_meta('fluent-support');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='tablepress') || ($_REQUEST['page']=='tablepress_add') || ($_REQUEST['page']=='tablepress_import') || ($_REQUEST['page']=='tablepress_export')))){
      $this->set_service_meta('tablepress');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='ninja_tables')))){
      $this->set_service_meta('ninja-tables');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='akismet-key-config')))){
      $this->set_service_meta('akismet');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='postman') || ($_REQUEST['page']=='postman_email_log')))){
      $this->set_service_meta('post-smtp');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='userswp') || ($_REQUEST['page']=='uwp_form_builder') || ($_REQUEST['page']=='uwp_status') || ($_REQUEST['page']=='uwp-addons')))){
      $this->set_service_meta('userswp');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='bp-components') || ($_REQUEST['page']=='bp-integrations') || ($_REQUEST['page']=='bp-profile-setup') || ($_REQUEST['page']=='bp-activity') || ($_REQUEST['page']=='bp-tools') || ($_REQUEST['page']=='bp-help') || ($_REQUEST['page']=='bp-settings') || ($_REQUEST['page']=='bp-pages')))){
      $this->set_service_meta('buddyboss');
    }
		else if(($pagenow=='admin.php') && ((!empty($_REQUEST['page'])) && (($_REQUEST['page']=='easy-login-woocommerce-settings') || ($_REQUEST['page']=='xoo-el-fields')))){
      $this->set_service_meta('login-signup-popup');
    }
		else if(($pagenow=='edit.php') || ($pagenow=='upload.php') || ($pagenow=='edit-comments.php') || ($pagenow=='users.php')){
      $this->set_service_meta('wordpress-org');
    }


    if($this->is_plugin_integration){
      $this->has_api_key = $this->has_api_keys();
    }
	}

  private function set_service_meta($service_id){
		if($service_id == 'wordpress-org'){
      $this->id = 'wordpress-org';
      $this->name = 'WordPress.org';
      $this->icon_file = 'wordpress.png';
      $this->gallery_app_link = 'wordpress-org';
      $this->plugin_api_page_link = 'wordpress-org';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'contact-form-7'){
      $this->id = 'contact-form-7';
      $this->name = 'Contact Form 7';
      $this->icon_file = 'contact-form-7.png';
      $this->gallery_app_link = 'contact-form-7';
      $this->plugin_api_page_link = 'contact-form-7';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'ninja-forms'){
      $this->id = 'ninja-forms';
      $this->name = 'Ninja Forms';
      $this->icon_file = 'ninja-forms.png';
      $this->gallery_app_link = 'ninja-forms';
      $this->plugin_api_page_link = 'ninja-forms';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'formidable-forms'){
      $this->id = 'formidable-forms';
      $this->name = 'Formidable Forms';
      $this->icon_file = 'formidable-forms.png';
      $this->gallery_app_link = 'formidable-forms';
      $this->plugin_api_page_link = 'formidable-forms';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'ultimate-member'){
      $this->id = 'ultimate-member';
      $this->name = 'Ultimate Member';
      $this->icon_file = 'ultimate-member.png';
      $this->gallery_app_link = 'ultimate-member';
      $this->plugin_api_page_link = 'ultimate-member';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'digi-member'){
      $this->id = 'digi-member';
      $this->name = 'DigiMember';
      $this->icon_file = 'digi-member.png';
      $this->gallery_app_link = 'digimember';
      $this->plugin_api_page_link = 'digi-member';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'learndash'){
      $this->id = 'learndash';
      $this->name = 'LearnDash';
      $this->icon_file = 'learndash.png';
      $this->gallery_app_link = 'learndash';
      $this->plugin_api_page_link = 'learndash';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'planso-forms'){
      $this->id = 'planso-forms';
      $this->name = 'PlanSo Forms';
      $this->icon_file = 'planso-forms.png';
      $this->gallery_app_link = 'planso-forms';
      $this->plugin_api_page_link = 'planso-forms';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'simple-membership'){
      $this->id = 'simple-membership';
      $this->name = 'Simple Membership';
      $this->icon_file = 'simple-membership.png';
      $this->gallery_app_link = 'simple-membership';
      $this->plugin_api_page_link = 'simple-membership';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'advanced-custom-fields'){
      $this->id = 'advanced-custom-fields';
      $this->name = 'Advanced Custom Fields';
      $this->icon_file = 'acf.png';
      $this->gallery_app_link = 'advanced-custom-fields';
      $this->plugin_api_page_link = 'advanced-custom-fields';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'forminator'){
      $this->id = 'forminator';
      $this->name = 'Forminator';
      $this->icon_file = 'forminator.png';
      $this->gallery_app_link = 'forminator';
      $this->plugin_api_page_link = 'forminator';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'givewp'){
      $this->id = 'givewp';
      $this->name = 'GiveWP';
      $this->icon_file = 'givewp.png';
      $this->gallery_app_link = 'givewp';
      $this->plugin_api_page_link = 'givewp';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'user-registration'){
      $this->id = 'user-registration';
      $this->name = 'User Registration';
      $this->icon_file = 'user-registration.png';
      $this->gallery_app_link = 'user-registration';
      $this->plugin_api_page_link = 'user-registration';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'paid-memberships-pro'){
      $this->id = 'paid-memberships-pro';
      $this->name = 'Paid Memberships Pro';
      $this->icon_file = 'paid-memberships-pro.png';
      $this->gallery_app_link = 'paid-memberships-pro';
      $this->plugin_api_page_link = 'paid-memberships-pro';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'wp-travel-engine'){
      $this->id = 'wp-travel-engine';
      $this->name = 'WP Travel Engine';
      $this->icon_file = 'wp-travel-engine.png';
      $this->gallery_app_link = 'wp-travel-engine';
      $this->plugin_api_page_link = 'wp-travel-engine';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'mailpoet'){
      $this->id = 'mailpoet';
      $this->name = 'MailPoet';
      $this->icon_file = 'mailpoet.png';
      $this->gallery_app_link = 'mailpoet';
      $this->plugin_api_page_link = 'mailpoet';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'fluent-forms'){
      $this->id = 'fluent-forms';
      $this->name = 'Fluent Forms';
      $this->icon_file = 'fluent-forms.png';
      $this->gallery_app_link = 'fluent-forms';
      $this->plugin_api_page_link = 'fluent-forms';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'fluentcrm'){
      $this->id = 'fluentcrm';
      $this->name = 'FluentCRM';
      $this->icon_file = 'fluentcrm.png';
      $this->gallery_app_link = 'fluentcrm';
      $this->plugin_api_page_link = 'fluentcrm';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'fluent-support'){
      $this->id = 'fluent-support';
      $this->name = 'Fluent Support';
      $this->icon_file = 'fluent-support.png';
      $this->gallery_app_link = 'fluent-support';
      $this->plugin_api_page_link = 'fluent-support';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'tablepress'){
      $this->id = 'tablepress';
      $this->name = 'TablePress';
      $this->icon_file = 'tablepress.png';
      $this->gallery_app_link = 'tablepress';
      $this->plugin_api_page_link = 'tablepress';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'ninja-tables'){
      $this->id = 'ninja-tables';
      $this->name = 'Ninja Tables';
      $this->icon_file = 'ninja-tables.png';
      $this->gallery_app_link = 'ninja-tables';
      $this->plugin_api_page_link = 'ninja-tables';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'post-smtp'){
      $this->id = 'post-smtp';
      $this->name = 'Post SMTP';
      $this->icon_file = 'post-smtp.png';
      $this->gallery_app_link = 'post-smtp';
      $this->plugin_api_page_link = 'post-smtp';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'akismet'){
      $this->id = 'akismet';
      $this->name = 'Akismet';
      $this->icon_file = 'akismet.png';
      $this->gallery_app_link = 'akismet';
      $this->plugin_api_page_link = 'akismet';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'userswp'){
      $this->id = 'userswp';
      $this->name = 'UsersWP';
      $this->icon_file = 'userswp.png';
      $this->gallery_app_link = 'userswp';
      $this->plugin_api_page_link = 'userswp';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'buddyboss'){
      $this->id = 'buddyboss';
      $this->name = 'BuddyBoss';
      $this->icon_file = 'buddyboss.png';
      $this->gallery_app_link = 'buddyboss';
      $this->plugin_api_page_link = 'buddyboss';
      $this->is_plugin_integration = true;
    }
		else if($service_id == 'login-signup-popup'){
      $this->id = 'login-signup-popup';
      $this->name = 'Login/Signup Popup';
      $this->icon_file = 'login-signup-popup.png';
      $this->gallery_app_link = 'login-signup-popup';
      $this->plugin_api_page_link = 'login-signup-popup';
      $this->is_plugin_integration = true;
    }
    else if($service_id == 'woocommerce'){
      $this->id = 'woocommerce';
      $this->name = 'WooCommerce';
      $this->icon_file = 'woocommerce.png';
      $this->gallery_app_link = 'woocommerce';
      $this->plugin_api_page_link = '';
      $this->is_plugin_integration = false;
    }
    else if($service_id == 'gravity-forms'){
      $this->id = 'gravity-forms';
      $this->name = 'Gravity Forms';
      $this->icon_file = 'gravity-forms.png';
      $this->gallery_app_link = 'gravity-forms';
      $this->plugin_api_page_link = '';
      $this->is_plugin_integration = false;
    }
  }

  public function permission_to_show(){
    if(($this->is_plugin_integration) && (!$this->has_api_key)){
      return true;
    }
    else if(!($this->is_plugin_integration)){
      return true;
    }
    return false;
  }

  private function has_api_keys(){
    $args = array(
						'post_type' => WP_ZOHO_FLOW_API_KEY_POST_TYPE,
						'posts_per_page' => -1,
						'author' => get_current_user_id(),
						'fields' => 'ids',
            'meta_query' => array(
                  	'relation' => 'AND',
                  	array(
            					'key' => 'user_id',
            					'value' => get_current_user_id(),
            					'compare' => '='
            				),
            				array(
            					'key' => 'plugin_service',
            					'value' => $this->id,
            					'compare' => '='
            				)
      			)
					);
		$api_keys = get_posts( $args );
		if(isset($api_keys) && (sizeof($api_keys)>0))	{
			return true;
		}
		return false;
  }

  public function get_option_slug(){
    return "zoho_flow_next_suggestion_date_".$this->id."_".get_current_user_id();
  }

  public function display(){
    if(!empty($this->id)){
      ?>
      <div id= "flow-suggestion-notice" style="border: 5px solid transparent;border-bottom: 0;border-left: 0;border-right: 0;padding: 10px;border-image: url('<?php echo plugins_url('../assets/images/zoho-colors.gif', __FILE__); ?>') 100% 1 stretch;" class="notice notice-info is-dismissible">
    		<div style="display:flex;">
    			<div style="margin-top: auto;margin-bottom: auto;padding: 15px;display: inline-block;">
            <p hidden id="flow_service_id"><?php echo $this->id; ?></p>
    				<div style="display:inline-flex;">
    					<img style="max-height: 40px;max-width: 40px;object-fit: contain;padding: 10px;border: solid;border-color: #b8afaf;border-width: thin;border-radius: 10px;-webkit-box-sizing: content-box;" src="<?php echo plugins_url('../assets/images/flow-256.png', __FILE__); ?>"/>
    					<div style="margin-top: auto;margin-bottom: auto;font-size: 25px;font-weight: 400;">&#8644;</div>
              <img style="max-height: 40px;max-width: 40px;object-fit: contain;padding: 10px;border: solid;border-color: #b8afaf;border-width: thin;border-radius: 10px;-webkit-box-sizing: content-box;" src="<?php echo plugins_url('../assets/images/logos/' . $this->icon_file, __FILE__); ?>"/>
    				</div>
    			</div>
    			<div>
    				<div style="font-size: 15px;padding: 10px;padding-top: 5px;text-align: center;margin-left: auto;margin-right: auto;max-width: 90%;">
    					<?php
    					echo sprintf(
    						esc_html__('Unlock unlimited possibilities with %2$s! Seamlessly integrate your favorite services, including %1$s, with various business applications and experience the true potential of automation.'), sprintf('<strong>'.$this->name.'</strong>'),sprintf('<strong>Zoho Flow</strong>')
    					);
    					?>
    				</div>
    				<div style="text-align:center;">
              <?php
              if(!empty($this->plugin_api_page_link)){
                $flow_plugin_api_page_link = add_query_arg(
                  array(
                    'service' => $this->plugin_api_page_link
                  ),
                  menu_page_url( 'zoho_flow', false )
                );
                ?>
                  <a id="suggestion-notice-review-botton" class="button button-primary" style="margin:5px;" href="<?php echo $flow_plugin_api_page_link ?>" target="_blank"><?php echo 'Try now' ?></a>
                  <a id="suggestion-notice-gallery-botton" class="button button-secondary" style="margin:5px;" href="https://www.zohoflow.com/apps/<?php echo $this->gallery_app_link ?>/integrations/?utm_source=wordpress&utm_medium=link&utm_campaign=zoho_flow_integration_suggestion_<?php echo $this->id ?>" target="_blank"><?php echo 'Check how' ?></a>
                <?php
              }
              else{
                ?>
                  <a id="suggestion-notice-gallery-botton" class="button button-primary" style="margin:5px;" href="https://www.zohoflow.com/apps/<?php echo $this->gallery_app_link ?>/integrations/?utm_source=wordpress&utm_medium=link&utm_campaign=zoho_flow_plugin_suggestion" target="_blank"><?php echo 'Check how' ?></a>
                <?php
              }
               ?>
    					<a id="suggestion-notice-later-botton" class="button button-secondary" style="margin:5px;"><?php echo 'Remind me later' ?></a>
    					<a id="suggestion-notice-donot-botton" class="button button-secondary" style="margin:5px;"><?php echo 'Do not show again' ?></a>
    				</div>
    			</div>
    		</div>
  	   </div>
    <?php
    }
  }

}
