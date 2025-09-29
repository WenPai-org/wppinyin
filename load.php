<?php
/**
 * 插件装载文件
 *
 * @package WenPai\PinYin
 */

namespace WenPai\PinYin;

use WenPai\PinYin\Src\PinYin;

/** 载入Composer的自动加载程序 */
require_once 'vendor/autoload.php';

/** 载入公共函数 */
require_once 'src/functions.php';

/** 载入设置项 */
if ( is_admin() && ! ( defined('DOING_AJAX' ) && DOING_AJAX) ) {
    require_once 'src/setting.php';
}

/** 载入Meta Box */
if ( is_admin() ) {
    require_once 'src/meta-box.php';
}



/** 载入前端脚本 */
if ( ! is_admin() ) {
    add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\wppy_enqueue_frontend_scripts' );
}

function wppy_enqueue_frontend_scripts() {
    $engine = wppy_get_option( 'pinyin_engine', 'content_add_pinyin', 'overtrue' );
    
    if ( $engine === 'pinyinpro' ) {
        wp_enqueue_script(
            'wppy-pinyin-pro-engine',
            WPPY_PLUGIN_URL . 'assets/js/pinyin-pro-engine.js',
            array(),
            WPPY_VERSION,
            true
        );
        
        $custom_rules_text = wppy_get_option( 'pinyinpro_custom_rules', 'content_add_pinyin', '' );
        $custom_rules = array();
        if ( ! empty( $custom_rules_text ) ) {
            $lines = explode( "\n", $custom_rules_text );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( ! empty( $line ) && strpos( $line, '=' ) !== false ) {
                    list( $char, $pinyin ) = explode( '=', $line, 2 );
                    $custom_rules[ trim( $char ) ] = trim( $pinyin );
                }
            }
        }
        
        $config = array(
            'engine' => 'pinyinpro',
            'toneStyle' => wppy_get_option( 'tone_style', 'content_add_pinyin', 'symbol' ),
            'autoProcess' => ! empty( wppy_get_option( 'global_autoload_py', 'content_add_pinyin', [] ) ),
            'surnameMode' => wppy_get_option( 'pinyinpro_surname_mode', 'content_add_pinyin', false ),
            'customRules' => $custom_rules,
            'processingMode' => wppy_get_option( 'frontend_processing_mode', 'content_add_pinyin', 'auto' ),
            'polyphoneMode' => wppy_get_option( 'polyphone_mode', 'content_add_pinyin', 'context' )
        );
        
        wp_localize_script( 'wppy-pinyin-pro-engine', 'wpPinyinConfig', $config );
    }
}

/** 载入内容注音功能 */
$args = array(
    'global_autoload_py' => wppy_get_option( 'global_autoload_py', 'content_add_pinyin', [] ),
);
$pinyin = new PinYin( $args );
$pinyin->register_hook();
