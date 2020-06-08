<?php
/*
Plugin Name: GFeed
Description: Generates an XML product data feed for use with the Google Merchant Center.
Version: 1.3.0
Author: Paul Bilinas
*/

namespace GFeed;

defined('ABSPATH') || exit;

require_once 'product_data_xml.php';

$googleSupportedAttributes = ['age_group', 'color', 'gender', 'material', 'pattern', 'size'];

add_action('gfeed_generate_xml', 'GFeed\getProductFeed');
function getProductFeed($echo=false) {
  if(!$echo) { ob_start(); }
  (new ProductDataXML())->generateXML();
  if(!$echo) { return ob_get_clean(); }
}
add_action('init', 'GFeed\registerShortcode');
function registerShortcode( ) {
  add_shortcode('gfeed-xml', 'GFeed\getProductFeed');
}

/*** ADMIN INTERFACE ***/

function addSettingsLink( $links ) {
  $url = esc_url( add_query_arg(
    'page','gfeed_settings',
    get_admin_url() . 'admin.php') );
  $links[] = "<a href='$url'>".__('Settings')."</a>";
  return $links;
}

add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'GFeed\addSettingsLink' );

add_action( 'admin_init', 'GFeed\initSettings' );

function initSettings( ) {
  register_setting(
    'gfeed_settings',   // option group
    'gfeed_options'     // option name
  );

  add_settings_section(
    'gfeed_section_defaults',             // id
    'Default Values',                     // title
    'GFeed\outputSectionDefaults',        // markup callback
    'gfeed_settings'                      // page
  );

  $options = get_option( 'gfeed_options' );

  // add default availability field
  add_settings_field(
    'gfeed_field_default_availability',       // id
    'Availability',                           // title
    'GFeed\fieldDefaultAvailability',         // callback
    'gfeed_settings',                         // page
    'gfeed_section_defaults',                 // section
    [
      'label_for' => 'gfeed_field_default_availability',
      'label_text' => 'Select a default availability',
      'class' => 'gfeed',
      'selected' => $options['gfeed_field_default_availability']?? 'out of stock'
    ]
  );

  add_settings_field(
    'gfeed_field_default_brand',    // id
    'Product Brand',                // title
    'GFeed\outputTextField',        // callback
    'gfeed_settings',               // page
    'gfeed_section_defaults',       // section
    [
      'label_for' => 'gfeed_field_default_brand',
      'label_text' => 'Enter a default product brand',
      'description' => 'The default product brand can be overridden on the product edit screen.',
      'placeholder' => "E.g., 'Nike'",
      'value' => $options['gfeed_field_default_brand']?? ''
    ]
  );

  // add default condition field
  add_settings_field(
    'gfeed_field_default_condition',      // id
    'Condition',                          // title
    'GFeed\fieldDefaultCondition',        // markup callback
    'gfeed_settings',                     // page
    'gfeed_section_defaults',             // section
    [
      'label_for' => 'gfeed_field_default_condition',
      'class' => 'gfeed',
      'label_text' => 'Select a default product condition',
      'selected' => $options['gfeed_field_default_condition']?? 'new'
    ]
  );
}

// this can be used to output content between the heading and fields
function outputSectionDefaults( $args ) { /* add markup if required */ }

function outputTextField( $args ) {
  // ensure that the optional parameters are defined in $args
  $args = array_merge(['label_text'=>'','description'=>'','placeholder'=>'','value'=>''], $args);
  $fieldId = esc_attr($args['label_for']);
  ?>
  <div class='gfeed-field-container'>
    <label for='<?= $fieldId; ?>' class='gfeed-field-label'><?= esc_html($args['label_text']); ?></label><br />
    <input type='text' name='gfeed_options[<?= $fieldId; ?>]' id='<?= $fieldId; ?>' placeholder='<?= esc_attr($args['placeholder']); ?>' size='50' value="<?= $args['value']; ?>" />
    <div class='gfeed-field-description'><?= esc_html($args['description']); ?></div>
  </div>
  <?php
}

