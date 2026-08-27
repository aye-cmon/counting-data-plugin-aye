<?php
/**
 * Plugin Name: HSA Counting Data Plugin
 * Description: A plugin to create animated statistics with categories and items.
 * Author: Aye Chan Mon
 * Version: 2.0.0
 */
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'counting_data_plugin_enqueue_scripts');
add_action('init', 'counting_data_plugin_register');
add_shortcode('counting_data_plugin',      'counting_data_plugin_shortcode');
add_shortcode('counting_data_plugin_item', 'counting_data_item_shortcode');
add_action('vc_before_init', 'counting_data_plugin_register_vc_classes');

// Enqueue CSS and JS on the front end
if (!function_exists('counting_data_plugin_enqueue_scripts')):
    function counting_data_plugin_enqueue_scripts()
    {
        wp_enqueue_style('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/css/stats.css');
        wp_enqueue_script('counting-data-plugin', plugin_dir_url(__FILE__) . 'assets/js/stats.js', array(), '2.0.0', true);
    }
endif;

// WPBakery element mapping
if (!function_exists('counting_data_plugin_register')):
    function counting_data_plugin_register()
    {
        //Creating a Parent(Container) element
        wpb_map(array(
            'name'        => 'Counting Data',
            'base'        => 'counting_data_plugin',
            'category'    => 'Content',
            'description' => 'Container for animated statistic items grouped by category',
            'icon'        => plugin_dir_url(__FILE__) . 'assets/images/icon.svg',
            // Declares this element as a container; only accepts counting_data_plugin_item children
            'as_parent'   => array('only' => 'counting_data_plugin_item'),
            'content_element'         => true,
            'show_settings_on_create' => false,
            'js_view'                 => 'VcColumnView',
            'params'      => array(
                array(
                    'type'        => 'textfield',
                    'heading'     => 'Extra CSS Class',
                    'param_name'  => 'el_class',
                    'description' => 'Optional extra class to add to the wrapper div.',
                ),
            ),
        ));

        // Creating a Child element
        wpb_map(array(
            'name'        => 'Counting Data Item',
            'base'        => 'counting_data_plugin_item',
            'category'    => 'Content',
            'description' => 'A single animated statistic. Place inside a Counting Data container.',
            'icon'        => plugin_dir_url(__FILE__) . 'assets/images/icon.svg',
            // Declares this element as a child; can only live inside counting_data_plugin
            'as_child'    => array('only' => 'counting_data_plugin'),
            'content_element' => true,
            'params'      => array(
                array(
                    'type'        => 'textfield',
                    'heading'     => 'Category',
                    'param_name'  => 'item_category',
                    'admin_label' => true,
                    'description' => 'Category label for this item (e.g. Justice Outcomes). Items that share the same category name will be grouped under one heading.',
                ),
                array(
                    'type'        => 'textfield',
                    'heading'     => 'Target Number',
                    'param_name'  => 'target_number',
                    'admin_label' => true,
                    'value'       => '',
                    'description' => 'Numbers only — e.g. 15500 or 58',
                ),
                array(
                    'type'        => 'dropdown',
                    'heading'     => 'Format',
                    'param_name'  => 'format',
                    'description' => 'Select the display format for the target number.',
                    'value'       => array(
                        'None (plain number, K-formatted if 1000+)' => 'none',
                        'Percentage (e.g. 58%)'                     => 'percent',
                        'Decimal K (e.g. 15.5 K)'                   => 'decimal',
                    ),
                ),
                array(
                    'type'        => 'textfield',
                    'heading'     => 'Description',
                    'param_name'  => 'description',
                    'description' => 'Label beneath the number — e.g. Reduction in recidivism',
                ),
            ),
        ));
    }
endif;

//Parent shortcode; renders the wrapper and processes child shortcodes
if (!function_exists('counting_data_plugin_shortcode')):
    function counting_data_plugin_shortcode($atts, $content = null)
    {
        $atts = shortcode_atts(array(
            'el_class' => '',
        ), $atts);

        if (empty(trim($content))) {
            return '<p style="color:red;">Counting Data: please add at least one Counting Data Item inside this element.</p>';
        }

        $extra_class = !empty($atts['el_class']) ? ' ' . esc_attr($atts['el_class']) : '';

        // do_shortcode processes all [counting_data_plugin_item] children
        ob_start();
        ?>
        <div class="counting-data-wrapper<?php echo $extra_class; ?>">
            <?php echo do_shortcode($content); ?>
        </div>
        <?php
        return ob_get_clean();
    }
endif;

// Child shortcode; renders a single statistic item. Category headings are injected via a shared static tracker so consecutive 
// items with the same category don't repeat the heading.

if (!function_exists('counting_data_item_shortcode')):
    function counting_data_item_shortcode($atts, $content = null)
    {
        $atts = shortcode_atts(array(
            'item_category' => '',
            'target_number' => '0',
            'format'        => 'none',
            'description'   => '',
        ), $atts);

        // Static variable tracks the last-rendered category within one page load
        static $current_category = null;

        $cat        = trim($atts['item_category']);
        $raw_target = trim($atts['target_number']);
        $target     = is_numeric($raw_target) ? esc_attr($raw_target) : '0';
        $format     = $atts['format'];
        $desc       = esc_html($atts['description']);

        $suffix   = '';
        $decimals = '';
        if ($format === 'percent') {
            $suffix = '%';
        } elseif ($format === 'decimal') {
            $decimals = '1';
        }

        ob_start();

        // Print category heading only when the category changes
        if ($cat && $cat !== $current_category) :
            $current_category = $cat;
            ?>
            <p class="category"><?php echo esc_html($cat); ?></p>
        <?php endif; ?>

        <div class="item">
            <div class="circle">
                <span class="counter"
                    data-target="<?php echo $target; ?>"
                    <?php if ($suffix)   echo 'data-suffix="'   . esc_attr($suffix)   . '"'; ?>
                    <?php if ($decimals) echo 'data-decimals="' . esc_attr($decimals) . '"'; ?>>0</span>
            </div>
            <p class="desc"><?php echo $desc; ?></p>
        </div>

        <?php
        return ob_get_clean();
    }
endif;

if (!function_exists('counting_data_plugin_register_vc_classes')):
function counting_data_plugin_register_vc_classes() {
    if (class_exists('WPBakeryShortCodesContainer')) {
        class WPBakeryShortCode_Counting_Data_Plugin extends WPBakeryShortCodesContainer {}
    }
    if (class_exists('WPBakeryShortCode')) {
        class WPBakeryShortCode_Counting_Data_Plugin_Item extends WPBakeryShortCode {}
    }
}
endif;