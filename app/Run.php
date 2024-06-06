<?php

namespace DisembarkInterface;

class Run {

    protected $plugin_path = "";
    protected $plugin_url = "";

    public function __construct() {
        if ( defined( 'DISEMBARK_DEV_MODE' ) ) {
            add_filter('https_ssl_verify', '__return_false');
            add_filter('https_local_ssl_verify', '__return_false');
            add_filter('http_request_host_is_external', '__return_true');
        }
        $this->plugin_path = dirname( plugin_dir_path( __FILE__ ) );
        $this->plugin_url = dirname( plugin_dir_url( __FILE__ ) );
        add_shortcode( 'disembark_backup', [ $this, 'disembark_backup_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'disembark_connect_script' ] );
        add_action( 'rest_api_init', [ $this, 'disembark_register_rest_endpoints' ] );
    }

    function disembark_register_rest_endpoints() {
        register_rest_route(
            'disembark/v1', '/remote/connect', [
                'methods'       => 'POST',
                'callback'      => [ $this, 'connect' ],
                'show_in_index' => false
            ]
        );
        register_rest_route(
            'disembark/v1', '/remote/export-database', [
                'methods'       => 'POST',
                'callback'      => [ $this, 'export_database' ],
                'show_in_index' => false
            ]
        );
        register_rest_route(
            'disembark/v1', '/remote/zip-files', [
                'methods'       => 'POST',
                'callback'      => [ $this, 'zip_files' ],
                'show_in_index' => false
            ]
        );

        register_rest_route(
            'disembark/v1', '/remote/zip-database', [
                'methods'       => 'POST',
                'callback'      => [ $this, 'zip_database' ],
                'show_in_index' => false
            ]
        );
        
    }

    function connect ( $request ) {
        $site_url = $request["site_url"];
        $token    = $request["token"];
        if ( empty( $site_url ) ||  empty( $token ) ) {
            return;
        }
        $args = [
            'timeout'   => 600,
            'sslverify' => false
        ];
        $backup_token = substr( bin2hex( random_bytes( 20 ) ), 0, -24);
        $response = wp_remote_get( "$site_url/wp-json/disembark/v1/database?token=$token&backup_token=$backup_token", $args );
        $database = json_decode( $response["body"] );
        $response = wp_remote_get( "$site_url/wp-json/disembark/v1/files?token=$token&backup_token=$backup_token", $args );
        $files    = json_decode( $response["body"] );
        
        return [
            "token"    => $backup_token,
            "database" => $database, 
            "files"    => $files
        ];
    }

    function export_database ( $request ) {
        $site_url = $request["site_url"];
        $token    = $request["token"];
        $table    = $request["table"];
        if ( empty( $site_url ) ||  empty( $token ) || empty( $table ) ) {
            return;
        }
        $data = [
			'timeout' => 600,
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8'
            ],
			'body'        => json_encode( [
                "site_url"     => $site_url,
                "token"        => $token, 
                "backup_token" => $request["backup_token"]
            ] ), 
			'method'      => 'POST', 
			'data_format' => 'body' 
		];
        $response = wp_remote_post( "$site_url/wp-json/disembark/v1/export/database/$table", $data );
        return json_decode( $response["body"] );
    }

    function zip_files ( $request ) {
        $site_url = $request["site_url"];
        $token    = $request["token"];
        $file     = $request["file"];
        if ( empty( $site_url ) ||  empty( $token ) || empty( $file ) ) {
            return;
        }
        $data = [
			'timeout' => 600,
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8'
            ],
			'body'        => json_encode( [
                "site_url"     => $site_url,
                "token"        => $token, 
                "backup_token" => $request["backup_token"],
                "file"         => $file
            ] ), 
			'method'      => 'POST', 
			'data_format' => 'body' 
		];
        $response = wp_remote_post( "$site_url/wp-json/disembark/v1/zip-files", $data );
        return json_decode( $response["body"] );
    }

    function zip_database ( $request ) {
        $site_url = $request["site_url"];
        $token    = $request["token"];
        if ( empty( $site_url ) ||  empty( $token ) ) {
            return;
        }
        $data = [
			'timeout' => 600,
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8'
            ],
			'body'        => json_encode( [
                "site_url"     => $site_url,
                "token"        => $token, 
                "backup_token" => $request["backup_token"]
            ] ), 
			'method'      => 'POST', 
			'data_format' => 'body' 
		];
        $response = wp_remote_post( "$site_url/wp-json/disembark/v1/zip-database", $data );
        return json_decode( $response["body"] );
    }

    function disembark_connect_script() {
        wp_enqueue_style( 'vuejs-font', "https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" );
        wp_enqueue_style( 'vuejs-icons', "https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" );
        wp_enqueue_style( 'vuetify', "https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" );
        wp_enqueue_style( 'disembark', "{$this->plugin_url}/css/style.css" );
    }

    function disembark_backup_shortcode() {
        require_once $this->plugin_path .'/template.php';
    }

}