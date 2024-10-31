<div id="prs-login-warning"></div>
<form id="login-form">
    <div class="container">
        <div>
            <label><b><?php _e( 'User Name', 'priceshape' ); ?></b></label>
            <input type="text" placeholder="<?php _e( 'Enter User Name', 'priceshape' ); ?>" name="user[name]" required>

            <label><b><?php _e( 'User Phone Number', 'priceshape' ); ?></b></label>
            <input type="tel" placeholder="<?php _e( 'Enter User Phone Number', 'priceshape' ); ?>" name="user[tel]" required>

            <label><b><?php _e( 'User Email', 'priceshape' ); ?></b></label>
            <input type="email" placeholder="<?php _e( 'Enter User Email', 'priceshape' ); ?>" name="user[email]" required>

            <label><b><?php _e( 'User Country', 'priceshape' ); ?></b></label>
            <input type="text" placeholder="<?php _e( 'Enter User Country', 'priceshape' ); ?>" name="user[country]" required>

            <div>
                <input type="checkbox" name="user[agree]" required>
                <span><?php _e( 'I agree to the collection and processing of personal data', 'priceshape' ); ?></span>
            </div>

            <button type="submit"><?php _e( 'SEND', 'priceshape' ); ?></button>
        </div>
    </div>
</form>