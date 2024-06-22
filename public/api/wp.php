<?php
require_once("../wp-config.php");
require_once("../wp-load.php");

$query = new WC_Product_Query( array(
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'ids',
) );
$products = $query->get_products();
echo "<pre>";
$args = array(
    'include' => $products,
);
$products = wc_get_products( $args );
print_R($products);