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
        // Log the current form data
        error_log('KulaHub GF: add_custom_form_settings called');
        error_log('KulaHub GF: Current form data: ' . print_r($form, true));
        
        // Get API keys
        $api = new KulaHub_GF_API();
        $api_key_options = $api->get_api_keys_options();
        
        // Add default empty option
        $api_key_options = array_merge(array('' => __('Select an account', 'kulahub-gf')), $api_key_options);
        
        // Get currently selected API key or the first available key
        $selected_api_key = rgar($form, 'kulahub_api_key_id');
        error_log('KulaHub GF: Selected API key from form: ' . $selected_api_key);
        
        // If no key is selected and we have keys available, use the first one
        if (empty($selected_api_key) && !empty($api_key_options) && count($api_key_options) > 1) {
            // Get first non-empty key (skip the empty option we added)
            $keys = array_keys($api_key_options);
            if (isset($keys[1])) { // Index 1 is the first actual key (index 0 is the empty option)
                $selected_api_key = $keys[1];
                error_log('KulaHub GF: Using first available key: ' . $selected_api_key);
            }
        }
        
        // Make sure we use the correct field names that match our form fields
        $custom_fields = array(
            array(
                'name'          => 'formid',
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => false,
                'label'         => __('KulaHub Form ID', 'kulahub-gf'),
                'tooltip'       => __('Enter the KulaHub Form ID', 'kulahub-gf'),
                'default_value' => rgar($form, 'formid') ?: rgar($form, 'kulahubFormId'),
            ),
            array(
                'name'          => 'clientid',
                'type'          => 'text',
                'class'         => 'medium',
                'required'      => false,
                'label'         => __('KulaHub Client ID', 'kulahub-gf'),
                'tooltip'       => __('Enter the KulaHub Client ID', 'kulahub-gf'),
                'default_value' => rgar($form, 'clientid') ?: rgar($form, 'kulahubClientId'),
            ),
            array(
                'name'          => 'kulahub_api_key_id',
                'type'          => 'select',
                'choices'       => $this->format_api_key_choices($api_key_options),
                'class'         => 'medium',
                'required'      => false,
                'label'         => __('KulaHub Account', 'kulahub-gf'),
                'tooltip'       => __('Select which KulaHub account to use for this form', 'kulahub-gf'),
                'default_value' => $selected_api_key,
            ),
        );

        // Add to the Form Basics section if it exists
        $form_basics_index = array_search('Form Basics', array_column($fields, 'title'));
        if ($form_basics_index !== false) {
            error_log('KulaHub GF: Found Form Basics section at index ' . $form_basics_index);
            $fields[$form_basics_index]['fields'] = array_merge(
                (array) $fields[$form_basics_index]['fields'], 
                $custom_fields
            );
        } else {
            // If Form Basics section doesn't exist, create a new section
            error_log('KulaHub GF: Form Basics section not found, creating new section');
            $fields[] = array(
                'title'  => 'KulaHub Integration',
                'fields' => $custom_fields
            );
        }

        error_log('KulaHub GF: Returning fields: ' . print_r($fields, true));
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
        // Debug what data is coming in
        error_log('KulaHub GF: Saving form settings');
        error_log('KulaHub GF: Posted data: ' . print_r($_POST, true));
        error_log('KulaHub GF: Current form data: ' . print_r($form, true));

        // Get the posted values - these must match exactly what Gravity Forms is expecting
        $form_settings = array(
            'formid',
            'clientid',
            'kulahub_api_key_id'
        );
        
        foreach ($form_settings as $setting) {
            if (isset($_POST[$setting])) {
                $form[$setting] = rgpost($setting);
                error_log('KulaHub GF: Setting ' . $setting . ' to ' . $form[$setting]);
            }
        }
        
        // For backwards compatibility
        if (isset($form['formid'])) {
            $form['kulahubFormId'] = $form['formid'];
        }
        
        if (isset($form['clientid'])) {
            $form['kulahubClientId'] = $form['clientid'];
        }
        
        error_log('KulaHub GF: Returning form data: ' . print_r($form, true));
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
        error_log('KulaHub GF: Form settings: ' . json_encode($form));

        $form_data = array();
        $contact_data = array();

        // Check all possible field names for Form ID and Client ID
        $form_id_fields = array('formid', 'kulahubFormId', 'formTypeId');
        $client_id_fields = array('clientid', 'kulahubClientId', 'clientId');
        
        // Get Form ID
        foreach ($form_id_fields as $field) {
            if (!empty(rgar($form, $field))) {
                $form_data['formTypeId'] = rgar($form, $field);
                error_log('KulaHub GF: Found Form ID in field: ' . $field . ' = ' . $form_data['formTypeId']);
                break;
            }
        }
        
        // Get Client ID
        foreach ($client_id_fields as $field) {
            if (!empty(rgar($form, $field))) {
                $form_data['clientId'] = rgar($form, $field);
                error_log('KulaHub GF: Found Client ID in field: ' . $field . ' = ' . $form_data['clientId']);
                break;
            }
        }
        
        // Get API Key ID
        $api_key_id = rgar($form, 'kulahub_api_key_id');
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
            if (isset($field['encryptField']) && !empty($field['encryptField'])) {
                $field_key = $field['encryptField'];
                $is_email_subscribe = strtolower($field_key) === 'emailsubscribe';

                // KulaHub stores emailsubscribe as a Boolean, so it must be resolved
                // to true or false rather than passed through as a string.
                $field_value = $is_email_subscribe
                    ? $this->get_email_subscribe_value($field, $entry)
                    : $this->get_field_value($field, $entry);

                error_log('KulaHub GF: Processing field - ' . $field_key . ' = ' . $this->format_value_for_log($field_value));

                if ($this->is_contact_field($field_key)) {
                    $contact_data[$field_key] = $field_value;
                } else {
                    $form_data[$field_key] = $field_value;
                }

                // emailsubscribe is also mirrored at the top level of the payload.
                if ($is_email_subscribe) {
                    $form_data[$field_key] = $field_value;
                }
            }
        }

        // Add contact data to form data
        $form_data['Contact'] = $contact_data;
        error_log('KulaHub GF: Final form data: ' . json_encode($form_data));

        // Send to API using the selected API key
        $api = new KulaHub_GF_API();
        $result = $api->send_data($form_data, $form['id'], $entry['id'], $api_key_id);
        
        if (is_wp_error($result)) {
            error_log('KulaHub GF: API request failed - ' . $result->get_error_message());
        } else {
            error_log('KulaHub GF: API request successful');
        }
    }

    /**
     * Get field value based on field type
     */
    private function get_field_value($field, $entry) {
        $value = $this->get_raw_field_value($field, $entry);

        if ($field['type'] === 'checkbox') {
            return $value;
        }

        return ucwords(strtolower($value));
    }

    /**
     * Get the entry value for a field without any presentation formatting
     */
    private function get_raw_field_value($field, $entry) {
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

        return rgar($entry, $field['id']);
    }

    /**
     * Resolve the emailsubscribe field to a Boolean
     *
     * The KulaHub AddForm endpoint expects Contact.Emailsubscribe as a Boolean.
     * A checkbox or consent field only reports a value when it is ticked, so
     * presence alone is consent. Every other field type (radio, select, text)
     * carries the answer in its value, so the value itself is interpreted.
     */
    private function get_email_subscribe_value($field, $entry) {
        $value = $this->get_raw_field_value($field, $entry);

        if ($field['type'] === 'checkbox' || $field['type'] === 'consent') {
            return !empty($value);
        }

        return $this->is_truthy_value($value);
    }

    /**
     * Interpret a submitted value as a Boolean
     *
     * Handles the answers a Gravity Forms opt-in field realistically produces:
     * "Yes"/"No", "True"/"False", "1"/"0", "On"/"Off" and phrases such as
     * "No thanks" or "Opt out". An empty value is always false.
     */
    private function is_truthy_value($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        $normalised = strtolower(trim((string) $value));

        if ($normalised === '') {
            return false;
        }

        $negative_values = array(
            'no', 'n', 'false', '0', 'off', 'none', 'not', 'never', 'nope',
            'decline', 'declined', 'disagree', 'unsubscribe', 'unsubscribed',
        );

        if (in_array($normalised, $negative_values, true)) {
            return false;
        }

        // Catch phrased answers such as "No thanks" or "Not right now".
        $words = preg_split('/[^a-z0-9\']+/', $normalised, -1, PREG_SPLIT_NO_EMPTY);
        $first_word = is_array($words) && !empty($words) ? $words[0] : '';

        if (in_array($first_word, $negative_values, true) || $first_word === 'dont') {
            return false;
        }

        if (strpos($normalised, 'opt out') === 0 || strpos($normalised, 'opt-out') === 0) {
            return false;
        }

        return true;
    }

    /**
     * Render a field value for the debug log without losing its type
     */
    private function format_value_for_log($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return wp_json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Check if field is a contact field
     */
    private function is_contact_field($field_key) {
        $contact_fields = array(
            'title', 'firstname', 'lastname', 'organisationname',
            'address1', 'address2', 'address3', 'town', 'county',
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