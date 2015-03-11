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
function microtrx_getPostPaymentInfo($sessionId, $postId){
  global $wpdb;
  global $microtrx_table_name;

  $row = $wpdb->get_row(
    $wpdb->prepare(
      "
        SELECT * FROM $microtrx_table_name
        WHERE session = %s AND post = %d
      ",
      $sessionId,
      $postId
    )
  );

  return $row;
}

// Insert or update data to the db for the post
function microtrx_updatePostPaymentInfo($sessionId, $postId, $paid, $address){
  global $wpdb;
  global $microtrx_table_name;

  $wpdb->replace(
    $microtrx_table_name,
    array(
      'session' => $sessionId,
      'post' => $postId,
      'address' => $address,
      'paid' => $paid
    ),
    array(
      '%s',
      '%d',
      '%s',
      '%d'
    )
  );
}

// Create a new payment request with the gateway
function microtrx_submitPaymentRequest($amount){
  $options = get_option('microtrx_options');

  $response = wp_remote_get( 'http://testnet.microtrx.com/api/v1/simple/payments?publicKey=' . $options[public_key_string] . '&amountRequested='. $amount);

  return $response;
}

// Check with the payment gateway for a payment status
function microtrx_requestPaymentStatus($address, $timeout){
  $response = wp_remote_get( 'http://testnet.microtrx.com/api/v1/simple/payments/' . $address . '?timeout=' . $timeout);
  return $response;
}

//

/**
 * Filter the post objects.  If the paywall is active then only show partial content.
 */
function microtrx_the_content_filter( $content ) {

    global $post;
    $debug = true;

    // Bail out if this is not a single post page or not the main query loop for the post
    if(!is_single() || !is_main_query())
      return $content;


    // Check to see if this post is paywalled
    if(microtrx_getPaywallEnabledForPost()){

      // Get the paywall status from the DB
      $postPaymentInfo = microtrx_getPostPaymentInfo(session_id(), $post->ID);
      $paywalled = false;

      if($debug)
        $content = '<p>' . json_encode($postPaymentInfo) . ' </p>' . $content;

      // If the payment record doesn't exist in the db, then create a new payment address and save it off to the DB, and block with paywall
      if(!$postPaymentInfo){

        if($debug)
          $content = '<p> DB shows payment not made for post </p>' . $content;

        $paywalled = true;
        $paymentRequest = microtrx_submitPaymentRequest(microtrx_getPaywallAmountForPost($post->ID));

        // Check for HTTP Error
        if ( is_wp_error( $paymentRequest ) ) {
          $error_message = "Failed to contact MicroTrx Server: " . $paymentRequest->get_error_message();
          return '<p>' . $error_message . '</p>' . $content;
        }

        // Get the response body
        $body = wp_remote_retrieve_body($paymentRequest);

        // Convert the JSON string into an associative array
        $json = json_decode($body, true);

        // Check for API success
        if($json['success'] === 'false'){
          $error_message = "Failed to submit payment request with MicroTrx Server: " . $json['error'];
          return '<p>' . $error_message . '</p>' . $content;
        }

        microtrx_updatePostPaymentInfo(session_id(), $post->ID, false, $json['result']['paymentAddress']);

        if($debug)
          $content = '<p> Updated DB with new payment request info for this post </p>' . $content;

      }else{

        if($debug)
          $content = '<p> Post payment request already found in db</p>' . $content;

        // The payment record does exist in the local DB, check if it is already paid
        if(!$postPaymentInfo->paid){

          if($debug)
            $content = '<p> DB shows post not paid for </p>' . $content;

          // Not already paid.  Verify with the payment gateway.
          $gatewayPaymentInfo = microtrx_requestPaymentStatus($postPaymentInfo->address, 0);

          // Check for HTTP Error
          if ( is_wp_error( $gatewayPaymentInfo ) ) {
            $error_message = "Failed to contact MicroTrx Server: " . $gatewayPaymentInfo->get_error_message();
            return '<p>' . $error_message . '</p>' . $content;
          }

          // Get the response body
          $body = wp_remote_retrieve_body($gatewayPaymentInfo);

          // Convert the JSON string into an associative array
          $json = json_decode($body, true);

          // Check for API success
          if($json['success'] === 'false'){
            $error_message = "Failed to get payment status with MicroTrx Server: " . $json['error'];
            return '<p>' . $error_message . '</p>' . $content;
          }

          if($json['result']['paid']){

            if($debug)
              $content = '<p> Gateway shows post already paid for </p>' . $content;

            // Already paid according to the gateway.  Save updated DB record and open paywall
            microtrx_updatePostPaymentInfo(session_id(), $post->ID, true, $json['result']['paymentAddress']);

            if($debug)
              $content = '<p> Updated DB to show payment made </p>' . $content;

          }else{

            if($debug)
              $content = '<p> Gateway shows payment still not made </p>' . $content;

            // Not already paid according to the gateway. Block with paywall
            $paywalled = true;
          }

        }else{

          if($debug)
            $content = '<p> DB shows post already paid for </p>' . $content;
        }
      }

      // If we are paywalled, then don't show entire post and add payment info to bottom
      if($paywalled){
        $content = '<p> Paywalled! <br /> Session ID:' . session_id() .'<br />Post ID: ' . $post->ID . '</p>' . $content;
      }

    }

    return $content;
}

add_filter( 'the_content', 'microtrx_the_content_filter' );

?>
