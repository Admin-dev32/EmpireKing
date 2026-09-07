<?php
/**
 * Header template.
 *
 * @package Empire_King
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header" data-header>
	<div class="site-header__inner">
		<button class="header-icon-button header-menu-toggle" type="button" aria-label="<?php esc_attr_e( 'Open navigation menu', 'empire-king' ); ?>" aria-expanded="false" aria-controls="mobile-navigation">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
		</button>
		<p class="site-title site-header__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Empire King Burger home', 'empire-king' ); ?>">
				<?php $logo_url = empire_king_get_logo_url(); ?>
				<?php if ( $logo_url ) : ?>
					<img class="site-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Empire King Burger', 'empire-king' ); ?>">
				<?php else : ?>
					<?php esc_html_e( 'Empire King Burger', 'empire-king' ); ?>
				<?php endif; ?>
			</a>
		</p>
		<nav class="primary-navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'empire-king' ); ?>">
			<?php empire_king_render_primary_navigation( 'primary-menu' ); ?>
		</nav>
		<a class="header-order-link" href="<?php echo esc_url( home_url( '/#order' ) ); ?>">Order Now</a>
		<a class="header-icon-button header-location-link" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>" aria-label="<?php esc_attr_e( 'Choose a location', 'empire-king' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg>
		</a>
	</div>
	<dialog id="mobile-navigation" class="mobile-navigation" aria-label="<?php esc_attr_e( 'Mobile navigation', 'empire-king' ); ?>">
		<div class="mobile-navigation__topbar">
			<button class="header-icon-button mobile-navigation__close" type="button" aria-label="<?php esc_attr_e( 'Close navigation menu', 'empire-king' ); ?>">
				<svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" /></svg>
			</button>
			<p class="site-title mobile-navigation__brand"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Empire King Burger home', 'empire-king' ); ?>"><?php if ( $logo_url ) : ?><img class="site-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Empire King Burger', 'empire-king' ); ?>"><?php else : ?><?php esc_html_e( 'Empire King Burger', 'empire-king' ); ?><?php endif; ?></a></p>
			<a class="header-icon-button" href="<?php echo esc_url( home_url( '/#locations' ) ); ?>" aria-label="<?php esc_attr_e( 'Choose a location', 'empire-king' ); ?>">
				<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.25" /></svg>
			</a>
		</div>
		<nav class="mobile-navigation__nav" aria-label="<?php esc_attr_e( 'Mobile primary navigation', 'empire-king' ); ?>">
			<?php empire_king_render_primary_navigation( 'mobile-primary-menu' ); ?>
		</nav>
		<a class="button button--primary mobile-navigation__order" href="<?php echo esc_url( home_url( '/#order' ) ); ?>">Order Now</a>
	</dialog>
</header>
<main id="primary" class="site-content">
