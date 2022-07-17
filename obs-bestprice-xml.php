<?php
/**
* Plugin Name: OBS Bestprice XML
* Plugin URI:
* Description: Export woocommerce products as xml for bestprice.
* Version: 2.0.1
* Author: OBS Technologies
* Author URI: https://obstechnologies.com/
* License:
* License URI:
*/

use OBS\OBSXMLExport;

require_once( 'vendor/autoload.php' );


class OBSBestpriceXML extends OBSXMLExport{
	
	function __construct() {
		
		$this->name = 'OBS Bestprice XML';
		$this->slug = 'obs_bestprice_xml';
		$this->pluginSlug = 'obs-bestprice-xml';
		$this->pluginPath = __FILE__;
		
		$this->settings_fields = [];
		$this->names = [];
		
        parent::__construct();
		
	}
	
	public function product_xml($product, $variation_id = null, $sizes = null){
		
		$parent = $product;
		if($variation_id){
			$product = wc_get_product($variation_id);
		}
		
		$name = $this->get_product_name($parent, $product);
		if($sizes && count($sizes)){
			$size = $this->get_sizes($product, $sizes);
		}else{
			$size = $this->get_sizes($product);
		}
		if(($size == null || empty($size)) && in_array($name, $this->names)){
			return;
		}
		$price = $this->get_regular_prices($product);
		$sale_price = $this->get_sale_prices($product);
		
		$this->names[] = $name;
		$this->xml->startElement('product');
		
		$this->output_xml('productId' , $this->get_product_id($product));
		$this->output_xml('title' , $name, true);
		$this->output_xml('price', $price);
		
		//PHOTOS
		$this->xml->startElement('imagesURL');
		$gallery = $this->get_gallery($parent);
		$this->output_xml('img1' , $this->get_image($product, $parent), true);
		if(count($gallery)){
			foreach($gallery as $key => $image){
				$this->output_xml('img'.($key+2) , $image , true);
			}
		}
		$this->xml->endElement();
		// END PHOTOS
		
		$this->output_xml('productURL' , $this->get_link($product, $size), true);
		$this->output_xml('category_name' , $this->get_categories_for_bestprice($parent), true);
		$this->output_xml('color' , $this->get_colors($product), true);
		$this->output_xml('manufacturer' , $this->get_brand($parent), true);
		$this->output_xml('stock' , $this->get_stock_quantity($product));
		$this->output_xml('availability' , $this->get_availability($parent), true);
		$this->output_xml('mpn' , $this->get_mpn($product), true);
		$this->output_xml('weight' , $this->get_weight($product), true);
		$this->output_xml('size' , $this->get_sizes($product), true);
		
		$tags = apply_filters($this->slug.'_append_xml',[], $product, $parent);
		
		foreach($tags as $tag){
			$this->output_xml($tag['name'] , $tag['value'], $tag['cdata_wrapper'] ?? false);
		}
		
		$this->xml->endElement();
		
	}
	
	
	public function generate_xml($loop, $filename='', $limit = -1){
		//print_r($this->options);
		$header = $footer = '';
		$this->xml = new XMLWriter();
		$this->xml->openMemory();		
		if($limit == -1){
			$header = $this->generate_xml_header();
		}
		while ($loop->have_posts()) {
			$loop->the_post();
			$product = apply_filters($this->slug.'_product_locale', wc_get_product(get_the_ID()));
			if ( !$product->is_type( 'variable' ) ) {
				if(isset($this->options['skip_backordered_products']) && $this->options['skip_backordered_products'] && $this->is_on_backorder($product)){
					continue;
				} elseif (isset($this->options['skip_out_of_stock_products']) && $this->options['skip_out_of_stock_products'] && !$this->is_on_backorder($product) && !$product->is_in_stock()){
					continue;
				}
				$this->product_xml($product);
			}else{
				$variations = $product->get_children();
				$size_price_map = [];
				$has_sizes = false;
				$has_colors = false;
				foreach($variations as $key => $variation){
					$product_variation = new WC_Product_Variation($variation);
					if(isset($this->options['skip_backordered_products']) && $this->options['skip_backordered_products'] && $this->is_on_backorder($product_variation)){
						unset($variations[$key]);
						continue;
					} elseif (isset($this->options['skip_out_of_stock_products']) && $this->options['skip_out_of_stock_products'] && !$this->is_on_backorder($product_variation) && !$product_variation->is_in_stock()){
						unset($variations[$key]);
						continue;
					}
					$this->product_xml($product, $product_variation);
				}
			}
		}
		if($limit == -1){
			$footer = $this->generate_xml_footer();
		}
		return file_put_contents($filename, $header.$this->xml->flush().$footer);

	}
	
	public function generate_xml_header(){
		$xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'."\n";
		$xml .= '<store>'."\n";
		$xml .= '<date>'.date('Y-m-d H:i').'</date>'."\n";
		$xml .= '<products>'."\n";
		return $xml;
	}
	public function generate_xml_footer(){
		$xml = '</products>'."\n";
		$xml .= '</store>';
		return $xml;
	}
	
	public function get_categories_for_bestprice($product){
		$data = '';
		$cats = $this->get_categories($product);
		if(is_array($cats)){
			$data = implode(' > ', $cats);
		}
		return apply_filters($this->slug.'_custom_categories_imploded', $data, $product);
	}
	
}

$obs_plugin_obj = new OBSBestpriceXML();
