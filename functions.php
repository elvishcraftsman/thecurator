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
    $element = $doc->getElementByID( $id );

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

    $content = preg_replace('#<!-- /?wp.* -->#', '', $post->post_content );

    $content = wpautop( $content );

    $header_end = strpos( $content, '<p' );

    if ($header_end !== false) {

        $first_p_close = strpos($content, '</', $header_end);

        if ($first_p_close !== false && $first_p_close > $header_end) {

            $first_p = substr($content, $header_end, $first_p_close - $header_end);

            if (preg_match('#<p.*>\s*[Bb]y .*#', $first_p)) {
                $header_end = strpos($content, '<p', $first_p_close);
            }
        }
    }

    if ( $header_end === false ) {
        $header_end = 0;
    }

    $excerpt_end = strpos( $content, '<p><!--more-->');

    if ( $excerpt_end === false ) {
        $excerpt_end = strpos( $content, '<!--more-->');
    }

    if ( $excerpt_end === false || $excerpt_end < $header_end ) {

        $lines = 0;
        $length = strlen($content) - 2;

        for ($i = $header_end + 1; $i < $length; $i++) {
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
                    case '</d':
                        $excerpt_end = $i;
                        break;
                }
            }
            if ($lines >= 4) {
                $excerpt_end = $i;
            }
            if ($excerpt_end === $i) {
                break;
            }
        }
    }

    if ( $excerpt_end === false ) {
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
function curator_latest_post_shortcode( $attributes ) {

	$attributes = shortcode_atts(array(
		'number' => 1,
	), $attributes);

    $category = term_exists('weekly-poems', 'category');
	if (is_array( $category)) {
		$category = $category['term_id'];
	}

    $posts = get_posts( array ( 'numberposts' => $attributes['number'], 'category' => $category, ) );

    $output = '';

    $post = array_last($posts);

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

    $excerpt = curator_post_excerpt( $post );

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

    $output .= '<div class="wp-block-cover is-light curator-latest-post">
    <span aria-hidden="true" class="wp-block-cover__background has-white-background-color has-background-dim-80 has-background-dim"></span>';

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

	return $output;

}

add_shortcode( 'curator_latest_post', 'curator_latest_post_shortcode' );


/**
 * Define custom shortcode for latest post by category
 */
function curator_latest_from_shortcode($attributes) {

    $output = '';

	$attributes = shortcode_atts(array(
		'from' => 'posts',
	), $attributes);

    $category = term_exists($attributes['from'], 'category');
	if (is_array( $category)) {
		$category = $category['term_id'];
	}

    $posts = get_posts(array('numberposts' => 1, 'category' => $category,));

    foreach ($posts as $post) {

        $image_id = get_post_thumbnail_id($post);

        $featured_image = get_the_post_thumbnail_url($post, "large");

        $title = get_post_element_content($post, 'title', 'h2');

        $author = get_post_element_content($post, 'author', 'h3');

        if ($author == '') {
            $author = get_post_element_content($post, 'author', 'h6');
        }

        if ($author == '') {
            $author = get_post_element_content($post, 'author', 'i');
        }

        $author = trim($author);

        if (str_starts_with($author, 'by ')) {
            $author = substr($author, 3);
        }

        $output .= sprintf(
'<figure class="wp-block-image size-full">
    <img class="wp-image-%1$s" alt="" width="550" src="%2$s" style="aspect-ratio:3/2;object-fit:cover">
</figure>',
            $image_id,
            $featured_image
        );

        $output .= sprintf(
'<h3 class="wp-block-heading has-text-align-center has-medium-font-size" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:0">
    %1$s
</h3>',
            $title
        );

        $output .= sprintf(
'<p class="has-text-align-center has-montserrat-font-family has-small-font-size">
    <em>by %1$s</em>
</p>',
            $author
        );

    }

    return $output;

}

add_shortcode('curator_latest_from', 'curator_latest_from_shortcode');


function curator_render_poem_excerpt($attributes, $content) {
    $excerpt_length = $attributes['excerptLength'];
    if (!isset($excerpt_length)) $excerpt_length = 10;
    $compact = false;
    if ($excerpt_length === 10) {
        $excerpt_length = 3;
        $compact = true;
    }
    $excerpt_length += 2;

    $content = preg_replace('#</p>\s*<!-- /wp:paragraph -->\s*<!-- wp:more -->\s*<!--more-->\s*<!-- /wp:more -->\s*<!-- wp:paragraph -->\s*<p>#', 
                            '<br>', $content);
    $content = preg_replace('#<!--.*-->#', '', $content);
    if ($compact) {
        $content = preg_replace('#<h(\d).*>[^<]*</h\1>#', '', $content);
        $content = preg_replace('#<p.*author.*><i>[^<]*</i></p>#', '', $content);
    }
    $content = str_replace('<h6', '<p><i', $content);
    $content = str_replace('</h6>', '</i></p>', $content);
    $content = str_replace('<h2', '<h3', $content);
    $content = str_replace('</h2>', '</h3>', $content);

    $content = wpautop($content);


	$classes = array();
	if (isset($attributes['textAlign'])) {
		$classes[] = 'has-text-align-' . $attributes['textAlign'];
	}
	if (isset($attributes['style']['elements']['link']['color']['text'])) {
		$classes[] = 'has-link-color';
	}
	$wrapper_attributes = get_block_wrapper_attributes(array('class' => implode(' ', $classes)));

	$output = '';
    
    $poem_start = strpos($content, '<p');
    if (!$compact) {
        $poem_start = strpos($content, '<h3');
    }

    if ($poem_start === false) {
        $poem_start = 0;
    }

    $lines = 0;
    $length = strlen($content) - 2;

    for ($i = $poem_start; $i < $length; $i++) {
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
                case '</d':
	                $output = '<div ' . $wrapper_attributes . '>';
                    $output .= substr($content, $poem_start, $i - $poem_start);
                    $output .= '</div>';
                    return $output;
                    break;
            }
        }
        if ($excerpt_length - $lines < 2) {
            $output = substr($content, $poem_start, $i - $poem_start);
        }
        if ($lines > $excerpt_length) {
            break;
        }
    }

    $output = curator_autop_poem($output);
    $output = str_replace('<h2', '<h3', $output);
    $output = str_replace('</h2>', '</h3>', $output);
    $output = str_replace('<p>', '<p></p><p class="poem-line">', $output);
    $output = preg_replace('#<br\s?/?>#', '</p><p class="poem-line">', $output);
    $output = preg_replace('#</?\s*ol\s*>#', '', $output);
    $output = preg_replace('#</?\s*li\s*>#', '', $output);

    $pos = strpos($output, '<p></p><p class="poem-line"><i');
    if ($pos !== false) {
        $output = substr_replace($output, '<p><i', $pos, 30);
    }

    $output = close_tags($output);
    $output = '<div ' . $wrapper_attributes . '>' . $output . "</div>\n";

    return $output;
}


