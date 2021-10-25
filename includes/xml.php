<?php
function lh_create_xml() {
    // Build your file contents as a string
    ob_start();?>
    <rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
        <channel>
            <title><?php bloginfo_rss('name'); ?> - Feed</title>
            <link><?php bloginfo_rss('url') ?></link>
            <description>Product feed for leadhub platform</description>
            <?php 
        $args = array(
            'post_type' => 'product',
            'posts_per_page'=>-1,
            'post_status' => array( 'publish')
        );
        $the_query = new WP_Query( $args ); 
        $currency = get_woocommerce_currency();
        if ( $the_query->have_posts() ) :
        while ( $the_query->have_posts() ) : $the_query->the_post(); 
        global $product;
        $ID = $product->get_id();


        if ( $product->is_type( 'variable' ) ) :
        $variations = $product->get_available_variations();

        foreach ($variations as $variation):
            if($variation['is_in_stock'] == 1) {
                $stock_info = 'in stock';
            }
            else {
                $stock_info = 'out of stock';
            }

            $title = esc_html(get_the_title($variation['variation_id']));
            $title_edited = str_replace("&#8211;", "-", $title);
        ?>
            <item>
                <g:id><?=$variation['variation_id'];?></g:id>
                <g:title><?=$title_edited;?></g:title>
                <g:link><?=get_permalink();?></g:link>
                <g:image_link><?=$variation['image']['full_src'];?></g:image_link>
                <g:availability><?=$stock_info;?></g:availability>
                <g:price><?=$variation['display_price'];?> <?=$currency;?></g:price>
                <g:condition>New</g:condition>
                <g:identifier_exists>no</g:identifier_exists>
            </item>
        <?php 
        endforeach;
        else:
            if ( method_exists( $product, 'get_stock_status' ) ) {
                $stock_status = $product->get_stock_status();
            }
            if($stock_status == 'instock') {
                $stock_info = 'in stock';
            }
            elseif($stock_status == 'outofstock') {
                $stock_info = 'out of stock';
            }
            else {
                $stock_info = 'preorder';
            }
        ?>
        <item>
            <g:id><?=$ID;?></g:id>
            <g:title><?=get_the_title();?></g:title>
            <g:link><?=get_permalink();?></g:link>
            <g:image_link><?= get_the_post_thumbnail_url();?></g:image_link>
            <g:availability><?= $stock_info; ?></g:availability>
            <g:price><?=$currency;?> <?=get_post_meta($ID , '_price', true);?></g:price>
            <g:condition>New</g:condition>
            <g:identifier_exists>no</g:identifier_exists>
        </item>
<?php 
endif;
endwhile;  
endif;
wp_reset_postdata();?>
</channel>
</rss>
    <?php 
    $file_contents = ob_get_contents();
    ob_end_clean();

    // Open or create a file (this does it in the same dir as the script)
    $my_file = fopen(plugin_dir_path( __FILE__ ) . 'feed.xml', "w");

    // Write the string's contents into that file
    fwrite($my_file, $file_contents);

    // Close 'er up
    fclose($my_file);

    echo 'Hotovo';
}

add_action( 'wp_ajax_lh_create_xml', 'lh_create_xml' );
add_action( 'wp_ajax_nopriv_lh_create_xml', 'lh_create_xml' );

//add_action( 'wp_footer', 'lh_create_xml' );
