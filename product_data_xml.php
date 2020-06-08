<?php

namespace GFeed;

defined('ABSPATH') || exit;

class ProductDataXML {

  static $supportedAttributes = ['age_group', 'color', 'gender', 'material', 'pattern', 'size'];

  private $dom = null;
  private $root = null;
  private $options = null;

  public function __construct() {
    $this->dom = new \DomDocument('1.0', 'UTF-8');
    $this->root = $this->dom->createElement('feed');
    $this->root->setAttribute('xmlns','http://www.w3.org/2005/Atom');
    $this->root->setAttribute('xmlns:g','http://base.google.com/ns/1.0');
    $this->dom->appendChild($this->root);

    $this->options = get_option('gfeed_options');
  }

  public function generateXML( ) {
    $queryArgs = ['status'=>'publish', 'limit'=>-1];

    $filterByCat = $this->options['gfeed_field_enable_filter_by_category']?? false;
    if($filterByCat) {
      $includedCats = get_option( 'gfeed_field_included_categories', [] );
      $queryArgs['category'] = $includedCats;
    }

    // get data
    $queryArgs['type'] = 'simple';
    $simpleProducts = wc_get_products($queryArgs);
    $queryArgs['type'] = 'variable';
    $variableProducts = wc_get_products($queryArgs);

    // build xml
    $this->addElement($this->root, 'title', get_option('blogname'));
    $this->addElement($this->root, 'link', null, ['rel'=>'self', 'href'=>esc_url(site_url())]);
    $this->addElement($this->root, 'updated', date_create()->format("Y-m-d\TH:i:sP"));

    // add entries for simple products
    foreach($simpleProducts as $product) {
      $entry = $this->createEntry($product);
      $this->addElement($entry, 'g:id', $product->get_id());
      $this->root->appendChild($entry);
    }

    // add entries for variable products
    foreach($variableProducts as $variable) {
      $this->addVariations($variable);
    }

    // output the xml
    echo $this->dom->saveXML();
  } // generateXML

  private function addVariations($product) {
    // get product attributes and check for unmapped attributes
    $attributes = array_keys($product->get_variation_attributes());
    $attributeMap = $product->get_meta( 'gfeed_attr_map', true );
    $unmapped = $attributeMap? array_diff( $attributes, array_values($attributeMap)) : $attributes;

    // if all attributes are mapped to supported Google attributes,
    // use the product id as the item_group_id.
    $groupId = empty($unmapped)? $product->get_id() : null;

    // loop through variations, adding an entry for each
    $variations = $product->get_available_variations();
    foreach($variations as $variation) {
      $productVariation = new \WC_Product_Variation($variation['variation_id']);

      $entry = $this->createEntry($product, $productVariation);
      $this->addElement($entry, 'g:id', $variation['variation_id']);
      if($groupId) {
        $this->addElement($entry, 'g:item_group_id', $groupId);
      }
      $this->maybeAppendAttributes($entry, $productVariation, $attributeMap);
      $this->root->appendChild($entry);
    }
  }

  private function maybeAppendAttributes($addTo, $variation, $attributeMap) {
    if(empty($attributeMap)) {
      return;
    }
    foreach($attributeMap as $googleAttr=>$productAttr) {
      $value = $variation->get_attribute($productAttr);
      if(is_wp_error($value)) {
        error_log("Could not retrieve value of $productAttr for ".$variation->get_title());
        error_log($value->get_error_message());
        continue;
      } else if($value) {
        $this->addElement($addTo, "g:$googleAttr", $value);
      }
    }
  }

  /*
  * Creates a DomElement and prefills it with the available product data.
  * The g:id and g:item_group_id tags are not added here.
  * Params:
  ** $product - a WC_Product, WC_Product_Simple or WC_Product_Variable instance.
  ** $variation - a WC_Product_Variation instance.
  */
  private function createEntry($product, $variation=null) {
    $entry = $this->dom->createElement('entry');
    $prodOrVar = $variation?? $product;

    $this->addElement($entry, 'g:title', $variation? $this->getVariationTitle($variation) : $product->get_title());
    $this->addElement($entry, 'g:link', esc_url($product->get_permalink()));
    $this->addElement($entry, 'g:condition', $this->getCondition($product));
    $this->addElement($entry, 'g:adult', $this->getAdult($product));

    // description
    $desc = $this->dom->createCDATASection(substr($this->getDescription($product), 0, 4988));
    $elem = $this->dom->createElement('g:description');
    $elem->appendChild($desc);
    $entry->appendChild($elem);

    // add price
    if($prodOrVar->is_on_sale()) {
      $this->addElement($entry, 'g:price', $prodOrVar->get_regular_price());
      $this->addElement($entry, 'g:sale_price', $prodOrVar->get_sale_price());
      $from = $prodOrVar->get_date_on_sale_from();
      $to   = $prodOrVar->get_date_on_sale_to();
      if($from && $to) {
        $salePeriod = $from->date_i18n(DATE_ATOM).'/'.$to->date_i18n(DATE_ATOM);
        $this->addElement($entry, 'g:sale_price_effective_date', $salePeriod);
      }
    } else {
      $this->addElement($entry, 'g:price', $prodOrVar->get_price());
    }

    $this->appendAvailability($entry, $product, $variation);
    $this->appendIdentifiers($entry, $product);

    $this->maybeAppendMultipack($entry, $product);
    if($product->get_meta('gfeed_product_bundle', 'no') === 'yes') {
      $this->addElement($entry, 'g:is_bundle', 'yes');
    }
    $this->maybeAppendGoogleProductCategory($entry, $product);

    // images
    $imageId = $prodOrVar->get_image_id();
    if(!is_null($imageId)) {
      $imageData = wp_get_attachment_image_src($imageId,'rp_thumbnail_size');
      if(!empty($imageData)) {
        $this->addElement($entry, 'g:image_link', esc_url($imageData[0]));
      }
    }
    $this->appendExtraImages($entry, $product);

    return $entry;
  }

