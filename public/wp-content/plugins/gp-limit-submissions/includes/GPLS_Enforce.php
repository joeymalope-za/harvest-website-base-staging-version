<?php

class GPLS_Enforce {
	private $form_id     = 0;
	private $rule_groups = array(); // array of GPLS_RuleGroup objects
	private $test_result;

	public static $field_values = array();

	public function __construct() {

		// check for required minimum version of Gravity Forms
		if ( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.8', '>=' ) ) {
			return;
		}
		$this->init();
	}

	public function init() {

		// enforce limits
		add_filter( 'gform_pre_render', array( $this, 'pre_render' ), 10, 3 );
		add_filter( 'gform_validation', array( $this, 'validate' ) );
		// Gravity Flow validation
		add_filter( 'gravityflow_validation_user_input', array( $this, 'validate' ) );
		// notification handling
		add_action( 'gform_entry_created', array( $this, 'maybe_send_notification' ), 10, 2 );
		add_filter( 'gform_notification_events', array( $this, 'notification_events' ) );
		add_filter( 'gform_notification', array( $this, 'add_notification_sent_entry_meta' ), 10, 3 );
	}

	/*
	 * Fires on the gform_entry_created action
	 */
	public function maybe_send_notification( $entry, $form ) {

		$this->form_id = $form['id'];
		// limit form if limit reached
		if ( $this->is_limit_reached() ) {
			$this->send_limit_reached_notifications( $form, $entry );
		}
	}

	public function notification_events( $notification_events ) {

		$gpls  = gp_limit_submissions();
		$feeds = $gpls->get_feeds( rgget( 'id' ) );

		if ( empty( $feeds ) ) {
			return $notification_events;
		}

		$notification_events['gpls_limit_reached'] = __( 'Submission limit reached', 'gp-limit-submissions' );

		if ( ! empty( $feeds ) ) {
			foreach ( $feeds as $feed ) {

				if ( $feed['addon_slug'] != 'gp-limit-submissions' ) {
					continue;
				}
				$name = sprintf( __( 'Submission limit reached (%s)', 'gp-limit-submissions' ), $feed['meta']['rule_group_name'] );
				$notification_events[ 'gpls_limit_reached_feed_' . $feed['id'] ] = $name;
			}
		}

		return $notification_events;
	}

	public function get_feeds( $form_id ) {
		$gpls = gp_limit_submissions();

		return $gpls->get_active_feeds( $form_id );
	}

	public function test() {

		// default test result properties
		$this->test_result[ $this->form_id ]                    = new stdClass;
		$this->test_result[ $this->form_id ]->fail              = false;
		$this->test_result[ $this->form_id ]->failed_rule_group = false;
		$this->test_result[ $this->form_id ]->tests             = array();
		/** @var GPLS_RuleGroup $rule_group */
		foreach ( $this->rule_groups[ $this->form_id ] as $rule_group ) {
			foreach ( $rule_group->get_rulesets() as $ruleset ) {

				$test                   = new GPLS_RuleTest;
				$test->rule_group       = $rule_group;
				$test->rules            = $ruleset;
				$test->limit            = $rule_group->get_limit();
				$test->form_id          = $this->form_id;
				$test->time_period      = $rule_group->get_time_period();
				$test->applicable_forms = $rule_group->get_applicable_forms();
				$test->limit_per_form   = $rule_group->is_limit_per_form();
				$test->run();
				// store test results
				$this->test_result[ $this->form_id ]->tests[] = $test;
				// store failure
				if ( $test->failed() ) {

					$this->test_result[ $this->form_id ]->fail              = true;
					$this->test_result[ $this->form_id ]->failed_rule_group = $rule_group;

					return $this->test_result[ $this->form_id ];
				}
			}
		}

		gp_limit_submissions()->log( sprintf( '%s: %s', __METHOD__, print_r( $this->test_result[ $this->form_id ], true ) ) );

		return $this->test_result[ $this->form_id ];
	}

	public function set_form_id( $form_id ) {
		$this->form_id = $form_id;
	}

	public function get_test_result() {
		return $this->test_result[ $this->form_id ];
	}

	public function set_rule_groups( $rule_groups ) {

		/**
		 * Filter the rule groups that will be enforced for this form.
		 *
		 * @since 1.0-beta-1
		 *
		 * @param array $rule_groups An array of GPLS_RuleGroup objects.
		 * @param int   $form_id     The current form ID for which rules are being enforced.
		 */
		$this->rule_groups[ $this->form_id ] = gf_apply_filters( array( 'gpls_rule_groups', $this->form_id ), $rule_groups, $this->form_id );

		foreach ( $this->rule_groups[ $this->form_id ] as &$rule_group ) {
			$rule_group->populate_applicable_forms( $this->form_id );
		}

	}

