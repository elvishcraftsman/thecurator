<?php
/**
 * Curator custom functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 */


/**
 * Custom function to fetch post attributes by id or tag
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

        $excerpt = str_replace( '<p>', 
            sprintf( '<blockquote class="has-text-align-center" cite="%1$s">', $post_link ),
            $excerpt
        );
        $excerpt = str_replace( '</p>', '</blockquote>', $excerpt );
    
        $caption = sprintf('<figcaption class="has-text-align-center">
                <cite>"%1$s"</cite> by %2$s
            </figcaption>',
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

        $output .= '<figure>';
        $output .= $excerpt;
        $output .= $caption;
        $output .= '</figure>';

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
 * Define custom rendering function for post excerpt block
 */
function curator_render_excerpt_block($attributes, $content, $block) {
    if (!isset($block->context['postId'])) {
		return '';
	}

    $post = get_post($block->context['postId']);
    
    $content = $post->post_content;
    
    $content = preg_replace('#<!--.*-->#', '', $content);
    $content = str_replace('<h6', '<p><i', $content);
    $content = str_replace('</h6>', '</i></p>', $content);
    $content = str_replace('<h2', '<h3', $content);
    $content = str_replace('</h2>', '</h3>', $content);

    $content = wpautop($content);

    $excerpt_length = $attributes['excerptLength'];
    if (!isset($excerpt_length)) $excerpt_length = 4;
    $excerpt_length += 2;

	$classes = array();
	if (isset($attributes['textAlign'])) {
		$classes[] = 'has-text-align-' . $attributes['textAlign'];
	}
	if (isset($attributes['style']['elements']['link']['color']['text'])) {
		$classes[] = 'has-link-color';
	}
	$wrapper_attributes = get_block_wrapper_attributes(array('class' => implode(' ', $classes)));

	$output = '';

    $lines = 0;
    $length = strlen($content) - 2;

    for ($i = 0; $i < $length; $i++) {
        if ($content[$i] == '<') {
            switch (substr($content, $i, 3)) {
                case '<h1':
                case '<h2':
                case '<h3':
                case '<h4':
                case '<h5':
                case '<h6':
                case '<br':
                    $lines++;
                    break;
                case '<p ':
                case '<p>':
                case '<li':
                    $lines += 2;
                    break;
                case '<hr':
                case '<di':
	                $output = '<div ' . $wrapper_attributes . '>';
                    $output .= substr($content, 0, $i);
                    $output .= '</div>';
                    return $output;
                    break;
            }
        }
        if ($excerpt_length - $lines < 2) {
            $output = substr($content, 0, $i);
        }
        if ($lines > $excerpt_length) {
            break;
        }
    }

    $output = '<div ' . $wrapper_attributes . '>' . $output;
    $output .= '<p><a href="' . esc_url(get_permalink($post)) . '">Read More</a></p>';
    $output .= '</div>';

    return $output;
}

add_filter('register_block_type_args', function ($args, $block_type) {
    if ($block_type == 'core/post-excerpt') {
        $args['render_callback'] = 'curator_render_excerpt_block';
    }
    return $args;
}, null, 3);


/**
 *  Change the "Read more" links
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
		get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
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