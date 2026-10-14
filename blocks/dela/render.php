<?php
/**
 * Render: wpu/dela – same output as [wpu_dela].
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

$wpu_dela_post = (int) ( $block->context['postId'] ?? 0 ) ?: get_the_ID();

echo wpu_dela_html( $wpu_dela_post ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in wpu_dela_html().
