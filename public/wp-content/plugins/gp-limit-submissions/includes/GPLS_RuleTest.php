<?php

class GPLS_RuleTest {
	public $form_id;
	public $rule_group;
	public $rules;
	public $limit;
	public $limit_message;
	public $time_period;
	public $applicable_forms;
	public $limit_per_form;
	public $context = true;
	public $count;
	public $where = array();
	public $join  = array();
	public $fail  = false;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function failed() {
		return $this->fail;
	}

	private function builtin_context_tests() {

		// Don't test when editing an entry via Nested Forms.
		if ( class_exists( 'GP_Nested_Forms' ) && rgpost( 'action' ) == 'gpnf_edit_entry' ) {
			return false;
		}

		// Don't test when editing via Gravity View.
		/**
		 * Specify whether rules should be bypassed in GravityView.
		 *
		 * @since 1.0-beta-1.19
		 *
		 * @param bool $bypass Whether GravityView should be bypassed. Defaults to true.
		 */
		if ( apply_filters( 'gpls_bypass_gravityview', true, $this ) && is_callable( 'gravityview_get_context' ) && gravityview_get_context() == 'edit' ) {
			return false;
		}

		// Don't test when editing via Sticky List.
		if ( is_callable( 'stickylist_fs' ) && rgpost( 'mode' ) == 'edit' && rgpost( 'edit_id' ) ) {
			return false;
		}

		foreach ( $this->rules as $rule ) {
			if ( ! $rule->context() ) {
				return false;
			}
		}

		return true;
	}

	public function context_test() {
		$should_apply_rules = $this->builtin_context_tests();
		/**
		 * Allows filtering of whether or not the limit submission rules should be applied.
		 *
		 * @param bool $should_apply_rules Whether or not we should apply the limit submission rules.
		 * @param int $form_id The ID of the form being tested.
		 * @param GPLS_RuleTest $gpls_rule_test The current instance of the GPLS_RuleTest class.
		 *
		 * @since 1.1.6
		 */
		return apply_filters( 'gpls_should_apply_rules', $should_apply_rules, $this->form_id, $this );
	}

	public function run() {

		// verify we need to test this ruleset
		if ( ! $this->context_test() ) {
			$this->count   = 0;
			$this->context = false;

			return; // do not run tests because at least 1 rule is out of context
		}
		// setup and run query
		foreach ( $this->rules as $rule ) {
			$this->query_rules( $rule );
		}

		$this->query_run();

		// compare count to limit
		if ( $this->count >= $this->limit ) {
			$this->fail = true;
		}
	}

	public function query_setup() {

		$this->where[] = "e.status = 'active'";
		if ( is_array( $this->applicable_forms ) /*|| $this->limit_per_form*/ ) {
			$this->where[] = sprintf( 'e.form_id IN( %s )', implode( ', ', array_map( 'intval', $this->applicable_forms ) ) );
		} else {
			$this->where[] = $this->wpdb->prepare( 'e.form_id = %d', $this->form_id );
		}

		if ( class_exists( 'GF_Partial_Entries' ) ) {
			$feeds = GFAPI::get_feeds( null, $this->form_id, 'gravityformspartialentries' );
			if ( ! empty( $feeds ) && rgars( $feeds, '0/meta/enable' ) ) {
				$this->where[] = "e.id NOT IN( SELECT entry_id FROM {$this->wpdb->prefix}gf_entry_meta WHERE meta_key = 'partial_entry_id' )";
			}
		}

		// Add an exception for current entry when in a Gravity Flow workflow
		$lid = rgget( 'lid' );

		// page is fetched from $_REQUEST since Gravity Flow unsets it on $_GET.
		if ( $lid && rgget( 'view' ) === 'entry' && rgar( $_REQUEST, 'page' ) === 'gravityflow-inbox' ) {
			$this->where[] = $this->wpdb->prepare( "e.id != %d", $lid );
		}

	}

	public function query_run() {

		/**
		 * Do something (like modify the GPLS_RuleTest object) before the query is executed.
		 *
		 * @since 1.0-beta-1.20
		 *
		 * @param GPLS_RuleTest $gpls_rule_test The current instance of the GPLS_RuleTest object.
		 */
		do_action( 'gpls_before_query', $this );

		if ( empty( $this->where ) ) {
			$this->count = 0;

			return;
		}

		$this->query_setup();
		$this->query_time_period();

		$where = implode( ' AND ', $this->where );
		$join  = implode( "\n", $this->join );

		$sql = "SELECT count( e.id )
      			FROM {$this->wpdb->prefix}gf_entry e
      			$join
      			WHERE $where";

		$this->count = $this->wpdb->get_var( $sql );
	}

