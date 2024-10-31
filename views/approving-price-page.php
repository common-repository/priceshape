<section id="prs-plugin-table">
    <div class="prs-list-table-title-wrapper">
        <h1 class="prs-list-table-title"><?php _e( 'Products with updated prices', 'priceshape' ); ?></h1>
    </div>
    <form action="" method="POST">
		<?php

		use Priceshape\DataTables\Products_Approve_Data_Tables;

		$GLOBALS['Priceshape_Table_List_Approve_Products'] = new Products_Approve_Data_Tables();
		if ( isset( $_REQUEST['s'] ) ) {
			$GLOBALS['Priceshape_Table_List_Approve_Products']->prepare_items( sanitize_text_field( $_REQUEST['s'] ) );
		} else {
			$GLOBALS['Priceshape_Table_List_Approve_Products']->prepare_items();
		}

		$GLOBALS['Priceshape_Table_List_Approve_Products']->search_box( 'search', 'search_id' );
		$GLOBALS['Priceshape_Table_List_Approve_Products']->display();
		?>
    </form>
</section>
