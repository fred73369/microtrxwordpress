<?php

// Determines if the paywall is enabled for a specific post ID
function microtrx_getPaywallEnabledForPost(){
  global $post;
  $options = get_option('microtrx_options');
  $paywall_enabled = get_post_meta( $post->ID, 'microtrx_paywall_enabled', true );

  return ($paywall_enabled === 'Yes' || $options[default_mode_string] === 'Yes');
}

// Determines the amount to charge for a specific post
function microtrx_getPaywallAmountForPost(){
  global $post;
  $options = get_option('microtrx_options');
  $paywall_amount = get_post_meta( $post->ID, 'microtrx_paywall_amount', true );

  // If the value is set specifically for the post, then use that amount
  if($paywall_amount)
    return $paywall_amount;

  // If the post is not specified then fall back to default
  return $options[default_charge_string];
}

// Gets the db payment info about a post and user session
function microtrx_getPostPaymentInfo(sessionId, postId){
  global $wpdb;
  global $microtrx_table_name;

  $row = $wpdb->get_row(
    $wpdb->prepare(
      "
        SELECT * FROM " . $microtrx_table_name . "
        WHERE session = %s AND post = %d
      ",
      sessionId,
      postId
    )
  );

  return $row;
}

// Insert or update data to the db for the post
function microtrx_updatePostPaymentInfo(sessionId, postId, paid, address){

}

// Create a new payment request with the gateway
function microtrx_submitPaymentRequest(amount){

}

// Check with the payment gateway for a payment status
function microtrx_requestPaymentStatus(address, timeout){

}

//

/**
 * Filter the post objects.  If the paywall is active then only show partial content.
 */
function microtrx_the_content_filter( $content ) {

    global $post;

    // Check to see if this post is paywalled
    if(microtrx_getPaywallEnabledForPost()){

      // Get the paywall status from the DB
      $postPaymentInfo = microtrx_getPostPaymentInfo(session_id(), $post->ID);
      $paywalled = false;

      // If the payment record doesn't exist in the db, then create a new payment address and save it off to the DB, and block with paywall
      if(!$postPaymentInfo){

        $paywalled = true;
        $paymentRequest = microtrx_submitPaymentRequest(microtrx_getPaywallAmountForPost($post->ID));
        microtrx_updatePostPaymentInfo(session_id(), $post->ID, false, $paymentRequest->res->paymentAddress);

      }else{

        // The payment record does exist in the local DB, check if it is already paid
        if(!postPaymentInfo->paid){

          // Not already paid.  Verify with the payment gateway.
          $gatewayPaymentInfo = microtrx_requestPaymentStatus(postPaymentInfo->address, 0);

          if($gatewayPaymentInfo->res->paid){

            // Already paid according to the gateway.  Save updated DB record and open paywall
            microtrx_updatePostPaymentInfo(session_id(), $post->ID, true, $paymentRequest->res->paymentAddress);

          }else{

            // Not already paid according to the gateway. Block with paywall
            $paywalled = true;
          }
        }
      }

      // If we are paywalled, then don't show entire post and add payment info to bottom
      if($paywalled){
        //$content = '<p> Session ID:' . session_id() .'<br />Post ID: ' . $post->ID . '</p>' . $content;
      }

    }

    return $content;
}

add_filter( 'the_content', 'microtrx_the_content_filter' );

?>
