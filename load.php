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



/** 载入内容注音功能 */
$args = array(
    'global_autoload_py' => wppy_get_option( 'global_autoload_py', 'content_add_pinyin', [] ),
);
$pinyin = new PinYin( $args );
$pinyin->register_hook();
