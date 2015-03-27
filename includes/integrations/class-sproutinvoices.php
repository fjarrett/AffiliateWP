<?php

class Affiliate_WP_Sprout_Invoices extends Affiliate_WP_Base {

	public function init() {
		$this->context = 'sproutinvoices';

		add_action( 'payment_authorized', array( $this, 'add_pending_referral' ) );
		add_action( 'payment_complete', array( $this, 'mark_referral_complete' ) );
		add_action( 'si_void_payment', array( $this, 'revoke_referral_on_void' ), 10, 2 );

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

	}

	public function add_pending_referral( SI_Payment $payment ) {

		if( $this->was_referred() ) {
			$payment_id = $payment->get_id();
			$referral_total = $this->calculate_referral_amount( $payment->get_amount(), $payment_id );
			$this->insert_pending_referral( $referral_total, $payment_id, $payment->get_title() );
		}

	}

	public function mark_referral_complete( SI_Payment $payment ) {
		
		$this->complete_referral( $entry['id'] );

		$referral = affiliate_wp()->referrals->get_by( 'reference', $entry['id'], $this->context );
		$amount   = affwp_currency_filter( affwp_format_amount( $referral->amount ) );
		$name     = affiliate_wp()->affiliates->get_affiliate_name( $referral->affiliate_id );
		$note     = sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral->referral_id, $amount, $name );

		$new_data = wp_parse_args( $payment->get_data(), array( 'affwp_notes' => $note ) );
		$payment->set_data( $new_data );
	}

	public function revoke_referral_on_refund( $payment_id = 0 ) {
		$this->reject_referral( $payment_id );

		$referral = affiliate_wp()->referrals->get_by( 'reference', $payment_id, $this->context );
		$amount   = affwp_currency_filter( affwp_format_amount( $referral->amount ) );
		$name     = affiliate_wp()->affiliates->get_affiliate_name( $referral->affiliate_id );
		$note     = sprintf( __( 'Referral #%d for %s for %s rejected', 'affiliate-wp' ), $referral->referral_id, $amount, $name );

		$payment = SI_Payment::get_instance( $payment_id );
		$new_data = wp_parse_args( $payment->get_data(), array( 'affwp_notes' => $note ) );
		$payment->set_data( $new_data );
	}

	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || $this->context != $referral->context ) {
			return $reference;
		}

		$payment = SI_Payment::get_instance( $payment_id );
		$invoice_id = $payment->get_invoice_id();
		$url = get_edit_post_link( $invoice_id );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

}
new Affiliate_WP_Sprout_Invoices;