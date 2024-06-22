<?php

namespace MABEL_WOF\Code\Services {

    use MABEL_WOF\Core\Common\Linq\Enumerable;
    use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

    class Drip_Service
    {

        public static function add_to_list($list_id, $email, $fields = []) {

            $data = [
                'subscribers' => [
                    [
                        'email' => $email
                    ]
                ]
            ];

            if(!empty($fields))
               $data['subscribers'][0]['custom_fields'] = [];

            foreach ($fields as $field) {
                $data['subscribers'][0]['custom_fields'][$field->id] = $field->value;
            }

            $request_url = 'subscribers';

            if(strpos($list_id, 'tag:') !== false)
                $data['subscribers'][0]['tags'] = [ str_replace('tag:','',$list_id) ];

            if(strpos($list_id, 'campaign:') !== false) {
                $request_url = str_replace('campaign:','',$list_id) . '/subscribers';
                $data['subscribers'][0]['tags'] = [ str_replace('campaign:', '', $list_id) ];
            }

            $data = apply_filters('wof_drip_values',$data);

            $response = self::request($request_url, $data);

            if($response === null || $response->status !== 200)
                return "Could not add email to list.";

            return true;
        }

        public static function get_fields_from_list() {

            $response = self::request('custom_field_identifiers',null,'get');

            if($response === null || $response->status !== 200)
                return new WP_Error();

            return Enumerable::from($response->body->custom_field_identifiers)->select(function($x){
                return [
                    'id' => $x,
                    'title' => $x,
                    'type' => 'text'
                ];
            })->toArray();

        }

        public static function get_email_lists() {

            $tag_responses = self::request('tags',null,'get');
            $campaign_response = self::request('campaigns',null,'get');

            if($campaign_response === null || $tag_responses === null)
                return new WP_Error();

            $lists = [];

            if($campaign_response->status === 200) {
                $lists = array_merge( $lists, Enumerable::from( $campaign_response->body->campaigns )->where(function($x){
                    return $x->status === 'active';
                })->select( function ( $x ) {
                    return [
                        'id'    => 'campaign:' . $x->id,
                        'title' => 'Campaign: ' . $x->name
                    ];
                } )->toArray() );
            }


            if($tag_responses->status === 200) {
                $lists = array_merge($lists, Enumerable::from($tag_responses->body->tags)->select(function($x){
                    return [
                        'id' => 'tag:'.$x,
                        'title' => 'Tag: '.$x
                    ];
                })->toArray());
            }

            return $lists;
        }

        private static function request($action, array $body = null, $method = 'post') {

            $token = base64_encode(Settings_Manager::get_setting('drip_api'));
            $account = Settings_Manager::get_setting('drip_account');

            $url = 'https://api.getdrip.com/v2/' . $account . '/' .$action;

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $token
            ];

            $options = [
                'timeout' => 15,
                'headers' => $headers,
                'method' => strtoupper($method)
            ];

            if($body != null)
                $options['body'] = json_encode($body);

            $response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

            if(is_wp_error($response)) return null;

            return (object) [
                'status' => $response['response']['code'],
                'body' => json_decode(wp_remote_retrieve_body($response))
            ];
        }
    }
}