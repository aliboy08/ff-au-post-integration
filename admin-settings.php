<?php
if ( ! defined( 'ABSPATH' ) ) die();
if( isset($_POST['save_changes']) ) {
    $options = [
        'au_post_enable_on_shipping_classes',
        'au_post_shipping_classes',
        'au_post_disable_other_methods',
        'au_post_disable_other_methods_names',
    ];
    foreach( $options as $option ) {
        update_option( $option, $_POST[$option] );
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<form action="" method="post">

<p>
    <label><input type="checkbox" name="au_post_enable_on_shipping_classes"<?php echo get_option('au_post_enable_on_shipping_classes') ? ' checked' : ''; ?>>Enable only on specific shipping classes</label>
</p>

<?php
// Shipping classes
$shipping_classes = get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => false ) );
if( $shipping_classes ) {
    $selected_shipping_classes = get_option('au_post_shipping_classes');
    if( !$selected_shipping_classes ) $selected_shipping_classes = [];
    echo '<p class="select-con">';
        echo '<select name="au_post_shipping_classes[]" class="select_shipping_classes" multiple>';
            foreach( $shipping_classes as $term ) {
                $selected = in_array( $term->term_id, $selected_shipping_classes ) ? ' selected' : '';
                echo '<option value="'. $term->term_id .'"'. $selected .'>'. $term->name .'</option>';
            }
        echo '</select>';
    echo '</p>';
}
?>

<br/>

<p>
    <label><input type="checkbox" name="au_post_disable_other_methods"<?php echo get_option('au_post_disable_other_methods') ? ' checked' : ''; ?>>Disable other shipping options if Australia Post method is present</label>
</p>


<?php
// Disable other shipping options if this method is active
$zones = WC_Shipping_Zones::get_zones();
$shipping_methods = [];
foreach( $zones as $zone ) {
    foreach( $zone['shipping_methods'] as $method ) {
        if( $method->title == 'FF Australia Post' ) continue;
        $shipping_methods[] = $method->title;
    }
}
$selected_methods_to_disable = get_option('au_post_disable_other_methods_names');
if( !$selected_methods_to_disable ) $selected_methods_to_disable = [];
echo '<p class="select-con">';
echo '<select name="au_post_disable_other_methods_names[]" class="select_methods_to_disable" multiple>';
    foreach( $shipping_methods as $method ) {
        $selected = in_array( $method, $selected_methods_to_disable ) ? ' selected' : '';
        echo '<option value="'. $method .'"'. $selected .'>'. $method .'</option>';
    }
echo '</select>';
echo '</p>';
?>

<br/>
<br/>
<input type="submit" name="save_changes" class="button button-primary" value="Save Changes">

</form>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    jQuery('.select_shipping_classes').select2({
        placeholder: 'Select shipping classes',
        width: 400,
    });

    jQuery('.select_methods_to_disable').select2({
        placeholder: 'Select shipping methods to disable if AU post option is present',
        width: 400,
    });
});
</script>