  private function getVariationTitle($variation) {
    $title = $variation->get_title();
    $rawAttrs = $variation->get_variation_attributes();
    $attrVals = array_map(function($val) {
      return ucfirst($val);
    }, array_values($rawAttrs));
    $title .= ' - '.implode(', ', $attrVals);
    return $title;
  }

  private function addElement($addTo, $name, $content, $attr=null) {
    $newTag = $this->dom->createElement($name, esc_html($content));
    if($attr && !empty($attr)) {
      foreach($attr as $key=>$value) {
        $newTag->setAttribute($key, esc_attr($value));
      }
    }
    return $addTo->appendChild($newTag);
  } // addElement

  private function maybeAppendGoogleProductCategory( $addTo, $product ) {
    $cat = $product->get_meta( 'gfeed_google_product_category', true );
    if(!$cat) {
      return;
    }
    $this->addElement( $addTo, 'g:google_product_category', esc_html($cat) );
  }

  private function maybeAppendMultipack($addTo, $product) {
    if($product->get_meta('gfeed_product_multipack', true)) {
      $qty = $product->get_meta('gfeed_product_multipack_quantity', true);
      if($qty && intval($qty) >= 2) {
        $this->addElement($addTo, 'g:multipack', $qty);
      }
    }
  }

  private function appendExtraImages($addTo, $product, $variation=null) {
    $extra = $product->get_meta('gfeed_product_extra_image', true);
    if(!$extra) { return; }
    for($i=0; $i<count($extra); $i++) {
      if($extra[$i]) {
        $this->addElement($addTo, 'g:additional_image_link', esc_url($extra[$i]));
      }
    }
  }

  private function appendIdentifiers($addTo, $product, $variation=null) {
    // todo: check for variation data
    $gtin   = $product->get_meta('gfeed_product_gtin', true);
    if($gtin) {
      $this->addElement($addTo, 'g:gtin', esc_html($gtin));
    }
    $mpn    = $product->get_meta('gfeed_product_mpn', true);
    if($mpn) {
      $this->addElement($addTo, 'g:mpn', esc_html($mpn));
    }
    $brand  = $this->getBrand($product);
    if($brand) {
      $this->addElement($addTo, 'g:brand', esc_html($brand));
    }
    // todo: add check for product type
    // media: submit no if no gtin exists
    // apparel: submit no if no brand is found
    if(!$gtin && (!$mpn || !$brand)) {
      $this->addElement($addTo, 'g:identifier_exists', 'no');
    }
  }

  private function appendAvailability( $addTo, $product, $variation=null ) {
    $preorder = $product->get_meta( 'gfeed_product_preorder', true );
    if($preorder) {
      $this->addElement($addTo, 'g:availability', 'preorder');
      $date = $product->get_meta( 'gfeed_product_availability_date', true );
      if($date) {
        $this->addElement($addTo, 'g:availability_date', $date);
      }
      return;
    }

    $prodOrVar = $variation?? $product;
    $managedStock = $prodOrVar->get_manage_stock();
    if($managedStock) {
      $q = $prodOrVar->get_stock_quantity();
      if($q === null) {
        $avail = $this->options['gfeed_field_default_availability']?? 'out of stock';
      } else {
        $avail = $q > 0? 'in stock' : 'out of stock';
      }
    } else {
      $stockStatus = $prodOrVar->get_stock_status();
      $avail = strcasecmp($stockStatus, 'instock') === 0? 'in stock' : 'out of stock';
    }

    $this->addElement($addTo, 'g:availability', $avail);
  }

  private function getAdult($product) {
    $val = $product->get_meta('gfeed_adult_product', 'no');
    return $val === 'yes'? 'yes' : 'no';
  }

  private function getDescription($product) {
    // todo: check for variation data
    $desc = $product->get_meta('gfeed_product_description', true);
    if(!$desc) {
      $desc = $product->get_short_description();
    }
    return $desc;
  }

  private function getCondition($product) {
    $condition = strtolower($product->get_meta('gfeed_product_condition', true));
    if(!$condition) {
      $condition = $this->options['gfeed_field_default_condition']?? 'new';
    }
    return $condition;
  }

  private function getBrand($product) {
    $brand = $product->get_meta('gfeed_product_brand', true);
    if(!$brand) {
      $brand = $this->options['gfeed_field_default_brand']?? '';
    }
    return $brand;
  }

} // class ProductDataXML
