<?php
/**
 * Gravity Forms Integration functionality
 */
class KulaHub_GF_Integration {
    /**
     * Initialize the integration
     */
    public function __construct() {
        // Add custom field to field settings
        add_action('gform_field_standard_settings', array($this, 'add_custom_field_settings'), 10, 2);
        
        // Add custom form settings
        add_filter('gform_form_settings_fields', array($this, 'add_custom_form_settings'), 10, 2);
        
        // Save custom form settings
        add_filter('gform_pre_form_settings_save', array($this, 'save_custom_form_settings'));
        
        // Add custom scripts to form editor
        add_action('gform_editor_js', array($this, 'editor_script'));
        
        // Handle form submission
        add_action('gform_after_submission', array($this, 'handle_form_submission'), 10, 2);
    }

    /**
     * Add custom field settings
     */
    public function add_custom_field_settings($position, $form_id) {
        if ($position == 25) {
            ?>
            <li class="encrypt_setting field_setting">
                <label for="kulahubFieldId" style="display:inline;">
                    <?php _e('KulaHub Field ID', 'kulahub-gf'); ?>
                </label>
                <input type="text" id="kulahubFieldId" name="kulahubFieldId" 
                       class="fieldwidth-3" 
                       onchange="SetFieldProperty('encryptField', this.value);" />
            </li>
            <?php
            $field_value = sanitize_text_field(rgpost('kulahubFieldId'));
            printf('<input type="hidden" name="gform_field_value" value="%s" />', 
                   esc_attr($field_value));
        }
    }

    /**
     * Add custom form settings
     */
    public function add_custom_form_settings($fields, $form) {
        $custom_fields = array(
            array(
                'name'          => 'formid',
                'type'          => 'text',
                'class'         => 'medium',
                'label'         => __('KulaHub Form ID', 'kulahub-gf'),
                'default_value' => rgar($form, 'formid'),
            ),
            array(
                'name'          => 'clientid',
                'type'          => 'text',
                'class'         => 'medium',
                'label'         => __('KulaHub Client ID', 'kulahub-gf'),
                'default_value' => rgar($form, 'clientid'),
            ),
        );

        $form_basics_index = array_search('Form Basics', array_column($fields, 'title'));
        if ($form_basics_index !== false) {
            $fields[$form_basics_index]['fields'] = array_merge(
                (array) $fields[$form_basics_index]['fields'], 
                $custom_fields
            );
        }

        return $fields;
    }

    /**
     * Save custom form settings
     */
    public function save_custom_form_settings($form) {
        $form['kulahubFormId'] = rgpost('kulahubFormId');
        $form['kulahubClientId'] = rgpost('kulahubClientId');
        return $form;
    }

    /**
     * Add custom scripts to form editor
     */
    public function editor_script() {
        ?>
        <script type='text/javascript'>
            // Adding setting to all field types
            for (var fieldType in fieldSettings) {
                if (fieldSettings.hasOwnProperty(fieldType)) {
                    fieldSettings[fieldType] += ', .encrypt_setting';
                }
            }

            // Binding to the load field settings event
            jQuery(document).on('gform_load_field_settings', function(event, field, form) {
                jQuery('#kulahubFieldId').val(field['encryptField'] || '');
            });

            // Binding to the save field settings event
            jQuery(document).on('gform_field_standard_settings', function(event, field, form) {
                field['encryptField'] = jQuery('#kulahubFieldId').val();
            });
        </script>
        <?php
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission($entry, $form) {
        $form_data = array();
        $contact_data = array();

        // Get form and client IDs
        $form_data['formTypeId'] = rgar($form, 'formid');
        $form_data['clientId'] = rgar($form, 'clientid');

        // Process form fields
        foreach ($form['fields'] as $field) {
            if (isset($field['encryptField'])) {
                $field_key = $field['encryptField'];
                $field_value = $this->get_field_value($field, $entry);

                if ($this->is_contact_field($field_key)) {
                    $contact_data[$field_key] = $field_value;
                } else {
                    $form_data[$field_key] = $field_value;
                }

                // Handle email subscribe specially
                if ($field_key == 'emailsubscribe') {
                    $form_data[$field_key] = !empty($field_value);
                }
            }
        }

        // Add contact data to form data
        $form_data['Contact'] = $contact_data;

        // Send to API
        $api = new KulaHub_GF_API();
        $api->send_data($form_data, $form['id'], $entry['id']);
    }

    /**
     * Get field value based on field type
     */
    private function get_field_value($field, $entry) {
        if ($field['type'] === 'checkbox') {
            $checkbox_values = array();
            foreach ($field['inputs'] as $input) {
                $input_value = rgar($entry, $input['id']);
                if (!empty($input_value)) {
                    $checkbox_values[] = $input_value;
                }
            }
            return implode(', ', $checkbox_values);
        }
        
        $value = rgar($entry, $field['id']);
        return ucwords(strtolower($value));
    }

    /**
     * Check if field is a contact field
     */
    private function is_contact_field($field_key) {
        $contact_fields = array(
            'firstname', 'lastname', 'organisationname', 
            'address1', 'address2', 'town', 'county', 
            'postcode', 'country', 'email', 'telephone', 
            'mobile', 'website', 'jobtitle'
        );

        foreach ($contact_fields as $contact_field) {
            if (stripos($field_key, $contact_field) !== false) {
                return true;
            }
        }

        return false;
    }
} 