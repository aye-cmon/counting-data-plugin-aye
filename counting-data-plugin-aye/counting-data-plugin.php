add_shortcode();

function counting_data_plugin($atts){
    $atts = shortcode_atts(array(
        'items' => '',
    ), $atts);

    $items = vc_param_group_parse_atts($atts['items']);
}