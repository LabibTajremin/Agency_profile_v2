<?php
/**
 * Registration form controls and markup, shared by the "Registration Form"
 * and "Benefits + Form" widgets.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use QeEdu\Core\Forms\Countries;
use QeEdu\Core\Forms\Defaults;
use QeEdu\Core\Forms\Handler;

defined( 'ABSPATH' ) || exit;

/**
 * Registration form trait.
 */
trait RegistrationFormTrait {

	/**
	 * Register the form controls (fields, steps, labels, messages, email).
	 *
	 * @param array<string,mixed> $condition Elementor condition for every section.
	 * @return void
	 */
	protected function register_form_controls( $condition = array() ) {
		$d = Defaults::registration();

		$this->start_controls_section(
			'section_form_fields',
			array(
				'label'     => __( 'Form fields', 'qe-edu' ),
				'condition' => $condition,
			)
		);

		$fields = new Repeater();
		$fields->add_control(
			'label',
			array(
				'label'       => __( 'Label', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'New field', 'qe-edu' ),
				'label_block' => true,
			)
		);
		$fields->add_control(
			'type',
			array(
				'label'   => __( 'Type', 'qe-edu' ),
				'type'    => Controls_Manager::SELECT,
				'options' => Defaults::field_types(),
				'default' => 'text',
			)
		);
		$fields->add_control(
			'required',
			array(
				'label'        => __( 'Required', 'qe-edu' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);
		$fields->add_control(
			'placeholder',
			array(
				'label'       => __( 'Hint inside the box', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$fields->add_control(
			'options',
			array(
				'label'       => __( 'Choices (one per line)', 'qe-edu' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'condition'   => array( 'type' => 'select' ),
				'label_block' => true,
			)
		);
		$fields->add_control(
			'width',
			array(
				'label'   => __( 'Width', 'qe-edu' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'full' => __( 'Full width', 'qe-edu' ),
					'half' => __( 'Half width (side by side on computers)', 'qe-edu' ),
				),
				'default' => 'full',
			)
		);
		$fields->add_control(
			'step',
			array(
				'label'   => __( 'Step', 'qe-edu' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'1' => __( 'Step 1', 'qe-edu' ),
					'2' => __( 'Step 2', 'qe-edu' ),
				),
				'default' => '1',
			)
		);
		$this->add_control(
			'fields',
			array(
				'label'       => __( 'Fields', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $fields->get_controls(),
				'default'     => $d['fields'],
				'title_field' => '{{{ label }}}',
			)
		);
		$this->add_control(
			'countries',
			array(
				'label'       => __( 'Countries in the "Country list" field (one per line)', 'qe-edu' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => $d['countries'],
				'rows'        => 8,
				'label_block' => true,
			)
		);
		$this->add_text( 'select_placeholder', __( 'First line of drop-downs', 'qe-edu' ), $d['select_placeholder'] );
		$this->add_textarea( 'consent_text', __( 'Consent checkbox text (leave empty for no checkbox)', 'qe-edu' ), $d['consent_text'] );
		$this->add_switch(
			'create_account',
			__( 'Create a website account (uses the Password field)', 'qe-edu' ),
			$d['create_account'],
			array( 'description' => __( 'Only works when Settings → General → "Anyone can register" is ticked; otherwise password fields are hidden.', 'qe-edu' ) )
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_steps',
			array(
				'label'     => __( 'Steps & buttons', 'qe-edu' ),
				'condition' => $condition,
			)
		);
		$this->add_switch( 'multi_step', __( 'Split into two steps', 'qe-edu' ), $d['multi_step'] );
		$this->add_text( 'step_1_title', __( 'Step 1 title', 'qe-edu' ), $d['step_1_title'], array( 'condition' => array( 'multi_step' => 'yes' ) ) );
		$this->add_text( 'step_2_title', __( 'Step 2 title', 'qe-edu' ), $d['step_2_title'], array( 'condition' => array( 'multi_step' => 'yes' ) ) );
		$this->add_text( 'next_label', __( '"Next" button', 'qe-edu' ), $d['next_label'], array( 'condition' => array( 'multi_step' => 'yes' ) ) );
		$this->add_text( 'back_label', __( '"Back" button', 'qe-edu' ), $d['back_label'], array( 'condition' => array( 'multi_step' => 'yes' ) ) );
		$this->add_text( 'submit_label', __( 'Send button', 'qe-edu' ), $d['submit_label'] );
		$this->add_text( 'sending_label', __( 'Send button while sending', 'qe-edu' ), $d['sending_label'] );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_messages',
			array(
				'label'     => __( 'Messages & email', 'qe-edu' ),
				'condition' => $condition,
			)
		);
		$this->add_text(
			'recipient',
			__( 'Send new registrations to (email)', 'qe-edu' ),
			$d['recipient'],
			array(
				'description' => __( 'Leave empty to use Theme Settings → Contact & Social → Email.', 'qe-edu' ),
				'qe_scope'    => 'server',
			)
		);
		$this->add_text( 'email_subject', __( 'Email subject', 'qe-edu' ), $d['email_subject'], array( 'qe_scope' => 'server' ) );
		$this->add_textarea( 'success_message', __( 'Thank-you message', 'qe-edu' ), $d['success_message'], array( 'qe_scope' => 'server' ) );
		$this->add_textarea( 'error_message', __( 'Error message', 'qe-edu' ), $d['error_message'], array( 'qe_scope' => 'server' ) );
		$this->add_text( 'required_message', __( 'Empty required field', 'qe-edu' ), $d['required_message'] );
		$this->add_text( 'invalid_message', __( 'Wrong format', 'qe-edu' ), $d['invalid_message'] );
		$this->add_textarea( 'rate_message', __( 'Too many attempts', 'qe-edu' ), $d['rate_message'], array( 'qe_scope' => 'server' ) );
		$this->add_textarea( 'account_exists_message', __( 'Account already exists', 'qe-edu' ), $d['account_exists_message'], array( 'qe_scope' => 'server' ) );
		$this->end_controls_section();
	}

	/**
	 * Autocomplete hint for a field.
	 *
	 * @param array<string,string> $field Field.
	 * @return string
	 */
	public static function autocomplete( $field ) {
		$by_id   = array(
			'first_name' => 'given-name',
			'last_name'  => 'family-name',
			'dob'        => 'bday',
		);
		$by_type = array(
			'email'    => 'email',
			'tel'      => 'tel',
			'country'  => 'country-name',
			'password' => 'new-password',
		);

		if ( isset( $by_id[ $field['_id'] ] ) ) {
			return $by_id[ $field['_id'] ];
		}

		return isset( $by_type[ $field['type'] ] ) ? $by_type[ $field['type'] ] : 'on';
	}

	/**
	 * Print one field.
	 *
	 * @param array<string,string> $field    Field.
	 * @param array<string,mixed>  $settings Widget settings.
	 * @return void
	 */
	protected function print_form_field( $field, $settings ) {
		$id       = 'qe-' . $this->get_id() . '-' . sanitize_key( $field['_id'] );
		$name     = 'qe_' . $field['_id'];
		$required = 'yes' === $field['required'];
		$attrs    = sprintf(
			'id="%1$s" name="%2$s" autocomplete="%3$s"%4$s data-type="%5$s"',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( self::autocomplete( $field ) ),
			$required ? ' required aria-required="true"' : '',
			esc_attr( $field['type'] )
		);

		printf( '<div class="qe-form__field qe-form__field--%s">', 'half' === $field['width'] ? 'half' : 'full' );
		printf(
			'<label for="%1$s" class="qe-form__label">%2$s%3$s</label>',
			esc_attr( $id ),
			esc_html( $field['label'] ),
			$required ? ' <span class="qe-form__req" aria-hidden="true">*</span>' : ''
		);

		if ( in_array( $field['type'], array( 'country', 'select' ), true ) ) {
			$choices = Countries::parse( 'country' === $field['type'] ? $settings['countries'] : $field['options'] );
			printf( '<select %s class="qe-form__input">', $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			printf( '<option value="">%s</option>', esc_html( $settings['select_placeholder'] ) );
			foreach ( $choices as $choice ) {
				printf( '<option value="%1$s">%1$s</option>', esc_attr( $choice ) );
			}
			echo '</select>';
		} elseif ( 'textarea' === $field['type'] ) {
			printf( '<textarea %1$s rows="4" class="qe-form__input" placeholder="%2$s"></textarea>', $attrs, esc_attr( $field['placeholder'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		} else {
			printf(
				'<input type="%1$s" %2$s class="qe-form__input" placeholder="%3$s"%4$s>',
				esc_attr( $field['type'] ),
				$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_attr( $field['placeholder'] ),
				'password' === $field['type'] ? ' minlength="8"' : ''
			);
		}

		echo '<p class="qe-form__error" role="alert" hidden></p></div>';
	}

	/**
	 * Print the whole form.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return void
	 */
	protected function print_form( $settings ) {
		$settings = array_merge( Defaults::registration(), $settings );
		$fields   = Handler::active_fields( $settings );
		$steps    = 'yes' === $settings['multi_step'] ? array( '1', '2' ) : array( '1' );
		$multi    = count( $steps ) > 1;

		printf(
			'<form class="qe-form" method="post" action="%1$s" novalidate data-qe-form="register" data-required="%2$s" data-invalid="%3$s" data-sending="%4$s">',
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( $settings['required_message'] ),
			esc_attr( $settings['invalid_message'] ),
			esc_attr( $settings['sending_label'] )
		);
		echo '<input type="hidden" name="action" value="qe_edu_register">';
		printf( '<input type="hidden" name="post_id" value="%d">', absint( $this->document_id() ) );
		printf( '<input type="hidden" name="form_id" value="%s">', esc_attr( $this->get_id() ) );
		printf( '<input type="hidden" name="nonce" value="%s">', esc_attr( wp_create_nonce( Handler::NONCE ) ) );
		printf(
			'<div class="qe-form__hp" aria-hidden="true"><label>%s<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>',
			esc_html__( 'Leave this field empty', 'qe-edu' )
		);

		if ( $multi ) {
			printf(
				'<ol class="qe-form__progress"><li class="is-active" aria-current="step">%1$s</li><li>%2$s</li></ol>',
				esc_html( $settings['step_1_title'] ),
				esc_html( $settings['step_2_title'] )
			);
		}

		foreach ( $steps as $index => $step ) {
			printf( '<fieldset class="qe-form__step" data-step="%1$s"%2$s>', esc_attr( $step ), $index > 0 ? ' hidden' : '' );
			if ( $multi ) {
				printf( '<legend class="screen-reader-text">%s</legend>', esc_html( $settings[ 'step_' . $step . '_title' ] ) );
			}
			echo '<div class="qe-form__grid">';
			foreach ( $fields as $field ) {
				if ( ! $multi || (string) $field['step'] === $step ) {
					$this->print_form_field( $field, $settings );
				}
			}
			echo '</div></fieldset>';
		}

		if ( '' !== trim( (string) $settings['consent_text'] ) ) {
			printf(
				'<div class="qe-form__consent"%1$s><label><input type="checkbox" name="qe_consent" value="1" required> %2$s</label><p class="qe-form__error" role="alert" hidden></p></div>',
				$multi ? ' data-step-only="2" hidden' : '',
				wp_kses_post( $settings['consent_text'] )
			);
		}

		echo '<div class="qe-form__actions">';
		if ( $multi ) {
			printf( '<button type="button" class="qe-btn qe-btn--ghost" data-qe-back hidden>%s</button>', esc_html( $settings['back_label'] ) );
			printf( '<button type="button" class="qe-btn" data-qe-next>%s</button>', esc_html( $settings['next_label'] ) );
		}
		printf( '<button type="submit" class="qe-btn" data-qe-submit%1$s>%2$s</button>', $multi ? ' hidden' : '', esc_html( $settings['submit_label'] ) );
		echo '</div><div class="qe-form__message" role="status" aria-live="polite"></div></form>';
	}
}
