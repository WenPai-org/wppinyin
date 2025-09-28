<?php
/**
 * Plugin Name: WPPinyin
 * Description: 将汉语拼音的应用规则体系引入 WordPress 网站，对文章内容进行拼音标注等。
 * Author: WenPai.org
 * Author URI: https://wppinyin.com/
 * Version: 1.1.0
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace WenPai\PinYin;

define( 'WPPY_PREFIX', 'wppy' );
define( 'WPPY_VERSION', '1.1.0' );
define( 'WPPY_PLUGIN_URL' , plugin_dir_url( __FILE__ ) );

require_once 'load.php';
