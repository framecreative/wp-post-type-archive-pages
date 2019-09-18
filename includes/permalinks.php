<?php

class PTAP_Permalinks {

    private $transient_name;

    function __construct()
    {
        
        add_filter( 'register_post_type_args', [ $this, 'set_post_type_archive' ], 10, 2 );
        add_filter( 'register_taxonomy_args', [ $this, 'set_taxonomy_archive' ], 10, 3 );

        add_action( 'update_option_' . post_type_archive_pages()::CONFIG_KEY, array( $this, 'updated_option' ) );
        add_action( 'post_updated', array( $this, 'updated_post' ), 10, 3 );
        add_action( 'admin_init', array( $this, 'maybe_flush_rules' ) );

    }

    public function set_post_type_archive( $args, $name ) {

        $archiveRoute = post_type_archive_pages()->get_route($name);

        if ( !$archiveRoute )
            return $args;

        $args['has_archive'] = true;

        $args['rewrite'] = [
            'slug' =>           $archiveRoute,
            'with_front' =>     false
        ];

        return $args;

    }

    public function set_taxonomy_archive( $args, $taxonomy, $object_type ) {

        if ( isset($args['public']) && $args['public'] === false )
            return $args;

        if ( is_array( $object_type ) )
            $object_type = ( count($object_type) === 1 ) ? $object_type[0] : null;

        if ( !$object_type )
            return $args;

        $archiveRoute = post_type_archive_pages()->get_route( $object_type );

        if ( !$archiveRoute )
            return $args;

        $args['rewrite'] = [
            'slug' => $archiveRoute . '/' . $taxonomy,
            'with_front' => false
        ];

        return $args;

    }

    public function updated_option() {

        set_transient( $this->transient_name, 1 );

    }

    public function updated_post( $post_ID, $post_after, $post_before ) {

        if ( $post_after->post_type !== 'page' )
            return;

        if ( !post_type_archive_pages()->is_archive_page( $post_ID ) )
            return;
            
        if ( $post_after->post_name === $post_before->post_name && $post_after->post_status === $post_before->post_status )
            return;

        set_transient( $this->transient_name, 1 );

    }

    public function maybe_flush_rules() {

        if ( delete_transient($this->transient_name) ) {
            flush_rewrite_rules();
        }

    }

}