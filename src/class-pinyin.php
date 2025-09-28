<?php
/**
 * 内容注音类文件
 *
 * @package WenPai\PinYin
 */

namespace WenPai\PinYin\Src;

use Overtrue\Pinyin\Pinyin as PinYinClass;
use Overtrue\Pinyin\ToneStyle;
use PHPHtmlParser\Dom;

if ( !class_exists( PinYin::class ) ) {

    /**
     * 内容注音类
     *
     * @since 1.0.0
     */
    class PinYin {

        /**
         * 会被添加注音的HTML标记
         */
        const ZHUYIN_ELE = 'h1,h2,h3,h4,h5,p,li,em';

        /**
         * @var array
         */
        private $global_autoload_py_config;
        
        public function __construct( array $args ) {
            $default = array(
                'global_autoload_py' => [],
            );
            $args = wp_parse_args( $args, $default );

            $this->global_autoload_py_config = $args['global_autoload_py'];
        }

        public function register_hook() {
            add_shortcode( 'wppinyin_shortcode', array( $this, 'wppinyin_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'wppinyin_single_js' ) , 11 );
            add_filter( 'the_content', array( $this, 'wppinyin_zhuyin' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'wppinyin_admin_scripts' ) );
        }

        /**
         * shortcode 短代码功能
         *
         * @since 1.0.0
         */
        public function wppinyin_shortcode() {
            $zhuyin_status = get_post_meta( get_the_ID(), 'zhuyin_status', true );
            $article_status = '';

            if( 'auto' === $zhuyin_status )
                $zhuyin_status = $this->check_zhuyin_status() ? 'on' : 'off';

            if ( 'off' == $zhuyin_status ) {
                $article_status = 'off';
            } else {
                $article_status = 'on';
            }

            $name = 'wppy_nihao_status_' . get_the_ID();

            return sprintf( '<input type="submit" value="" data-id="%s" data-article-status="%s" id="wppy_zhuyin_submit">', $name, $article_status );
        }

        /**
         * 前端单页面js引用
         *
         * @since 1.0.0
         */
        public function wppinyin_single_js() {
            wp_register_script( 'wppy_nihao', WPPY_PLUGIN_URL . 'assets/js/wppinyin-nihao.js', array( 'jquery' ), WPPY_VERSION , true);
            wp_enqueue_script( 'wppy_nihao');
        }

        /**
         * 检测该类型的全局开关状态
         *
         * @since 1.0.0
         *
         * @return bool
         */
        public function check_zhuyin_status() {
            global $post;

            if ( '' == $post ) {
                return false;
            }

            $post_type = $post->post_type;
            $post_type_options = $this->global_autoload_py_config;
            if( isset( $post_type_options[$post_type] ) && '' !== $post_type_options[$post_type] ) {
                return true;
            }

            return false;
        }

        /**
         * 文章内容中自动增加注音
         *
         * @since 1.0.0
         * @param $content_original
         *
         * @return string
         */
        public function wppinyin_zhuyin( string $content_original) {
            $zhuyin_status = get_post_meta( get_the_ID() ,'zhuyin_status' , true );

            if( 'auto' === $zhuyin_status )
                $zhuyin_status = $this->check_zhuyin_status() ? 'on' : 'off';

            if( is_admin() ||
                ( defined('DOING_AJAX') && DOING_AJAX ) ||
                ( ! isset( $_GET['zhuyin'] ) && 'off' === $zhuyin_status ) ||
                ( isset( $_GET['zhuyin'] ) && 'off' === $_GET['zhuyin'] )
            ) {
                return $content_original;
            } else if( ( isset( $_GET['zhuyin'] ) && 'on' === $_GET['zhuyin'] ) || ( 'on' === $zhuyin_status ) ) {
                return $this->add_zhuyin( $content_original );
            } else if( $this->check_zhuyin_status() ) {
                return $this->add_zhuyin( $content_original );
            }
            
            return $content_original;
        }

        /**
         * 正则替换手动增加的<rbuy></ruby>字符为###md5(字符串)###
         *
         * @since 1.0.0
         * @param string $content_original
         * @param array $preg_array
         *
         * @return array
         */
        public function preg_match_to_md5( string $content_original , array $preg_array ) {
            $return_ary = array();

            if( is_array( $preg_array ) ) {
                foreach( $preg_array as $preg ) {
                    preg_match_all( $preg, $content_original, $matches );
                    if( $matches ) {
                        foreach( $matches[0] as $match ) {
                            $str_replace = '###' . md5( $match ) . '###';
                            $content_original = str_replace( $match, $str_replace, $content_original );
                            $return_ary['search'][] = $str_replace;
                            $return_ary['replace'][] = $match;
                        }
                    }
                }

                $return_ary['content'] = $content_original;
            }

            return $return_ary;
        }

        /**
         * 添加注音功能
         *
         * @since 1.0.0
         * @param $content_original
         *
         * @return string
         */
        public function add_zhuyin( string $content_original ) {
            try {
                $preg_array = array(
                    '/<a .*?>.*?<\/a>/',
                    '/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/',
                    '/\<ruby\>(.*)\<\/ruby\>/',
                    '/\<strong\>(.*)\<\/strong\>/',
                );
                $matches = $this->preg_match_to_md5( $content_original , $preg_array );

                $pinyin = new PinYinClass();
                $html_tags = self::ZHUYIN_ELE;
                $dom = new Dom;
                $dom->loadStr( $matches['content'] );

                foreach( explode(',' , $html_tags) as $tag ) {
                    $elements = $dom->find($tag);
                    if (!$elements) {
                        continue;
                    }
                    
                    foreach ( $elements as $line ) {
                        if (!$line || !$line->text) {
                            continue;
                        }
                        
                        $ruby_line = '<ruby>';
                        foreach ( my_mb_str_split( $line->text ) as $char ) {
                            if ( preg_match( "/[\x{4e00}-\x{9fa5}]/u", $char ) ) {
                                $pinyin_result = PinYinClass::sentence( $char );
                                $pinyin_str = (string) $pinyin_result;
                                $ruby_line .= "{$char}<rp>(</rp><rt>{$pinyin_str}</rt><rp>)</rp>";
                            } else {
                                $ruby_line .= '</ruby>';
                                $ruby_line .= $char;
                                $ruby_line .= '<ruby>';
                            }
                        }
                        $ruby_line .= '</ruby>';

                        if ( method_exists( $line, 'firstChild' ) && $line->hasChildren() ) {
                            try {
                                $line->firstChild()->setText( $ruby_line );
                            } catch ( \Exception $e ) {
                                continue;
                            }
                        }
                    }
                }

                if( isset( $matches['search'] ) && $matches['replace'] ) {
                    $dom = str_replace( $matches['search'], $matches['replace'], (string)$dom );
                    $dom = str_replace( $this->add_ruby_tag( $matches['search'] ), $matches['replace'], (string)$dom );
                }

                return $dom;
            } catch ( \Exception $e ) {
                return $content_original;
            }
        }

        /**
         * 将字符串使用 '<ruby></ruby>' 包围。
         *
         * @since 1.0.0
         * @param array $arys 搜索的数组
         *
         * @return array
         */
        public function add_ruby_tag( array $arys ) : array {
            $return_ary = array();

            foreach( $arys as $ary ) {
                $tmp = implode( ',', str_split( $ary ) );
                $return_ary[] = '' . str_replace( ',', '<ruby></ruby>', $tmp );
            }

            return $return_ary;
        }

        /**
         * 加载后台JS
         *
         * @since 1.0.0
         * @param string $hook
         */
        public function wppinyin_admin_scripts( string $hook ) {
            if( 'post.php' === $hook || 'post-new.php' === $hook ) {
                /** 加载手动注音功能 */
                $deps = array(
                    'wp-element', 'wp-editor', 'wp-i18n',
                    'wp-rich-text', 'wp-compose','wp-components',
                );

                /** 加载手动注音功能 */
                wp_enqueue_script( 'wppy_gutenberg', WPPY_PLUGIN_URL . 'assets/js/wppy-gutenberg.js' , $deps , WPPY_VERSION, true );

                $dataToBePassed = array(
                    'url'   => WPPY_PLUGIN_URL . '/assets/',
                );
                wp_localize_script( 'wppy_gutenberg', 'assets', $dataToBePassed );
            }
        }

    }

}
