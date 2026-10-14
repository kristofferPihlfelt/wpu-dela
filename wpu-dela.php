<?php
/**
 * Plugin Name:       WPU Dela
 * Plugin URI:        https://wordpressutveckling.se/
 * Description:       Enkla delningslänkar för inlägg: LinkedIn, Facebook, X, e-post och Kopiera länk. Vanliga länkar utan externa skript, spårning, räknare eller kakor.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Author:            Pihlfelt & Vänner
 * Author URI:        https://pihlfelt.se
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpu-dela
 */

defined( 'ABSPATH' ) || exit;

/** Every network the plugin knows, in the fixed display order. */
function wpu_dela_networks(): array {
	return array(
		'linkedin' => 'LinkedIn',
		'facebook' => 'Facebook',
		'x'        => 'X',
		'epost'    => __( 'E-post', 'wpu-dela' ),
		'kopiera'  => __( 'Kopiera länk', 'wpu-dela' ),
	);
}

/** One setting; the option is stored as a single array. */
function wpu_dela_setting( string $key ) {
	$defaults = array(
		'networks'   => array_keys( wpu_dela_networks() ),
		'placement'  => 'after', // after | before | both | off.
		'post_types' => array( 'post' ),
	);
	$saved = (array) get_option( 'wpu_dela_settings', array() );
	return array_key_exists( $key, $saved ) ? $saved[ $key ] : $defaults[ $key ];
}

/* 1. Markup ---------------------------------------------------------------- */

