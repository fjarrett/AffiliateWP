<?php

class Affiliate_WP_Emails_Template_Tags {

	/**
	 * Container for storing all tags
	 *
	 * @since 1.2
	 */
	private $tags;

	/**
	 * Email args
	 *
	 * @since 1.2
	 */
	private $args;

	/**
	 * Add an email tag
	 *
	 * @since 1.2
	 *
	 * @param string   $tag  Email tag to be replace in email
	 * @param callable $func Hook to run when email tag is found
	 */
	public function add( $tag, $description, $func ) {
		if ( is_callable( $func ) ) {
			$this->tags[$tag] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func
			);
		}
	}

	/**
	 * Remove an email tag
	 *
	 * @since 1.2
	 *
	 * @param string $tag Email tag to remove hook from
	 */
	public function remove( $tag ) {
		unset( $this->tags[$tag] );
	}

	/**
	 * Check if $tag is a registered email tag
	 *
	 * @since 1.2
	 *
	 * @param string $tag Email tag that will be searched
	 *
	 * @return bool
	 */
	public function email_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}

	/**
	 * Returns a list of all email tags
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Search content for email tags and filter email tags through their hooks
	 *
	 * @param string $content Content to search for email tags
	 * @param array $args additional arguments
	 *
	 * @since 1.2
	 *
	 * @return string Content with email tags filtered out.
	 */
	public function do_tags( $content, $args = array() ) {

		// Check if there is atleast one tag added
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->args = $args;

		$new_content = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'do_tag' ), $content );

		$this->args = null;

		return $new_content;
	}

	/**
	 * Do a specific tag, this function should not be used. Please use affwp_do_email_tags instead.
	 *
	 * @since 1.2
	 *
	 * @param $m message
	 *
	 * @return mixed
	 */
	public function do_tag( $m ) {

		// Get tag
		$tag = $m[1];

		// Return tag if tag not set
		if ( ! $this->email_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[$tag]['func'], $this->args, $tag );
	}

}


/**
 * Add an email tag
 *
 * @since 1.2
 *
 * @param string   $tag  Email tag to be replace in email
 * @param callable $func Hook to run when email tag is found
 */
function affwp_add_email_tag( $tag, $description, $func ) {
	affiliate_wp()->email_tags->add( $tag, $description, $func );
}

/**
 * Remove an email tag
 *
 * @since 1.2
 *
 * @param string $tag Email tag to remove hook from
 */
function affwp_remove_email_tag( $tag ) {
	affiliate_wp()->email_tags->remove( $tag );
}

/**
 * Check if $tag is a registered email tag
 *
 * @since 1.2
 *
 * @param string $tag Email tag that will be searched
 *
 * @return bool
 */
function affwp_email_tag_exists( $tag ) {
	return affiliate_wp()->email_tags->email_tag_exists( $tag );
}

/**
 * Get all email tags
 *
 * @since 1.2
 *
 * @return array
 */
function affwp_get_email_tags() {
	return affiliate_wp()->email_tags->get_tags();
}

/**
 * Get a formatted HTML list of all available email tags
 *
 * @since 1.2
 *
 * @return string
 */
function affwp_get_emails_tags_list() {
	// The list
	$list = '';

	// Get all tags
	$email_tags = affwp_get_email_tags();

	// Check
	if ( count( $email_tags ) > 0 ) {

		// Loop
		foreach ( $email_tags as $email_tag ) {

			// Add email tag to list
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>';

		}

	}

	// Return the list
	return $list;
}

/**
 * Search content for email tags and filter email tags through their hooks
 *
 * @param string $content Content to search for email tags
 *
 * @since 1.2
 *
 * @return string Content with email tags filtered out.
 */
function affwp_do_email_tags( $content, $args = array() ) {

	// Replace all tags
	$content = affiliate_wp()->email_tags->do_tags( $content, $args );

	// Maintaining backwards compatibility
	$content = apply_filters( 'affwp_email_template_tags', $content, $args );

	// Return content
	return $content;
}

/**
 * Load email tags
 *
 * @since 1.2
 */
function affwp_load_email_tags() {
	do_action( 'affwp_add_email_tags' );
}
add_action( 'init', 'affwp_load_email_tags', -999 );

/**
 * Add default AffiliateWP email template tags
 *
 * @since 1.2
 */
function affwp_setup_email_tags() {

	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'affiliate_name',
			'description' => __( "The name of the affiliate", 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_affiliate_name'
		),
		array(
			'tag'         => 'site_name',
			'description' => __( 'Your site name', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_site_name'
		),
		array(
			'tag'         => 'site_url',
			'description' => __( 'Your site URL', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_site_url'
		),
		array(
			'tag'         => 'login_url',
			'description' => __( 'The affiliate login URL', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_login_url'
		),
		array(
			'tag'         => 'referral_amount',
			'description' => __( 'The commission amount rewarded to the affiliate', 'affiliate-wp' ),
			'function'    => 'affwp_email_tag_referral_amount'
		),

	);

	// Apply affwp_email_tags filter
	$email_tags = apply_filters( 'affwp_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		affwp_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
	}

}
add_action( 'affwp_add_email_tags', 'affwp_setup_email_tags' );



/**
 * Email template tag: site_name
 * Your site name
 *
 *
 * @return string site name
 */
function affwp_email_tag_site_name() {
	return get_bloginfo( 'name' );
}

/**
 * Email template tag: affiliate_name
 * The affiliate who registered
 *
 *
 * @return string affiliate name
 */
function affwp_email_tag_affiliate_name( $args ) {
	return affiliate_wp()->affiliates->get_affiliate_name( $args['affiliate_id'] );
}

/**
 * Email template tag: site_url
 * Your site URL
 *
 *
 * @return string home URL
 */
function affwp_email_tag_site_url() {
	return home_url();
}

/**
 * Email template tag: login_url
 * The affiliate login URL
 *
 *
 * @return string affiliate login URL
 */
function affwp_email_tag_login_url() {
	return affiliate_wp()->login->get_login_url();
}

/**
 * Email template tag: login_url
 * The affiliate login URL
 *
 *
 * @return string affiliate login URL
 */
function affwp_email_tag_referral_amount( $args ) {
	return html_entity_decode( affwp_currency_filter( $args['amount'] ), ENT_COMPAT, 'UTF-8' );
}



