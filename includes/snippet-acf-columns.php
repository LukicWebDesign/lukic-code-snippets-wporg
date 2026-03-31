<?php
/**
 * Snippet: Show ACF Fields in Admin Tables
 *
 * Displays ACF fields as columns in admin tables for posts, pages, and custom post types.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'Lukic_ACF_COLUMNS_OPTION', 'Lukic_acf_columns_settings' );

/**
 * Add plugin menu item for ACF columns settings
 */
function Lukic_acf_columns_menu() {
	add_submenu_page(
		'lukic-code-snippets',
		'ACF Columns Settings',
		'ACF Columns',
		'manage_options',
		'lukic-acf-columns',
		'Lukic_acf_columns_page'
	);
}
add_action( 'admin_menu', 'Lukic_acf_columns_menu' );

/**
 * Register settings
 */
function Lukic_acf_columns_register_settings() {
	register_setting( 'Lukic_acf_columns_group', Lukic_ACF_COLUMNS_OPTION, 'sanitize_text_field' );
}
add_action( 'admin_init', 'Lukic_acf_columns_register_settings' );

/**
 * Check if ACF is active
 */
function Lukic_is_acf_active() {
	return class_exists( 'ACF' );
}

/**
 * Get all ACF field groups and their fields
 *
 * @return array Array of field groups and fields
 */
function Lukic_get_acf_fields() {
	if ( ! Lukic_is_acf_active() ) {
		return array();
	}

	$field_groups = acf_get_field_groups();
	$all_fields   = array();

	foreach ( $field_groups as $field_group ) {
		$fields = acf_get_fields( $field_group );

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$all_fields[ $field['key'] ] = array(
					'key'   => $field['key'],
					'name'  => $field['name'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'group' => $field_group['title'],
				);
			}
		}
	}

	return $all_fields;
}

/**
 * Get post types that ACF fields can be displayed for
 */
function Lukic_get_post_types_for_acf() {
	$post_types = get_post_types(
		array(
			'show_ui' => true,
		),
		'objects'
	);

	$excluded = array( 'attachment', 'acf-field-group', 'acf-field' );
	$types    = array();

	foreach ( $post_types as $post_type ) {
		if ( ! in_array( $post_type->name, $excluded ) ) {
			$types[ $post_type->name ] = $post_type->label;
		}
	}

	return $types;
}

/**
 * Get settings for ACF columns
 */
function Lukic_get_acf_columns_settings() {
	$default_settings = array(
		'fields'     => array(),
		'post_types' => array(),
	);

	$settings = get_option( Lukic_ACF_COLUMNS_OPTION, $default_settings );

	// Ensure we have the expected structure
	if ( ! isset( $settings['fields'] ) ) {
		$settings['fields'] = array();
	}

	if ( ! isset( $settings['post_types'] ) ) {
		$settings['post_types'] = array();
	}

	return $settings;
}

/**
 * Render ACF Columns Settings Page
 */
