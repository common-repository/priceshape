<section id="prs-plugin-table">
    <h1 class="prs-list-table-title"><?php _e( 'All Products','priceshape' ); ?></h1>
    <form action="" method="POST">
		<?php

		use Priceshape\DataTables\Products_Data_Tables;

		$GLOBALS['Priceshape_Table_List_All_Products'] = new Products_Data_Tables();
		if ( isset( $_REQUEST['s'] ) ) {
			$GLOBALS['Priceshape_Table_List_All_Products']->prepare_items( sanitize_text_field( $_REQUEST['s'] ) );
		} else {
			$GLOBALS['Priceshape_Table_List_All_Products']->prepare_items();
		}

		$GLOBALS['Priceshape_Table_List_All_Products']->search_box( 'search', 'search_id' );
		$GLOBALS['Priceshape_Table_List_All_Products']->display();
		?>
    </form>
</section>
