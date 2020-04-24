<?php

/**
 * @package CupraCode\WPActivityLog
 * @author Harry Finn <harry@harryfinn.co.uk>
 */

namespace CupraCode\WPActivityLog;

class ActivityLogAdmin
{
    public function __construct()
    {
        $this->setupLogDbTable();

        add_action('admin_menu', [$this, 'setupAdminPages']);
        add_action('acf/save_post', [$this, 'acfMetaHook'], 5);
    }

    public function setupAdminPages()
    {
        add_menu_page(
            __('Activity Log', 'wp-activity-log'),
            __('Activity Log', 'wp-activity-log'),
            'manage_options',
            'wp-activity-log',
            [$this, 'renderTopLevelAdminPage'],
            'dashicons-welcome-write-blog',
            100
        );
    }

    public function renderTopLevelAdminPage()
    {
        $activity_log_table = new ActivityLogListTable();
        $activity_log_table->prepare_items(); ?>
        <div class="wrap">
            <h1>WP Activity Log</h1>
            <p>
                The following table displays changes to various different data types within WordPress,
                primarily meta data, which is recorded when a record is updated, allowing for a clear
                audit trail.
            </p>
            <input type="hidden" name="page" value="">
            <?php $activity_log_table->views(); ?>
            <form method="post">
                <?php $activity_log_table->display(); ?>
            </form>
        </div>
        <style type="text/css">
            .wp-list-table .column-entry_id,
            .wp-list-table .column-entry_type {
                width: 10%;
            }

            .wp-list-table .column-activity {
                width: 30%;
            }
        </style>

        <?php if (empty($activity_log_table->items)) : ?>
            <p>
                Sorry, no activity log entries found. Please check the activity log settings if you
                have made changes which should have resulted in them being logged here.
            </p>
            <?php
        endif;
    }

    public function acfMetaHook($post_id)
    {
        if (empty($_POST['acf'])) {
            return;
        }

        foreach ($_POST['acf'] as $acf_key => $new_value) {
            if (($acf_object = get_field_object($acf_key, $post_id)) !== false) {
                $previous_value = $acf_object['value'];

                if ($previous_value == $new_value) {
                    continue;
                }

                $activity_json = json_encode([
                    'field_name' => $acf_object['label'],
                    'previous_value' => $previous_value,
                    'updated_value' => $new_value
                ]);
                $post_type = ucfirst(get_post_type($post_id));
                $user_id = !empty($user = wp_get_current_user()) ? $user->ID : 0;

                ActivityLog::addEntry($post_type, $post_id, $user_id, $activity_json);
            }
        }
    }

    private function setupLogDbTable()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the activity_log table
        $table_name = "{$wpdb->prefix}activity_log";
        $sql = "CREATE TABLE $table_name (
                entry_id INTEGER NOT NULL AUTO_INCREMENT,
                entry_type VARCHAR(255) NOT NULL,
                entry_object INTEGER NOT NULL DEFAULT '0',
                user_id INTEGER NOT NULL DEFAULT '0',
                activity JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (entry_id),
                KEY user_id (user_id)
            ) $charset_collate;";

        dbDelta($sql);
    }
}
