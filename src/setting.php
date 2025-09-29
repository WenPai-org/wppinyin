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

    add_settings_field(
        'smart_filter_enabled',
        __('智能过滤', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_smart_filter_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'html_tag_filter_mode',
        __('HTML标签过滤', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_html_tag_filter_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'excluded_html_tags',
        __('排除的HTML标签', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_excluded_html_tags_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'included_html_tags',
        __('仅包含的HTML标签', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_included_html_tags_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'pinyin_engine',
        __('拼音引擎', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_pinyin_engine_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'tone_style',
        __('声调风格', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_tone_style_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'performance_strategy',
        __('性能策略', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_performance_strategy_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'polyphone_mode',
        __('多音字处理', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_polyphone_mode_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'pinyinpro_surname_mode',
        __('姓氏模式', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_pinyinpro_surname_mode_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'pinyinpro_custom_rules',
        __('自定义拼音规则', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_pinyinpro_custom_rules_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'frontend_processing_mode',
        __('前端引擎', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_frontend_processing_mode_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'enable_cache',
        __('拼音缓存', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_enable_cache_render',
        'wppy_content_add_pinyin',
        'wppy_content_add_pinyin_section'
    );

    add_settings_field(
        'quality_check',
        __('拼音质量检测', 'wppy-nihao'),
        __NAMESPACE__ . '\\wppy_quality_check_render',
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

function wppy_smart_filter_render() {
    $options = get_option('wppy_content_add_pinyin');
    $checked = isset($options['smart_filter_enabled']) && $options['smart_filter_enabled'] ? 'checked' : '';
    echo "<label><input type='checkbox' name='wppy_content_add_pinyin[smart_filter_enabled]' value='1' {$checked}> 启用智能过滤</label>";
    echo '<p class="description">' . __('自动识别并跳过已包含拼音注释的文本，避免重复注音', 'wppy-nihao') . '</p>';
}

function wppy_html_tag_filter_render() {
    $options = get_option('wppy_content_add_pinyin');
    $mode = isset($options['html_tag_filter_mode']) ? $options['html_tag_filter_mode'] : 'none';
    
    echo '<select name="wppy_content_add_pinyin[html_tag_filter_mode]">';
    echo '<option value="none"' . ($mode === 'none' ? ' selected' : '') . '>不过滤（默认）</option>';
    echo '<option value="exclude"' . ($mode === 'exclude' ? ' selected' : '') . '>排除指定标签</option>';
    echo '<option value="include"' . ($mode === 'include' ? ' selected' : '') . '>仅包含指定标签</option>';
    echo '</select>';
    echo '<p class="description">' . __('选择HTML标签过滤模式', 'wppy-nihao') . '</p>';
}

function wppy_excluded_html_tags_render() {
    $options = get_option('wppy_content_add_pinyin');
    $tags = isset($options['excluded_html_tags']) ? $options['excluded_html_tags'] : 'code,pre,script,style';
    echo "<input type='text' name='wppy_content_add_pinyin[excluded_html_tags]' value='{$tags}' class='regular-text'>";
    echo '<p class="description">' . __('用逗号分隔的HTML标签列表，这些标签内的内容将不会添加拼音（如：code,pre,script,style）', 'wppy-nihao') . '</p>';
}

function wppy_included_html_tags_render() {
    $options = get_option('wppy_content_add_pinyin');
    $tags = isset($options['included_html_tags']) ? $options['included_html_tags'] : 'p,h1,h2,h3,h4,h5,h6,div,span';
    echo "<input type='text' name='wppy_content_add_pinyin[included_html_tags]' value='{$tags}' class='regular-text'>";
    echo '<p class="description">' . __('用逗号分隔的HTML标签列表，仅这些标签内的内容会添加拼音（如：p,h1,h2,h3,h4,h5,h6,div,span）', 'wppy-nihao') . '</p>';
}

function wppy_tone_style_render() {
    $options = get_option('wppy_content_add_pinyin');
    $style = isset($options['tone_style']) ? $options['tone_style'] : 'symbol';
    
    $styles = array(
        'symbol' => '符号风格 (zhōng) - 推荐',
        'number' => '数字风格 (zhong4)',
        'none' => '无声调 (zhong)'
    );
    
    echo '<select name="wppy_content_add_pinyin[tone_style]">';
    foreach ($styles as $value => $label) {
        $selected = ($style === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
    echo '<p class="description">' . __('选择拼音的声调显示风格', 'wppy-nihao') . '</p>';
}

function wppy_pinyin_engine_render() {
    $options = get_option('wppy_content_add_pinyin');
    $engine = isset($options['engine_type']) ? $options['engine_type'] : 'overtrue';
    
    $engines = array(
        'overtrue' => array(
            'name' => 'Overtrue PHP 引擎',
            'status' => class_exists('Overtrue\Pinyin\Pinyin') ? 'available' : 'unavailable',
            'description' => '服务端处理'
        ),
        'pinyinpro' => array(
            'name' => 'PinyinPro JavaScript 引擎',
            'status' => 'available',
            'description' => '前端处理'
        )
    );
    
    echo '<div class="engine-selector-wrapper">';
    echo '<select name="wppy_content_add_pinyin[engine_type]" id="pinyin_engine_select" style="width: 100%; max-width: 400px;">';
    foreach ($engines as $value => $info) {
        $selected = ($engine === $value) ? ' selected' : '';
        $disabled = $info['status'] === 'unavailable' ? ' disabled' : '';
        echo "<option value='{$value}'{$selected}{$disabled}>{$info['name']} - {$info['description']}</option>";
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div class="engine-status" style="margin-top: 10px;">';
    echo '<div id="current_engine_status">';
    $current_engine_info = $engines[$engine];
    $status_color = $current_engine_info['status'] === 'available' ? '#46b450' : '#dc3232';
    echo '<span style="color: ' . $status_color . '; font-weight: bold;">当前引擎状态: ' . ($current_engine_info['status'] === 'available' ? '可用' : '不可用') . '</span>';
    echo '</div>';
    echo '</div>';
    
    echo '<div id="engine_details" style="margin-top: 15px;">';
    
    echo '<div id="overtrue_details" class="engine-detail-panel" style="display: ' . ($engine === 'overtrue' ? 'block' : 'none') . '; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
    echo '<h4 style="margin-top: 0; color: #0073aa;">Overtrue PHP 引擎</h4>';
    echo '<p>服务端处理，适合内容网站。</p>';
    echo '</div>';
    
    echo '<div id="pinyinpro_details" class="engine-detail-panel" style="display: ' . ($engine === 'pinyinpro' ? 'block' : 'none') . '; padding: 10px; background: #f0f8ff; border: 1px solid #0073aa; border-radius: 5px;">';
    echo '<h4 style="margin-top: 0; color: #0073aa;">PinyinPro JavaScript 引擎</h4>';
    echo '<p>前端处理，功能更丰富。</p>';
    echo '<p style="color: #d63638;">注意：仅在前台页面生效。</p>';
    echo '</div>';
    
    echo '</div>';
    
    echo '<script>
     document.getElementById("pinyin_engine_select").addEventListener("change", function() {
         var engine = this.value;
         var engines = ' . json_encode($engines) . ';
         
         document.getElementById("overtrue_details").style.display = engine === "overtrue" ? "block" : "none";
         document.getElementById("pinyinpro_details").style.display = engine === "pinyinpro" ? "block" : "none";
         
         var currentEngine = engines[engine];
         var statusColor = currentEngine.status === "available" ? "#46b450" : "#dc3232";
         var statusText = currentEngine.status === "available" ? "可用" : "不可用";
         document.getElementById("current_engine_status").innerHTML = 
             "<span style=\"color: " + statusColor + "; font-weight: bold;\">当前引擎状态: " + statusText + "</span>";
             
         toggleEngineSpecificSettings(engine);
     });
    
    function toggleEngineSpecificSettings(engine) {
        var pinyinProSettings = document.querySelectorAll(".pinyinpro-only");
        var overtrueSettings = document.querySelectorAll(".overtrue-only");
        
        pinyinProSettings.forEach(function(el) {
            el.style.display = engine === "pinyinpro" ? "block" : "none";
            var parentRow = el.closest("tr");
            if (parentRow) {
                parentRow.style.display = engine === "pinyinpro" ? "table-row" : "none";
            }
        });
        
        overtrueSettings.forEach(function(el) {
            el.style.display = engine === "overtrue" ? "block" : "none";
            var parentRow = el.closest("tr");
            if (parentRow) {
                parentRow.style.display = engine === "overtrue" ? "table-row" : "none";
            }
        });
    }
    
    document.addEventListener("DOMContentLoaded", function() {
          toggleEngineSpecificSettings(document.getElementById("pinyin_engine_select").value);
      });
     </script>';
}

function wppy_performance_strategy_render() {
    $options = get_option('wppy_content_add_pinyin');
    $strategy = isset($options['performance_strategy']) ? $options['performance_strategy'] : 'memory';
    
    $strategies = array(
        'memory' => '内存优化 (~400KB) - 推荐Web环境',
        'cached' => '全缓存 (~4MB) - 适合批处理',
        'smart' => '智能策略 (600KB-1.5MB) - 自动优化'
    );
    
    echo '<div class="overtrue-only">';
    echo '<select name="wppy_content_add_pinyin[performance_strategy]">';
    foreach ($strategies as $value => $label) {
        $selected = ($strategy === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
    echo '<p class="description">' . __('选择拼音处理的性能策略，内存优化策略最适合WordPress网站', 'wppy-nihao') . '</p>';
    echo '</div>';
}



function wppy_polyphone_mode_render() {
    $options = get_option('wppy_content_add_pinyin');
    $mode = isset($options['polyphone_mode']) ? $options['polyphone_mode'] : 'context';
    
    $modes = array(
        'context' => '智能上下文 - 推荐',
        'heteronym' => '显示所有读音',
        'chars' => '逐字符处理'
    );
    
    echo '<select name="wppy_content_add_pinyin[polyphone_mode]">';
    foreach ($modes as $value => $label) {
        $selected = ($mode === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
    echo '<p class="description">' . __('选择多音字的处理方式。智能上下文模式根据词汇语境选择最合适的读音', 'wppy-nihao') . '</p>';
}

function wppy_enable_cache_render() {
    $options = get_option('wppy_content_add_pinyin');
    $enabled = isset($options['enable_cache']) && $options['enable_cache'];
    $checked = $enabled ? 'checked' : '';
    
    echo "<label><input type='checkbox' name='wppy_content_add_pinyin[enable_cache]' value='1' {$checked}> 启用拼音缓存</label>";
    echo '<p class="description">' . __('缓存已处理的拼音结果，提高重复内容的处理速度', 'wppy-nihao') . '</p>';
}

function wppy_quality_check_render() {
    $options = get_option('wppy_content_add_pinyin');
    $enabled = isset($options['quality_check']) && $options['quality_check'];
    $checked = $enabled ? 'checked' : '';
    
    echo "<label><input type='checkbox' name='wppy_content_add_pinyin[quality_check]' value='1' {$checked}> 启用拼音质量检测</label>";
    echo '<p class="description">' . __('自动检测并报告拼音处理中的潜在问题，如覆盖率不足、错误拼音等', 'wppy-nihao') . '</p>';
    
    if ($enabled) {
        echo '<div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">';
        echo '<strong>质量统计：</strong><br>';
        echo '缓存命中率: ' . wppy_get_cache_hit_rate() . '%<br>';
        echo '平均处理时间: ' . wppy_get_avg_processing_time() . 'ms<br>';
        echo '拼音覆盖率: ' . wppy_get_pinyin_coverage() . '%';
        echo '</div>';
    }
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

function wppy_pinyinpro_surname_mode_render() {
    $options = get_option('wppy_content_add_pinyin');
    $enabled = isset($options['pinyinpro_surname_mode']) && $options['pinyinpro_surname_mode'];
    $checked = $enabled ? 'checked' : '';
    
    echo '<div class="pinyinpro-only">';
    echo "<label><input type='checkbox' name='wppy_content_add_pinyin[pinyinpro_surname_mode]' value='1' {$checked}> 启用姓氏模式</label>";
    echo '<p class="description">' . __('优化中文姓氏的拼音识别', 'wppy-nihao') . '</p>';
    echo '</div>';
}

function wppy_pinyinpro_custom_rules_render() {
    $options = get_option('wppy_content_add_pinyin');
    $rules = isset($options['pinyinpro_custom_rules']) ? $options['pinyinpro_custom_rules'] : '';
    
    echo '<div class="pinyinpro-only">';
    echo '<textarea name="wppy_content_add_pinyin[pinyinpro_custom_rules]" rows="4" cols="50" style="width: 100%; max-width: 500px;" placeholder="汉字=拼音，每行一个">' . esc_textarea($rules) . '</textarea>';
    echo '<p class="description">' . __('自定义特定汉字的拼音，格式：汉字=拼音', 'wppy-nihao') . '</p>';
    echo '</div>';
}

function wppy_frontend_processing_mode_render() {
    $options = get_option('wppy_content_add_pinyin');
    $mode = isset($options['frontend_engine']) ? $options['frontend_engine'] : 'off';
    
    $modes = array(
        'off' => '关闭前端处理',
        'on' => '启用前端处理'
    );
    
    echo '<div class="pinyinpro-only">';
    echo '<select name="wppy_content_add_pinyin[frontend_engine]" style="width: 100%; max-width: 400px;">';
    foreach ($modes as $value => $label) {
        $selected = ($mode === $value) ? ' selected' : '';
        echo "<option value='{$value}'{$selected}>{$label}</option>";
    }
    echo '</select>';
    echo '<p class="description">' . __('选择是否启用前端JavaScript处理', 'wppy-nihao') . '</p>';
    echo '</div>';
}