	public function get_rule_groups() {
		return $this->rule_groups[ $this->form_id ];
	}

	public function is_limit_reached( $field_values = array() ) {

		$this->set_rule_groups( GPLS_RuleGroup::load_by_form( $this->form_id, $field_values ) );
		if ( empty( $this->rule_groups[ $this->form_id ] ) ) {
			return false;
		}
		// test rules
		$this->test();
		if ( $this->test_result[ $this->form_id ]->fail == true ) {
			return true;
		}

		return false;
	}

	public function enforce_limit() {

		$submission_info = rgar( GFFormDisplay::$submission, $this->form_id );
		if ( ! $submission_info || ! rgar( $submission_info, 'is_valid' ) ) {
			// Overwriting the form's markup breaks AJAX, use `gform_validation_message` instead
			$filter_name = ( rgpost( 'gform_ajax' ) ) ? 'gform_validation_message_' : 'gform_get_form_filter_';
			add_filter( $filter_name . $this->form_id, array( $this, 'get_limit_message' ), 10, 2 );
		}
	}

	public function get_limit_message( $form_string = '', $form = null ) {

		// replace merge tags
		if ( ! $form ) {
			$form = GFAPI::get_form( $this->form_id );
		}

		$message = $this->test_result[ $form['id'] ]->failed_rule_group->get_message();
		if ( empty( $message ) ) {
			$message = __( 'The submission limit has been reached for this form.', 'gp-limit-submissions' );
		}

		/**
		 * Filter the message that shows if the submission limit for a form has been reached.
		 *
		 * @since 1.0-beta-2.7
		 *
		 * @param string $message  The "submission limit has been reached" error message
		 * @param array  $form     The current GF form array
		 * @param GPLS_Enforce $gpls_enforce_instance  The GPLS_Enforce instance.
		 */
		$message = gf_apply_filters( array( 'gpls_limit_message', $form['id'] ), $message, $form, $this );

		$entry = false;
		if ( rgpost( 'gform_submit' ) ) {
			$entry = GFFormsModel::get_current_lead();
		}

		$message = GFCommon::replace_variables( $message, $form, $entry, false, false, false, 'html' );
		ob_start();
		?>
		<div id="gpls-limit-message-container-<?php echo $this->form_id; ?>" class="gpls-limit-message-container">
			<div class="gpls-limit-message">
				<?php echo do_shortcode( $message ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function pre_render( $form, $ajax, $field_values ) {

		self::$field_values = $field_values;

		// set form id
		$this->form_id = $form['id'];
		if ( $this->should_enforce_on_render( $form, $field_values ) ) {
			$this->enforce_limit();
		}

		return $form;
	}

	public function should_enforce_on_render( $form, $field_values ) {

		$should_enforce = $this->is_limit_reached( $field_values ) && ( ! $this->is_limited_by_field_value() || $this->has_render_enforceable_field_value_limit() );

		/**
		 * Filter whether Limit Submissions should be enforced on render.
		 *
		 * By default, only field-based rules are exempted from being enforced on render as the user may change the value
		 * before submitting the form; however, fields that are hidden are enforced on render.
		 *
		 * @param bool          $should_enforce Should Limit Submissions be enforced on render?
		 * @param array         $form           The meta for the form being rendered.
		 * @param array         $field_values   An array of dynamic population field values used to populate the form.
		 * @param \GPLS_Enforce $gpls_enforce   The current instance of the GPLS_Enforce class.
		 *
		 * @since 1.1.5
		 */
		return gf_apply_filters( array( 'gpls_should_enforce_on_render', $this->form_id ), $should_enforce, $form, $field_values, $this );
	}

	public function validate( $validation_result ) {

		$this->form_id = $validation_result['form']['id'];

		// check if limit reached
		if ( ! $this->is_limit_reached() ) {
			return $validation_result;
		}
		$validation_result['is_valid'] = false;
		if ( $this->is_limited_by_field_value() ) {
			$field_ids = array_map( 'intval', $this->get_limit_field_ids() );
			foreach ( $validation_result['form']['fields'] as &$field ) {
				if ( in_array( $field['id'], $field_ids ) ) {
					$field['failed_validation'] = gf_apply_filters( array(
						'gpls_field_failed_validation',
						$this->form_id,
						$field->id,
					), true, $this );

					$field['validation_message'] = gf_apply_filters( array(
						'gpls_field_validation_message',
						$this->form_id,
						$field->id,
					), do_shortcode( $this->get_limit_message() ), $this );
				}
			}
		}

		return $validation_result;
	}

	public function is_limited_by_field_value() {

		$failed_rule_group = $this->test_result[ $this->form_id ]->failed_rule_group;
		$rulesets          = $failed_rule_group->get_rulesets();
		if ( empty( $rulesets ) ) {
			return false;
		}
		foreach ( $rulesets as $ruleset ) {
			foreach ( $ruleset as $rule ) {
				if ( $rule->get_type() == 'field' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array $feed
	 *
	 * @return bool
	 */
	public function has_conditional_logic( $feed ) {
		$feed_meta            = rgar( $feed, 'meta' );
		$is_condition_enabled = rgar( $feed_meta, 'feed_condition_conditional_logic' ) == true;
		$logic                = rgars( $feed_meta, 'feed_condition_conditional_logic_object/conditionalLogic' );

		if ( ! $is_condition_enabled || empty( $logic ) ) {
			return false;
		}

		return true;
	}

	public function has_render_enforceable_field_value_limit() {
		$failed_rule_group = $this->test_result[ $this->form_id ]->failed_rule_group;
		$feed              = gp_limit_submissions()->get_feed( $this->test_result[ $this->form_id ]->failed_rule_group->get_feed_id() );
		$rulesets          = $failed_rule_group->get_rulesets();

		if ( empty( $rulesets ) || $this->has_conditional_logic( $feed ) ) {
			return false;
		}

		foreach ( $rulesets as $ruleset ) {
			foreach ( $ruleset as $rule ) {
				if ( $rule->get_type() == 'field' ) {
					$field = GFAPI::get_field( $failed_rule_group->form_id, $rule->get_field() );

					if ( $field && ( $field->visibility === 'hidden' || $field->get_input_type() === 'hidden' ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function get_limit_field_ids() {

		$field_ids         = array();
		$failed_rule_group = $this->test_result[ $this->form_id ]->failed_rule_group;
		foreach ( $failed_rule_group->get_rulesets() as $ruleset ) {
			foreach ( $ruleset as $rule ) {
				if ( $rule->get_type() == 'field' ) {
					$field_ids[] = $rule->get_field();
				}
			}
		}

		return $field_ids;
	}

	public function send_limit_reached_notifications( $form, $entry ) {

		// main notification
		$notifications = GFCommon::get_notifications_to_send( 'gpls_limit_reached', $form, $entry );
		$ids           = array();
		foreach ( $notifications as $notification ) {
			if ( ! gform_get_meta( $entry['id'], 'notification_' . $notification['id'] ) ) {
				$ids[] = $notification['id'];
			}
		}
		// feed notifications
		$feed_notifications = $this->get_feed_notifications( $form, $entry ); // get all feed notifications
		if ( ! empty( $feed_notifications ) ) {

			// get the failed feed id, check if it has any notification events setup
			$failed_feed_id = $this->test_result[ $this->form_id ]->failed_rule_group->get_feed_id();
			if ( array_key_exists( $failed_feed_id, $feed_notifications ) && is_array( $feed_notifications[ $failed_feed_id ] ) ) {

				// found notification events for the failing feed id
				foreach ( $feed_notifications[ $failed_feed_id ] as $feed_notification ) {

					// add to send notification list of ids if not already sent
					if ( ! gform_get_meta( $entry['id'], 'notification_' . $feed_notification['id'] ) ) {
						$ids[] = $feed_notification['id'];
					}
				}
			}
		}
		GFCommon::send_notifications( $ids, $form, $entry, true, true );
	}

	/*
	* @return (array) feed notifications indexed by the feed id
	 */
	public function get_feed_notifications( $form, $entry ) {

		$notifications = array();
		$gpls          = gp_limit_submissions();
		$feeds         = $gpls->get_active_feeds();
		if ( empty( $feeds ) ) {
			return $notifications;
		}
		foreach ( $feeds as $feed ) {
			$notifications[ $feed['id'] ] = GFCommon::get_notifications_to_send( 'gpls_limit_reached_feed_' . $feed['id'], $form, $entry );
		}

		return $notifications;
	}

	public function add_notification_sent_entry_meta( $notification, $form, $entry ) {
		if ( rgar( $notification, 'event' ) == 'gpls_limit_reached' ) {
			gform_update_meta( $entry['id'], "notification_{$notification['id']}", 1 );
		}

		return $notification;
	}
}