function curator_render_post_excerpt($attributes, $content) {
    $content = preg_replace('#</p>\s*<!-- /wp:paragraph -->\s*<!-- wp:more -->\s*<!--more-->\s*<!-- /wp:more -->\s*<!-- wp:paragraph -->\s*<p>#', 
                            '<br>', $content);
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

    $post_start = strpos($content, '<p');
    $post_end = strpos($content, '</p>');

    if ($post_start === false || $post_end === false || $post_end <= $post_start) {
        return '';
    }
    $output = substr($content, $post_start, $post_end - $post_start);

    if (preg_match("#<p.*>\s*[Bb]y #", $output)) {
        $post_start = strpos($content, '<p', $post_end);
        $post_end = strpos($content, '</p>', $post_start);
        if ($post_start !== false && $post_end !== false) {
            $output = substr($content, $post_start, $post_end - $post_start);
        }
    }

    if (strlen($output) > 110) {
        $post_end = strpos($output, ' ', 90);
        if ($post_end !== false) {
            $output = close_tags(substr($output, 0, $post_end) . '...');
        } else {
            $output = close_tags(substr($output, 0, 100) . '...');
        }
    }

    $output = '<div ' . $wrapper_attributes . '>' . $output . "</div>\n";

    return $output;
}


/**
 * Search the string for open HTML tags, and add closing tags if necessary
 */
