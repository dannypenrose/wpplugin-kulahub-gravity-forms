<div class="wrap">
    <h1><?php _e('Failed KulaHub Submissions', 'kulahub-gf'); ?></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Entry ID', 'kulahub-gf'); ?></th>
                <th><?php _e('Form', 'kulahub-gf'); ?></th>
                <th><?php _e('Error', 'kulahub-gf'); ?></th>
                <th><?php _e('Retry Count', 'kulahub-gf'); ?></th>
                <th><?php _e('Last Retry', 'kulahub-gf'); ?></th>
                <th><?php _e('Actions', 'kulahub-gf'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($failed_submissions as $submission): ?>
                <tr>
                    <td><?php echo esc_html($submission->entry_id); ?></td>
                    <td><?php echo esc_html(GFAPI::get_form($submission->form_id)['title']); ?></td>
                    <td><?php echo esc_html($submission->error_message); ?></td>
                    <td><?php echo esc_html($submission->retry_count); ?></td>
                    <td><?php echo $submission->last_retry ? esc_html($submission->last_retry) : '-'; ?></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=retry_failed_submission&id=' . $submission->id), 'retry_submission'); ?>" 
                           class="button button-secondary">
                            <?php _e('Retry', 'kulahub-gf'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 