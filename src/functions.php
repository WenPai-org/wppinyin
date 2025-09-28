<?php
/**
 * 公共函数
 *
 * @package WenPai\PinYin
 */

if ( ! function_exists( 'wppy_get_option' ) ) {
    function wppy_get_option( string $option, string $section, $default = '' ) {
        $options = get_option( "wppy_{$section}", array() );

        if ( is_array( $options ) && isset( $options[ $option ] ) ) {
            return $options[ $option ];
        }

        return $default;
    }
}

if( ! function_exists( 'wppy_get_registered_post_types' ) ) {
    function wppy_get_registered_post_types() {
        $core_label = array();

        $args = array( 'public' => true );
        $post_types = get_post_types( $args , 'objects' );
        foreach ( $post_types as $post_type ) {
            $core_label[$post_type->name] = $post_type->label;
        }

        return $core_label;
    }
}

/**
 * 中文字符串分割
 *
 * @since 1.0.0
 * @param string $str 需要截取的字符串
 * @param int $split_length 截取的长度
 * @param string $charset 字符集
 *
 * @return array
 */
if ( ! function_exists( 'my_mb_str_split' ) ) {
    function my_mb_str_split( string $str, int $split_length = 1, string $charset = 'UTF-8' ): array {
        if ( 1 === func_num_args() ) {
            return preg_split( '/(?<!^)(?!$)/u', $str );
        }
        if ( $split_length < 1 ) {
            return false;
        }

        $len = mb_strlen( $str, $charset );
        $arr = array();
        for ( $i = 0; $i < $len; $i += $split_length ) {
            $s     = mb_substr( $str, $i, $split_length, $charset );
            $arr[] = $s;
        }

        return $arr;
    }
}

if ( is_admin() ) {
    add_action( 'admin_init', 'wppy_mce_addbuttons' );
}
if ( ! function_exists( 'wppy_mce_addbuttons' ) ) {
    function wppy_mce_addbuttons() {
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( 'true' == get_user_option( 'rich_editing' ) ) {
            add_filter( "mce_external_plugins", 'wppy_add_tinymce_plugin', 5 );
            add_filter( 'mce_buttons', 'wppy_register_tinymce_button', 5 );
        }
    }
}

if ( ! function_exists( 'wppy_register_tinymce_button' ) ) {
    function wppy_register_tinymce_button( $buttons ) {
        array_push( $buttons, "wppy_code" );

        return $buttons;
    }
}

if ( ! function_exists( 'wppy_add_tinymce_plugin' ) ) {
    function wppy_add_tinymce_plugin( $plugin_array ) {
        $plugin_array['wppy_code_button'] = WPPY_PLUGIN_URL . 'assets/js/mce.js';

        return $plugin_array;
    }
}