function fieldDefaultAvailability( $args ) {
  $args = array_merge(['label_text'=>'','description'=>'','selected'=>'out of stock'], $args);
  $fieldName = esc_attr($args['label_for']);
  $class = esc_attr($args['class']);
  ?>
  <div class='gfeed-field-container'>
    <label class='gfeed-field-label'><?= esc_html($args['label_text']); ?></label><br />
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-availability-in-stock' value='in stock' class='<?= $class; ?>' <?= $args['selected']==='in stock'? 'checked':''; ?> />
    <label for='gfeed-availability-in-stock'>In Stock</label>
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-availability-out-of-stock' value='out of stock' class='<?= $class; ?>' <?= $args['selected']==='out of stock'? 'checked':''; ?> />
    <label for='gfeed-availability-out-of-stock'>Out of Stock</label>
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-availability-preorder' value='preorder' class='<?= $class; ?>' <?= $args['selected']==='preorder'? 'checked':''; ?> />
    <label for='gfeed-availability-preorder'>Preorder</label>
  </div>
  <?php
}

function fieldDefaultCondition( $args ) {
  $args = array_merge(['label_text'=>'','description'=>'','selected'=>'new'], $args);
  $fieldName = esc_attr($args['label_for']);
  $class = esc_attr($args['class']);
  ?>
  <div class='gfeed-field-container'>
    <label class='gfeed-field-label'><?= esc_html($args['label_text']); ?></label><br />
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-condition-new' value='new' class='<?= $class; ?>' <?= $args['selected']==='new'? 'checked':''; ?> />
    <label for='gfeed-condition-new'>New</label>
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-condition-used' value='used' class='<?= $class; ?>' <?= $args['selected']==='used'? 'checked':''; ?> />
    <label for='gfeed-condition-used'>Used</label>
    <input type='radio' name='gfeed_options[<?= $fieldName; ?>]' id='gfeed-condition-refurbished' value='refurbished' class='<?= $class; ?>' <?= $args['selected']==='refurbished'? 'checked':''; ?> />
    <label for='gfeed-condition-refurbished'>Refurbished</label>
  </div>
  <?php
}

add_action( 'admin_menu', function() {
  add_options_page(
    __('GFeed Settings'),
    __('GFeed'), 'manage_options',
    'gfeed_settings',
    'GFeed\buildSettingsPage'
  );
});

function buildSettingsPage( ) {
  if(!current_user_can('manage_options')) {
    return;
  }

  ?>
  <div class='wrap'>
    <div style='text-align:center'>
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    </div>
    <form action='options.php' method='post'>
  <?php

  // output security fields for the registered settings
  settings_fields( 'gfeed_settings' );
  // output sections and their fields
  do_settings_sections( 'gfeed_settings' );
  submit_button( 'Save Settings' );

  ?>
    </form>
  </div>
  <?php
}

/*** PRODUCT META ***/

// add tab to product data section
add_filter( 'woocommerce_product_data_tabs', 'GFeed\addProductDataTab' );
function addProductDataTab( $tabs ) {
  $tabs['gfeed'] = array(
    'label'   => 'GFeed',
    'target'  => 'gfeed_product_data',
    'class'   => []
  );
  return $tabs;
}

