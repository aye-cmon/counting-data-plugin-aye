<?php
/*
Plugin Name: Counting Data Plugin Aye
Description: A plugin to create animated statistics with categories and items.
Version: 1.0
*/
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'counting_data_plugin_enqueue_scripts');
add_action('vc_before_init', 'counting_data_plugin');
add_shortcode('counting_data_plugin', 'counting_data_plugin_shortcode');

//Enqueue CSS and JS on the front end
if (!function_exists('counting_data_plugin_enqueue_scripts')):
    function counting_data_plugin_enqueue_scripts()
    {
        wp_enqueue_style('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_script('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/js/script.js', array(), 1.0, true);
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
            'icon' => plugin_dir_url(__FILE__) . 'assets/images/icon.png',
            'params' => array(
                array(
                    'type' => 'param_group',
                    'heading' => 'Statistic Items',
                    'param_name' => 'items',
                    'description' => 'Add as many items as needed.Items sharing the same category will be grouped together.',
                    'params' => array(
                        array(
                            'type' => 'textfield',
                            'heading' => 'Category',
                            'param_name' => 'category',
                            'admin_label' => true,
                            'description' => 'e.g. Justice Outcomes. Items with the same category will be grouped together',
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => 'Target Number',
                            'param_name' => 'target_number',
                            'admin_label' => true,
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
            'items' => '',
        ), $atts);

        $items = vc_param_group_parse_atts($atts['items']);

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
            <?php foreach ($categories as $cat) :

                // Filter items for this category
                $cat_items = array_filter($items, function ($item) use ($cat) {
                    return isset($item['category']) && trim($item['category']) === $cat;
                });

            ?>
                <p class="category"><?php echo esc_html($cat); ?></p>

                <?php foreach ($cat_items as $item) :

                    // Validate target is numeric, fallback to 0
                    $raw_target = isset($item['target_number']) ? trim($item['target_number']) : '0';
                    $target     = is_numeric($raw_target) ? esc_attr($raw_target) : '0';

                    $format = isset($item['format']) ? $item['format'] : 'none';
                    $desc   = isset($item['description'])   ? esc_html($item['description']) : '';

                    // Map format to data attributes
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

            <?php endforeach; ?>
        </div>

<?php return ob_get_clean();
    }
endif;
