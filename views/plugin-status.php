<?php

use Priceshape\Priceshape_Plugin;
use Priceshape\Priceshape_Queries;

$status = Priceshape_Queries::priceshape_get_plugin_param( Priceshape_Plugin::PLUGIN_STATUS );
if ( empty( $status ) ) {
	return;
}
?>
    <h3><?php _e( 'PriceShape Plugin Status:', 'priceshape' ); ?>
        <b class="prs-status"><?php echo $status; ?></b>
    </h3>
<?php if ( $status == Priceshape_Plugin::PLUGIN_STATUS_UNSUCCESSFUL ) : ?>
    <a id="prs-try-again" href="#"><?php _e( 'Try again', 'priceshape'); ?></a>
<?php endif;