function Lukic_acf_columns_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings   = Lukic_get_acf_columns_settings();
	$all_fields = Lukic_get_acf_fields();
	$post_types = Lukic_get_post_types_for_acf();
	$acf_active = Lukic_is_acf_active();

	// Save settings if form is submitted
	if ( isset( $_POST['Lukic_acf_columns_save'] ) && check_admin_referer( 'Lukic_acf_columns_nonce' ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$new_settings = array(
			'fields'     => isset( $_POST['acf_fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['acf_fields'] ) ) : array(),
			'post_types' => isset( $_POST['post_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['post_types'] ) ) : array(),
		);

		update_option( Lukic_ACF_COLUMNS_OPTION, $new_settings );
		$settings = $new_settings;

		echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
	}

	// Count selected fields and post types
	$field_count     = count( $settings['fields'] );
	$post_type_count = count( $settings['post_types'] );

	// Header component is already loaded in main plugin file

	// Prepare stats for header
	$stats = array(
		array(
			'count' => $field_count,
			'label' => 'Fields',
		),
		array(
			'count' => $post_type_count,
			'label' => 'Post Types',
		),
	);

	?>
	<div class="wrap Lukic-settings-wrap">
		<?php Lukic_display_header( __( 'ACF Columns Configuration', 'lukic-code-snippets' ), $stats ); ?>
		
		<div class="Lukic-settings-intro">
			<p><?php esc_html_e( 'Configure which ACF fields should display as columns in your WordPress admin tables.', 'lukic-code-snippets' ); ?></p>
		</div>
		
		<?php if ( ! $acf_active ) : ?>
			<div class="notice notice-warning">
				<p><strong>Advanced Custom Fields plugin is not active.</strong> This feature requires ACF to be installed and activated.</p>
			</div>
		<?php else : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'Lukic_acf_columns_nonce' ); ?>
				
				<div class="Lukic-settings-container">
					<h3>Select Post Types</h3>
					<p class="description">Choose which post types should display ACF columns:</p>
					
					<div class="Lukic-checkbox-grid">
						<?php foreach ( $post_types as $type => $label ) : ?>
							<label class="Lukic-checkbox-label">
								<input 
									type="checkbox" 
									name="post_types[]" 
									value="<?php echo esc_attr( $type ); ?>"
									<?php checked( in_array( $type, $settings['post_types'] ) ); ?>
								>
								<span class="Lukic-checkbox-text"><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				
				<div class="Lukic-settings-container">
					<h3>Select ACF Fields</h3>
					<p class="description">Choose which ACF fields should be displayed as columns:</p>
					
					<?php if ( empty( $all_fields ) ) : ?>
						<div class="Lukic-empty-state">
							<p>No ACF fields found. Please create some fields first.</p>
						</div>
					<?php else : ?>
						<?php
						$grouped_fields = array();
						foreach ( $all_fields as $field ) {
							if ( ! isset( $grouped_fields[ $field['group'] ] ) ) {
								$grouped_fields[ $field['group'] ] = array();
							}
							$grouped_fields[ $field['group'] ][] = $field;
						}

						foreach ( $grouped_fields as $group => $fields ) :
							?>
							<div class="Lukic-settings-section">
								<div class="Lukic-section-header">
									<h4><?php echo esc_html( $group ); ?></h4>
								</div>
								<div class="Lukic-checkbox-grid">
									<?php foreach ( $fields as $field ) : ?>
										<label class="Lukic-checkbox-label" title="Field type: <?php echo esc_attr( $field['type'] ); ?>">
											<input 
												type="checkbox" 
												name="acf_fields[]" 
												value="<?php echo esc_attr( $field['key'] ); ?>"
												data-field-type="<?php echo esc_attr( $field['type'] ); ?>"
												<?php checked( in_array( $field['key'], $settings['fields'] ) ); ?>
											>
											<span class="Lukic-checkbox-text">
												<?php echo esc_html( $field['label'] ); ?>
												<span class="field-type">(<?php echo esc_html( $field['type'] ); ?>)</span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				
				<div class="Lukic-submit-container">
					<button type="submit" name="Lukic_acf_columns_save" class="button button-primary">Save Settings</button>
					<p class="description">Changes will take effect immediately after saving.</p>
				</div>
			</form>
			
			<div class="Lukic-footer">
				<p>Part of <strong>Lukic Code Snippets</strong> plugin. Need help? <a href="#">View Documentation</a></p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Enqueue admin styles and scripts
 */
function Lukic_acf_columns_admin_scripts( $hook ) {
	// This function is now empty as the enqueuing is handled by the main plugin file
	// Keeping the function to avoid breaking existing code that might call it
}
add_action( 'admin_enqueue_scripts', 'Lukic_acf_columns_admin_scripts' );

/**
 * Add ACF columns to posts tables
 */
function Lukic_acf_add_admin_columns() {
	// Check if ACF is active
	if ( ! Lukic_is_acf_active() ) {
		return;
	}

	$settings            = Lukic_get_acf_columns_settings();
	$selected_fields     = $settings['fields'];
	$selected_post_types = $settings['post_types'];

	if ( empty( $selected_fields ) || empty( $selected_post_types ) ) {
		return;
	}

	// Get all fields data
	$all_fields = Lukic_get_acf_fields();

	// Add columns for each post type
	foreach ( $selected_post_types as $post_type ) {
		// Get valid fields for this post type
		$valid_field_keys = array();
		$groups           = acf_get_field_groups( array( 'post_type' => $post_type ) );

		if ( $groups ) {
			foreach ( $groups as $group ) {
				$fields = acf_get_fields( $group['key'] );
				if ( $fields ) {
					foreach ( $fields as $field ) {
						$valid_field_keys[] = $field['key'];
					}
				}
			}
		}

		// Add column headers
		add_filter(
			"manage_{$post_type}_posts_columns",
			function ( $columns ) use ( $selected_fields, $all_fields, $valid_field_keys ) {
				foreach ( $selected_fields as $field_key ) {
					// Skip if field doesn't belong to this post type
					if ( ! in_array( $field_key, $valid_field_keys ) ) {
						continue;
					}

					if ( isset( $all_fields[ $field_key ] ) ) {
						$field                              = $all_fields[ $field_key ];
						$columns[ 'acf_' . $field['name'] ] = $field['label'];
					}
				}
				return $columns;
			}
		);

		// Add column content
		add_action(
			"manage_{$post_type}_posts_custom_column",
			function ( $column, $post_id ) use ( $selected_fields, $all_fields, $valid_field_keys ) {
				foreach ( $selected_fields as $field_key ) {
					// Skip if field doesn't belong to this post type
					if ( ! in_array( $field_key, $valid_field_keys ) ) {
						continue;
					}

					if ( isset( $all_fields[ $field_key ] ) ) {
						$field       = $all_fields[ $field_key ];
						$column_name = 'acf_' . $field['name'];

						if ( $column === $column_name ) {
							Lukic_display_acf_field_value( $post_id, $field );
						}
					}
				}
			},
			10,
			2
		);

		// Make columns sortable
		add_filter(
			"manage_edit-{$post_type}_sortable_columns",
			function ( $columns ) use ( $selected_fields, $all_fields, $valid_field_keys ) {
				foreach ( $selected_fields as $field_key ) {
					// Skip if field doesn't belong to this post type
					if ( ! in_array( $field_key, $valid_field_keys ) ) {
						continue;
					}

					if ( isset( $all_fields[ $field_key ] ) ) {
						$field = $all_fields[ $field_key ];
						// Only make certain field types sortable
						$sortable_types = array( 'text', 'number', 'date', 'select' );
						if ( in_array( $field['type'], $sortable_types ) ) {
							$columns[ 'acf_' . $field['name'] ] = 'acf_' . $field['name'];
						}
					}
				}
				return $columns;
			}
		);
	}

	// Handle sorting
	add_action(
		'pre_get_posts',
		function ( $query ) use ( $selected_fields, $all_fields ) {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$orderby = $query->get( 'orderby' );

			// Check if we're ordering by an ACF field
			if ( strpos( $orderby, 'acf_' ) === 0 ) {
				$field_name = substr( $orderby, 4 );

				// Find the field key by name
				$field_key = null;
				foreach ( $all_fields as $key => $field ) {
					if ( $field['name'] === $field_name ) {
						$field_key = $key;
						break;
					}
				}

				if ( $field_key ) {
					$query->set( 'meta_key', $field_name );
					$query->set( 'orderby', 'meta_value' );
				}
			}
		}
	);
}
add_action( 'admin_init', 'Lukic_acf_add_admin_columns' );

/**
 * Display ACF field value in admin column
 *
 * @param int   $post_id  Post ID
 * @param array $field    Field data
 */
function Lukic_display_acf_field_value( $post_id, $field ) {
	$value = get_field( $field['name'], $post_id );

	if ( empty( $value ) && $value !== 0 ) {
		echo '<span class="acf-empty">—</span>';
		return;
	}

	switch ( $field['type'] ) {
		case 'image':
			// Handle both image ID and image array return formats
			if ( is_array( $value ) ) {
				// Image array return format
				if ( isset( $value['ID'] ) ) {
					// Get the thumbnail using the ID from the array
					$image = wp_get_attachment_image( $value['ID'], 'thumbnail', false, array( 'class' => 'Lukic-acf-image-preview' ) );
					if ( $image ) {
						echo wp_kses_post( $image );
						break;
					}
				}

				// Fallback if we have a URL but no valid WordPress attachment
				if ( isset( $value['url'] ) ) {
					echo '<img src="' . esc_url( $value['url'] ) . '" alt="' . esc_attr( $value['alt'] ?? '' ) . '" class="Lukic-acf-image-preview" />';
					break;
				}
			} elseif ( is_numeric( $value ) ) {
				// Image ID return format
				$image = wp_get_attachment_image( $value, 'thumbnail', false, array( 'class' => 'Lukic-acf-image-preview' ) );
				if ( $image ) {
					echo wp_kses_post( $image );
					break;
				}
			} elseif ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
				// Direct URL (rare case but handling it for completeness)
				echo '<img src="' . esc_url( $value ) . '" alt="" class="Lukic-acf-image-preview" />';
				break;
			}

			// If we get here, we couldn't display the image
			echo '<span class="acf-empty">—</span>';
			break;

		case 'file':
			// Handle both file ID and file array return formats
			if ( is_array( $value ) ) {
				// File array return format
				if ( isset( $value['url'] ) ) {
					$filename = isset( $value['filename'] ) ? $value['filename'] : basename( $value['url'] );
					echo '<a href="' . esc_url( $value['url'] ) . '" target="_blank" class="Lukic-acf-file-link">' . esc_html( $filename ) . '</a>';
					break;
				}
			} elseif ( is_numeric( $value ) ) {
				// File ID return format
				$url = wp_get_attachment_url( $value );
				if ( $url ) {
					$filename = basename( $url );
					echo '<a href="' . esc_url( $url ) . '" target="_blank" class="Lukic-acf-file-link">' . esc_html( $filename ) . '</a>';
					break;
				}
			} elseif ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
				// Direct URL (rare case)
				$filename = basename( $value );
				echo '<a href="' . esc_url( $value ) . '" target="_blank" class="Lukic-acf-file-link">' . esc_html( $filename ) . '</a>';
				break;
			}

			// If we get here, we couldn't display the file
			echo '<span class="acf-empty">—</span>';
			break;

		case 'link':
			if ( is_array( $value ) && isset( $value['url'] ) ) {
				$title = ! empty( $value['title'] ) ? $value['title'] : $value['url'];
				echo '<a href="' . esc_url( $value['url'] ) . '" target="_blank">' . esc_html( $title ) . '</a>';
			} else {
				echo '<span class="acf-empty">—</span>';
			}
			break;

		case 'select':
		case 'radio':
		case 'checkbox':
			if ( is_array( $value ) ) {
				echo esc_html( implode( ', ', $value ) );
			} else {
				echo esc_html( $value );
			}
			break;

		case 'true_false':
			echo wp_kses_post( $value ? '<span class="acf-true">✓</span>' : '<span class="acf-false">✗</span>' );
			break;

		case 'date_picker':
		case 'date_time_picker':
			echo esc_html( $value );
			break;

		case 'relationship':
		case 'post_object':
			if ( is_array( $value ) ) {
				$titles = array();
				foreach ( $value as $post_item ) {
					if ( is_object( $post_item ) && isset( $post_item->ID ) ) {
						// WP_Post object
						$titles[] = $post_item->post_title;
					} elseif ( is_array( $post_item ) && isset( $post_item['ID'] ) ) {
						// Post array format
						$titles[] = $post_item['post_title'];
					} elseif ( is_numeric( $post_item ) ) {
						// Post ID format
						$post_title = get_the_title( $post_item );
						if ( $post_title ) {
							$titles[] = $post_title;
						}
					}
				}
				echo esc_html( implode( ', ', $titles ) );
			} elseif ( is_object( $value ) && isset( $value->ID ) ) {
				// Single WP_Post object
				echo esc_html( $value->post_title );
			} elseif ( is_array( $value ) && isset( $value['ID'] ) ) {
				// Single post as array
				echo esc_html( $value['post_title'] );
			} elseif ( is_numeric( $value ) ) {
				// Single post ID
				echo esc_html( get_the_title( $value ) );
			} else {
				echo '<span class="acf-empty">—</span>';
			}
			break;

		case 'taxonomy':
			if ( is_array( $value ) ) {
				$terms = array();
				foreach ( $value as $term ) {
					if ( is_object( $term ) && isset( $term->name ) ) {
						// WP_Term object
						$terms[] = $term->name;
					} elseif ( is_array( $term ) && isset( $term['name'] ) ) {
						// Term array format
						$terms[] = $term['name'];
					} elseif ( is_numeric( $term ) ) {
						// Term ID format
						$term_obj = get_term( $term );
						if ( ! is_wp_error( $term_obj ) && $term_obj ) {
							$terms[] = $term_obj->name;
						}
					} elseif ( is_string( $term ) ) {
						// Term name or slug
						$terms[] = $term;
					}
				}
				echo esc_html( implode( ', ', $terms ) );
			} elseif ( is_object( $value ) && isset( $value->name ) ) {
				// Single WP_Term object
				echo esc_html( $value->name );
			} elseif ( is_array( $value ) && isset( $value['name'] ) ) {
				// Single term as array
				echo esc_html( $value['name'] );
			} elseif ( is_numeric( $value ) ) {
				// Single term ID
				$term_obj = get_term( $value );
				if ( ! is_wp_error( $term_obj ) && $term_obj ) {
					echo esc_html( $term_obj->name );
				} else {
					echo '<span class="acf-empty">—</span>';
				}
			} elseif ( is_string( $value ) ) {
				// Single term name or slug
				echo esc_html( $value );
			} else {
				echo '<span class="acf-empty">—</span>';
			}
			break;

		case 'user':
			if ( is_array( $value ) ) {
				$names = array();
				foreach ( $value as $user ) {
					if ( is_object( $user ) && isset( $user->ID ) ) {
						// WP_User object
						$names[] = $user->display_name;
					} elseif ( is_array( $user ) && isset( $user['ID'] ) ) {
						// User array format
						$names[] = $user['display_name'];
					} elseif ( is_numeric( $user ) ) {
						// User ID format
						$user_data = get_userdata( $user );
						if ( $user_data ) {
							$names[] = $user_data->display_name;
						}
					}
				}
				echo esc_html( implode( ', ', $names ) );
			} elseif ( is_object( $value ) && isset( $value->ID ) ) {
				// Single WP_User object
				echo esc_html( $value->display_name );
			} elseif ( is_array( $value ) && isset( $value['ID'] ) ) {
				// Single user as array
				echo esc_html( $value['display_name'] );
			} elseif ( is_numeric( $value ) ) {
				// Single user ID
				$user_data = get_userdata( $value );
				if ( $user_data ) {
					echo esc_html( $user_data->display_name );
				} else {
					echo '<span class="acf-empty">—</span>';
				}
			} else {
				echo '<span class="acf-empty">—</span>';
			}
			break;

		case 'google_map':
			if ( is_array( $value ) && isset( $value['address'] ) ) {
				echo '<span title="' . esc_attr( $value['address'] ) . '">' . esc_html( $value['address'] ) . '</span>';
			} else {
				echo '<span class="acf-empty">—</span>';
			}
			break;

		default:
			// Handle text, textarea, number, etc.
			if ( is_array( $value ) || is_object( $value ) ) {
				echo '<span class="acf-complex">[Complex Value]</span>';
			} else {
				// Limit text to 50 characters
				if ( strlen( $value ) > 50 ) {
					echo '<span title="' . esc_attr( $value ) . '">' . esc_html( substr( $value, 0, 50 ) ) . '...</span>';
				} else {
					echo esc_html( $value );
				}
			}
			break;
	}
}

/**
 * Add custom styling for ACF columns
 */
function Lukic_acf_columns_admin_head() {
	wp_add_inline_style( 'Lukic-admin-styles', '
		.Lukic-acf-image-preview { max-width: 60px; max-height: 60px; display: block; }
		.acf-true { color: #46b450; font-weight: bold; }
		.acf-false { color: #dc3232; }
		.acf-empty { color: #ccc; }
		.acf-complex { color: #555; font-style: italic; }
	' );
}
add_action( 'admin_enqueue_scripts', 'Lukic_acf_columns_admin_head' ); // Changed to enqueue_scripts for asset pipeline
