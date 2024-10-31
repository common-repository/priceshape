<?php

use Priceshape\Priceshape_Plugin;

$limit = Priceshape_Plugin ::priceshape_get_param( Priceshape_Plugin::PRODUCTS_LIMIT );
if ( 0 == $limit ) {
	return;
}
?>
<h3>
	<?php _e( 'Your PriceShape export limit is', 'priceshape' ); ?>
    <b class="prs-status"><?php echo $limit; ?> </b>
	<?php _e( 'items.', 'priceshape' ); ?>
</h3>
