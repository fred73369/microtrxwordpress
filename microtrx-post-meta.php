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

  // Get the plugin options
  $options = get_option('microtrx_options');

  $disabled = '';

  // If the default mode is all paywall (Yes), then disable the radio buttons and set it to enabled
  if($options[default_mode_string] === 'Yes'){
    $disabled = 'disabled';
    $paywall_enabled = 'Yes';
  }

  // If the default mode is not enabled, figure out if the paywall is enabled for this post
  if($paywall_enabled !== 'Yes'){

    // Check to see if it is already set for the post
    $paywall_enabled = get_post_meta( $post->ID, 'microtrx_paywall_enabled', true );

    // If it hasn't been set for the post, then default to No
    if( empty( $paywall_enabled ) ) {
      $paywall_enabled = 'No';
    }
  }

  echo '<label for="microtrx_enabled_field">';
  _e( 'Enable Bitcoin Paywall', 'microtrx_textdomain' );
  echo '</label> ';
  ?>
  <br />
  <input type="radio" name="microtrx_enable_radio" value="Yes" <?php checked( $paywall_enabled, 'Yes' ); echo "{$disabled}" ?> >Yes<br />
  <input type="radio" name="microtrx_enable_radio" value="No" <?php checked( $paywall_enabled, 'No' ); echo "{$disabled}" ?> >No<br />
  <br />
  <?

  // Get the post specific paywall amount
  $paywall_amount = get_post_meta( $post->ID, 'microtrx_paywall_amount', true );

  // If it hasn't been set for the post, then default to the global amount
  if( empty( $paywall_amount ) ) {
    $paywall_amount = $options[default_charge_string];
  }

  echo '<label for="mictrx_paywall_value">';
  _e( 'Amount to charge (BTC)', 'microtrx_textdomain' );
  echo '</label> ';
  echo "<input id='microtrx_charge_string' name='microtrx_charge_string' size='10' type='text' value='" . esc_attr( $paywall_amount ) . "' />";

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
  if ( ! isset( $_POST['microtrx_enable_radio'] ) || ! isset( $_POST['microtrx_charge_string'] ) ) {
    return;
  }

  // Sanitize user input.
  $paywall_enabled = sanitize_text_field( $_POST['microtrx_enable_radio'] );
  $paywall_amount = sanitize_text_field( $_POST['microtrx_charge_string'] );

  // Update the meta field in the database.
  update_post_meta( $post_id, 'microtrx_paywall_enabled', $paywall_enabled );
  update_post_meta( $post_id, 'microtrx_paywall_amount', $paywall_amount );
}

add_action( 'save_post', 'microtrx_save_meta_box_data' );

?>
