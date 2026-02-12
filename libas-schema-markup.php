<?php
/*
Plugin Name: Libas Schema Markup
Description: Adds Schema Markup for Website, BreadcrumbList, ItemList, and Products.
Version: 1.0.1
Author: Sandip Mistry
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Libas_Schema_Markup {

    public function __construct() {
        add_action('wp_head', [$this, 'add_schema_markup']);
    }

    public function add_schema_markup() {
        if ( is_front_page() || is_home() ) {
            $this->website_schema();
        }
        if ( is_product_category() ) {
            $this->itemlist_schema();
            $this->breadcrumb_schema();
        }
        if ( function_exists('is_product') && is_product() ) {
            $this->product_schema();
            $this->breadcrumb_schema();
        }
    }

    private function website_schema() {
        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "WebSite",
          "name": "<?php bloginfo('name'); ?>",
          "url": "<?php echo esc_url(home_url('/')); ?>",
          "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo esc_url(home_url('/?s={search_term_string}')); ?>",
            "query-input": "required name=search_term_string"
          }
        }
        </script>
        <?php
    }

    private function itemlist_schema() {
        if ( !is_tax('product_cat') ) return;
        $term = get_queried_object();
        $products = wc_get_products(['category' => [$term->slug], 'limit' => 10]);
        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "ItemList",
          "name": "<?php echo esc_html($term->name); ?>",
          "itemListElement": [
            <?php
            $items = [];
            $pos = 1;
            foreach ($products as $product) {
                $items[] = '{
                  "@type": "Product",
                  "position": '.$pos++.',
                  "name": "'.esc_html($product->get_name()).'",
                  "url": "'.get_permalink($product->get_id()).'"
                }';
            }
            echo implode(',', $items);
            ?>
          ]
        }
        </script>
        <?php
    }

    private function breadcrumb_schema() {
        global $post;
        $breadcrumbs = [];
        $position = 1;

        $breadcrumbs[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => "Home",
            "item" => home_url('/')
        ];

        if ( is_product_category() ) {
            $term = get_queried_object();
            $breadcrumbs[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $term->name,
                "item" => get_term_link($term)
            ];
        }

        if ( function_exists('is_product') && is_product() ) {
            $terms = wp_get_post_terms($post->ID, 'product_cat');
            if (!empty($terms)) {
                $term = $terms[0];
                $breadcrumbs[] = [
                    "@type" => "ListItem",
                    "position" => $position++,
                    "name" => $term->name,
                    "item" => get_term_link($term)
                ];
            }
            $breadcrumbs[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => get_the_title(),
                "item" => get_permalink()
            ];
        }

        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": <?php echo wp_json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>
        }
        </script>
        <?php
    }

    private function product_schema() {
        global $product;
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) return;
        $clean_price = number_format( (float) wc_get_price_to_display( $product ), 2, '.', '' );
        $color = $product->get_attribute('pa_color');
        $size = str_replace( ', ', ',', $product->get_attribute( 'pa_size' ) );
        $rating_count = $product->get_rating_count();
        $average      = $product->get_average_rating();
        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Product",
          "name": "<?php echo esc_html($product->get_name()); ?>",
          "image": "<?php echo wp_get_attachment_url($product->get_image_id()); ?>",
          "description": "<?php echo esc_html(wp_strip_all_tags($product->get_short_description())); ?>",
          "sku": "<?php echo esc_html($product->get_sku()); ?>",
          "brand": {
            "@type": "Brand",
            "name": "The Libas Collection"
          },
          "color": "<?php echo $color; ?>", 
          "size": "<?php echo $size; ?>",
          "material": "Silk",
          "offers": {
            "@type": "Offer",
            "url": "<?php echo get_permalink($product->get_id()); ?>",
            "priceCurrency": "<?php echo get_woocommerce_currency(); ?>",
            "price": "<?php echo $clean_price; ?>",
            "priceValidUntil": "2030-12-31",
            "availability": "https://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>",
            "itemCondition": "https://schema.org/NewCondition",
            "shippingDetails": {
              "@type": "OfferShippingDetails",
              "shippingRate": {
                "@type": "MonetaryAmount",
                "value": "0",
                "currency": "INR"
              },
              "deliveryTime": {
                "@type": "ShippingDeliveryTime",
                "handlingTime": {
                  "@type": "QuantitativeValue",
                  "minValue": 1,
                  "maxValue": 2,
                  "unitCode": "DAY"
                },
                "transitTime": {
                  "@type": "QuantitativeValue",
                  "minValue": 3,
                  "maxValue": 5,
                  "unitCode": "DAY"
                }
              },
              "shippingDestination": {
                "@type": "DefinedRegion",
                "addressCountry": "IN"
              }
            },
            "hasMerchantReturnPolicy": {
              "@type": "MerchantReturnPolicy",
              "applicableCountry": "IN",
              "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
              "merchantReturnDays": 7,
              "returnMethod": "https://schema.org/ReturnByMail",
              "returnFees": "https://schema.org/FreeReturn",
              "merchantReturnLink": "https://thelibas.com/returns-policy"
            }
          }
          <?php if ( $rating_count > 0 ) { ?>,
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?php echo $average; ?>",
            "reviewCount": "<?php echo $product->get_review_count(); ?>"
          }
          <?php } ?>
        }
        </script>
        <?php
    }
}

new Libas_Schema_Markup();
?>