/** Small monochrome icons; they follow the text colour. */
function wpu_dela_icon( string $name ): string {
	$stroke = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
	$paths  = array(
		'linkedin' => '<path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3zm6.5 0h3.8v1.6h.06c.53-1 1.83-2.06 3.77-2.06 4.03 0 4.77 2.65 4.77 6.1V21h-4v-5.1c0-1.22-.02-2.79-1.7-2.79-1.7 0-1.96 1.33-1.96 2.7V21h-4z"/>',
		'facebook' => '<path d="M14 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.2c-.3 0-1.3-.2-2.5-.2-2.5 0-4.2 1.5-4.2 4.3v2.2H7.5V12h2.8v10H14V12h2.8l.4-3.5z"/>',
		'x'        => '<path d="M17.75 3h3.07l-6.7 7.66L22 21h-6.17l-4.83-6.32L5.47 21H2.4l7.17-8.2L2 3h6.33l4.37 5.78zm-1.08 16.2h1.7L7.4 4.73H5.58z"/>',
		'epost'    => '<path ' . $stroke . ' d="M3.5 5.5h17v13h-17zm0 .5 8.5 7 8.5-7"/>',
		'kopiera'  => '<path ' . $stroke . ' d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1-1"/>',
		'dela'     => '<path ' . $stroke . ' d="M12 15V3.5M7.5 8 12 3.5 16.5 8M5 12.5V20h14v-7.5"/>',
	);
	return '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Share links for a post; empty string when there is nothing to share. */
function wpu_dela_html( $post = null ): string {
	$post     = get_post( $post );
	$networks = array_intersect( array_keys( wpu_dela_networks() ), (array) wpu_dela_setting( 'networks' ) );
	if ( ! $post || ! $networks ) {
		return '';
	}

	// Plain permalink and title; no tracking parameters.
	$url   = (string) get_permalink( $post );
	$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
	$u     = rawurlencode( $url );
	$t     = rawurlencode( $title );
	$hrefs = array(
		'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u,
		'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u,
		'x'        => 'https://x.com/intent/tweet?url=' . $u . '&text=' . $t,
		'epost'    => 'mailto:?subject=' . $t . '&body=' . $u,
	);
	$names = wpu_dela_networks();
	$items = '';
	foreach ( $networks as $key ) {
		if ( 'kopiera' === $key ) {
			// Needs JavaScript, so it stays hidden until the script has checked the clipboard API.
			$items .= '<li class="wpu-dela__kopiera" hidden><button type="button" class="wpu-dela__lank">' . wpu_dela_icon( 'kopiera' ) . esc_html( $names[ $key ] ) . '</button></li>';
			continue;
		}
		$external = 'epost' !== $key
			? ' target="_blank" rel="noopener noreferrer"'
			: '';
		$new_tab  = 'epost' !== $key
			? '<span class="wpu-dela__sr"> ' . esc_html__( '(öppnas i ny flik)', 'wpu-dela' ) . '</span>'
			: '';
		$items   .= sprintf(
			'<li class="wpu-dela__natverk"><a class="wpu-dela__lank" href="%s"%s>%s%s%s</a></li>',
			esc_url( $hrefs[ $key ], array( 'https', 'mailto' ) ),
			$external,
			wpu_dela_icon( $key ),
			esc_html( $names[ $key ] ),
			$new_tab
		);
	}

	wpu_dela_enqueue();

	return sprintf(
		'<nav class="wpu-dela" aria-label="%1$s" data-url="%2$s" data-titel="%3$s" data-kopierad="%4$s" data-fel="%5$s">'
			. '<span class="wpu-dela__rubrik">%6$s</span>'
			. '<ul><li class="wpu-dela__inbyggd" hidden><button type="button" class="wpu-dela__lank">%7$s%8$s</button></li>%9$s</ul>'
			. '<span class="wpu-dela__status" role="status" aria-live="polite"></span>'
		. '</nav>',
		esc_attr__( 'Dela inlägget', 'wpu-dela' ),
		esc_url( $url ),
		esc_attr( $title ),
		esc_attr__( 'Länken är kopierad', 'wpu-dela' ),
		esc_attr__( 'Länken kunde inte kopieras', 'wpu-dela' ),
		esc_html__( 'Dela', 'wpu-dela' ),
		wpu_dela_icon( 'dela' ),
		esc_html__( 'Dela inlägget', 'wpu-dela' ),
		$items
	);
}

/* 2. Assets: only on pages where the share row is printed ------------------ */

add_action( 'init', 'wpu_dela_register_assets', 9 );
function wpu_dela_register_assets(): void {
	$file = __DIR__ . '/dela.js';
	wp_register_script( 'wpu-dela', plugins_url( 'dela.js', __FILE__ ), array(), (string) filemtime( $file ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	// A handful of rules, inlined instead of an extra request. Themes can dequeue "wpu-dela" or override.
	wp_register_style( 'wpu-dela', false, array(), '1.0.0' );
	wp_add_inline_style( 'wpu-dela', wpu_dela_css() );
}

function wpu_dela_css(): string {
	return '.wpu-dela{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem 1rem;margin-block:2rem;font-size:.9rem}'
		. '.wpu-dela__rubrik{font-weight:600}'
		. '.wpu-dela ul{display:flex;flex-wrap:wrap;gap:.4rem 1rem;margin:0;padding:0;list-style:none}'
		. '.wpu-dela [hidden],.wpu-dela--inbyggd .wpu-dela__natverk,.wpu-dela--inbyggd .wpu-dela__rubrik{display:none}'
		. '.wpu-dela__lank{display:inline-flex;align-items:center;gap:.35em;padding:0;border:0;background:none;color:inherit;font:inherit;text-decoration:none;cursor:pointer}'
		. '.wpu-dela__lank:hover,.wpu-dela__lank:focus-visible{text-decoration:underline;text-underline-offset:3px}'
		. '.wpu-dela svg{width:1em;height:1em;flex:none}'
		. '.wpu-dela__sr{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap}';
}

function wpu_dela_enqueue(): void {
	wp_enqueue_script( 'wpu-dela' );
	wp_enqueue_style( 'wpu-dela' );
}

/* 3. Placement ------------------------------------------------------------- */

add_filter( 'the_content', 'wpu_dela_auto', 20 );
function wpu_dela_auto( $content ) {
	static $done = array();
	$placement = (string) wpu_dela_setting( 'placement' );
	$post      = get_post();
	if (
		'off' === $placement
		|| ! $post
		|| is_admin() || is_feed() || wp_is_serving_rest_request() || wp_is_json_request()
		|| doing_filter( 'get_the_excerpt' )
		|| ! is_main_query() || ! in_the_loop()
		|| ! is_singular( (array) wpu_dela_setting( 'post_types' ) )
		|| $post->ID !== get_queried_object_id()
		|| isset( $done[ $post->ID ] )
		// Already placed by hand with the shortcode or the block.
		|| has_shortcode( $post->post_content, 'wpu_dela' ) || has_block( 'wpu/dela', $post )
		|| ! apply_filters( 'wpu_dela_auto', true, $post )
	) {
		return $content;
	}
	$done[ $post->ID ] = true;
	$row               = wpu_dela_html( $post );
	return match ( $placement ) {
		'before' => $row . $content,
		'both'   => $row . $content . $row,
		default  => $content . $row,
	};
}

/** Template tag: <?php wpu_dela(); ?> or do_action( 'wpu_dela' ). */
function wpu_dela( $post = null ): void {
	echo wpu_dela_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in wpu_dela_html().
}
add_action( 'wpu_dela', 'wpu_dela' );

add_shortcode( 'wpu_dela', static fn() => wpu_dela_html() );

add_action( 'init', 'wpu_dela_register_block' );
function wpu_dela_register_block(): void {
	register_block_type( __DIR__ . '/blocks/dela' );
}

/* 4. Settings page (Inställningar → Dela) ---------------------------------- */

add_action( 'admin_menu', 'wpu_dela_admin_menu' );
function wpu_dela_admin_menu(): void {
	add_options_page( __( 'Dela', 'wpu-dela' ), __( 'Dela', 'wpu-dela' ), 'manage_options', 'wpu-dela', 'wpu_dela_settings_page' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpu_dela_action_links' );
function wpu_dela_action_links( array $links ): array {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=wpu-dela' ) ) . '">' . esc_html__( 'Inställningar', 'wpu-dela' ) . '</a>' );
	return $links;
}

add_action( 'admin_init', 'wpu_dela_register_setting' );
function wpu_dela_register_setting(): void {
	register_setting(
		'wpu_dela',
		'wpu_dela_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wpu_dela_sanitize_settings',
			'default'           => array(),
		)
	);
}

/** Public post types, attachments excluded. */
function wpu_dela_available_post_types(): array {
	$types = get_post_types( array( 'public' => true ) );
	unset( $types['attachment'] );
	return array_values( $types );
}

function wpu_dela_placements(): array {
	return array(
		'after'  => __( 'Efter innehållet', 'wpu-dela' ),
		'before' => __( 'Före innehållet', 'wpu-dela' ),
		'both'   => __( 'Både före och efter', 'wpu-dela' ),
		'off'    => __( 'Av (bara kortkod, block eller mallfunktion)', 'wpu-dela' ),
	);
}

function wpu_dela_sanitize_settings( $in ): array {
	$in        = is_array( $in ) ? $in : array();
	$networks  = array_map( 'sanitize_key', (array) ( $in['networks'] ?? array() ) );
	$types     = array_map( 'sanitize_key', (array) ( $in['post_types'] ?? array() ) );
	$placement = sanitize_key( (string) ( $in['placement'] ?? 'after' ) );
	return array(
		'networks'   => array_values( array_intersect( array_keys( wpu_dela_networks() ), $networks ) ),
		'placement'  => array_key_exists( $placement, wpu_dela_placements() ) ? $placement : 'after',
		'post_types' => array_values( array_intersect( wpu_dela_available_post_types(), $types ) ),
	);
}

function wpu_dela_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$networks  = (array) wpu_dela_setting( 'networks' );
	$placement = (string) wpu_dela_setting( 'placement' );
	$types     = (array) wpu_dela_setting( 'post_types' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Dela', 'wpu-dela' ); ?></h1>
		<p><?php esc_html_e( 'Delningslänkar under (eller över) inläggen. Vanliga länkar: inga externa skript, ingen spårning, inga räknare och inga kakor.', 'wpu-dela' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'wpu_dela' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Tjänster', 'wpu-dela' ); ?></th>
					<td>
						<fieldset>
						<?php foreach ( wpu_dela_networks() as $key => $name ) : ?>
							<label style="display:block;margin-bottom:4px"><input type="checkbox" name="wpu_dela_settings[networks][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $networks, true ) ); ?>> <?php echo esc_html( $name ); ?></label>
						<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Visas i den här ordningen. På mobiler med inbyggd delning ersätts tjänsterna av en knapp som öppnar telefonens delningsmeny.', 'wpu-dela' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatisk placering', 'wpu-dela' ); ?></th>
					<td>
						<fieldset>
						<?php foreach ( wpu_dela_placements() as $key => $label ) : ?>
							<label style="display:block;margin-bottom:4px"><input type="radio" name="wpu_dela_settings[placement]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $placement, $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
						</fieldset>
						<p class="description">
							<?php
							/* translators: 1: shortcode, 2: block name, 3: PHP template tag. */
							printf( esc_html__( 'Manuellt: kortkoden %1$s, blocket %2$s eller %3$s i temat. Står kortkoden eller blocket redan i inlägget läggs ingen automatisk rad till.', 'wpu-dela' ), '<code>[wpu_dela]</code>', '<code>Dela</code>', '<code>&lt;?php wpu_dela(); ?&gt;</code>' );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Visas på', 'wpu-dela' ); ?></th>
					<td>
						<fieldset>
						<?php foreach ( wpu_dela_available_post_types() as $type ) : ?>
							<?php $obj = get_post_type_object( $type ); ?>
							<label style="display:block;margin-bottom:4px"><input type="checkbox" name="wpu_dela_settings[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $types, true ) ); ?>> <?php echo esc_html( $obj ? $obj->labels->name : $type ); ?></label>
						<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Gäller den automatiska placeringen.', 'wpu-dela' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
