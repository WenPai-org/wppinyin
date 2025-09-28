<?php

namespace WenPai\PinYin\Src;

add_action('admin_menu', __NAMESPACE__ . '\\wppy_add_admin_menu');
add_action('admin_init', __NAMESPACE__ . '\\wppy_settings_init');

function wppy_add_admin_menu() {
    add_options_page(
        '文派拼音生成器',
        '文派拼音生成器',
        'manage_options',
        'wppy',
        __NAMESPACE__ . '\\wppy_options_page'
    );
}

function wppy_settings_init() {
    register_setting('wppy_content_add_pinyin', 'wppy_content_add_pinyin');

    add_settings_section(
        'wppy_content_add_pinyin_section',
        __('内容注音', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_content_add_pinyin_section_callback',
        'wppy_content_add_pinyin'
    );

    add_settings_field(
        'global_autoload_py',
        __('自动注音', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_global_autoload_py_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );
}

function wppy_global_autoload_py_render() {
    $options = get_option('wppy_content_add_pinyin');
    $post_types = wppy_get_registered_post_types();
    $selected = isset($options['global_autoload_py']) ? $options['global_autoload_py'] : array();
    
    foreach ($post_types as $post_type => $label) {
        $checked = isset($selected[$post_type]) ? 'checked' : '';
        echo "<label><input type='checkbox' name='wppy_content_add_pinyin[global_autoload_py][{$post_type}]' value='{$post_type}' {$checked}> {$label}</label><br>";
    }
    echo '<p class="description">' . __('勾选想开启自动注音功能的文章类型', 'wppy-nihao') . '</p>';
}

function wppy_content_add_pinyin_section_callback() {
    echo __('配置内容注音相关设置', 'wppy-nihao');
}

function wppy_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h1>文派拼音生成器</h1>
        <?php
        settings_fields('wppy_content_add_pinyin');
        do_settings_sections('wppy_content_add_pinyin');
        submit_button();
        ?>
    </form>
    <?php
}
