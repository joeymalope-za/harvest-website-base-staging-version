<?php

namespace MABEL_WOF\Code\Services {

    use MABEL_WOF\Core\Common\Linq\Enumerable;
    use MABEL_WOF\Core\Common\Managers\Settings_Manager;
    use WP_Error;

    class KV_Service {

        public static function add_to_list($list_id, $email, $fields = []) {

            if( self::api_version() === 'old' ) {
                $values = [
                    'email' => $email,
                    'confirm_optin' => 'false',
                ];

                if (!empty($fields)) {
                    $values['properties'] = [];
                    foreach ($fields as $f) {
                        $values['properties'][$f->id] = $f->value;
                    }
                    $values['properties'] = json_encode($values['properties']);
                }

                $values = apply_filters('wof_klaviyo_values', $values);

                $response = self::request('list/' . $list_id . '/members', $values, 'post');

                if ($response === null || $response->status !== 200)
                    return "Could not add email to list.";

                return true;

            }

            $values = [
                'data' => [
                    'type'                      => 'subscription',
                    'attributes'                => [
                        'profile'               => [
                            'data'              => [
                                'type'          => 'profile',
                                'attributes'    => [
                                    'email'     => $email,
                                ]
                            ]
                        ]
                    ],
                    'relationships'             => [
                        'list'                  => [ 'data' => [ 'type' => 'list', 'id' => $list_id ] ]
                    ]
                ]
            ];

            if ( ! empty( $fields ) ) {

                foreach ($fields as $f) {

                    $key = str_replace( '$','', $f->id );

                    if( ! empty( $f->value ) ) {
                        if (in_array($f->id, ['$city', '$region', '$country', '$zip'])) {
                            if (empty($values['data']['attributes']['profile']['data']['attributes']['location'])) {
                                $values['data']['attributes']['profile']['data']['attributes']['location'] = [];
                            }
                            $values['data']['attributes']['profile']['data']['attributes']['location'][$key] = $f->value;
                        } else {
                            $values['data']['attributes']['profile']['data']['attributes'][$key] = $f->value;
                        }
                    }

                }
            }

            $response = self::request('client/subscriptions?company_id=' . Settings_Manager::get_setting( 'kv_public_api' ), $values, 'post');

            if ($response === null || ( $response->body && ! empty( $response->body->errors ) ) ) {
                return $response->body->errors[0]->detail;
            }

            return true;

        }

        public static function get_fields_from_list() {
            return [
                ['id' => '$first_name', 'title' => 'First name', 'type' => 'text'],
                ['id' => '$last_name', 'title' => 'Last name', 'type' => 'text'],
                ['id' => '$phone_number', 'title' => 'Phone number', 'type' => 'text'],
                ['id' => '$title', 'title' => 'Title', 'type' => 'text'],
                ['id' => '$organization', 'title' => 'Organization', 'type' => 'text'],
                ['id' => '$city', 'title' => 'City', 'type' => 'text'],
                ['id' => '$region', 'title' => 'Region', 'type' => 'text'],
                ['id' => '$country', 'title' => 'Country', 'type' => 'text'],
                ['id' => '$zip', 'title' => 'Zip code', 'type' => 'text'],
            ];
        }

        public static function get_email_lists() {

            if( self::api_version() === 'old' ) {

                $response = self::request('lists', ['count' => 100], 'get');

                if ($response->status === 200) {
                    return Enumerable::from($response->body->data)->where(function ($x) {
                        return $x->list_type === 'list';
                    })->select(function ($x) {
                        return [
                            'id' => $x->id,
                            'title' => $x->name
                        ];
                    })->toArray();
                }

                return new WP_Error();

            }

            $response = self::request('api/lists', null, 'get');

            if($response->status === 200) {
                return Enumerable::from($response->body->data)->where(function($x){
                    return $x->type === 'list' && $x->attributes->name !== 'Preview List' && $x->attributes->name !== 'Recent Subscribers, Last 30 Days';
                })->select(function($x){
                    return [
                        'id' => $x->id,
                        'title' => $x->attributes->name
                    ];
                })->toArray();
            }

            return new WP_Error();

        }

        private static function request($action, array $body = null, $method = 'post') {

            $api_version = self::api_version();

            if( $api_version === 'old' ) {
                $base = 'https://a.klaviyo.com/api/v1/';
                $url = $base . $action . '?api_key=' . Settings_Manager::get_setting('kv_api');

                if ($method === 'get' && is_array($body) && sizeof($body) > 0)
                    $url .= '&' . Enumerable::from($body)->join(function ($v, $k) {
                            return urlencode($k) . '=' . urlencode($v);
                        }, '&');

                $headers = [
                    'Content-Type' => $method === 'get' ? 'application/json' : 'application/x-www-form-urlencoded'
                ];

                $options = [
                    'timeout' => 15,
                    'headers' => $headers,
                    'method' => strtoupper($method)
                ];

                if ($body != null && $method === 'post')
                    $options['body'] = $body;

                $response = $method === 'post' ? wp_remote_post($url, $options) : wp_remote_get($url, $options);

                if (is_wp_error($response)) return null;

                return (object)[
                    'status' => $response['response']['code'],
                    'body' => json_decode(wp_remote_retrieve_body($response))
                ];

            }

            $base = 'https://a.klaviyo.com/';
            $url =  $base . $action;

            if($method === 'get' && is_array($body) && sizeof($body) > 0)
                $url .= '&'.Enumerable::from($body)->join(function($v, $k){
                        return urlencode($k).'='.urlencode($v);
                    }, '&');

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Klaviyo-API-Key ' . Settings_Manager::get_setting('kv_api'),
                'revision'      => '2023-07-15'
            ];

            $options = [
                'timeout' => 15,
                'headers' => $headers,
                'method' => strtoupper($method)
            ];

            if($body != null && $method === 'post') {
                $options['body'] = json_encode( $body );
            }

            $response = $method === 'post' ? wp_remote_post( $url, $options) : wp_remote_get($url,$options);

            if(is_wp_error($response)) return null;

            return (object) [
                'status' => $response['response']['code'],
                'body' => json_decode(wp_remote_retrieve_body($response))
            ];

        }

        private static function api_version() {
            return empty( Settings_Manager::get_setting( 'kv_public_api' ) ) ? 'old' : 'new';
        }

    }
}