// add data panel
add_action( 'woocommerce_product_data_panels', 'GFeed\addProductDataPanel' );
function addProductDataPanel( ) {
  $id = get_the_id();
  $condition = get_post_meta( $id, 'gfeed_product_condition', true );
  $description = get_post_meta( $id, 'gfeed_product_description', true );
  ?>
  <div id="gfeed_product_data" class="panel woocommerce_options_panel hidden">
    <?php
    wp_nonce_field('gfeed_qMQ88', 'gfeed_product_data_nonce');

    woocommerce_wp_text_input([
      'id'          => 'gfeed_product_brand',
      'type'        => 'text',
      'label'       => 'Brand',
      'description' => 'Enter a brand for the product. If blank, the default will be used.',
      'value'       => get_post_meta( $id, 'gfeed_product_brand', true )
    ]);

    woocommerce_wp_text_input([
      'id'          => 'gfeed_product_gtin',
      'type'        => 'text',
      'label'       => 'GTIN',
      'description' => 'The manufacturer-supplied Global Trade Item Number. Enter a number with up to 50 numeric digits (no spaces or dashes). Leave blank if you do not have one.',
      'value'       => get_post_meta( $id, 'gfeed_product_gtin', true),
      'custom_attributes' => array(
        'pattern' => '^[0-9]*$',
        'maxlength' => 50
      )
    ]);

    woocommerce_wp_text_input([
      'id'          => 'gfeed_product_mpn',
      'type'        => 'text',
      'label'       => 'MPN',
      'description' => 'The Manufacturer Part Number. Enter up to 70 alphanumeric characters. Leave blank if you do not have one.',
      'value'       => get_post_meta( $id, 'gfeed_product_mpn', true ),
      'custom_attributes' => array(
        'pattern' => '^[0-9a-zA-Z]*$',
        'maxlength' => 70
      )
    ]);
    ?>
    <p class='form-field gfeed_product_condition_field'>
      <label>Condition</label>
      <input type='radio' name='gfeed_product_condition' value='new' <?= $condition==='new'? 'checked':''; ?> />
      <span class='gfeed-radio-label'>New</span>
      <input type='radio' name='gfeed_product_condition' value='used' <?= $condition==='used'? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Used</span>
      <input type='radio' name='gfeed_product_condition' value='refurbished' <?= $condition==='refurbished'? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Refurbished</span>
      <span class='description'>The product's condition. If blank, the default will be used.</span>
    </p>

    <?php
    $preorder_checked = get_post_meta( $id, 'gfeed_product_preorder', true );
    ?>
    <p class='form-field'>
      <label>Preorder</label>
      <input type='checkbox' id='gfeed-preorder-cb' name='gfeed_product_preorder' value='preorder' <?= $preorder_checked? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Set item availability to preorder?</span>
      <span class='description'>This will force the availability attribute to 'preorder', ignoring the stock quantity and default availability.</span>
    </p>

    <p class='form-field'>
      <?php
      woocommerce_wp_text_input([
        'id'          => 'gfeed_product_availability_date',
        'type'        => 'date',
        'label'       => 'Availability Date',
        'description' => 'The date that a preordered item becomes available for delivery. Only used when availability is set to preorder.',
        'value'       => get_post_meta( $id, 'gfeed_product_availability_date', true ),
        'custom_attributes' => array(
          'pattern' => '^\d{4}-\d{2}-\d{2}$'
        )
      ]);
      ?>
    </p>

    <p class='form-field gfeed_product_description_field'>
      <label>Description</label>
      <textarea maxlength='5000' name='gfeed_product_description'><?= esc_textarea($description); ?></textarea>
      <span class='description'>Accurately describe your product and match the description from your landing page.</span>
    </p>

    <?php
    $multipackChecked = get_post_meta( $id, 'gfeed_product_multipack', true );
    $multipackQuantity = get_post_meta( $id, 'gfeed_product_multipack_quantity', true );
    ?>
    <p class='form-field'>
      <label>Multipack</label>
      <input type='checkbox' id='gfeed-multipack-cb' name='gfeed_product_multipack' value='yes' <?= $multipackChecked? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Is this a multipack item?</span>
      <span class='description'>It is a multipack if you packaged multiple identical items for sale.</span>
      <div id='multipack-quantity-container' <?= $multipackChecked? '':'style="display:none;"'; ?>>
        <?php
        woocommerce_wp_text_input([
          'type'  => 'number',
          'id'    => 'gfeed-multipack-quantity-input',
          'name'  => 'gfeed_product_multipack_quantity',
          'value' => $multipackQuantity? $multipackQuantity : '0',
          'label' => 'Quantity per Pack',
          'style' => 'width:5em;',
          'min' => 2
        ]);
        ?>
      </div>
    </p>

    <!-- Bundle -->
    <?php
    $bundleChecked = get_post_meta( $id, 'gfeed_product_bundle', true );
    ?>
    <p class='form-field'>
      <label>Bundle</label>
      <input type='checkbox' id='gfeed-bundle-cb' name='gfeed_product_bundle' value='yes' <?= $bundleChecked? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Is this a bundle?</span>
      <span class='description'>It is a bundle if you have combined separate products and there is a clear main product. For example, a camera sold with a lens and a memory card.</span>
    </p>

    <!-- Adult Content -->
    <?php
    $adultChecked = get_post_meta( $id, 'gfeed_adult_product', false );
    ?>
    <p class='form-field'>
      <label>Adults Only</label>
      <input type='checkbox' id='gfeed-adult-cb' name='gfeed_adult_product' value='yes' <?= $adultChecked? 'checked':''; ?> />
      <span class='gfeed-radio-label'>Does this product listing feature adult content?</span>
    </p>

    <!-- Google Product Category -->
    <?php
    $googleCategory = get_post_meta( $id, 'gfeed_google_product_category', true );
    ?>
    <p class='form-field'>
      <label>Google Product Category (optional)</label>
      <input type='text' id='gfeed-google-cat' name='gfeed_google_product_category' value='<?= esc_attr($googleCategory); ?>' maxlength='500' />
      <span class='description'>Fill this field if you want to override Google's assigned product category. For more information, see <?= esc_url('https://support.google.com/merchants/answer/6324436'); ?>.</span>
    </p>

    <!-- google product attribute mapping -->
    <?php
    maybeInsertGoogleAttributeMapping( $id );
    ?>

    <!-- Additional images -->
    <p class='form-field'>
      <h4>Additional Images</h4>
    <?php
    $extraImages = get_post_meta( $id, "gfeed_product_extra_image", true );
    if(!$extraImages) { $extraImages = []; }
    if(count($extraImages) < 10): ?>
      <button type='button' id='gfeed-add-product-image'>Add Another Image</button>
    <?php endif; ?>
    </p>

    <table id='gfeed-extra-images-table'>
      <thead>
        <tr><th>Image URL</th><th></th></tr>
      </thead>
      <tbody>
        <?php for($i = 0; $i < 10; $i++):
          $value = $extraImages[$i]?? ''; ?>
          <tr id="gfeed-image-row-<?= $i; ?>"<?= $value? '' : " style='display:none;'"; ?>>
            <td>
              <input type='text' name='<?= "gfeed_product_extra_image[$i]"; ?>' value='<?= $value; ?>' size='200' maxlength='2000' pattern='^(http(s)?:\/\/)?(((\d+\.){3}\d+(:\d+)?)|(((?<=\w)\.)?[\w-]{1,63})+)(\/.*)*' />
            </td><td>
              <?php insertCloseIcon( "class='gfeed-close-icon' data-index='$i'" ); ?>
            </td>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <?php
    insertProductDataPanelScript(count($extraImages));
    ?>
  </div>
  <?php
}

