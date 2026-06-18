<?php

/**
 * Plugin Name: Counting Data Plugin Aye
 * Description: A plugin to create animated statistics with categories and items.
 * Author: Aye Chan Mon
 * Version: 1.2.0
 */
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'counting_data_plugin_enqueue_scripts');
add_action('init', 'counting_data_plugin');
add_shortcode('counting_data_plugin', 'counting_data_plugin_shortcode');

//Enqueue CSS and JS on the front end
if (!function_exists('counting_data_plugin_enqueue_scripts')):
    function counting_data_plugin_enqueue_scripts()
    {
        wp_enqueue_style('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/css/stats.css');
        wp_enqueue_script('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/js/stats.js', array(), 1.2.0, true);
    }
endif;
//WPBakery element mapping
if (!function_exists('counting_data_plugin')):
    function counting_data_plugin()
    {
        wpb_map(array(
            'name' => 'Counting Data',
            'base' => 'counting_data_plugin',
            'category' => 'Content',
            'description' => 'Animated statistic with categories and items',
            'icon' => plugin_dir_url(__FILE__) . 'assets/images/icon.svg',
            'params' => array(
                array(
                    'type' => 'textfield',
                    'heading' => 'Categories',
                    'param_name' => 'category_list',
                    'admin_label' => true,
                    'description' => 'Type your category label separated by commas. e.g. Justice Outcomes, Housing Outcomes, Clinical Outcomes',
                ),
                array(
                    'type' => 'param_group',
                    'heading' => 'Statistic Items',
                    'param_name' => 'items',
                    'description' => 'Add items in the order you want them displayed. Select the cateory from the drop down.',
                    'params' => array(
                        array(
                            'type' => 'dropdown',
                            'heading' => 'Category',
                            'param_name' => 'item_category',
                            'admin_label' => true,
                            'description' => 'Select the category this item belongs to. Add categories in the field above first.',
                            'value' => array('Select category' => ''),
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => 'Target Number',
                            'param_name' => 'target_number',
                            'admin_label' => true,
                            'value' => '',
                            'description' => 'Numbers only e.g. 15500 or 58',
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => 'Format',
                            'param_name' => 'format',
                            'description' => 'Select the format for the target number',
                            'value' => array(
                                'None (plain number, K-formatted if 1000+)' => 'none',
                                'Percentage(e.g., 58%)' => 'percent',
                                'Decimal K (e.g., 15.5 K)' => 'decimal',
                            )
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => 'Description',
                            'param_name' => 'description',
                            'description' => 'Please put description for the statistic item here. e.g.  Reduction in recidivism',
                        ),
                    ),
                ),
            ),
        ));
    }
endif;

//Shortcode function
if (!function_exists('counting_data_plugin_shortcode')):
    function counting_data_plugin_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'category_list' => '',
            'items' => '',
        ), $atts);

        $items = vc_param_group_parse_atts($atts['items']);
        error_log(print_r($items, true));

        if (empty($items)) {
            return '<p style="color: red;">Counting data: please add at least one item</p>';
        }

        $categories = array();
        foreach ($items as $item) {
            $cat = isset($item['category']) ? $item['category'] : '';
            if ($cat && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        ob_start(); ?>
        <div class="counting-data-wrapper">
            <?php
            $current_category = null;
            foreach ($items as $item) :
                $cat = isset($item['item_category']) ? trim($item['item_category']) : '';
                // CHANGED: print category heading only when it changes
                    if ($cat && $cat !== $current_category) :
                        $current_category = $cat;
            ?>
                    <p class="category"><?php echo esc_html($cat); ?></p>
            <?php endif;
            // Validate target is numeric, fallback to 0
                    $raw_target = isset($item['target']) ? trim($item['target']) : '0';
                    $target     = is_numeric($raw_target) ? esc_attr($raw_target) : '0';
                    $format = isset($item['format']) ? $item['format'] : 'none';
                    $desc   = isset($item['desc'])   ? esc_html($item['desc']) : '';
                    
                    $suffix   = '';
                    $decimals = '';
                    if ($format === 'percent') {
                        $suffix = '%';
                    } elseif ($format === 'decimal') {
                        $decimals = '1';
                    }
            ?>
            <div class="item">
                <div class="circle">
                    <span class="counter"
                        data-target="<?php echo $target; ?>"
                        <?php if ($suffix)   echo 'data-suffix="'   . esc_attr($suffix)   . '"'; ?>
                        <?php if ($decimals) echo 'data-decimals="' . esc_attr($decimals) . '"'; ?>>0</span>
                </div>
                <p class="desc"><?php echo $desc; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php return ob_get_clean();
}
endif;
