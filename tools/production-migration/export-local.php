<?php
/** Export only the approved local menu. No source database writes. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( "WP-CLI only.\n" ); }
define( 'EKM_EXPORT_LIBRARY', true );
require_once __DIR__ . '/import-production.php';

final class EKM_Local_Export {
    private $directory;
    private $media = array();
    private $terms = array();
    private $keys = array();

    private function media( $id ) {
        if ( ! $id ) { return null; }
        $post = get_post( $id ); $file = get_attached_file( $id );
        EKM_Menu_Package::check( $post && $post->post_type === 'attachment' && wp_attachment_is_image( $id ) && $file && is_file( $file ), 'Missing image attachment/file: ' . $id );
        $hash = hash_file( 'sha256', $file );
        $extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        EKM_Menu_Package::check( preg_match( '/^[a-z0-9]+$/D', $extension ), 'Unsupported image extension.' );
        if ( ! isset( $this->media[$hash] ) ) {
            $name = 'media/' . $hash . '.' . $extension;
            EKM_Menu_Package::check( copy( $file, $this->directory . '/' . $name ), 'Cannot copy referenced media.' );
            $this->media[$hash] = array( 'file' => $name, 'mime' => $post->post_mime_type, 'title' => $post->post_title, 'caption' => $post->post_excerpt, 'description' => $post->post_content, 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
        }
        return $hash;
    }

    private function term( $id, $taxonomy ) {
        $t = get_term( $id, $taxonomy );
        EKM_Menu_Package::check( $t && ! is_wp_error( $t ), 'Unresolved source term.' );
        $key = $taxonomy . ':' . $t->slug;
        if ( ! isset( $this->terms[$key] ) ) {
            $this->terms[$key] = array( 'taxonomy' => $taxonomy, 'slug' => $t->slug, 'name' => $t->name, 'description' => $t->description, 'parent' => $t->parent ? $this->term( $t->parent, $taxonomy ) : null, 'meta' => array() );
            foreach ( array( 'order', 'display_type', '_ek_order' ) as $meta ) {
                if ( metadata_exists( 'term', $id, $meta ) ) { $this->terms[$key]['meta'][$meta] = get_term_meta( $id, $meta, true ); }
            }
        }
        return $key;
    }

    private function terms( $id, $taxonomy ) {
        $terms = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
        EKM_Menu_Package::check( ! is_wp_error( $terms ), 'Cannot read source taxonomy.' );
        return array_map( function ( $term ) use ( $taxonomy ) { return $this->term( $term, $taxonomy ); }, $terms );
    }

    private function references( $ids ) {
        return array_map( function ( $id ) {
            EKM_Menu_Package::check( isset( $this->keys[$id] ), 'Product reference is outside the approved published catalog: ' . $id );
            return $this->keys[$id];
        }, $ids );
    }

    private function record( $p, $variation = false ) {
        EKM_Menu_Package::check( ! $p->get_downloads() && ! $p->get_shipping_class_id( 'edit' ), 'This one-time package does not support downloads/shipping classes.' );
        foreach ( array_keys( get_post_meta( $p->get_id() ) ) as $key ) {
            if ( preg_match( '/wapf|apf/i', $key ) ) { EKM_Menu_Package::check( $key === EKM_Menu_Package::APF_META && ! $variation, 'Unrecognized APF metadata: ' . $key ); }
        }
        $props = array();
        foreach ( EKM_Menu_Package::props( $variation ) as $prop ) {
            $value = $p->{'get_' . $prop}( 'edit' );
            $props[$prop] = $value instanceof DateTimeInterface ? $value->getTimestamp() : $value;
        }
        $record = array( 'props' => $props, 'image' => $this->media( $p->get_image_id( 'edit' ) ) );
        if ( $variation ) { $record['attributes'] = $p->get_attributes( 'edit' ); return $record; }
        EKM_Menu_Package::check( ! method_exists( $p, 'get_brand_ids' ) || ! $p->get_brand_ids(), 'Unexpected brand relationships need explicit support.' );
        $record['key'] = $this->keys[$p->get_id()];
        $record['type'] = $p->get_type();
        $record['gallery'] = array_map( array( $this, 'media' ), $p->get_gallery_image_ids( 'edit' ) );
        $record['categories'] = $this->terms( $p->get_id(), 'product_cat' );
        $record['tags'] = $this->terms( $p->get_id(), 'product_tag' );
        $record['upsells'] = $this->references( $p->get_upsell_ids( 'edit' ) );
        $record['cross_sells'] = $this->references( $p->get_cross_sell_ids( 'edit' ) );
        $record['attributes'] = array();
        foreach ( $p->get_attributes( 'edit' ) as $a ) {
            EKM_Menu_Package::check( ! $a->is_taxonomy(), 'Global attributes were not present in the approved catalog; refusing unmapped term IDs.' );
            $record['attributes'][] = array( 'name' => $a->get_name(), 'options' => $a->get_options(), 'position' => $a->get_position(), 'visible' => $a->get_visible(), 'variation' => $a->get_variation() );
        }
        $group = metadata_exists( 'post', $p->get_id(), EKM_Menu_Package::APF_META ) ? get_post_meta( $p->get_id(), EKM_Menu_Package::APF_META, true ) : null;
        $record['apf'] = EKM_Menu_Package::apf( $group, $this->keys, $record['key'], true );
        $record['variations'] = array();
        foreach ( EKM_Menu_Package::posts( 'product_variation', array( 'post_parent' => $p->get_id(), 'post_status' => 'publish' ) ) as $v ) {
            $record['variations'][] = $this->record( wc_get_product( $v->ID ), true );
        }
        return $record;
    }

    private function post( $post ) {
        return array_intersect_key( (array) $post, array_flip( array( 'post_title', 'post_name', 'post_content', 'post_excerpt', 'post_status', 'menu_order', 'comment_status', 'ping_status' ) ) );
    }

    public function run( $args, $flags ) {
        try {
            EKM_Menu_Package::check( wp_get_environment_type() === 'local', 'Exporter is restricted to the local environment.' );
            EKM_Menu_Package::check( function_exists( 'wc_get_products' ) && function_exists( 'ek_deals_data' ) && function_exists( 'wapf' ), 'WooCommerce, APF and Deals must be active.' );
            EKM_Menu_Package::check( ! EKM_Menu_Package::posts( 'wapf_product', array( 'post_status' => 'publish' ) ), 'Global APF groups are outside the verified per-product export.' );
            $this->directory = $flags['output'] ?? dirname( __DIR__, 2 ) . '/production-migration-output';
            EKM_Menu_Package::check( ! is_dir( $this->directory ) || count( scandir( $this->directory ) ) === 2, 'Output must be absent or empty; choose a fresh directory.' );
            EKM_Menu_Package::check( wp_mkdir_p( $this->directory . '/media' ), 'Cannot create output directory.' );
            $products = array(); $names = array(); $skus = array();
            foreach ( EKM_Menu_Package::posts( 'product', array( 'post_status' => 'publish' ) ) as $post ) {
                $p = wc_get_product( $post->ID );
                EKM_Menu_Package::check( $p && $p->is_type( array( 'simple', 'variable' ) ), 'Unsupported parent product.' );
                $products[] = $p;
                $name = EKM_Menu_Package::normalized( $p->get_name() ); $sku = EKM_Menu_Package::normalized( $p->get_sku() );
                $names[$name] = ( $names[$name] ?? 0 ) + 1;
                if ( $sku ) { $skus[$sku] = ( $skus[$sku] ?? 0 ) + 1; }
            }
            foreach ( $products as $p ) {
                $name = EKM_Menu_Package::normalized( $p->get_name() ); $sku = EKM_Menu_Package::normalized( $p->get_sku() );
                EKM_Menu_Package::check( $sku ? $skus[$sku] === 1 : $name && $names[$name] === 1, 'Ambiguous source product key: ' . $p->get_name() );
                $this->keys[$p->get_id()] = EKM_Menu_Package::key( $p->get_sku(), $p->get_name() );
            }
            $records = array_map( array( $this, 'record' ), $products );
            $deals = array();
            foreach ( EKM_Menu_Package::posts( 'ek_deal', array( 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ) ) ) as $post ) {
                $meta = get_post_meta( $post->ID, '_ek_deal', true );
                EKM_Menu_Package::check( is_array( $meta ), 'Missing Deal configuration.' );
                $product_id = absint( $meta['product_id'] ?? 0 );
                EKM_Menu_Package::check( ! $product_id || isset( $this->keys[$product_id] ), 'Broken local Deal product link: ' . $post->post_title );
                unset( $meta['product_id'] );
                $deals[] = array( 'post' => $this->post( $post ), 'data' => $meta, 'product_key' => $product_id ? $this->keys[$product_id] : null, 'image' => $this->media( get_post_thumbnail_id( $post->ID ) ), 'categories' => $this->terms( $post->ID, 'ek_deal_category' ) );
            }
            $page = get_page_by_path( 'deals' );
            EKM_Menu_Package::check( $page && $page->post_status === 'publish', 'Missing published local /deals/ page.' );
            $counts = array( 'parents' => count( $records ), 'variations' => 0, 'sold_individually' => 0, 'featured' => 0, 'deals' => count( $deals ) );
            foreach ( $records as $p ) {
                $counts['variations'] += count( $p['variations'] );
                $counts['featured'] += (int) $p['props']['featured'];
                $counts['sold_individually'] += (int) $p['props']['sold_individually'];
                foreach ( $p['variations'] as $v ) { $counts['sold_individually'] += (int) $v['props']['sold_individually']; }
            }
            foreach ( array( 306 => array( 'simple', '6.75', 0 ), 363 => array( 'variable', '17.00', 1 ), 370 => array( 'simple', '9.25', 0 ) ) as $id => $expected ) {
                $p = wc_get_product( $id );
                EKM_Menu_Package::check( $p && $p->get_type() === $expected[0] && (float) $p->get_price() === (float) $expected[1], 'Approved sentinel configuration changed: ' . $id );
                EKM_Menu_Package::check( count( EKM_Menu_Package::posts( 'product_variation', array( 'post_parent' => $id, 'post_status' => 'publish' ) ) ) === $expected[2], 'Unexpected sentinel variations: ' . $id );
            }
            ksort( $this->terms ); ksort( $this->media );
            $data = array( 'version' => EKM_Menu_Package::VERSION, 'exported_at' => gmdate( 'c' ), 'apf_meta_key' => EKM_Menu_Package::APF_META, 'apf_version' => wapf_get_setting( 'version' ), 'counts' => $counts, 'products' => $records, 'terms' => $this->terms, 'deals' => $deals, 'deals_page' => $this->post( $page ), 'media' => $this->media );
            EKM_Menu_Package::check( file_put_contents( $this->directory . '/catalog.json', json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ) . "\n" ) !== false, 'Could not write catalog.' );
            EKM_Menu_Package::load( $this->directory );
            EKM_Menu_Package::check( copy( __DIR__ . '/import-production.php', $this->directory . '/import-production.php' ), 'Could not bundle importer.' );
            WP_CLI::log( json_encode( $counts ) );
            WP_CLI::log( 'Banana Split: simple / 6.75 / 0 published variations. Chicken Wing: variable / 17.00 / 1 variation. Deluxe Burger: simple / 9.25.' );
            WP_CLI::success( 'Export validated; no ambiguous product keys; all ' . count( $this->media ) . ' referenced media files present. Output: ' . $this->directory );
        } catch ( Throwable $e ) { WP_CLI::error( $e->getMessage() ); }
    }
}
WP_CLI::add_command( 'ek-menu-export', array( new EKM_Local_Export(), 'run' ) );
