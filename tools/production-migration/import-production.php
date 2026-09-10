<?php
/** One-time menu importer. Load with wp --require=/path/import-production.php ek-menu-import. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( "WP-CLI only.\n" ); }

final class EKM_Menu_Package {
    const VERSION = 1;
    const APF_META = '_wapf_fieldgroup';

    public static function check( $ok, $message ) {
        if ( ! $ok ) { throw new RuntimeException( $message ); }
    }

    public static function normalized( $name ) {
        $name = html_entity_decode( wp_strip_all_tags( $name ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $name = preg_replace( '/[\s\x{00a0}]+/u', ' ', trim( $name ) );
        return function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
    }

    public static function key( $sku, $name ) {
        return trim( $sku ) !== '' ? 'sku:' . self::normalized( $sku ) : 'name:' . self::normalized( $name );
    }

    public static function props( $variation = false ) {
        $common = array( 'name', 'slug', 'status', 'description', 'sku', 'regular_price', 'sale_price', 'price', 'date_on_sale_from', 'date_on_sale_to', 'manage_stock', 'stock_quantity', 'stock_status', 'backorders', 'low_stock_amount', 'sold_individually', 'virtual', 'downloadable', 'weight', 'length', 'width', 'height', 'tax_status', 'tax_class', 'menu_order' );
        return $variation ? $common : array_merge( $common, array( 'short_description', 'featured', 'catalog_visibility', 'reviews_allowed', 'purchase_note', 'default_attributes' ) );
    }

    public static function posts( $type, $extra = array() ) {
        return get_posts( array_merge( array( 'post_type' => $type, 'post_status' => array_keys( get_post_stati() ), 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true ), $extra ) );
    }

    public static function combination( $attributes ) {
        ksort( $attributes, SORT_STRING );
        return json_encode( $attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT );
    }

    public static function apf( $group, $map, $owner, $export = false ) {
        if ( $group === null ) { return null; }
        self::check( is_array( $group ) && isset( $group['fields'], $group['rule_groups'] ), 'Unsupported APF field group.' );
        $group['id'] = $export ? 'product:' . $owner : 'p_' . $owner;
        foreach ( $group['rule_groups'] as &$rule_group ) {
            foreach ( $rule_group['rules'] as &$rule ) {
                self::check( $rule['subject'] === 'product' && $rule['condition'] === 'product', 'Unsupported APF product rule; refusing to copy unmapped IDs.' );
                foreach ( $rule['value'] as &$value ) {
                    $ref = $export ? ( $value['id'] ?? '' ) : ( $value['product_key'] ?? '' );
                    self::check( isset( $map[$ref] ), 'Unresolved APF product reference: ' . $ref );
                    unset( $value['id'], $value['product_key'] );
                    $value[$export ? 'product_key' : 'id'] = (string) $map[$ref];
                }
                unset( $value );
            }
            unset( $rule );
        }
        unset( $rule_group );
        return $group;
    }

    public static function load( $directory ) {
        $file = $directory . '/catalog.json';
        self::check( is_file( $file ), 'Missing catalog.json.' );
        $data = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
        self::check( ( $data['version'] ?? 0 ) === self::VERSION, 'Unsupported package version.' );
        self::check( ( $data['apf_meta_key'] ?? '' ) === self::APF_META, 'Unsupported APF metadata key.' );
        $keys = array(); $skus = array(); $slugs = array(); $counts = array( 'parents' => 0, 'variations' => 0, 'sold_individually' => 0, 'featured' => 0, 'deals' => count( $data['deals'] ) );
        foreach ( $data['products'] as $p ) {
            self::check( ! isset( $keys[$p['key']] ), 'Duplicate product key: ' . $p['key'] );
            self::check( $p['key'] === self::key( $p['props']['sku'], $p['props']['name'] ), 'Invalid stable product key.' );
            self::check( in_array( $p['type'], array( 'simple', 'variable' ), true ), 'Unsupported product type.' );
            self::check( $p['props']['status'] === 'publish' && ! isset( $slugs[$p['props']['slug']] ), 'Non-published parent or duplicate product slug.' );
            $slugs[$p['props']['slug']] = true;
            $keys[$p['key']] = true;
            ++$counts['parents'];
            $counts['featured'] += (int) $p['props']['featured'];
            $counts['sold_individually'] += (int) $p['props']['sold_individually'];
            self::check( $p['image'] && isset( $data['media'][$p['image']] ), 'Parent missing featured image: ' . $p['key'] );
            $combinations = array();
            foreach ( $p['variations'] as $v ) {
                $combo = self::combination( $v['attributes'] );
                self::check( ! isset( $combinations[$combo] ), 'Duplicate variation attributes: ' . $p['key'] );
                $combinations[$combo] = true;
                ++$counts['variations'];
                $counts['sold_individually'] += (int) $v['props']['sold_individually'];
            }
            self::check( $p['type'] === 'variable' || ! $p['variations'], 'Simple product has variations.' );
            foreach ( array_merge( array( $p ), $p['variations'] ) as $record ) {
                self::check( ! array_diff( array_keys( $record['props'] ), self::props( isset( $record['attributes'] ) && ! isset( $record['type'] ) ) ), 'Unexpected product properties.' );
                foreach ( array_merge( array( $record['image'] ), $record['gallery'] ?? array() ) as $image ) {
                    self::check( ! $image || isset( $data['media'][$image] ), 'Missing image reference.' );
                }
                $sku = self::normalized( $record['props']['sku'] );
                self::check( ! $sku || ! isset( $skus[$sku] ), 'Duplicate source SKU: ' . $sku );
                if ( $sku ) { $skus[$sku] = true; }
            }
        }
        foreach ( $data['products'] as $p ) {
            self::apf( $p['apf'], $keys, 0 );
            foreach ( array_merge( $p['upsells'], $p['cross_sells'] ) as $key ) { self::check( isset( $keys[$key] ), 'Unresolved related product.' ); }
        }
        $deal_slugs = array();
        foreach ( $data['deals'] as $deal ) {
            self::check( ! isset( $deal_slugs[$deal['post']['post_name']] ), 'Duplicate source Deal slug.' );
            $deal_slugs[$deal['post']['post_name']] = true;
            self::check( ! isset( $deal['data']['product_id'] ), 'Local numeric Deal product ID is forbidden.' );
            self::check( ! $deal['product_key'] || isset( $keys[$deal['product_key']] ), 'Unresolved Deal product.' );
            self::check( ! $deal['image'] || isset( $data['media'][$deal['image']] ), 'Missing Deal media.' );
        }
        foreach ( $data['media'] as $hash => $media ) {
            self::check( preg_match( '/^[a-f0-9]{64}$/D', $hash ) && preg_match( '#^media/[a-f0-9]{64}\.[a-z0-9]+$#D', $media['file'] ), 'Invalid media path.' );
            $path = $directory . '/' . $media['file'];
            self::check( is_file( $path ) && hash_file( 'sha256', $path ) === $hash, 'Missing or corrupt media: ' . $media['file'] );
        }
        self::check( $counts === $data['counts'], 'Catalog counts do not match manifest.' );
        self::check( $counts === array( 'parents' => 117, 'variations' => 73, 'sold_individually' => 0, 'featured' => 33, 'deals' => 13 ), 'Approved catalog counts changed.' );
        return $data;
    }

    public static function plan( $data ) {
        $plan = array( 'products' => array(), 'retire' => array(), 'variations' => array(), 'deals' => array(), 'page' => 0, 'errors' => array() );
        $by_sku = array(); $by_name = array(); $by_slug = array(); $sku_owners = array(); $all = self::posts( array( 'product', 'product_variation' ) );
        foreach ( $all as $post ) {
            $sku = self::normalized( (string) get_post_meta( $post->ID, '_sku', true ) );
            if ( $sku ) { $sku_owners[$sku][] = $post->ID; }
            if ( $post->post_type !== 'product' ) { continue; }
            if ( $sku ) { $by_sku[$sku][] = $post->ID; }
            $by_slug[$post->post_name][] = $post->ID;
            $by_name[self::normalized( $post->post_title )][] = $post->ID;
        }
        $used = array();
        foreach ( $data['products'] as $p ) {
            $sku = self::normalized( $p['props']['sku'] );
            $matches = $sku ? ( $by_sku[$sku] ?? array() ) : array();
            if ( ! $matches ) { $matches = $by_name[self::normalized( $p['props']['name'] )] ?? array(); }
            if ( count( $matches ) > 1 || ( $matches && isset( $used[$matches[0]] ) ) ) {
                $plan['errors'][] = 'Ambiguous product ' . $p['key'] . ': ' . implode( ',', $matches );
                continue;
            }
            $id = $matches ? $matches[0] : 0;
            $plan['products'][$p['key']] = $id;
            if ( array_diff( $by_slug[$p['props']['slug']] ?? array(), array( $id ) ) ) { $plan['errors'][] = 'Product slug collision: ' . $p['props']['slug']; }
            if ( $id ) { $used[$id] = true; }
            $current = $id ? self::posts( 'product_variation', array( 'post_parent' => $id ) ) : array();
            $combinations = array();
            foreach ( $current as $post ) {
                $variation = wc_get_product( $post->ID );
                if ( ! $variation ) { $plan['errors'][] = 'Unreadable variation ' . $post->ID; continue; }
                $combinations[self::combination( $variation->get_attributes( 'edit' ) )][] = $post->ID;
            }
            $vp = array( 'keep' => array(), 'retire' => array() );
            foreach ( $p['variations'] as $index => $v ) {
                $found = $combinations[self::combination( $v['attributes'] )] ?? array();
                if ( count( $found ) > 1 ) { $plan['errors'][] = 'Ambiguous variation ' . $p['key'] . ': ' . implode( ',', $found ); }
                $vp['keep'][$index] = count( $found ) === 1 ? $found[0] : 0;
                $vs = self::normalized( $v['props']['sku'] );
                if ( $vs && array_diff( $sku_owners[$vs] ?? array(), array( $vp['keep'][$index] ) ) ) { $plan['errors'][] = 'Variation SKU collision: ' . $vs; }
            }
            foreach ( $current as $post ) { if ( ! in_array( $post->ID, $vp['keep'], true ) && in_array( $post->post_status, array( 'publish', 'private', 'future' ), true ) ) { $vp['retire'][] = $post->ID; } }
            $plan['variations'][$p['key']] = $vp;
            if ( $sku && array_diff( $sku_owners[$sku] ?? array(), array( $id ) ) ) { $plan['errors'][] = 'Product SKU collision: ' . $sku; }
        }
        foreach ( $all as $post ) {
            if ( $post->post_type === 'product' && $post->post_status === 'publish' && ! isset( $used[$post->ID] ) ) { $plan['retire'][$post->ID] = $post->post_title; }
        }
        $deals = self::posts( 'ek_deal' );
        foreach ( $data['deals'] as $deal ) {
            $matches = array_values( array_filter( $deals, static function ( $post ) use ( $deal ) { return $post->post_name === $deal['post']['post_name']; } ) );
            if ( count( $matches ) > 1 ) { $plan['errors'][] = 'Ambiguous Deal slug: ' . $deal['post']['post_name']; }
            $plan['deals'][$deal['post']['post_name']] = count( $matches ) === 1 ? $matches[0]->ID : 0;
        }
        $pages = self::posts( 'page', array( 'name' => 'deals', 'post_parent' => 0 ) );
        if ( count( $pages ) > 1 ) { $plan['errors'][] = 'Multiple root /deals/ pages.'; }
        $plan['page'] = count( $pages ) === 1 ? $pages[0]->ID : 0;
        foreach ( $data['terms'] as $term ) {
            if ( $term['taxonomy'] !== 'ek_deal_category' && ! taxonomy_exists( $term['taxonomy'] ) ) { $plan['errors'][] = 'Missing taxonomy: ' . $term['taxonomy']; }
        }
        if ( self::posts( 'wapf_product', array( 'post_status' => 'publish' ) ) ) { $plan['errors'][] = 'Published global APF groups exist; this package replaces per-product APF only.'; }
        if ( ! function_exists( 'wapf' ) ) { $plan['errors'][] = 'The compatible Advanced Product Fields plugin must already be active.'; }
        foreach ( $data['media'] as $hash => $m ) {
            $existing = self::posts( 'attachment', array( 'meta_key' => '_ekm_media_sha256', 'meta_value' => $hash ) );
            if ( count( $existing ) > 1 ) { $plan['errors'][] = 'Ambiguous imported media: ' . $hash; }
            foreach ( $existing as $attachment ) {
                $file = get_attached_file( $attachment->ID );
                if ( ! $file || ! is_file( $file ) || hash_file( 'sha256', $file ) !== $hash ) { $plan['errors'][] = 'Existing imported media was changed or removed: ' . $attachment->ID; }
            }
        }
        return $plan;
    }

    public static function report( $data, $plan ) {
        foreach ( $data['products'] as $p ) {
            if ( ! array_key_exists( $p['key'], $plan['products'] ) ) { continue; }
            $id = $plan['products'][$p['key']]; $v = $plan['variations'][$p['key']];
            WP_CLI::log( ( $id ? 'MATCH #' . $id : 'NEW' ) . ' <- ' . $p['key'] . ' (' . $p['props']['name'] . ', ' . $p['type'] . ')' );
            WP_CLI::log( '  Variations: preserve/update ' . count( array_filter( $v['keep'] ) ) . ', create ' . count( array_filter( $v['keep'], static function ( $n ) { return ! $n; } ) ) . ', retire [' . implode( ',', $v['retire'] ) . ']' );
        }
        foreach ( $plan['retire'] as $id => $name ) { WP_CLI::log( "RETIRE #$id $name (draft + hidden; retained for history)" ); }
        foreach ( $data['deals'] as $deal ) {
            $key = $deal['product_key'];
            WP_CLI::log( 'DEAL ' . $deal['post']['post_name'] . ' -> ' . ( $key ? $key . ' -> ' . ( $plan['products'][$key] ?? 0 ?: 'NEW ID on apply' ) : 'menu destination' ) );
        }
        WP_CLI::log( '/deals/ page: ' . ( $plan['page'] ? 'update #' . $plan['page'] : 'create' ) );
        WP_CLI::log( 'Ambiguous matches / blockers: ' . count( $plan['errors'] ) );
        foreach ( $plan['errors'] as $error ) { WP_CLI::warning( $error ); }
    }

    public static function term( $key, $data, &$mapped ) {
        if ( isset( $mapped[$key] ) ) { return $mapped[$key]; }
        $t = $data['terms'][$key];
        $parent = $t['parent'] ? self::term( $t['parent'], $data, $mapped ) : 0;
        $existing = get_term_by( 'slug', $t['slug'], $t['taxonomy'] );
        $props = array( 'name' => $t['name'], 'slug' => $t['slug'], 'description' => $t['description'], 'parent' => $parent );
        $result = $existing ? wp_update_term( $existing->term_id, $t['taxonomy'], $props ) : wp_insert_term( $t['name'], $t['taxonomy'], $props );
        self::check( ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : '' );
        $mapped[$key] = (int) $result['term_id'];
        foreach ( $t['meta'] as $k => $value ) { update_term_meta( $mapped[$key], $k, $value ); }
        return $mapped[$key];
    }

    public static function media( $data, $directory ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $mapped = array();
        $uploads = wp_upload_dir();
        self::check( ! $uploads['error'], (string) $uploads['error'] );
        foreach ( $data['media'] as $hash => $m ) {
            $existing = self::posts( 'attachment', array( 'meta_key' => '_ekm_media_sha256', 'meta_value' => $hash ) );
            self::check( count( $existing ) <= 1, 'Ambiguous imported media: ' . $hash );
            if ( $existing ) {
                $id = $existing[0]->ID;
                $file = get_attached_file( $id );
                self::check( $file && is_file( $file ) && hash_file( 'sha256', $file ) === $hash, 'Existing imported media was changed or removed: ' . $id );
            } else {
                $filename = wp_unique_filename( $uploads['path'], basename( $m['file'] ) );
                $file = $uploads['path'] . '/' . $filename;
                self::check( copy( $directory . '/' . $m['file'], $file ), 'Media copy failed.' );
                $id = wp_insert_attachment( wp_slash( array( 'post_title' => $m['title'], 'post_excerpt' => $m['caption'], 'post_content' => $m['description'], 'post_status' => 'inherit', 'post_mime_type' => $m['mime'] ) ), $file, 0, true );
                self::check( ! is_wp_error( $id ), is_wp_error( $id ) ? $id->get_error_message() : '' );
                update_post_meta( $id, '_ekm_media_sha256', $hash );
                // Keep the bundled source as the attachment file; thumbnails may be generated normally.
                add_filter( 'big_image_size_threshold', '__return_false' );
                wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
                remove_filter( 'big_image_size_threshold', '__return_false' );
                update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $m['alt'] ) );
            }
            $mapped[$hash] = (int) $id;
        }
        return $mapped;
    }

    public static function save_product( $record, $id, $media, $terms, $parent = 0 ) {
        $class = $parent ? 'WC_Product_Variation' : ( $record['type'] === 'variable' ? 'WC_Product_Variable' : 'WC_Product_Simple' );
        $product = new $class( $id );
        if ( $parent ) { $product->set_parent_id( $parent ); }
        $props = $record['props'];
        // Never overwrite historical sales/review metadata or unrelated plugin metadata.
        $error = $product->set_props( $props );
        self::check( ! is_wp_error( $error ), is_wp_error( $error ) ? $error->get_error_message() : '' );
        if ( $parent ) {
            $product->set_attributes( $record['attributes'] );
        } else {
            $attributes = array();
            foreach ( $record['attributes'] as $a ) {
                $attribute = new WC_Product_Attribute();
                $attribute->set_id( 0 ); $attribute->set_name( $a['name'] ); $attribute->set_options( $a['options'] );
                $attribute->set_position( $a['position'] ); $attribute->set_visible( $a['visible'] ); $attribute->set_variation( $a['variation'] );
                $attributes[] = $attribute;
            }
            $product->set_attributes( $attributes );
            $product->set_category_ids( array_map( static function ( $k ) use ( $terms ) { return $terms[$k]; }, $record['categories'] ) );
            $product->set_tag_ids( array_map( static function ( $k ) use ( $terms ) { return $terms[$k]; }, $record['tags'] ) );
            $product->set_gallery_image_ids( array_map( static function ( $k ) use ( $media ) { return $media[$k]; }, $record['gallery'] ) );
        }
        $product->set_image_id( $record['image'] ? $media[$record['image']] : 0 );
        // The approved catalog has no downloads or shipping classes.
        $product->set_downloads( array() );
        $product->set_shipping_class_id( 0 );
        $saved = $product->save();
        self::check( $saved > 0, 'Product save failed: ' . $props['name'] );
        return $saved;
    }

    public static function apply( $data, $plan, $directory ) {
        // Register Deal data types for this CLI process only if its UI plugin is not deployed yet.
        if ( ! post_type_exists( 'ek_deal' ) ) { register_post_type( 'ek_deal', array( 'public' => false ) ); }
        if ( ! taxonomy_exists( 'ek_deal_category' ) ) { register_taxonomy( 'ek_deal_category', 'ek_deal', array( 'public' => false ) ); }
        $media = self::media( $data, $directory ); $terms = array();
        foreach ( $data['terms'] as $key => $term ) { self::term( $key, $data, $terms ); }
        $products = array();
        foreach ( $data['products'] as $p ) {
            $products[$p['key']] = self::save_product( $p, $plan['products'][$p['key']], $media, $terms );
        }
        foreach ( $data['products'] as $p ) {
            $id = $products[$p['key']]; $vp = $plan['variations'][$p['key']];
            foreach ( $vp['retire'] as $obsolete ) {
                // Woo's child query includes publish/private, but excludes draft.
                $result = wp_update_post( array( 'ID' => $obsolete, 'post_status' => 'draft' ), true );
                self::check( ! is_wp_error( $result ), 'Could not retire variation ' . $obsolete );
            }
            foreach ( $p['variations'] as $index => $v ) { self::save_product( $v, $vp['keep'][$index], $media, $terms, $id ); }
            $group = self::apf( $p['apf'], $products, $id );
            if ( $group === null ) { delete_post_meta( $id, self::APF_META ); }
            else { update_post_meta( $id, self::APF_META, wp_slash( $group ) ); }
            $product = $p['type'] === 'variable' ? new WC_Product_Variable( $id ) : new WC_Product_Simple( $id );
            $product->set_upsell_ids( array_map( static function ( $k ) use ( $products ) { return $products[$k]; }, $p['upsells'] ) );
            $product->set_cross_sell_ids( array_map( static function ( $k ) use ( $products ) { return $products[$k]; }, $p['cross_sells'] ) );
            $product->save();
            wc_delete_product_transients( $id );
            if ( $p['type'] === 'variable' ) { WC_Product_Variable::sync( $id ); }
        }
        foreach ( $plan['retire'] as $id => $name ) {
            $product = wc_get_product( $id );
            self::check( (bool) $product, 'Cannot retire product ' . $id );
            $product->set_status( 'draft' ); $product->set_catalog_visibility( 'hidden' ); $product->save();
        }
        foreach ( $data['deals'] as $deal ) {
            $post = $deal['post']; $post['post_type'] = 'ek_deal'; $post['ID'] = $plan['deals'][$post['post_name']];
            $id = wp_insert_post( wp_slash( $post ), true );
            self::check( ! is_wp_error( $id ), is_wp_error( $id ) ? $id->get_error_message() : '' );
            $meta = $deal['data']; $meta['product_id'] = $deal['product_key'] ? $products[$deal['product_key']] : 0;
            update_post_meta( $id, '_ek_deal', wp_slash( $meta ) );
            update_post_meta( $id, '_thumbnail_id', $deal['image'] ? $media[$deal['image']] : 0 );
            wp_set_object_terms( $id, array_map( static function ( $k ) use ( $terms ) { return $terms[$k]; }, $deal['categories'] ), 'ek_deal_category', false );
        }
        $page = $data['deals_page']; $page['ID'] = $plan['page']; $page['post_type'] = 'page'; $page['post_status'] = 'publish'; $page['post_parent'] = 0;
        $result = wp_insert_post( wp_slash( $page ), true );
        self::check( ! is_wp_error( $result ) && get_post_field( 'post_name', $result ) === 'deals', 'Could not create the exact /deals/ page.' );
        WP_CLI::success( 'Menu and Deal data imported. Theme, commerce settings, users and order history were not migrated.' );
    }
}

if ( ! defined( 'EKM_EXPORT_LIBRARY' ) ) {
    WP_CLI::add_command( 'ek-menu-import', static function ( $args, $flags ) {
        $read_only = null;
        try {
            EKM_Menu_Package::check( ! $args, 'Unexpected positional arguments.' );
            EKM_Menu_Package::check( ! array_diff( array_keys( $flags ), array( 'dry-run', 'apply', 'package' ) ), 'Unknown option.' );
            EKM_Menu_Package::check( ! ( isset( $flags['apply'] ) && isset( $flags['dry-run'] ) ), 'Use either --apply or --dry-run.' );
            EKM_Menu_Package::check( ! isset( $flags['apply'] ) || $flags['apply'] === true, '--apply takes no value.' );
            EKM_Menu_Package::check( function_exists( 'wc_get_product' ), 'WooCommerce must be active.' );
            if ( ! isset( $flags['apply'] ) ) {
                // Guard against incidental writes by product readers or plugin hooks during dry run.
                $read_only = static function ( $sql ) {
                    EKM_Menu_Package::check( ! preg_match( '/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|RENAME)\b/i', $sql ), 'A plugin attempted a write during dry run; query blocked.' );
                    return $sql;
                };
                add_filter( 'query', $read_only, PHP_INT_MAX );
            }
            $directory = realpath( $flags['package'] ?? __DIR__ );
            EKM_Menu_Package::check( (bool) $directory, 'Package directory does not exist.' );
            $data = EKM_Menu_Package::load( $directory );
            $plan = EKM_Menu_Package::plan( $data );
            EKM_Menu_Package::report( $data, $plan );
            EKM_Menu_Package::check( ! $plan['errors'], 'Resolve all reported blockers before importing. No writes performed.' );
            if ( ! isset( $flags['apply'] ) ) { remove_filter( 'query', $read_only, PHP_INT_MAX ); WP_CLI::success( 'Dry run complete. No writes performed.' ); return; }
            EKM_Menu_Package::apply( $data, $plan, $directory );
        } catch ( Throwable $e ) {
            if ( $read_only ) { remove_filter( 'query', $read_only, PHP_INT_MAX ); }
            WP_CLI::error( $e->getMessage() );
        }
    } );
}