function close_tags($string) {
    $tags = [];
    $matches = [];
    $index = 0;
    while (preg_match('#<(/|[^\s>]+)\s*([^/\s>]*)[^/>]*>#', $string, $matches, PREG_OFFSET_CAPTURE, $index)) {
        $index = $matches[0][1];
        if ($matches[1][0] === '/' && count($matches) > 2) {
            $last = array_pop($tags);
            while ($matches[2][0] !== $last && strlen($last) > 0) {
                $tag = '</' . $last . '>';
                $string = substr($string, 0, $index) . $tag . substr($string, $index);
                $index += strlen($tag);
                $last = array_pop($tags);
            }
        } else {
            $tag = $matches[1][0];
            if ($tag != 'br') {
                $tags[] = $tag;
            }
        }
        $index++;
    }
    while (count($tags)) {
        $string .= '</' . array_pop($tags) . '>';
    }
    return $string;
}

/**
 * Define custom rendering function for post excerpt block
 * Uses the excerptLength field to determine the number of lines to display
 */
function curator_render_excerpt_block($attributes, $content, $block) {
    if (!isset($block->context['postId'])) {
		return '';
	}

    $id = $block->context['postId'];
    $post = get_post($id);
    $content = $post->post_content;
    
    foreach (get_the_category($id) as $category) {
        if ($category->slug == 'weekly-poems') {
            return curator_render_poem_excerpt($attributes, $content);
        }
    }
    
    return curator_render_post_excerpt($attributes, $content);;
}

add_filter('register_block_type_args', function ($args, $block_type) {
    if ($block_type == 'core/post-excerpt') {
        $args['render_callback'] = 'curator_render_excerpt_block';
    }
    return $args;
}, null, 3);


/**
 * Modifies the search block so that the search field appears after the button
 */
function curator_render_search_block($attributes) {
    $content = render_block_core_search($attributes);
    $input_start = strpos($content, '<input');
    if ($input_start !== false) {
        $button_start = strpos($content, '<button', $input_start);
        if ($button_start !== false) {
            $button_end = strpos($content, '</button>', $button_start);
            if ($button_end !== false) {
                $button_end += 9;
                $content = substr($content, 0, $input_start) . 
                           substr($content, $button_start, $button_end - $button_start) . 
                           substr($content, $input_start, $button_start - $input_start) . 
                           substr($content, $button_end);
            }
        }
    }
    return $content;
}

add_filter('register_block_type_args', function ($args, $block_type) {
    if ($block_type == 'core/search') {
        $args['render_callback'] = 'curator_render_search_block';
    }
    return $args;
}, null, 3);


/** 
 * Modify weekly poem posts so that each line is a separate paragraph, which is needed
 * for proper indentation
 */
function curator_autop_poem($content) {

    $credits = '';
    $poem_end = strpos($content, '<hr');
    if ($poem_end !== false) {
        $credits = substr($content, $poem_end);
        $content = substr($content, 0, $poem_end);
    }

    $content = wpautop($content);

    $content = str_replace('<p>', '<p></p><p class="poem-line">', $content);
    $content = preg_replace('#<br\s*/?>#', '</p><p class="poem-line">', $content);

    return $content . $credits;
}


/**
 * Render poss that were made in the classic editor
 */
