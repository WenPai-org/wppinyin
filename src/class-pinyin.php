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

        private static $engine_type = 'overtrue';
        private static $available_engines = array(
            'overtrue' => 'Overtrue PHP Engine',
            'pinyinpro' => 'PinyinPro JavaScript Engine'
        );

        /**
         * 会被添加注音的HTML标记
         */
        const ZHUYIN_ELE = 'h1,h2,h3,h4,h5,p,li,em';

        /**
         * @var array
         */
        private $global_autoload_py_config;
        
        /**
         * @var array
         */
        private static $pinyin_cache = array();
        
        public function __construct( array $args ) {
            $default = array(
                'global_autoload_py' => [],
            );
            $args = wp_parse_args( $args, $default );

            $this->global_autoload_py_config = $args['global_autoload_py'];
            $this->init_engine();
            $this->optimize_performance();
        }

        private function init_engine() {
            self::$engine_type = wppy_get_option( 'pinyin_engine', 'content_add_pinyin', 'overtrue' );
            
            if ( self::$engine_type === 'pinyinpro' && ! $this->is_pinyinpro_available() ) {
                self::$engine_type = 'overtrue';
            }
        }

        private function is_pinyinpro_available() {
            return ! is_admin();
        }

        public static function get_available_engines() {
            return self::$available_engines;
        }

        public static function get_current_engine() {
            return self::$engine_type;
        }

        private function process_with_frontend_engine( string $text ) {
            $text = esc_html( $text );
            $text = str_replace( array( "\r\n", "\r", "\n" ), '<br>', $text );
            
            return '<span class="wppy-frontend-process" data-text="' . esc_attr( $text ) . '">' . $text . '</span>';
        }

        private function optimize_performance() {
            if ( class_exists( 'Overtrue\Pinyin\Pinyin' ) ) {
                $strategy = wppy_get_option( 'performance_strategy', 'content_add_pinyin', 'memory' );
                
                PinYinClass::clearCache();
                
                switch ( $strategy ) {
                    case 'cached':
                        PinYinClass::useCached();
                        break;
                    case 'smart':
                        PinYinClass::useSmart();
                        break;
                    case 'memory':
                    default:
                        PinYinClass::useMemoryOptimized();
                        break;
                }
            }
        }

        private function get_cache_key( string $text, string $mode = 'context' ) {
            return md5( $text . '_' . $mode );
        }

        private function get_cached_pinyin( string $text, string $mode = 'context' ) {
            if ( ! wppy_get_option( 'enable_cache', 'content_add_pinyin', false ) ) {
                return false;
            }
            
            $cache_key = $this->get_cache_key( $text, $mode );
            return isset( self::$pinyin_cache[$cache_key] ) ? self::$pinyin_cache[$cache_key] : false;
        }

        private function set_cached_pinyin( string $text, string $result, string $mode = 'context' ) {
            if ( ! wppy_get_option( 'enable_cache', 'content_add_pinyin', false ) ) {
                return;
            }
            
            $cache_key = $this->get_cache_key( $text, $mode );
            self::$pinyin_cache[$cache_key] = $result;
            
            if ( count( self::$pinyin_cache ) > 100 ) {
                self::$pinyin_cache = array_slice( self::$pinyin_cache, -50, 50, true );
            }
        }

        private function get_char_pinyin( string $char ) {
            try {
                $tone_style_setting = $this->get_post_setting( 'tone_style', 'symbol' );
                $tone_style = $this->convert_tone_style( $tone_style_setting );
                $char_pinyin = PinYinClass::chars( $char, $tone_style );
                $pinyin_array = $char_pinyin->toArray();
                
                if ( isset( $pinyin_array[$char] ) ) {
                    $pinyin = (string) $pinyin_array[$char];
                    if ( $this->is_valid_pinyin( $pinyin ) ) {
                        return $pinyin;
                    }
                }
                
                foreach ( $pinyin_array as $pinyin ) {
                    if ( is_string( $pinyin ) && $this->is_valid_pinyin( $pinyin ) ) {
                        return $pinyin;
                    }
                }
                
                return '';
            } catch ( \Exception $e ) {
                return '';
            }
        }

        private function get_heteronym_pinyin( string $char ) {
            try {
                $tone_style_setting = $this->get_post_setting( 'tone_style', 'symbol' );
                $tone_style = $this->convert_tone_style( $tone_style_setting );
                $heteronym = PinYinClass::heteronymAsList( $char, $tone_style );
                $pinyin_array = $heteronym->toArray();
                
                if ( isset( $pinyin_array[0][$char] ) && is_array( $pinyin_array[0][$char] ) ) {
                    return implode( '/', $pinyin_array[0][$char] );
                }
                
                return '';
            } catch ( \Exception $e ) {
                return '';
            }
        }

        private function update_performance_stats( string $text, float $processing_time, bool $cache_hit = false ) {
            if ( ! wppy_get_option( 'quality_check', 'content_add_pinyin', false ) ) {
                return;
            }
            
            $stats = get_option( 'wppy_performance_stats', array() );
            
            $stats['total_requests'] = isset( $stats['total_requests'] ) ? $stats['total_requests'] + 1 : 1;
            $stats['total_time'] = isset( $stats['total_time'] ) ? $stats['total_time'] + $processing_time : $processing_time;
            
            if ( $cache_hit ) {
                $stats['cache_hits'] = isset( $stats['cache_hits'] ) ? $stats['cache_hits'] + 1 : 1;
            }
            
            $chinese_chars = mb_strlen( preg_replace( '/[^\x{4e00}-\x{9fa5}]/u', '', $text ), 'UTF-8' );
            $stats['chinese_chars'] = isset( $stats['chinese_chars'] ) ? $stats['chinese_chars'] + $chinese_chars : $chinese_chars;
            $stats['pinyin_chars'] = isset( $stats['pinyin_chars'] ) ? $stats['pinyin_chars'] + $chinese_chars : $chinese_chars;
            
            update_option( 'wppy_performance_stats', $stats );
        }

        private function get_post_setting( string $option, string $default = '' ) {
            $post_id = get_the_ID();
            if ( ! $post_id ) {
                return wppy_get_option( $option, 'content_add_pinyin', $default );
            }
            
            $post_value = get_post_meta( $post_id, "zhuyin_{$option}", true );
            if ( ! empty( $post_value ) ) {
                return $post_value;
            }
            
            return wppy_get_option( $option, 'content_add_pinyin', $default );
        }

        private function convert_tone_style( string $style ) {
            if ( ! class_exists( 'Overtrue\Pinyin\ToneStyle' ) ) {
                return $style;
            }
            
            switch ( $style ) {
                case 'number':
                    return \Overtrue\Pinyin\ToneStyle::NUMBER;
                case 'none':
                    return \Overtrue\Pinyin\ToneStyle::NONE;
                case 'symbol':
                default:
                    return \Overtrue\Pinyin\ToneStyle::SYMBOL;
            }
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
            
            wp_register_style( 'wppy_ruby_styles', WPPY_PLUGIN_URL . 'assets/css/ruby-styles.css', array(), WPPY_VERSION );
            wp_enqueue_style( 'wppy_ruby_styles');
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
            if ( strpos( $content_original, 'wppy-frontend-process' ) !== false ) {
                return $content_original;
            }
            
            try {
                $content_original = $this->clean_html_entities( $content_original );
                $content_original = $this->clean_wordpress_blocks( $content_original );
                
                return $this->process_html_content( $content_original );
            } catch ( \Exception $e ) {
                return $content_original;
            }
        }

        /**
         * 使用简单的正则表达式处理HTML内容，避免解析器编码问题
         *
         * @since 1.1.0
         * @param string $content
         *
         * @return string
         */
        private function process_html_content( string $content ) {
            $content = preg_replace_callback(
                '/<(br\s*\/?)>/i',
                function( $matches ) {
                    return '###BR_TAG_' . md5( $matches[0] ) . '###';
                },
                $content
            );
            
            $filter_mode = wppy_get_option( 'html_tag_filter_mode', 'content_add_pinyin', 'none' );
            
            if ( $filter_mode === 'exclude' ) {
                $excluded_tags = wppy_get_option( 'excluded_html_tags', 'content_add_pinyin', 'code,pre,script,style' );
                $content = $this->exclude_html_tags( $content, $excluded_tags );
            } else if ( $filter_mode === 'include' ) {
                $included_tags = wppy_get_option( 'included_html_tags', 'content_add_pinyin', 'p,h1,h2,h3,h4,h5,h6,div,span' );
                $html_tags = $included_tags;
            } else {
                $html_tags = self::ZHUYIN_ELE;
            }
            
            if ( $filter_mode !== 'exclude' ) {
                $tags_array = explode(',', $html_tags);
                
                foreach ($tags_array as $tag) {
                    $tag = trim($tag);
                    $pattern = '/<' . $tag . '([^>]*)>(.*?)<\/' . $tag . '>/s';
                    
                    $content = preg_replace_callback($pattern, array($this, 'process_tag_callback'), $content);
                }
            }
            
            $content = preg_replace_callback(
                '/###BR_TAG_([a-f0-9]{32})###/',
                function( $matches ) {
                    return '<br />';
                },
                $content
            );
            
            return $content;
        }

        /**
         * 处理HTML标签的回调函数
         *
         * @since 1.1.0
         * @param array $matches
         *
         * @return string
         */
        private function process_tag_callback( array $matches ) {
            $full_tag = $matches[0];
            $tag_attrs = $matches[1];
            $inner_content = $matches[2];
            
            if ($this->should_skip_content($inner_content)) {
                return $full_tag;
            }
            
            $processed_content = $this->process_text_content($inner_content);
            
            preg_match('/<([a-zA-Z0-9]+)/', $full_tag, $tag_matches);
            $tag_name = $tag_matches[1];
            
            return '<' . $tag_name . $tag_attrs . '>' . $processed_content . '</' . $tag_name . '>';
        }

        /**
         * 检查是否应该跳过处理某些内容
         *
         * @since 1.1.0
         * @param string $content
         *
         * @return bool
         */
        private function should_skip_content( string $content ) {
            $skip_patterns = array(
                '/<a\s+[^>]*>.*?<\/a>/i',
                '/<img[^>]*>/i',
                '/<ruby>.*?<\/ruby>/i',
                '/<strong>.*?<\/strong>/i',
                '/[a-zA-Z]+[áàǎāéèěēíìǐīóòǒōúùǔūǘǜǚǖ]+[a-zA-Z]*/',
                '/[\x{4e00}-\x{9fa5}][a-zA-Z]+/u',
            );
            
            foreach ($skip_patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return true;
                }
            }
            
            $smart_filter_enabled = wppy_get_option( 'smart_filter_enabled', 'content_add_pinyin', true );
            if ( $smart_filter_enabled && $this->contains_pinyin_annotation( $content ) ) {
                return true;
            }
            
            return false;
        }

        /**
         * 处理纯文本内容，添加拼音标注
         *
         * @since 1.1.0
         * @param string $content
         *
         * @return string
         */
        private function process_text_content( string $content ) {
            if (empty(trim($content))) {
                return $content;
            }
            
            return $this->process_text_with_context( $content );
        }

        /**
         * 基于上下文处理文本拼音标注
         *
         * @since 1.1.0
         * @param string $text
         *
         * @return string
         */
        private function process_text_with_context( string $text ) {
            $start_time = microtime( true );
            
            if ( self::$engine_type === 'pinyinpro' && ! is_admin() ) {
                return $this->process_with_frontend_engine( $text );
            }
            
            $smart_filter_enabled = wppy_get_option( 'smart_filter_enabled', 'content_add_pinyin', true );
            
            if ( $smart_filter_enabled && $this->contains_pinyin_annotation( $text ) ) {
                if ( strpos( $text, '<ruby>' ) !== false || strpos( $text, '<rt>' ) !== false ) {
                    return $text;
                } else {
                    $text = $this->clean_mixed_pinyin_text( $text );
                    self::$pinyin_cache = array();
                }
            }
            
            $polyphone_mode = wppy_get_option( 'polyphone_mode', 'content_add_pinyin', 'context' );
            $cached_result = $this->get_cached_pinyin( $text, $polyphone_mode );
            if ( $cached_result !== false ) {
                $this->update_performance_stats( $text, microtime( true ) - $start_time, true );
                return $cached_result;
            }
            
            $result = '';
            $in_ruby = false;
            $text_length = mb_strlen( $text, 'UTF-8' );
            $chinese_char_count = 0;
            
            for ( $i = 0; $i < $text_length; $i++ ) {
                $char = mb_substr( $text, $i, 1, 'UTF-8' );
                
                if ( preg_match( "/[\x{4e00}-\x{9fa5}]/u", $char ) ) {
                    if ( !$in_ruby ) {
                        $result .= '<ruby>';
                        $in_ruby = true;
                    }
                    
                    $pinyin_str = $this->get_contextual_pinyin( $text, $i );
                    if ( !empty( $pinyin_str ) && $pinyin_str !== $char ) {
                        $result .= "{$char}<rp>(</rp><rt>{$pinyin_str}</rt><rp>)</rp>";
                    } else {
                        $result .= $char;
                    }
                    $chinese_char_count++;
                } else {
                    if ( $this->is_chinese_punctuation( $char ) || $this->is_common_punctuation( $char ) ) {
                        if ( $in_ruby ) {
                            $result .= '</ruby>';
                            $in_ruby = false;
                        }
                        $result .= $char;
                    } else {
                        if ( $in_ruby ) {
                            $result .= '</ruby>';
                            $in_ruby = false;
                        }
                        $result .= $char;
                    }
                }
            }
            
            if ( $in_ruby ) {
                $result .= '</ruby>';
            }
            
            $this->set_cached_pinyin( $text, $result, $polyphone_mode );
            $this->update_performance_stats( $text, microtime( true ) - $start_time, false );
            return $result;
        }

        /**
         * 获取基于上下文的拼音
         *
         * @since 1.1.0
         * @param string $text 完整文本
         * @param int $position 当前字符位置
         *
         * @return string
         */
        private function get_contextual_pinyin( string $text, int $position ) {
            $char = mb_substr( $text, $position, 1, 'UTF-8' );
            
            if ( !preg_match( "/[\x{4e00}-\x{9fa5}]/u", $char ) ) {
                return '';
            }
            
            $corrected_pinyin = $this->get_corrected_pinyin( $text, $position, $char );
            if ( !empty( $corrected_pinyin ) ) {
                return $corrected_pinyin;
            }
            
            $polyphone_mode = $this->get_post_setting( 'polyphone_mode', 'context' );
            
            if ( $polyphone_mode === 'chars' ) {
                return $this->get_char_pinyin( $char );
            } elseif ( $polyphone_mode === 'heteronym' ) {
                return $this->get_heteronym_pinyin( $char );
            }
            
            try {
                $tone_style_setting = $this->get_post_setting( 'tone_style', 'symbol' );
                $tone_style = $this->convert_tone_style( $tone_style_setting );
                $sentence_pinyin = PinYinClass::sentence( $text, $tone_style );
                $pinyin_array = $sentence_pinyin->toArray();
                
                $chinese_char_count = 0;
                for ( $i = 0; $i < $position; $i++ ) {
                    $text_char = mb_substr( $text, $i, 1, 'UTF-8' );
                    if ( preg_match( "/[\x{4e00}-\x{9fa5}]/u", $text_char ) ) {
                        $chinese_char_count++;
                    }
                }
                
                $current_chinese_index = 0;
                foreach ( $pinyin_array as $pinyin_item ) {
                    if ( $this->is_valid_pinyin( $pinyin_item ) ) {
                        if ( $current_chinese_index === $chinese_char_count ) {
                            return (string) $pinyin_item;
                        }
                        $current_chinese_index++;
                    }
                }
                
                return $this->get_char_pinyin( $char );
            } catch ( \Exception $e ) {
            }
            
            return $this->get_char_pinyin( $char );
        }

        /**
         * 验证拼音是否有效
         *
         * @since 1.1.0
         * @param string $pinyin
         *
         * @return bool
         */
        private function is_valid_pinyin( string $pinyin ) {
            if ( empty( $pinyin ) ) {
                return false;
            }
            
            if ( preg_match( '/[\x{3002}\x{ff1f}\x{ff01}#]/u', $pinyin ) ) {
                return false;
            }
            
            if ( !preg_match( '/^[a-zA-ZāáǎàēéěèīíǐìōóǒòūúǔùǘǜǚǖĀÁǍÀĒÉĚÈĪÍǏÌŌÓǑÒŪÚǓÙǗǛǙǕ0-9]+$/u', $pinyin ) ) {
                return false;
            }
            
            return true;
        }

        /**
         * 提取词语上下文
         *
         * @since 1.1.0
         * @param string $text
         * @param int $position
         *
         * @return string
         */
        private function extract_word_context( string $text, int $position ) {
            $max_word_length = 8;
            $start = max( 0, $position - $max_word_length + 1 );
            $end = min( mb_strlen( $text, 'UTF-8' ), $position + $max_word_length );
            
            $context = mb_substr( $text, $start, $end - $start, 'UTF-8' );
            
            $words = array();
            for ( $len = 2; $len <= $max_word_length; $len++ ) {
                for ( $i = 0; $i <= mb_strlen( $context, 'UTF-8' ) - $len; $i++ ) {
                    $word = mb_substr( $context, $i, $len, 'UTF-8' );
                    $word_start_in_text = $start + $i;
                    $word_end_in_text = $word_start_in_text + $len - 1;
                    
                    if ( $word_start_in_text <= $position && $position <= $word_end_in_text ) {
                        if ( $this->is_valid_chinese_word( $word ) ) {
                            $words[] = $word;
                        }
                    }
                }
            }
            
            if ( !empty( $words ) ) {
                usort( $words, function( $a, $b ) {
                    return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' );
                });
                return $words[0];
            }
            
            return '';
        }

        /**
         * 检查是否为有效的中文词语
         *
         * @since 1.1.0
         * @param string $word
         *
         * @return bool
         */
        private function is_valid_chinese_word( string $word ) {
            if ( mb_strlen( $word, 'UTF-8' ) < 2 ) {
                return false;
            }
            
            return preg_match( "/^[\x{4e00}-\x{9fa5}]+$/u", $word );
        }

        /**
         * 清理WordPress块注释
         *
         * @since 1.1.0
         * @param string $content
         *
         * @return string
         */
        private function clean_wordpress_blocks( string $content ) {
            $content = preg_replace('/<!-- wp:.*? -->/', '', $content);
            $content = preg_replace('/<!-- \/wp:.*? -->/', '', $content);
            return $content;
        }

        /**
         * 清理HTML实体编码
         *
         * @since 1.1.0
         * @param string $content
         *
         * @return string
         */
        private function clean_html_entities( string $content ) {
            $html_entities = array(
                 '&#8211;' => '–',
                 '&#8212;' => '—',
                 '&#8220;' => '"',
                 '&#8221;' => '"',
                 '&#8216;' => "'",
                 '&#8217;' => "'",
                 '&#8230;' => '…',
                 '&quot;' => '"',
                 '&amp;' => '&',
                 '&lt;' => '<',
                 '&gt;' => '>',
                 '&nbsp;' => ' ',
             );
            
            return str_replace( array_keys( $html_entities ), array_values( $html_entities ), $content );
        }

        /**
         * 检查文本是否已包含拼音注释
         *
         * @since 1.1.0
         * @param string $text
         *
         * @return bool
         */
        private function clean_mixed_pinyin_text( string $text ) {
            $text = preg_replace( '/([\x{4e00}-\x{9fa5}])[a-zA-Z]+/u', '$1', $text );
            $text = preg_replace( '/[a-zA-Z]+([\x{4e00}-\x{9fa5}])/u', '$1', $text );
            $text = preg_replace( '/[a-zA-Z]+[0-9]*/u', '', $text );
            $text = preg_replace( '/[áàǎāéèěēíìǐīóòǒōúùǔūǘǜǚǖ]+/u', '', $text );
            $text = preg_replace( '/\s+/u', ' ', $text );
            $text = trim( $text );
            
            return $text;
        }

        private function contains_pinyin_annotation( string $text ) {
            if ( strpos( $text, '<ruby>' ) !== false || strpos( $text, '<rt>' ) !== false ) {
                return true;
            }
            
            $pinyin_patterns = array(
                '/[\x{4e00}-\x{9fa5}][a-zA-Z]+[\x{4e00}-\x{9fa5}]/u',
                '/[\x{4e00}-\x{9fa5}][a-zA-Z]{2,}/u',
                '/[a-zA-Z]{2,}[\x{4e00}-\x{9fa5}]/u',
                '/[\x{4e00}-\x{9fa5}].*?[áàǎāéèěēíìǐīóòǒōúùǔūǘǜǚǖ]/u',
                '/[áàǎāéèěēíìǐīóòǒōúùǔūǘǜǚǖ].*?[\x{4e00}-\x{9fa5}]/u',
                '/读作|正确读作|拼音|注音/u',
                '/[\x{4e00}-\x{9fa5}][a-zA-Z]+[\x{4e00}-\x{9fa5}][a-zA-Z]+/u',
            );
            
            foreach ( $pinyin_patterns as $pattern ) {
                if ( preg_match( $pattern, $text ) ) {
                    return true;
                }
            }
            
            return false;
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
                $deps = array(
                    'wp-element', 'wp-editor', 'wp-i18n',
                    'wp-rich-text', 'wp-compose','wp-components',
                );

                wp_enqueue_script( 'wppy_gutenberg', WPPY_PLUGIN_URL . 'assets/js/wppy-gutenberg.js' , $deps , WPPY_VERSION, true );
                wp_enqueue_style( 'wppy_gutenberg_css', WPPY_PLUGIN_URL . 'assets/css/wppy-gutenberg.css', array(), WPPY_VERSION );
            }
        }

        /**
         * 检查是否为中文标点符号
         *
         * @since 1.1.0
         * @param string $char
         *
         * @return bool
         */
        private function is_chinese_punctuation( string $char ) {
            return preg_match('/[\x{ff0c}\x{3002}\x{ff01}\x{ff1f}\x{ff1b}\x{ff1a}\x{201c}\x{201d}\x{2018}\x{2019}\x{ff08}\x{ff09}\x{3010}\x{3011}\x{300a}\x{300b}\x{3001}\x{2026}\x{2014}\x{ff5e}]/u', $char);
        }

        private function is_common_punctuation( string $char ) {
            $common_punctuation = array(
                ",", ".", "!", "?", ";", ":", "\"", "'", 
                "(", ")", "[", "]", "<", ">", "-", "_", "*"
            );
            return in_array( $char, $common_punctuation );
        }

        private function exclude_html_tags( string $content, string $excluded_tags ) {
            $tags_array = array_map('trim', explode(',', $excluded_tags));
            
            foreach ($tags_array as $tag) {
                if (empty($tag)) continue;
                
                $pattern = '/<' . preg_quote($tag, '/') . '([^>]*)>(.*?)<\/' . preg_quote($tag, '/') . '>/s';
                $content = preg_replace_callback($pattern, function($matches) {
                    return $matches[0];
                }, $content);
            }
            
            $all_tags = 'h1,h2,h3,h4,h5,h6,p,div,span,li,em,strong,b,i,u,blockquote,article,section';
            $all_tags_array = array_map('trim', explode(',', $all_tags));
            
            foreach ($all_tags_array as $tag) {
                if (in_array($tag, $tags_array)) continue;
                
                $pattern = '/<' . preg_quote($tag, '/') . '([^>]*)>(.*?)<\/' . preg_quote($tag, '/') . '>/s';
                $content = preg_replace_callback($pattern, array($this, 'process_tag_callback'), $content);
            }
            
            return $content;
        }

        private function get_corrected_pinyin( string $text, int $position, string $char ) {
            $corrections = array(
                '人行道' => array('人' => 'rén', '行' => 'xíng', '道' => 'dào'),
                '行人' => array('行' => 'xíng', '人' => 'rén'),
                '行走' => array('行' => 'xíng', '走' => 'zǒu'),
                '步行' => array('步' => 'bù', '行' => 'xíng'),
                '行进' => array('行' => 'xíng', '进' => 'jìn'),
                '进行' => array('进' => 'jìn', '行' => 'xíng'),
                '执行' => array('执' => 'zhí', '行' => 'xíng'),
                '实行' => array('实' => 'shí', '行' => 'xíng'),
                '举行' => array('举' => 'jǔ', '行' => 'xíng'),
                '流行' => array('流' => 'liú', '行' => 'xíng'),
                '银行' => array('银' => 'yín', '行' => 'háng'),
                '行业' => array('行' => 'háng', '业' => 'yè'),
                '同行' => array('同' => 'tóng', '行' => 'háng'),
                '重庆' => array('重' => 'chóng', '庆' => 'qìng'),
                '重要' => array('重' => 'zhòng', '要' => 'yào'),
                '重新' => array('重' => 'chóng', '新' => 'xīn'),
                '重点' => array('重' => 'zhòng', '点' => 'diǎn'),
                '重量' => array('重' => 'zhòng', '量' => 'liàng'),
                '重复' => array('重' => 'chóng', '复' => 'fù'),
                '今天' => array('今' => 'jīn', '天' => 'tiān'),
                '明天' => array('明' => 'míng', '天' => 'tiān'),
                '昨天' => array('昨' => 'zuó', '天' => 'tiān'),
                '天气' => array('天' => 'tiān', '气' => 'qì'),
                '会议' => array('会' => 'huì', '议' => 'yì'),
                '的确' => array('的' => 'dí', '确' => 'què'),
                '目的' => array('目' => 'mù', '的' => 'de'),
            );
            
            foreach ($corrections as $word => $char_pinyins) {
                $word_len = mb_strlen($word, 'UTF-8');
                $search_pos = 0;
                
                while (($word_pos = mb_strpos($text, $word, $search_pos, 'UTF-8')) !== false) {
                    $word_end = $word_pos + $word_len - 1;
                    if ($position >= $word_pos && $position <= $word_end) {
                        $char_index_in_word = $position - $word_pos;
                        $word_chars = mb_str_split($word, 1, 'UTF-8');
                        if (isset($word_chars[$char_index_in_word]) && 
                            $word_chars[$char_index_in_word] === $char &&
                            isset($char_pinyins[$char])) {
                            return $char_pinyins[$char];
                        }
                    }
                    $search_pos = $word_pos + 1;
                }
            }
            
            return '';
        }
    }
}
