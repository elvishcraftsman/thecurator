<?php
/**
 * Curator custom functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 */


/**
 * Custom post excerpt function using PHP's DOM API
 */
function get_post_element_content ( $post, $id, $fallback_tag ) {

    $doc = new DOMDocument();
    $doc->loadHTML( $post->post_content );
    $element = $doc->getElementByID( $id )->textContent;

    if ( is_null($element) ) {

        $nodes = $doc->getElementsByTagName( $fallback_tag ); 

        if ($nodes->length == 0) {
            return '';
        }

        $element = $nodes->item(0);

    }

    return $element->textContent;

}


/**
 * Custom post excerpt function
 */
function curator_post_excerpt ( $post ) {

    $content = $post->post_content;

    $header_end = strpos( $content, '<p' );

    if ( $header_end === false ) {
        $header_end = 0;
    }

    $excerpt_end = strpos( $content, '<!--more-->');

    if ( $excerpt_end === false || $excerpt_end < $header_end ) {
        $excerpt_end = $header_end + 1;
    }

    $excerpt_end--;

    $excerpt = substr( $content, $header_end, $excerpt_end - $header_end );

    $excerpt = preg_replace('#<!-- .* -->#', '', $excerpt);

    return $excerpt;
}


/**
 * Define custom latest post shortcode
 */
function curator_latest_post_shortcode( $atts ) {

    $posts = get_posts( array ( 'numberposts' => 1, 'category' => 'weekly-poems', ) );

    $ouput = '';

    foreach ( $posts as $post ) {
		$post_link = esc_url( get_permalink( $post ) );
		$title     = get_the_title( $post );

		if ( ! $title ) {
			$title = __( '(no title)' );
		}

        $image_id = get_post_thumbnail_id( $post );

        $featured_image = get_the_post_thumbnail_url( $post, "large" );

        $title = get_post_element_content( $post, 'title', 'h2' );

        $author = get_post_element_content( $post, 'author', 'h3' );

        if ( $author == '' ) {
            $author = get_post_element_content( $post, 'author', 'h6' );
        }

        $author = trim($author);

        if ( str_starts_with( $author, 'by ' ) ) {
            $author = substr( $author, 3 );
        }

        $excerpt = wpautop( curator_post_excerpt( $post ) );

        $excerpt = str_replace( '<p>', '<p class="has-text-align-center">', $excerpt );
    
        $excerpt .= sprintf('<h2 class="has-text-align-center">
                %1$s by %2$s
            </h2>',
            $title,
            $author
        );

        $output .= '<div class="wp-block-cover is-light curator-latest-post" style="min-height:20em">
        <span aria-hidden="true" class="wp-block-cover__background has-white-background-color has-background-dim-70 has-background-dim"></span>';

        $output .= sprintf(
            '<img class="wp-block-cover__image-background wp-image-%2$s" alt="" src="%1$s" data-object-fit="cover"/>',
            $featured_image,
            $image_id
        );

        $output .= '<div class="wp-block-cover__inner-container">';

        $output .= $excerpt;

        $output .= '<div class="wp-block-buttons is-content-justification-center is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">
            <div class="wp-block-button">';

        $output .= sprintf(
            '<a class="wp-block-button__link has-text-align-center wp-element-button" href="%1$s">
                    Read more
                </a>',
            $post_link
        );

        $output .= '            </div>
        </div>
    </div>
</div>';

    }

	return $output;

}

add_shortcode( 'curator_latest_post', 'curator_latest_post_shortcode' );


/**
 *  Remove the arrow added by astra to "Read more" links
 */
add_filter (
    'the_content_more_link',
    function ($value) {
        return str_replace('more&hellip;', 'Read More', $value);
    }
);


/**
 * Ensure that the curator stylesheet is loaded
 */
function curator_enqueue_styles() {
	wp_enqueue_style( 
		'curator-style', 
		get_stylesheet_uri()
	);
}

add_action( 'wp_enqueue_scripts', 'curator_enqueue_styles' );

function github_enqueue_styles() {
    wp_enqueue_style(
        'github-theme-css',
        'https://raw.githubusercontent.com/elvishcraftsman/thecurator/main/style.css',
        [],
        null
    );
}

add_action( 'wp_enqueue_scripts', 'github_enqueue_styles', 100 );
add_action( 'enqueue_block_editor_assets', 'github_enqueue_styles', 100 );

?>