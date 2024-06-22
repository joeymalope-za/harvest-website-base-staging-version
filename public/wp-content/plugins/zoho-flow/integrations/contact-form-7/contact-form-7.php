<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Zoho_Flow_Contact_Form_7 extends Zoho_Flow_Service
{
    public function get_forms( $request ) {
        $args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
        $posts = get_posts( $args );

        $data = array();

        if ( empty( $posts ) ) {
            return rest_ensure_response( $data );
        }

        foreach ( $posts as $post ) {

            $post_data = array();

            $schema = $this->get_form_schema();

            if ( isset( $schema['properties']['id'] ) ) {
                $post_data['id'] = (int) $post->ID;
            }

            if ( isset( $schema['properties']['title'] ) ) {
                $post_data['title'] = $post->post_title;
            }

            $response = rest_ensure_response( $post_data );
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
                    'description'  => esc_html__( 'ID of the Contact Form 7 form.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'title' => array(
                    'description'  => esc_html__( 'The title of the Contact Form 7 form.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit'),
                ),
            ),
        );

        return $schema;
    }


    public function get_fields( $request ) {
        $form_id = $request['form_id'];
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $contact_form = WPCF7_ContactForm::get_instance( $form_id );

        if(!$contact_form){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $form_tags = $contact_form->scan_form_tags();

        $fields = array();

        if ( empty( $form_tags ) ) {
            return rest_ensure_response( $fields );
        }

        foreach ( $form_tags as $form_tag ) {
            $basetype = $form_tag->basetype;
            if($basetype != 'text' && $basetype != 'email' && $basetype != 'url' && $basetype != 'tel' && $basetype != 'textarea' && $basetype != 'number' && $basetype != 'date' && $basetype != 'select' && $basetype != 'checkbox' && $basetype != 'radio' && $basetype != 'acceptance'&& $basetype != 'file' && $basetype != 'hidden' && $basetype != 'dynamictext' && $basetype != 'dynamichidden'){
                continue;
            }

            $type = $form_tag->type;
            $name = $form_tag->name;
            $name = $this->convert_field_name($name);
            $is_required = ( $form_tag->is_required() || 'radio' == $form_tag->type );

            $data = array(
                'name' => $name,
                'type' => $basetype,
                'is_required' => $is_required
            );

            if ( wpcf7_form_tag_supports( $form_tag->type, 'selectable-values' ) ) {
                $options = array();
                foreach ( $form_tag->values as $value ) {
                    $option = array('key' => $value, 'value' => $value);
                    array_push($options, $option);
                }
                $data['options'] = $options;
            }
            array_push($fields, $data);
        }

        return rest_ensure_response( $fields );
    }

    public function get_field_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'field',
            'type'                 => 'field',
            'properties'           => array(
                'name' => array(
                    'description'  => esc_html__( 'Unique name of the field.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'label' => array(
                    'description'  => esc_html__( 'Label of the field.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit')
                ),
                'type' => array(
                    'description'  => esc_html__( 'Type of the field.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit')
                ),
                'options' => array(
                    'description'  => esc_html__( 'Options of a dropdown/multiselect/checkbox/radio field.', 'zoho-flow' ),
                    'type'         => 'array',
                    'context'      => array( 'view', 'edit')
                ),
                'is_required' => array(
                    'description'  => esc_html__( 'Whether the field is mandatory.', 'zoho-flow' ),
                    'type'         => 'boolean',
                    'context'      => array( 'view', 'edit')
                ),
            ),
        );

        return $schema;
    }



    public function get_webhooks( $request ) {
        $form_id = $request['form_id'];
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $contact_form = WPCF7_ContactForm::get_instance( $form_id );

        if(!$contact_form){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $args = array(
            'form_id' => $contact_form->id()
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
                'form_id' => $contact_form->id(),
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
            return new WP_Error( 'rest_bad_request', esc_html__( 'The form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $contact_form = WPCF7_ContactForm::get_instance( $form_id );

        if(!$contact_form){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $form_title = $contact_form->title();

        $post_id = $this->create_webhook_post($form_title, array(
            'form_id' => $contact_form->id(),
            'url' => $url
        ));

        return rest_ensure_response( array(
            'plugin_service' => $this->get_service_name(),
            'id' => $post_id,
            'form_id' => $contact_form->id(),
            'url' => $url
        ) );
    }

    public function delete_webhook( $request ) {
        $form_id = $request['form_id'];
        if(!ctype_digit($form_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The form ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $contact_form = WPCF7_ContactForm::get_instance( $form_id );

        if(!$contact_form){
            return new WP_Error( 'not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $webhook_id = $request['webhook_id'];
        if(!ctype_digit($webhook_id)){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The webhook ID is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
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

    public function get_file( $request ){
        $filename = sanitize_file_name($request['filename']);

        if(validate_file($filename) > 0){
            return new WP_Error( 'rest_bad_request', esc_html__( 'The requested file name is invalid.', 'zoho-flow' ), array( 'status' => 400 ) );
        }
        $dest_dir = $this->upload_dir();
        $file_path = $dest_dir . '/' . $filename;
        if(!file_exists($file_path)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The requested file could not be found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $this->download_file($file_path);

    }

	public function process_form_submission($contact_form){
	    $id = $contact_form->id();

        $args = array(
            'form_id' => $contact_form->id()
        );
        $webhooks = $this->get_webhook_posts($args);
        $data = array();

        if ( !empty( $webhooks ) ) {
            $submission = WPCF7_Submission::get_instance();
            $posted_data = $submission->get_posted_data();
            foreach ($posted_data as $name => $value) {
                $data[$this->convert_field_name($name)] = $value;
            }
        	foreach ( $webhooks as $webhook ) {
        		$url = $webhook->url;
		        zoho_flow_execute_webhook($url, $data, $files);
        	}
        }



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
