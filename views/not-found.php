<?php

use Priceshape\Priceshape_Plugin;

$support_mail = Priceshape_Plugin::DEFAULT_PLUGIN_PARAMS[ Priceshape_Plugin::SUPPORT_MAIL ];
?>
<div>
	<?php _e( 'Sorry, you don\'t have WooCommerce plugin installed. Please contact', 'priceshape' ); ?>
    <a href="mailto:<?php echo $support_mail; ?>"><?php echo $support_mail; ?></a>
	<?php _e( 'for more details.', 'priceshape' ); ?>
</div>
