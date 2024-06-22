<?php
class Zoho_Flow_Ultimate_Member extends Zoho_Flow_Service
{
    public function get_forms( $request ) {
        $args = array('post_type' => 'um_form', 'posts_per_page' => -1);
        $forms = get_posts( $args );

        $data = array();
        if ( empty( $forms ) ) {
            return rest_ensure_response( $data );
        }
        $schema = $this->get_form_schema( $request );

        foreach ( $forms as $form ) {

            if ( isset( $schema['properties']['id'] ) ) {
                $post_data['id'] = $form->ID;
            }

            if ( isset( $schema['properties']['title'] ) ) {
                $post_data['title'] = $form->post_title;
            }
            if ( isset( $schema['properties']['created_at'] ) ) {
                $post_data['created_at'] = $form->post_date;
            }
            array_push($data, $post_data);
         }

        return rest_ensure_response( $data );
    }

    public function get_form_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'form',
            'type'                 => 'form',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'ID of the Ultimate Member Form.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'title' => array(
                    'description'  => esc_html__( 'The title of the Ultimate Member Form.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit'),
                ),
                'created_at' => array(
                    'description' => esc_html__("Created Date of the Ultimate Member Form", "zoho-flow"),
                    'type'        => 'string',
                    'context'     => array('view'),
                    'readonly'    => true,
                ),
            ),
        );

        return $schema;
    }

    public function get_fields( $request ) {
        $form_id = $request['form_id'];
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $form_fields = UM()->query()->get_attr( 'custom_fields', $form_id);

        if($this->check_form_exists($request['form_id'])){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $fields = array();
        foreach( $form_fields as $field ){
            $type = $field['type'];
            if($type!=='row'){
                $data = array(
                    'label' => $field['label'],
                    'metakey'=> $field['metakey'],
                    'type'=> $type,
                    'required' => $field['required'],
                );
                array_push($fields, $data);
            }
        }
        return rest_ensure_response( $fields );
    }

    public function get_webhooks($request){
        $form_id = $request['form_id'];
        
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }

        if($this->check_form_exists($form_id)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }
        
        $args = array(
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts($args);

        if ( empty( $webhooks ) ) {
            return rest_ensure_response( $webhooks );
        }

        $data = array();

        foreach ( $webhooks as $webhook ) {
            $webhook = array(
                'plugin_service' => $this->get_service_name(),
                'id' => $webhook->ID,
                'form_id' => $form_id,
                'url' => $webhook->url
            );
            array_push($data, $webhook);
        }

        return rest_ensure_response( $data );
    }

    public function create_webhook( $request ) {
        $form_id = $request['form_id'];
        $url = esc_url_raw($request['url']);
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        $forms = UM()->query()->forms();
        
        if($this->check_form_exists($form_id)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $form_title = $forms[$form_id];

        $post_id = $this->create_webhook_post($form_title, array(
            'form_id' => $form_id,
            'url' => $url
        ));

        return rest_ensure_response( array(
            'plugin_service' => $this->get_service_name(),
            'id' => $post_id,
            'form_id' => $form_id,
            'url' => $url
        ) );
    }

    public function delete_webhook( $request ) {
        $form_id = $request['form_id'];
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        
        if($this->check_form_exists($form_id)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $webhook_id = $request['webhook_id'];
        if(!ctype_digit($webhook_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The Webhook ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $result = $this->delete_webhook_post($webhook_id);
        if(is_wp_error($result)){
            return $result;
        }
        return rest_ensure_response(array(
            'plugin_service' => $this->get_service_name(),
            'id' => $result->ID
        ));
        return rest_ensure_response($result);
    }
    

    public function get_form_webhook_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'webhook',
            'type'                 => 'webhook',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique id of the webhook.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'form_id' => array(
                    'description'  => esc_html__( 'Unique id of the form.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'url' => array(
                    'description'  => esc_html__( 'The webhook URL.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit')
                ),
            ),
        );

        return $schema;
    }

    public function process_form_submission($user_id, $submitted)
    {
        error_log('ultimate member form submission');
        error_log(print_r($submitted, true));
        
        $form_id = $submitted['form_id'];
        $data = $this->convert_field_data($form_id, $submitted);
        $args = array(
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts($args);
        $files = array();
    	foreach ( $webhooks as $webhook ) {
    		$url = $webhook->url;
	        zoho_flow_execute_webhook($url, $data, $files);
    	}
    }
    
    public function um_user_updated($id, $args, $userinfo){
        error_log('um user updated');
        error_log(print_r($args, true));
        
        $form_id = $args['form_id'];
        $data = $this->convert_field_data($form_id, $userinfo);
        
        $args = array(
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts($args);
        $files = array();
        foreach ( $webhooks as $webhook ) {
            $url = $webhook->url;
            zoho_flow_execute_webhook($url, $data, $files);
        }
    }
    
    private function convert_field_data($form_id, $submitted){
        $form_fields = UM()->query()->get_attr( 'custom_fields', $form_id);

        $data = array();
        foreach ($form_fields as $formfield ) {
            $type = $formfield['type'];
            $value='';
            if( $type == "password" or $type == "row" ){
                continue;
            }
            else {
                $key = $formfield['metakey'];
                if(isset($submitted[$key])){
                    switch($type){
                        case 'checkbox':
                            $options = $submitted[$key];
                            $data[$key] = $options;
                        case 'select' :
                            if($key==='role_select'){
                                $key='role';
                                $value = $submitted[$key];
                            }else{
                                $value = $submitted[$key];
                            }
                            break;
                        case 'radio':
                            if($key==='gender'){
                                $arr = $submitted[$key];
                                $value = $arr[0];
                            }else if($key==='role_radio'){
                                $key='role';
                                $value = $submitted[$key];
                            }
                            break;
                        case 'select':
                            $values = $submitted[$key];
                            $data[$key] = $values;
                            break;
                        default:
                            $value = $submitted[$key];
                            break;
                    }
                    if($type != 'select' or $type != 'checkbox'){
                        $data[$key] = $value;
                    }
                }
            }
        }
        return $data;
    }
    
    private function check_form_exists($form_id){
        $forms = UM()->query()->forms();
        if(!array_key_exists($form_id, $forms)){
            return true;
        }
        return false;
    }

    private function convert_field_name($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_]+/', ' ', $name);
        $name = trim($name);
        $name = str_replace(" ", "_", $name);
        $name = strtolower($name);

        return $name;
    }
}
