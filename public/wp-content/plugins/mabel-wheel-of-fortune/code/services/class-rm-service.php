<?php

namespace MABEL_WOF\Code\Services {

    use MABEL_WOF\Core\Common\Linq\Enumerable;
    use MABEL_WOF\Core\Common\Managers\Settings_Manager;

    class RM_Service
    {

        public static function add_to_list($list_id, $email, $fields = []) {

            $post_fields = [
                'email' => $email,
                'tags' => [],
                'doubleOptin' => false,
                'marketingAllowed' => true,
                'properties' => new \stdClass()
            ];

            $firstName = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'firstName';});
            $lastName = Enumerable::from($fields)->firstOrDefault(function($x){return $x->id === 'lastName';});
            if($firstName !== null)
                $post_fields['firstName'] = $firstName->value;
            if($lastName !== null)
                $post_fields['lastName'] = $lastName->value;

            foreach($fields as $f){
                if($f->id === 'firstName' || $f->id === 'lastName')
                    continue;
                $post_fields['properties']->{$f->id} = $f->value;
            }

	        $post_fields = apply_filters('wof_remarkety_values',$post_fields);

            $response = self::request('contacts', $post_fields,'post');

            if($response === null || $response->status !== 200)
                return "Could not add email to list.";

            return true;
        }

        public static function is_in_list($list_id, $email) {
            return false;
        }

        public static function get_email_lists() {
           return [ (object) [ 'id' => 'contacts', 'title' => 'Contacts' ] ];
        }

        public static function get_fields_from_list() {
            return [
                (object) [ 'id' => 'firstName', 'title' => 'First name', 'type' => 'text' ],
                (object) [ 'id' => 'lastName', 'title' => 'Last name', 'type' => 'text' ],
                (object) [ 'id' => 'country', 'title' => 'Country code', 'type' => 'text' ],
                (object) [ 'id' => 'state', 'title' => 'State', 'type' => 'text' ],
                (object) [ 'id' => 'city','title' => 'City', 'type' => 'text' ],
                (object) [ 'id' => 'address', 'title' => 'Address', 'type' => 'text' ],
                (object) [ 'id' => 'phone', 'title' => 'Phone', 'type' => 'text' ],
                (object) [ 'id' => 'company', 'title' => 'Company', 'type' => 'text' ],
            ];
        }

        private static function request($action, array $body = null, $method = 'post') {

            $url =  'https://app.remarkety.com/api/v1/stores/' .Settings_Manager::get_setting('rm_key').'/'.$action;

            $headers = [
                'Content-Type' => 'application/json'
            ];

            $options = [
                'timeout' => 15,
                'headers' => $headers
            ];

            if($body != null && $method === 'post')
                $options['body'] = json_encode($body);

            $response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

            if(is_wp_error($response))
                return null;

            return (object) [
                'status' => $response['response']['code'],
                'body' => json_decode(wp_remote_retrieve_body($response),true)
            ];
        }

    }
}