	public function query_rules( $rule ) {

		$rule->set_query_data( array(
			'form_id' => $this->form_id,
		) );
		$query = $rule->query();
		if ( ! $query ) {
			return; // return false to make no changes to the query
		}
		if ( is_array( $query ) ) {
			if ( ! empty( $query['where'] ) ) {
				if ( is_array( $query['where'] ) ) {
					$this->where = array_merge( $this->where, $query['where'] );
				} else {
					$this->where[] = $query['where'];
				}
			}
			if ( ! empty( $query['join'] ) ) {
				if ( is_array( $query['join'] ) ) {
					$this->join = array_merge( $this->join, $query['join'] );
				} else {
					$this->join[] = $query['join'];
				}
			}
		} else {
			// a non-array string is for the common singular where addition
			$this->where[] = $query;
		}
	}

	public function query_rules_count( $rule ) {

		$rule->set_query_data( array(
			'form_id' => $this->form_id,
		) );
		$query = $rule->query_count();
		if ( ! $query ) {
			return; // return false to make no changes to the query
		}
		if ( is_array( $query ) ) {
			if ( ! empty( $query['where'] ) ) {
				$this->where[] = $query['where'];
			}
			if ( ! empty( $query['join'] ) ) {
				$this->join[] = $query['join'];
			}
		} else {
			// a non-array string is for the common singular where addition
			$this->where[] = $query;
		}
	}

	public function get_limit_field_value( $rule ) {

		$form     = GFAPI::get_form( $this->form_id );
		$values   = array();
		$field_id = $rule->get_field();
		$field    = GFFormsModel::get_field( $form, $field_id );
		if ( ! $field ) {
			return $values;
		}
		$input_name = 'input_' . str_replace( '.', '_', $field_id );
		$value      = GFFormsModel::prepare_value( $form, $field, rgpost( $input_name ), $input_name, null );
		if ( ! rgblank( $value ) ) {
			$values[ "$field_id" ] = $value;
		}

		return $values;
	}

	public function query_time_period() {

		if ( ! $this->time_period ) {
			return;
		}
		// form schedule
		if ( $this->time_period['type'] == 'form_schedule' ) {

			$form = GFAPI::get_form( $this->form_id );
			if ( $form['scheduleForm'] == true ) {
				$this->add_form_schedule( $form );
			}
		}
		// time period
		if ( $this->time_period['type'] == 'time_period' ) {
			$this->add_time_period();
		}
		// calendar period
		if ( $this->time_period['type'] == 'calendar_period' ) {
			$this->add_calendar_period();
		}
	}

	public function add_form_schedule( $form ) {

		$time_start = sprintf( '%s %02d:%02d %s', $form['scheduleStart'], $form['scheduleStartHour'], $form['scheduleStartMinute'], $form['scheduleStartAmpm'] );
		$time_end   = sprintf( '%s %02d:%02d %s', $form['scheduleEnd'], $form['scheduleEndHour'], $form['scheduleEndMinute'], $form['scheduleEndAmpm'] );

		// Times are stored in local timezone. Convert to UNIX to match the UNIX timestamps used by entries' `date_created` property.
		$unix_time_start = get_gmt_from_date( $time_start );
		$unix_time_end   = get_gmt_from_date( $time_end );

		$time_period_sql = $this->wpdb->prepare( 'date_created BETWEEN %s AND %s', $unix_time_start, $unix_time_end );
		$this->where[]   = $time_period_sql;

	}

	public function add_calendar_period() {

		$gmt_offset        = get_option( 'gmt_offset' );
		$date_func         = $gmt_offset < 0 ? 'DATE_SUB' : 'DATE_ADD';
		$hour_offset       = abs( $gmt_offset );
		$date_created_sql  = sprintf( '%s( date_created, INTERVAL %d HOUR )', $date_func, $hour_offset );
		$utc_timestamp_sql = sprintf( '%s( utc_timestamp(), INTERVAL %d HOUR )', $date_func, $hour_offset );

		$calendar_period_callback = rgars( self::get_calendar_periods(), $this->time_period['value'] . '/sql_callback' );

		if ( ! $calendar_period_callback ) {
			return new WP_Error( 'gpls_unsupported_calendar_period' );
		}

		$time_period_sql = $calendar_period_callback( $date_created_sql, $utc_timestamp_sql );

		$this->where[] = $time_period_sql;
	}

