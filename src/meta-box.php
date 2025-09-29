<?php

namespace WenPai\PinYin\Src;

add_action('add_meta_boxes', __NAMESPACE__ . '\\wppy_add_meta_boxes');
add_action('save_post', __NAMESPACE__ . '\\wppy_save_meta_box_data');

function wppy_add_meta_boxes() {
    add_meta_box(
        'wppy_zhuyin',
        '自动注音',
        __NAMESPACE__ . '\\wppy_zhuyin_meta_box_callback',
        array('post', 'page'),
        'side',
        'default'
    );
}

function wppy_zhuyin_meta_box_callback($post) {
    wp_nonce_field('wppy_save_meta_box_data', 'wppy_meta_box_nonce');
    
    $status = get_post_meta($post->ID, 'zhuyin_status', true);
    $status = $status ? $status : 'auto';
    
    $tone_style = get_post_meta($post->ID, 'zhuyin_tone_style', true);
    $polyphone_mode = get_post_meta($post->ID, 'zhuyin_polyphone_mode', true);
    
    echo '<h4>拼音状态</h4>';
    $status_options = array(
        'on' => '开启',
        'off' => '关闭',
        'auto' => '随全局设置'
    );
    
    foreach ($status_options as $option_value => $label) {
        $checked = ($status === $option_value) ? 'checked' : '';
        echo "<label><input type='radio' name='zhuyin_status' value='{$option_value}' {$checked}> {$label}</label><br>";
    }
    
    echo '<h4 style="margin-top: 15px;">声调风格覆盖</h4>';
    $tone_options = array(
        '' => '使用全局设置',
        'symbol' => '符号风格 (zhōng)',
        'number' => '数字风格 (zhong4)',
        'none' => '无声调 (zhong)'
    );
    
    echo '<select name="zhuyin_tone_style">';
    foreach ($tone_options as $value => $label) {
        $selected = ($tone_style === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
    
    echo '<h4 style="margin-top: 15px;">多音字处理覆盖</h4>';
    $polyphone_options = array(
        '' => '使用全局设置',
        'context' => '智能上下文',
        'heteronym' => '显示所有读音',
        'chars' => '逐字符处理'
    );
    
    echo '<select name="zhuyin_polyphone_mode">';
    foreach ($polyphone_options as $value => $label) {
        $selected = ($polyphone_mode === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
}

function wppy_save_meta_box_data($post_id) {
    if (!isset($_POST['wppy_meta_box_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['wppy_meta_box_nonce'], 'wppy_save_meta_box_data')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    if (isset($_POST['zhuyin_status'])) {
        $zhuyin_status = sanitize_text_field($_POST['zhuyin_status']);
        update_post_meta($post_id, 'zhuyin_status', $zhuyin_status);
    }
    
    if (isset($_POST['zhuyin_tone_style'])) {
        $tone_style = sanitize_text_field($_POST['zhuyin_tone_style']);
        update_post_meta($post_id, 'zhuyin_tone_style', $tone_style);
    }
    
    if (isset($_POST['zhuyin_polyphone_mode'])) {
        $polyphone_mode = sanitize_text_field($_POST['zhuyin_polyphone_mode']);
        update_post_meta($post_id, 'zhuyin_polyphone_mode', $polyphone_mode);
    }
}
