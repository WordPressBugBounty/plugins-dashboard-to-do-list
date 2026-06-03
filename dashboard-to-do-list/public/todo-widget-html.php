<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ardtdw_widget_html' ) ) {
	function ardtdw_widget_html() {
		$raw_content = stripslashes( get_option( 'ardtdw-textarea' ) );
		$position    = esc_attr( get_option( 'ardtdw-position', 'right' ) );
		$color       = sanitize_hex_color( get_option( 'ardtdw-color', '' ) );

		// Build list items, handling DONE marker
		$lines = explode( PHP_EOL, $raw_content );
		$items = '';
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) continue;

			if ( preg_match( '/\s+done\s*$/i', $line ) ) {
				$line    = preg_replace( '/\s+done\s*$/i', '', $line );
				$items  .= '<li class="ardtdw-done">' . $line . '</li>';
			} else {
				$items .= '<li>' . $line . '</li>';
			}
		}

		$color_style     = $color ? esc_attr( $color ) : 'var(--wp-admin-theme-color)';
		$position_class  = in_array( $position, array( 'left', 'right' ), true ) ? $position : 'right';
		
		echo '<div id="ardtdw-sitewidget" class="ardtdw-sitewidget ardtdw-' . $position_class . '">';
		echo '<div class="ardtdw-sitewidget-inner">';

		echo '<div class="ardtdw-sitewidget-head" style="background-color:' . $color_style . '">';
		echo '<p>' . esc_html__( 'To-Do List', 'dashboard-to-do-list' ) . '</p>';
		echo '<div class="ardtdw-head-icons">';
		echo '<a href="' . esc_url( admin_url() ) . '" target="_blank" title="' . esc_attr__( 'Edit list', 'dashboard-to-do-list' ) . '" class="ardtdw-cog-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" ><path d="M24 13.616v-3.232c-1.651-.587-2.694-.752-3.219-2.019v-.001c-.527-1.271.1-2.134.847-3.707l-2.285-2.285c-1.561.742-2.433 1.375-3.707.847h-.001c-1.269-.526-1.435-1.576-2.019-3.219h-3.232c-.582 1.635-.749 2.692-2.019 3.219h-.001c-1.271.528-2.132-.098-3.707-.847l-2.285 2.285c.745 1.568 1.375 2.434.847 3.707-.527 1.271-1.584 1.438-3.219 2.02v3.232c1.632.58 2.692.749 3.219 2.019.53 1.282-.114 2.166-.847 3.707l2.285 2.286c1.562-.743 2.434-1.375 3.707-.847h.001c1.27.526 1.436 1.579 2.019 3.219h3.232c.582-1.636.75-2.69 2.027-3.222h.001c1.262-.524 2.12.101 3.698.851l2.285-2.286c-.744-1.563-1.375-2.433-.848-3.706.527-1.271 1.588-1.44 3.221-2.021zm-12 2.384c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4z"/></svg></a>';
		echo '<button type="button" class="ardtdw-toggle" aria-label="' . esc_attr__( 'Toggle list', 'dashboard-to-do-list' ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M0 7.33l2.829-2.83 9.175 9.339 9.167-9.339 2.829 2.83-11.996 12.17z"/></svg></button>';
		echo '</div>';
		echo '</div>';

		echo '<div class="ardtdw-sitewidget-list">';
		echo '<ul>' . $items . '</ul>';
		echo '</div>';

		echo '</div>';
		echo '</div>';
	}
}