	public static function get_calendar_periods() {
		/**
		 * Filter the available calendar periods available to Limit Submissions. Useful for adding custom periods
		 * such as periods starting on a specific day of the month.
		 *
		 * @param array $periods
		 *
		 * @since 1.1.4
		 */
		return apply_filters( 'gpls_calendar_periods', array(
			'day'     => array(
				'label'        => __( 'Per Day', 'gp-limit-submissions' ),
				'value'        => 'day',
				'sql_callback' => function( $date_created_sql, $utc_timestamp_sql ) {
					return "DATE( $date_created_sql ) = DATE( $utc_timestamp_sql )";
				},
			),
			'week'    => array(
				'label'        => __( 'Per Week', 'gp-limit-submissions' ),
				'value'        => 'week',
				'sql_callback' => function( $date_created_sql, $utc_timestamp_sql ) {
					$time_period_sql  = "WEEK( $date_created_sql ) = WEEK( $utc_timestamp_sql )";
					$time_period_sql .= "AND YEAR( $date_created_sql ) = YEAR( $utc_timestamp_sql )";

					return $time_period_sql;
				},
			),
			'month'   => array(
				'label'        => __( 'Per Month', 'gp-limit-submissions' ),
				'value'        => 'month',
				'sql_callback' => function( $date_created_sql, $utc_timestamp_sql ) {
					$time_period_sql  = "MONTH( $date_created_sql ) = MONTH( $utc_timestamp_sql )";
					$time_period_sql .= "AND YEAR( $date_created_sql ) = YEAR( $utc_timestamp_sql )";

					return $time_period_sql;
				},
			),
			'quarter' => array(
				'label'        => __( 'Per Quarter', 'gp-limit-submissions' ),
				'value'        => 'quarter',
				'sql_callback' => function( $date_created_sql, $utc_timestamp_sql ) {
					$time_period_sql  = "QUARTER( $date_created_sql ) = QUARTER( $utc_timestamp_sql )";
					$time_period_sql .= "AND YEAR( $date_created_sql ) = YEAR( $utc_timestamp_sql )";

					return $time_period_sql;
				},
			),
			'year'    => array(
				'label'        => __( 'Per Year', 'gp-limit-submissions' ),
				'value'        => 'year',
				'sql_callback' => function( $date_created_sql, $utc_timestamp_sql ) {
					return "YEAR( $date_created_sql ) = YEAR( $utc_timestamp_sql )";
				},
			),
		));
	}

	public function add_time_period() {

		$time_period_sql     = '';
		$time_period_seconds = 0;
		$time_period_value   = intval( $this->time_period['value'] );

		$gmt_offset        = get_option( 'gmt_offset' );
		$date_func         = $gmt_offset < 0 ? 'DATE_SUB' : 'DATE_ADD';
		$hour_offset       = abs( $gmt_offset );
		$date_created_sql  = sprintf( '%s( date_created, INTERVAL %d HOUR )', $date_func, $hour_offset );
		$utc_timestamp_sql = sprintf( '%s( utc_timestamp(), INTERVAL %d HOUR )', $date_func, $hour_offset );

		switch ( $this->time_period['unit'] ) {
			case 'seconds':
				$time_period_seconds = $time_period_value;
				break;
			case 'minutes':
				$time_period_seconds = $time_period_value * MINUTE_IN_SECONDS;
				break;
			case 'hours':
				$time_period_seconds = $time_period_value * HOUR_IN_SECONDS;
				break;
			case 'days':
				$time_period_seconds = $time_period_value * DAY_IN_SECONDS;
				break;
			case 'weeks':
				$time_period_seconds = $time_period_value * WEEK_IN_SECONDS;
				break;
			case 'months':
				$time_period_seconds = $time_period_value * MONTH_IN_SECONDS;
				break;
			case 'years':
				$time_period_seconds = $time_period_value * YEAR_IN_SECONDS;
				break;
		}
		$time_period_sql = $this->wpdb->prepare( 'date_created BETWEEN DATE_SUB(utc_timestamp(),INTERVAL %d SECOND) AND utc_timestamp()', $time_period_seconds );
		$this->where[]   = $time_period_sql;
	}
}
