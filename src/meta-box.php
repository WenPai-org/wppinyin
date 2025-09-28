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
    
    $value = get_post_meta($post->ID, 'zhuyin_status', true);
    $value = $value ? $value : 'auto';
    
    $options = array(
        'on' => '开启',
        'off' => '关闭',
        'auto' => '随全局设置'
    );
    
    foreach ($options as $option_value => $label) {
        $checked = ($value === $option_value) ? 'checked' : '';
        echo "<label><input type='radio' name='zhuyin_status' value='{$option_value}' {$checked}> {$label}</label><br>";
    }
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
    
    if (!isset($_POST['zhuyin_status'])) {
        return;
    }
    
    $zhuyin_status = sanitize_text_field($_POST['zhuyin_status']);
    update_post_meta($post_id, 'zhuyin_status', $zhuyin_status);
}
