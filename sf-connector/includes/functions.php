<?php

/** Mask CC Numbers */
if ( ! function_exists( 'cc_masking' ) ){
	function cc_masking( $number ) {
		return substr( $number, 0, 4 ) . str_repeat( "X", strlen( $number ) - 8 ) . substr( $number, - 4 );
	}
}

/** Mask CC Numbers */
if ( ! function_exists( 'cc_last_four' ) ){
	function cc_last_four( $number ) {
		return substr( $number, - 4 );
	}
}

//** Convert State */
if  ( ! function_exists( 'convert_state' ) ){
	function convert_state( $name, $to = 'name' ) {

		if ( strlen( $name ) == 2 ) {
			return $name;
		}

		$states = array(
			array( 'name' => 'Alabama', 'abbrev' => 'AL' ),
			array( 'name' => 'Alaska', 'abbrev' => 'AK' ),
			array( 'name' => 'Arizona', 'abbrev' => 'AZ' ),
			array( 'name' => 'Arkansas', 'abbrev' => 'AR' ),
			array( 'name' => 'California', 'abbrev' => 'CA' ),
			array( 'name' => 'Colorado', 'abbrev' => 'CO' ),
			array( 'name' => 'Connecticut', 'abbrev' => 'CT' ),
			array( 'name' => 'Delaware', 'abbrev' => 'DE' ),
			array( 'name' => 'Florida', 'abbrev' => 'FL' ),
			array( 'name' => 'Georgia', 'abbrev' => 'GA' ),
			array( 'name' => 'Hawaii', 'abbrev' => 'HI' ),
			array( 'name' => 'Idaho', 'abbrev' => 'ID' ),
			array( 'name' => 'Illinois', 'abbrev' => 'IL' ),
			array( 'name' => 'Indiana', 'abbrev' => 'IN' ),
			array( 'name' => 'Iowa', 'abbrev' => 'IA' ),
			array( 'name' => 'Kansas', 'abbrev' => 'KS' ),
			array( 'name' => 'Kentucky', 'abbrev' => 'KY' ),
			array( 'name' => 'Louisiana', 'abbrev' => 'LA' ),
			array( 'name' => 'Maine', 'abbrev' => 'ME' ),
			array( 'name' => 'Maryland', 'abbrev' => 'MD' ),
			array( 'name' => 'Massachusetts', 'abbrev' => 'MA' ),
			array( 'name' => 'Michigan', 'abbrev' => 'MI' ),
			array( 'name' => 'Minnesota', 'abbrev' => 'MN' ),
			array( 'name' => 'Mississippi', 'abbrev' => 'MS' ),
			array( 'name' => 'Missouri', 'abbrev' => 'MO' ),
			array( 'name' => 'Montana', 'abbrev' => 'MT' ),
			array( 'name' => 'Nebraska', 'abbrev' => 'NE' ),
			array( 'name' => 'Nevada', 'abbrev' => 'NV' ),
			array( 'name' => 'New Hampshire', 'abbrev' => 'NH' ),
			array( 'name' => 'New Jersey', 'abbrev' => 'NJ' ),
			array( 'name' => 'New Mexico', 'abbrev' => 'NM' ),
			array( 'name' => 'New York', 'abbrev' => 'NY' ),
			array( 'name' => 'North Carolina', 'abbrev' => 'NC' ),
			array( 'name' => 'North Dakota', 'abbrev' => 'ND' ),
			array( 'name' => 'Ohio', 'abbrev' => 'OH' ),
			array( 'name' => 'Oklahoma', 'abbrev' => 'OK' ),
			array( 'name' => 'Oregon', 'abbrev' => 'OR' ),
			array( 'name' => 'Pennsylvania', 'abbrev' => 'PA' ),
			array( 'name' => 'Rhode Island', 'abbrev' => 'RI' ),
			array( 'name' => 'South Carolina', 'abbrev' => 'SC' ),
			array( 'name' => 'South Dakota', 'abbrev' => 'SD' ),
			array( 'name' => 'Tennessee', 'abbrev' => 'TN' ),
			array( 'name' => 'Texas', 'abbrev' => 'TX' ),
			array( 'name' => 'Utah', 'abbrev' => 'UT' ),
			array( 'name' => 'Vermont', 'abbrev' => 'VT' ),
			array( 'name' => 'Virginia', 'abbrev' => 'VA' ),
			array( 'name' => 'Washington', 'abbrev' => 'WA' ),
			array( 'name' => 'West Virginia', 'abbrev' => 'WV' ),
			array( 'name' => 'Wisconsin', 'abbrev' => 'WI' ),
			array( 'name' => 'Wyoming', 'abbrev' => 'WY' )
		);

		foreach ( $states as $state ) {
			if ( $to == 'name' ) {
				if ( strtolower( $state['abbrev'] ) == strtolower( $name ) ) {
					$return = $state['name'];
					break;
				}
			} else if ( $to == 'abbrev' ) {
				if ( strtolower( $state['name'] ) == strtolower( $name ) ) {
					$return = strtoupper( $state['abbrev'] );
					break;
				}
			}
		}

		return $return;
	}
}