function curator_render_post_content($content, $type) {
    // Ignore queries and posts made in the block editor
    $pos = strpos($content, '<div class=');
    if (!is_single() || $pos !== false) {
        if ($type == 'poem') {
            return curator_autop_poem($content);
        } else {
            return $content;
        }
    }

    // Remove 'more' tag and other rendering comments
    $content = preg_replace('#<!--\s?.*\s?-->#', '', $content);

    // Style the author name
    $pos = strpos($content, '<h6');
    if ($pos !== false) {
        $h6_pos = strpos($content, '<h6', $pos + 1);
        while ($h6_pos !== false) {
            $start = strpos($content, '>', $h6_pos);
            $end = false;
            if ($start !== false) {
                $end = strpos($content, '</', $start);
            }
            if ($end !== false) {
                if (preg_match('#>\s*[Bb]y .+#', substr($content, $start, $end - $start))) {
                    $pos = $h6_pos;
                    break;
                }
            }
            $h6_pos = strpos($content, '<h6', $h6_pos + 1);
        }
        $content = substr_replace(
            $content,
            '<p class="has-montserrat-font-family wp-block-paragraph" id="author" style="margin-top:0;margin-bottom:0"><i', 
            $pos,
            3
        );
        $pos = strpos($content, '</h6>', $pos);
        if ($pos !== false) {
            $content = substr_replace($content, '</i></p>', $pos, 5);
        }
    }

    // Get featured image
    $featured_image = get_the_post_thumbnail_url(null, "large");

    // Get post text
    $length = strlen($content);
    $text = $content;
    $pos = strpos($content, '<hr');
    while ($pos !== false && $length - $pos > 900) {
        $pos = strpos($content, '<hr', $pos + 1);
    }
    if ($pos === false) {
        $pos = strpos($content, '<img');
        while ($pos !== false && $length - $pos > 900) {
            $pos = strpos($content, '<img', $pos + 1);
        }
    } else if (strpos($content, '<img', $pos) === false) {
        $img_pos = strpos($content, '<img');
        while ($img_pos !== false && $length - $img_pos > 900) {
            $img_pos = strpos($content, '<img', $img_pos + 1);
        }
        if ($img_pos !== false) {
            $pos = $img_pos;
        }
    }
    if ($pos !== false) {
        $text = substr($content, 0, $pos);
    }
    if ($type == 'poem') {
        $text = curator_autop_poem($text);
    } else {
        $text = wpautop($text);
    }

    // Get author image
    $author_image = '';
    $pos = strpos($content, '<img');
    while ($pos !== false && $length - $pos > 900) {
        $pos = strpos($content, '<img', $pos + 1);
    }
    if ($pos !== false) {
        $pos = strpos($content, 'src="', $pos);
    }
    if ($pos !== false) {
        $pos += 5;
        $end = strpos($content, '"', $pos);
        $author_image = substr($content, $pos, $end - $pos);
    }

    // Get bio
    $bio = '';
    if ($pos !== false) {
        $pos = strpos($content, '>', $pos);
    }
    if ($pos === false) {
        $pos = strpos($content, '<hr');
        while ($pos !== false && $length - $pos > 900) {
            $pos = strpos($content, '<hr', $pos + 1);
        }
        if ($pos !== false) {
            $pos = strpos($content, '>', $pos);
        }
    }
    if ($pos !== false) {
        $bio = substr($content, $pos + 1);
    }

    // Get photo credits
    $credits = '';
    if ($bio) {
        $pos = strpos($bio, '<hr');
        if ($pos !== false) {
            $credits = substr($bio, $pos);
            $bio = substr($bio, 0, $pos);
        }
    }

    // Get links to adjacent posts
    $exclude = [
        get_cat_ID('blog'),
        get_cat_ID('stories'),
        get_cat_ID('weekly-poems'),
        get_cat_ID('poetry-videos'),
    ];
    if ($type == 'video') {
        unset($exclude[3]);
    } else if ($type == 'poem') {
        unset($exclude[2]);
    } else if ($type == 'story') {
        unset($exclude[1]);
    } else if ($type == 'blog') {
        unset($exclude[0]);
    }
    $next = get_next_post_link('%link', '%title', true, $exclude);
    $prev = get_previous_post_link('%link', '%title', true, $exclude);

    $content = '
<div class="wp-block-group has-custom-curator-light-gra-background-color has-background has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
  <div class="wp-block-group curator-post-container is-layout-flow wp-block-group-is-layout-flow" style="padding-right:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);';
    if ($type != 'poem') {
        $content .= 'max-width:800px;';
    }
    $content .= '">
    <div class="wp-block-group curator-post-block has-custom-white-background-color has-background is-vertical is-content-justification-stretch is-nowrap is-layout-flex wp-block-group-is-layout-flex" style="flex-wrap:nowrap;gap:0;flex-direction:column;align-items:stretch;border-top-left-radius:30px;border-top-right-radius:30px;border-bottom-left-radius:30px;border-bottom-right-radius:30px;margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);box-shadow:var(--wp--preset--shadow--natural)">
';
    
    if ($featured_image) {
        $content .= '
      <figure class="wp-block-post-featured-image">
        <img fetchpriority="high" decoding="async" src="' . $featured_image .
          '" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="" style="height:auto;max-height:calc(min(100vw - 86px,548px)*0.75);max-width:100%;width:100%;border-top-left-radius:30px;border-top-right-radius:30px;object-fit:cover;" />
      </figure>
';
    }
    
    $content .= '
      <div class="wp-block-group curator-post-text is-layout-flow wp-block-group-is-layout-flow" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
        ' . close_tags($text) . '
      </div>
    </div>
';
    
    if ($bio) {
        $content .= '
    <div class="wp-block-group curator-post-block curator-post-text has-custom-white-background-color has-background has-global-padding is-layout-constrained wp-block-group-is-layout-constrained" style="flex-wrap:nowrap;justify-content:flex-start;border-top-left-radius:30px;border-top-right-radius:30px;border-bottom-left-radius:30px;border-bottom-right-radius:30px;margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding:var(--wp--preset--spacing--50);box-shadow:var(--wp--preset--shadow--natural)">
      <div class="wp-block-group is-content-justification-center is-nowrap is-layout-flex wp-block-group-is-layout-flex" style="flex-wrap:nowrap;gap:var(--wp--preset--spacing--30);justify-content:center;">
';
        if ($author_image) {
            $content.= '
        <figure class="wp-block-image size-full has-custom-border is-style-rounded" style="flex-basis:25%;flex-shrink:0;">
          <img src ="' . $author_image . '" alt="" class="has-border-color has-custom-curator-rose-border-color" style="border-width:4px;aspect-ratio:1;object-fit:cover"/>
        </figure>
';
        }
        $content .= '
        <div style="flex-basis:75%;flex-shrink:1;">
' . close_tags($bio) . '
        </div>
      </div>
' . close_tags($credits) . '
    </div>
';
    }
    
    $content .= '
  </div>
  <div class="wp-block-group ';
    if ($type == 'poem') {
        $content .= 'curator-poem-navigation';
    } else {
        $content .= 'curator-blog-navigation';
    }
    $content .= ' is-content-justification-space-between is-nowrap is-layout-flex wp-block-group-is-layout-flex" style="flex-wrap:nowrap;justify-content:space-between;">
    <div class="post-navigation-link-previous wp-block-post-navigation-link">';
    if ($prev !== '') {
        $content .= $prev;
    }
    $content .= '
    </div>
    <div class="post-navigation-link-next wp-block-post-navigation-link">';
    if ($next !== '') {
        $content .= $next;
    }
    $content .= '</div></div></div>';

    return $content;
}

