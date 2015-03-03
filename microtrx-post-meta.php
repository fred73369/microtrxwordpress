<?php
// This should add an options box on the post edit page so that we can update settings individually
// http://codex.wordpress.org/Function_Reference/add_meta_box

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function microtrx_add_meta_box() {

    add_meta_box(
      'microtrx_sectionid',
      __( 'Microtrx Paywall Options', 'microtrx_textdomain' ),
      'microtrx_meta_box_callback',
      'post'
    );

}

add_action( 'add_meta_boxes', 'microtrx_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function microtrx_meta_box_callback( $post ) {

  // Add an nonce field so we can check for it later.
  wp_nonce_field( 'microtrx_meta_box', 'microtrx_meta_box_nonce' );

  /*
   * Use get_post_meta() to retrieve an existing value
   * from the database and use the value for the form.
   */
  $value = get_post_meta( $post->ID, '_my_meta_value_key', true );

  echo '<label for="microtrx_enabled_field">';
  _e( 'Enable Bitcoin Paywall', 'microtrx_textdomain' );
  echo '</label> ';
  ?>
  <br />
  <input type="radio" name="microtrx_enable_radio" value="Yes" <?php checked( $value, 'Yes' ); ?> >Yes<br />
  <input type="radio" name="microtrx_enable_radio" value="No" <?php checked( $value, 'No' ); ?> >No<br />
  <br />
  <?
  echo '<label for="mictrx_paywall_value">';
  _e( 'Amount to charge (BTC)', 'microtrx_textdomain' );
  echo '</label> ';
  echo "<input id='microtrx_charge_string' name='microtrx_charge_string' size='10' type='text' value='" . esc_attr( $value ) . "' />";

}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function microtrx_save_meta_box_data( $post_id ) {

  /*
   * We need to verify this came from our screen and with proper authorization,
   * because the save_post action can be triggered at other times.
   */

  // Check if our nonce is set.
  if ( ! isset( $_POST['microtrx_meta_box_nonce'] ) ) {
    return;
  }

  // Verify that the nonce is valid.
  if ( ! wp_verify_nonce( $_POST['microtrx_meta_box_nonce'], 'microtrx_meta_box' ) ) {
    return;
  }

  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }

  // Check the user's permissions.
  if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
  }

  /* OK, it's safe for us to save the data now. */

  // Make sure that it is set.
  if ( ! isset( $_POST['myplugin_new_field'] ) ) {
    return;
  }

  // Sanitize user input.
  $my_data = sanitize_text_field( $_POST['myplugin_new_field'] );

  // Update the meta field in the database.
  update_post_meta( $post_id, '_my_meta_value_key', $my_data );
}

add_action( 'save_post', 'microtrx_save_meta_box_data' );

?>
