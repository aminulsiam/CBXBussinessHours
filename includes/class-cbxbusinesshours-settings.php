<?php

/**
 * weDevs Settings API wrapper class
 *
 * @version 1.3 (27-Sep-2016)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link https://tareq.co Tareq Hasan
 * @example example/oop-example.php How to use the class
 */
if ( ! class_exists( 'CBXBusinessHoursSettings' ) ):
	class CBXBusinessHoursSettings {

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array
		 */
		function set_sections( $sections ) {
			$this->settings_sections = $sections;

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array $section
		 */
		function add_section( $section ) {
			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = $fields;

			return $this;
		}

		public function add_field( $section, $field ) {
			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text'
			);

			$arg                                 = wp_parse_args( $field, $defaults );
			$this->settings_fields[ $section ][] = $arg;

			return $this;
		}

		public function office_hours_form() { ?>
            <div></div>
		<?php }

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		function admin_init() {
			//register settings sections
			foreach ( $this->settings_sections as $section ) {
				if ( false == get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}

				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
					$callback        = function () use ( $section ) {
						echo str_replace( '"', '\"', $section['desc'] );
					};
				} else if ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			//register settings fields
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {

					$name     = $option['name'];
					$type     = isset( $option['type'] ) ? $option['type'] : 'text';
					$label    = isset( $option['label'] ) ? $option['label'] : '';
					$callback = isset( $option['callback'] ) ? $option['callback'] : array(
						$this,
						'callback_' . $type
					);

					$args = [
						'id'                => $name,
						'class'             => isset( $option['class'] ) ? $option['class'] : $name,
						'label_for'         => "{$section}[{$name}]",
						'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
						'name'              => $label,
						'section'           => $section,
						'size'              => isset( $option['size'] ) ? $option['size'] : null,
						'options'           => isset( $option['options'] ) ? $option['options'] : '',
						'std'               => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						'type'              => $type,
						'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
						'min'               => isset( $option['min'] ) ? $option['min'] : '',
						'max'               => isset( $option['max'] ) ? $option['max'] : '',
						'step'              => isset( $option['step'] ) ? $option['step'] : '',
					];

					add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
				}
			}

			// creates our settings in the options table
			foreach ( $this->settings_sections as $section ) {
				register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
			}
		}

		/**
		 * Get field description for display
		 *
		 * @param array $args settings field args
		 */
		public function get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_text( $args ) {
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$html        = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
			$html        .= $this->get_field_description( $args );
			echo $html;
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_time2( $args ) {
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			if ( ! is_array( $value ) ) {
				$value          = array();
				$value['start'] = '';
				$value['end']   = '';
			}

			if ( ! isset( $value['start'] ) ) {
				$value['start'] = '';
			}
			if ( ! isset( $value['end'] ) ) {
				$value['end'] = '';
			}

			$size = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type = isset( $args['type'] ) ? $args['type'] : 'text';
			//$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html = sprintf( '<input type="%1$s" class="%2$s-text2-0 timepicker" id="%3$s[%4$s]-0" name="%3$s[%4$s][start]" value="%5$s" placeholder="Opening Time"/>', $type, $size, $args['section'], $args['id'], $value['start'] );

			$html .= sprintf( '<input type="%1$s" class="%2$s-text2-1 timepicker" id="%3$s[%4$s]-1" name="%3$s[%4$s][end]" value="%5$s"  placeholder="Ending Time"/>', $type, $size, $args['section'], $args['id'], $value['end'] );


			$html .= $this->get_field_description( $args );
			echo $html;

		}

		function callback_time3( $args ) {
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			$value = ! is_array( $value ) ? array() : $value;

			/*if ( ! is_array( $value ) ) {
				$value          = array();
				$value['start'] = '';
				$value['end']   = '';
			}*/

			$size = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type = isset( $args['type'] ) ? $args['type'] : 'text';


			$dow = array(
				'sunday'    => esc_html__( 'Sunday', 'cbxbusinesshours' ),
				'monday'    => esc_html__( 'Monday', 'cbxbusinesshours' ),
				'tuesday'   => esc_html__( 'Tuesday', 'cbxbusinesshours' ),
				'wednesday' => esc_html__( 'Wednesday', 'cbxbusinesshours' ),
				'thursday'  => esc_html__( 'Thursday', 'cbxbusinesshours' ),
				'friday'    => esc_html__( 'Friday', 'cbxbusinesshours' ),
				'saturday'  => esc_html__( 'Saturday', 'cbxbusinesshours' )
			);

			foreach ( $dow as $key => $day ) {
				$start_value = isset( $value[ $key ]['start'] ) ? $value[ $key ]['start'] : '';
				$end_value   = isset( $value[ $key ]['end'] ) ? $value[ $key ]['end'] : '';

				$html = sprintf( '<div>' );
				$html .= sprintf( '<div class="labels"><label>' . $day . ' : </div></label>' );
				$html .= sprintf( '<div class="rightTab">' );
				$html .= sprintf( '<input type="%1$s" class="%2$s-text2-0 timepicker input-field" name="%3$s[%4$s][' . $key . '][start]" value="%5$s" placeholder="Opening Time"/>', $type, $size, $args['section'], $args['id'], $start_value );

				$html .= sprintf( '<input type="%1$s" class="%2$s-text2-1 timepicker input-field" name="%3$s[%4$s][' . $key . '][end]" value="%5$s"  placeholder="Ending Time"/>', $type, $size, $args['section'], $args['id'], $end_value );
				$html .= sprintf( '</div>' );
				$html .= sprintf( '</div>' );
				$html .= $this->get_field_description( $args );
				echo $html;
			}

		}// End of time3 method


		function callback_exceptionDay( $args ) {
			$exceptions_result = get_option( 'cbxbusinesshours_hours' );
			if ( ! is_array( $exceptions_result ) ) {
				$exceptions_result = array();
			}
			$exceptions    = isset( $exceptions_result['exceptions'] ) ? $exceptions_result['exceptions'] : array();
			$ex_last_count = isset( $exceptions_result['ex_last_count'] ) ? intval( $exceptions_result['ex_last_count'] ) : 0;
			?>
            <div class="ex_wrapper">
                <div class="ex_items">
					<?php
					if ( is_array( $exceptions ) && sizeof( $exceptions ) > 0 ) {
						foreach ( $exceptions as $key => $exception ) {
							?>
                            <p class="ex_item">

                                <input type="text" class="date"
                                       name="cbxbusinesshours_hours[exceptions][<?php echo esc_attr( $key ); ?>][ex_date]"
                                       value="<?php echo esc_attr( $exception['ex_date'] ) ?>">

                                <input type="text" class="timepicker" autocomplete="off"
                                       name='cbxbusinesshours_hours[exceptions][<?php echo esc_attr( $key ); ?>][ex_start]'
                                       value="<?php echo esc_attr( $exception['ex_start'] ) ?>">

                                <input type="text" class="timepicker" autocomplete="off"
                                       name='cbxbusinesshours_hours[exceptions][<?php echo esc_attr( $key ); ?>][ex_end]'
                                       value="<?php echo esc_attr( $exception['ex_end'] ) ?>">

                                <input type="text"
                                       name="cbxbusinesshours_hours[exceptions][<?php echo esc_attr( $key ); ?>][ex_subject]"
                                       value="<?php echo esc_attr( $exception['ex_subject'] ) ?>">

                                <a class="remove_exception button">
									<?php
									echo '<span class="dashicons dashicons-trash" style="margin-top: 3px;color: red;">
                                    </span>' . esc_html__( 'Remove', 'cbxbussinesshours' ); ?>
                                </a>
                            </p>

						<?php } // end foreach
					} // end if condition
					?>
                </div>
                <a class="add_exception button">
                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;color: darkgreen;"></span>
					<?php echo esc_html__( 'Add new', 'cbx_opening_hours' ); ?>
                </a>
                <input type="hidden" class="exception_last_count" name="cbxbusinesshours_hours[ex_last_count]"
                       value="<?= esc_attr( intval( $ex_last_count ) ); ?>"/>
            </div>
			<?php
		} // end of method callback_exceptionDay


		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_url( $args ) {
			$this->callback_text( $args );
		}


		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_select( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}

			$html .= sprintf( '</select>' );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays the html for a settings field
		 *
		 * @param array $args settings field args
		 *
		 * @return string
		 */
		function callback_html( $args ) {
			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_wysiwyg( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . $size . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
				'textarea_rows' => 10
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_file( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id    = $args['section'] . '[' . $args['id'] . ']';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

			$html = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
			$html .= $this->get_field_description( $args );

			echo $html;
		}


		/**
		 * Sanitize callback for Settings API
		 *
		 * @return mixed
		 */
		function sanitize_options( $options ) {

			if ( ! $options ) {
				return $options;
			}

			foreach ( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it
				if ( $sanitize_callback ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback
			foreach ( $this->settings_fields as $section => $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] != $slug ) {
						continue;
					}

					// Return the callback name
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 *
		 * @return string
		 */
		function get_option( $option, $section, $default = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		function show_navigation() {
			$html = '<h2 class="nav-tab-wrapper">';

			$count = count( $this->settings_sections );

			// don't show the navigation if only one section exists
			if ( $count === 1 ) {
				return;
			}

			foreach ( $this->settings_sections as $tab ) {
				$html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
			}

			$html .= '</h2>';

			echo $html;
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		function show_forms() {
			?>
            <div class="metabox-holder">
				<?php foreach ( $this->settings_sections as $form ) { ?>
                    <div id="<?php echo $form['id']; ?>" class="group" style="display: none;">
                        <form method="post" action="options.php">
							<?php
							do_action( 'wsa_form_top_' . $form['id'], $form );
							settings_fields( $form['id'] );
							do_settings_sections( $form['id'] );
							do_action( 'wsa_form_bottom_' . $form['id'], $form );
							if ( isset( $this->settings_fields[ $form['id'] ] ) ):
								?>
                                <div style="padding-left: 10px">
									<?php submit_button(); ?>
                                </div>
							<?php endif; ?>
                        </form>
                    </div>
				<?php } ?>
            </div>
			<?php
			$this->script();
		}

		/**
		 * Tabbable JavaScript codes & Initiate Color Picker
		 *
		 * This code uses localstorage for displaying active tabs
		 */
		function script() {
			?>
            <script>
                jQuery(document).ready(function ($) {
                    //Initiate Color Picker
                    $('.wp-color-picker-field').wpColorPicker();

                    // Switches option sections
                    $('.group').hide();
                    var activetab = '';
                    if (typeof (localStorage) != 'undefined') {
                        activetab = localStorage.getItem("activetab");
                    }

                    //if url has section id as hash then set it as active or override the current local storage value
                    if (window.location.hash) {
                        activetab = window.location.hash;
                        if (typeof (localStorage) != 'undefined') {
                            localStorage.setItem("activetab", activetab);
                        }
                    }

                    if (activetab != '' && $(activetab).length) {
                        $(activetab).fadeIn();
                    } else {
                        $('.group:first').fadeIn();
                    }
                    $('.group .collapsed').each(function () {
                        $(this).find('input:checked').parent().parent().parent().nextAll().each(
                            function () {
                                if ($(this).hasClass('last')) {
                                    $(this).removeClass('hidden');
                                    return false;
                                }
                                $(this).filter('.hidden').removeClass('hidden');
                            });
                    });

                    if (activetab != '' && $(activetab + '-tab').length) {
                        $(activetab + '-tab').addClass('nav-tab-active');
                    } else {
                        $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                    }
                    $('.nav-tab-wrapper a').click(function (evt) {
                        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                        $(this).addClass('nav-tab-active').blur();
                        var clicked_group = $(this).attr('href');
                        if (typeof (localStorage) != 'undefined') {
                            localStorage.setItem("activetab", $(this).attr('href'));
                        }
                        $('.group').hide();
                        $(clicked_group).fadeIn();
                        evt.preventDefault();
                    });

                    $('.wpsa-browse').on('click', function (event) {
                        event.preventDefault();

                        var self = $(this);

                        // Create the media frame.
                        var file_frame = wp.media.frames.file_frame = wp.media({
                            title: self.data('uploader_title'),
                            button: {
                                text: self.data('uploader_button_text'),
                            },
                            multiple: false
                        });

                        file_frame.on('select', function () {
                            attachment = file_frame.state().get('selection').first().toJSON();
                            self.prev('.wpsa-url').val(attachment.url).change();
                        });

                        // Finally, open the modal
                        file_frame.open();
                    });
                });
            </script>
			<?php
			$this->_style_fix();
		}

		function _style_fix() {
			global $wp_version;

			if ( version_compare( $wp_version, '3.8', '<=' ) ):
				?>
                <style type="text/css">
                    /** WordPress 3.8 Fix **/
                    .form-table th {
                        padding: 20px 10px;
                    }

                    #wpbody-content .metabox-holder {
                        padding-top: 5px;
                    }
                </style>
			<?php
			endif;
		}

	}

endif;
