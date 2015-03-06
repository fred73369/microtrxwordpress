<?php


/**
 * Add a string before the posts
 */
function microtrx_the_content_filter( $content ) {

    global $post;

    $options = get_option('microtrx_options');
    $paywall_enabled = get_post_meta( $post->ID, 'microtrx_paywall_enabled', true );

    if($paywall_enabled === 'Yes' || $options[default_mode_string] === 'Yes'){
      $content = '<p>This post is paywalled </p>' . $content;
    }

    return $content;
}

add_filter( 'the_content', 'microtrx_the_content_filter' );

?>