function maybeInsertGoogleAttributeMapping( $id ) {
  global $googleSupportedAttributes;

  $product = wc_get_product( $id );
  if(!$product || !$product->is_type('variable')) {
    return;
  }

  $attrMap = $product->get_meta( 'gfeed_attr_map', true );
  $productAttrs =  array_keys($product->get_variation_attributes());

  ?>
  <h4>Google Product Attributes</h4>
  <span class='description'>Optionally map your product&apos;s attributes to Google&apos;s supported attributes.</span>
  <table id='gfeed-attr-map-table'>
    <thead>
      <tr><th>Google Attribute</th><th>Product Attribute</th><td></td></tr>
    </thead><tbody>
  <?php foreach($googleSupportedAttributes as $attr):
    $assigned = array_key_exists($attr, $attrMap);
    $rowId = "gfeed-attr-map-row-$attr";
    ?>
      <tr id='<?= $rowId; ?>' <?= $assigned? '':"style='display:none'"; ?>>
        <td> <?= $attr; ?> </td>
        <td class='gfeed-product-attr-cell'> <?= $assigned? stripAttributePrefix($attrMap[$attr]) : ''; ?> </td>
        <td> <?php insertCloseIcon( "class='gfeed-close-icon' data-attr='$attr'" ); ?> </td>
        <input type='hidden' name='gfeed_attr_map[<?= $attr; ?>]' <?= $assigned? "value='{$attrMap[$attr]}'" : "disabled"; ?> />
      </tr>
  <?php endforeach; ?>
      <tr>
        <td><select id='gfeed-google-attr-select'>
          <?php foreach($googleSupportedAttributes as $googleAttr): ?>
            <option value='<?= $googleAttr; ?>'><?= $googleAttr; ?></option>
          <?php endforeach; ?>
        </select></td>
        <td><select id='gfeed-product-attr-select'>
          <?php foreach($productAttrs as $prodAttr): ?>
            <option value='<?= $prodAttr; ?>'><?= stripAttributePrefix($prodAttr); ?></option>
          <?php endforeach; ?>
        </select></td>
        <td>
          <button id='gfeed-add-attr-map-btn' type='button'>Add New</button>
        </td>
      </tr>
    </tbody>
  </table>

  <?php
}

function stripAttributePrefix($attr) {
  if(strpos($attr, 'pa_') === 0) {
    return substr($attr, 3);
  } else {
    return $attr;
  }
}

function insertCloseIcon($attrs) {
  ?>
  <svg <?= $attrs?? ''; ?> viewBox="0 0 100 100">
    <path d="M 50 5 A 45 45 0 0 0 50 95 A 45 45 0 0 0 50 5" stroke="red" stroke-width="8" />
    <path d="M 30 30 L 70 70 M 70 30 L 30 70" stroke-width="8" stroke-linecap="round" />
  </svg>
  <?php
}