add_filter(
    'the_content',
    function ($content) {
        foreach (get_the_category() as $category) {
            if ($category->slug == 'poetry-videos') {
                $content = curator_render_post_content($content, 'video');
            } else if ($category->slug == 'blog') {
                $content = curator_render_post_content($content, 'blog');
            } else if ($category->slug == 'stories') {
                $content = curator_render_post_content($content, 'story');
            } else if ($category->slug == 'weekly-poems') {
                $content = curator_render_post_content($content, 'poem');
            }
        }
        return $content;
    }
);


/**
 * Preload cover background images (to make the home page render faster)
 */
function preload_cover_images() {
    global $post;
    if (is_null($post)) {
        return;
    }
    $urls = array();
    $content = $post->post_content;
    $offset = strpos($content, 'wp-block-cover__image-background');
    while ($offset !== false) {
        $url_start = strpos($content, 'src="', $offset);
        if ($url_start !== false) {
            $url_start += 5;
            $url_end = strpos($content, '"', $url_start);
            if ($url_end !== false) {
                $urls[] = substr($content, $url_start, $url_end - $url_start);
            }
        }
        $offset = strpos($content, 'wp-block-cover__image-background', $offset + 22);
    }
    foreach ($urls as $url) {
        printf('<link rel="preload" as="image" href="%s"/>', esc_url($url));
        print "\n";
    }
}

add_action('wp_head', 'preload_cover_images');


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

/**
 * Prevent unauthenticated REST API access
 */
add_filter( 'rest_authentication_errors', function( $result ) {
    // If a previous authentication check was applied,
    // pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'You are not currently logged in.' ),
            array( 'status' => 401 )
        );
    }

    // Our custom authentication check should have no effect
    // on logged-in requests
    return $result;
});

?>