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
        // Get API keys
        $api = new KulaHub_GF_API();
        $api_key_options = $api->get_api_keys_options();
        
        // Add default empty option
        $api_key_options = array_merge(array('' => __('Select an account', 'kulahub-gf')), $api_key_options);
        
        // Get currently selected API key or the first available key
        $selected_api_key = rgar($form, 'kulahub_api_key_id');
        
        // If no key is selected and we have keys available, use the first one
        if (empty($selected_api_key) && !empty($api_key_options) && count($api_key_options) > 1) {
            // Get first non-empty key (skip the empty option we added)
            $keys = array_keys($api_key_options);
            if (isset($keys[1])) { // Index 1 is the first actual key (index 0 is the empty option)
                $selected_api_key = $keys[1];
            }
        }
        
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
            array(
                'name'          => 'kulahub_api_key_id',
                'type'          => 'select',
                'choices'       => $this->format_api_key_choices($api_key_options),
                'class'         => 'medium',
                'label'         => __('KulaHub Account', 'kulahub-gf'),
                'tooltip'       => __('Select which KulaHub account to use for this form', 'kulahub-gf'),
                'default_value' => $selected_api_key,
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
     * Format API key choices for select field
     */
    private function format_api_key_choices($api_keys) {
        $choices = array();
        
        foreach ($api_keys as $id => $name) {
            $choices[] = array(
                'label' => $name,
                'value' => $id
            );
        }
        
        return $choices;
    }

    /**
     * Save custom form settings
     */
    public function save_custom_form_settings($form) {
        // Get the posted values
        $form['formid'] = rgpost('formid');
        $form['clientid'] = rgpost('clientid');
        $form['kulahub_api_key_id'] = rgpost('kulahub_api_key_id');
        
        // For backwards compatibility
        $form['kulahubFormId'] = $form['formid'];
        $form['kulahubClientId'] = $form['clientid'];
        
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
        error_log('KulaHub GF: Form submission started');
        error_log('KulaHub GF: Form ID: ' . $form['id']);
        error_log('KulaHub GF: Full form settings: ' . print_r($form, true));
        error_log('KulaHub GF: Entry: ' . print_r($entry, true));

        $form_data = array();
        $contact_data = array();

        // Get form and client IDs - check both old and new setting names
        $form_data['formTypeId'] = rgar($form, 'formid') ?: rgar($form, 'kulahubFormId');
        $form_data['clientId'] = rgar($form, 'clientid') ?: rgar($form, 'kulahubClientId');
        $api_key_id = rgar($form, 'kulahub_api_key_id');

        error_log('KulaHub GF: Form Type ID: ' . $form_data['formTypeId']);
        error_log('KulaHub GF: Client ID: ' . $form_data['clientId']);
        error_log('KulaHub GF: API Key ID: ' . $api_key_id);

        if (empty($form_data['formTypeId']) || empty($form_data['clientId'])) {
            error_log('KulaHub GF: Missing required form settings (formTypeId or clientId)');
            return;
        }

        // If no API key is specified, try to get the first available one
        if (empty($api_key_id)) {
            $api = new KulaHub_GF_API();
            $api_keys = $api->get_api_keys_options();
            if (!empty($api_keys)) {
                $keys = array_keys($api_keys);
                if (isset($keys[0])) {
                    $api_key_id = $keys[0];
                    error_log('KulaHub GF: No API key specified, using first available: ' . $api_key_id);
                }
            }
        }

        // Process form fields
        foreach ($form['fields'] as $field) {
            if (isset($field['encryptField'])) {
                error_log('KulaHub GF: Processing field - ' . print_r($field, true));
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

        // Send to API using the selected API key
        $api = new KulaHub_GF_API();
        $api->send_data($form_data, $form['id'], $entry['id'], $api_key_id);
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