function insertProductDataPanelScript($numExtraImages) {
  ?>
  <script>
  // additional images event handlers
  var count = <?= $numExtraImages; ?>;
  var showing = new Array();
  showing.length = 10;
  showing.fill(true,0,count);
  showing.fill(false,count);
  // on add show the first hidden row
  jQuery('#gfeed-add-product-image').on('click', function() {
    var table = document.querySelector('#gfeed-extra-images-table');
    // var next = parseInt(table.dataset.next);
    var next = showing.findIndex(el => !el);
    if(next !== -1) {
      let id = '#gfeed-image-row-'+next;
      jQuery(id).show();
      showing[next] = true;
    }
  });
  // on close, clear and hide the row
  jQuery('#gfeed-extra-images-table .gfeed-close-icon').on('click', evt => {
    var index = evt.target.dataset.index;
    var id = '#gfeed-image-row-'+index;
    var $row = jQuery(id);
    $row.find('input').prop('value','');
    $row.hide();
    showing[index] = false;
  });

  // show or hide the multipack quantity field as the checkbox state changes
  jQuery('#gfeed-multipack-cb').on('change', () => {
    if(event.target.checked) {
      jQuery('#multipack-quantity-container').show();
    } else {
      jQuery('#multipack-quantity-container').hide();
    }
  });

  function stripAttributePrefix(attr) {
    if(attr.indexOf('pa_') === 0) {
      return attr.substring(3);
    } else {
      return attr;
    }
  }

  jQuery('#gfeed-add-attr-map-btn').on('click', evt => {
    var googleAttr = jQuery('#gfeed-google-attr-select option:checked').val();
    var productAttr = jQuery('#gfeed-product-attr-select option:checked').val();
    var row = jQuery('#gfeed-attr-map-row-'+googleAttr);
    var input = row.find('input[type=hidden]');
    var cell = row.find('td.gfeed-product-attr-cell');
    cell.text(stripAttributePrefix(productAttr));
    input.val(productAttr);
    input.prop('disabled', false);
    row.show();
  });

  jQuery('#gfeed-attr-map-table .gfeed-close-icon').on('click', evt => {
    var googleAttr = evt.target.dataset.attr;
    var row = jQuery('#gfeed-attr-map-row-'+googleAttr);
    var input = row.find('input[type=hidden]');
    var cell = row.find('td.gfeed-product-attr-cell');
    row.hide();
    cell.text('');
    input.val('');
    input.prop('disabled', true);
  });
  </script>
  <?php
}

add_action( 'woocommerce_process_product_meta', 'GFeed\saveProductData' );
function saveProductData( $id ) {
  global $googleSupportedAttributes;

  if(!current_user_can('edit_posts')) {
    return;
  }

  // check the nonce
  if(!isset($_POST['gfeed_product_data_nonce']) ||
     !wp_verify_nonce($_POST['gfeed_product_data_nonce'], 'gfeed_qMQ88')) {
       error_log('no nonce or failed verification');
       return;
  }

  $product = wc_get_product($id);

  updateMeta( $product, [
    'gfeed_product_brand'         =>  ['sanitise'=>'text'],
    'gfeed_product_gtin'          =>  ['sanitise'=>'number', 'maxlength'=>50],
    'gfeed_product_mpn'           =>  ['sanitise'=>'regex', 'pattern'=>"/^[0-9a-zA-Z]$/", 'maxlength'=>70],
    'gfeed_product_condition'     =>  ['sanitise'=>['new','used','refurbished']],
    'gfeed_product_description'   =>  ['sanitise'=>'textarea', 'maxlength'=>5000],
    'gfeed_product_multipack'     =>  ['sanitise'=>['yes','no']],
    'gfeed_product_multipack_quantity'  =>  ['sanitise'=>'number'],
    'gfeed_product_bundle'        =>  ['sanitise'=>['yes','no']],
    'gfeed_adult_product'         =>  ['sanitise'=>['yes','no']],
    'gfeed_google_product_category'     =>  ['sanitise'=>'text', 'maxlength'=>500],
    'gfeed_product_preorder'      =>  ['sanitise'=>['preorder']],
    'gfeed_product_availability_date'   =>  ['sanitise'=>'ISO8601']
  ]);

  // handle attribute map
  if(isset($_POST['gfeed_attr_map'])) {
    // remove entries with possibly dodgy keys
    $rawData = array_intersect_key($_POST['gfeed_attr_map'], array_flip($googleSupportedAttributes));
    // sanitise the values
    $map = array_map(function($productAttr) {
      return sanitize_text_field($productAttr);
    }, $rawData);
    // save the data
    $product->update_meta_data('gfeed_attr_map', $map);
  }

  // handle extra images
  if(isset($_POST['gfeed_product_extra_image'])) {
    $input = array_filter($_POST['gfeed_product_extra_image'], function($url) {
      return strlen($url) > 0;
    });
    // get escaped values only
    $imageUrls = array_map(function($url) {
      return esc_url($url);
    }, array_values($input));
    $product->update_meta_data(
      'gfeed_product_extra_image',
      array_slice($imageUrls, 0, 10));
  }

  // save metadata
  $product->save();
}

/*
* Checks $_POST for the keys given in $data. Updates the metadata for $product accordingly.
* Params:
* $product: an instance of WC_Product or a subclass.
* $data: (array) a list of keys to search for together with sanitisation types and default values.
* The required structure of $data is:
* ["key1"=>["sanitise"=>"<method1>", "default"=>"<default1>", "pattern"=>"regex pattern", "key2"=>[...], ...]
* where 'key' is both the name to search for in $_POST and the metadata key to update on $product,
* 'sanitise' is a method used to sanitise; i.e., one of 'text', 'textarea', 'ISO8601',
*   'number', 'regex' (in which case 'pattern' must be specified) or an array containing valid values,
* 'default' (optional) is the value to update $product with if the name given by 'key' is not found in $_POST -
*   if 'default' is not specified and the name is not found, existing metadata with the supplied key will be deleted,
* 'pattern' (optional) required if 'sanitise' is set to 'regex' in which case it should be a regular expression,
* 'maxlength' (optional) a positive number.
*/
function updateMeta( $product, $data ) {
  if(!$data || !is_array($data) || empty($data)) {
    return;
  }
  foreach($data as $key=>$specs) {
    if(isset($_POST[$key])) {
      $value = $_POST[$key];
      // truncate if required
      if(array_key_exists('maxlength', $specs)) {
        $value = substr($value, 0, $specs['maxlength']);
      }
      // sanitise
      if(array_key_exists('sanitise', $specs) && $specs['sanitise']) {
        $pattern = array_key_exists('pattern', $specs)? $specs['pattern'] : '';
        $cleaned = applySanitation( $value, $specs['sanitise'], $pattern );
      } else {
        $cleaned = applySanitation($value);
      }
      // store
      $product->update_meta_data( $key, $cleaned );
    } else if(array_key_exists('default', $specs)) {
      $product->update_meta_data( $key, $specs['default'] );
    } else {
      $product->delete_meta_data( $key );
    }
  }
}

function applySanitation($data, $method='text', $pattern='', $strict=true) {
  if(!$data) {
    return null;
  }
  if(is_array($method)) {
    return in_array($data, $method, $strict)? $data : null;
  }
  switch(strtolower($method)) {
    case 'text':
      return sanitize_text_field($data);
    case 'textarea':
      return sanitize_textarea_field($data);
    case 'iso8601':
      return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data, $matches)? $matches[0] : null;
    case 'number':
      return preg_match('/^\d+$/', $data, $matches)? $matches[0] : null;
    case 'regex':
      if($pattern) {
        return preg_match($pattern, $data, $matches)? $matches[0] : null;
      } else {
        return null;
      }
    default:
      return null;
  }
}

/*** STYLES ***/

add_action( 'admin_enqueue_scripts', 'GFeed\registerAdminStyles' );
function registerAdminStyles( $hook ) {
  if( $hook === 'settings_page_gfeed_settings' || $hook === 'post.php' ) {
    wp_enqueue_style( 'gfeed_settings_styles', plugins_url('styles.css', __FILE__), array(), 0.10 );
  }
}

/*** PAGE ***/

add_filter( 'page_template', 'GFeed\addFeedTemplate' );
function addFeedTemplate( $page_template ) {
  if(is_page('gfeed-xml')) {
    $page_template = dirname(__FILE__) . '/page-gfeed.php';
  }
  return $page_template;
}

add_action( 'init', 'GFeed\addPage' );
function addPage( ) {
  if(!get_page_by_title('GFeed XML')) {
    $postarr = array(
      'post_title'    => 'GFeed XML',
      'post_content'  => '',
      'post_type'     => 'page',
      'post_status'   => 'publish',
      'guid'          => 'gfeed-xml',
      'meta_input'    => ['_wp_page_template'=>'gfeed-template']
    );
    wp_insert_post($postarr);
  }
}
