<?php

/**
 * @package CupraCode\WPActivityLog
 * @author Harry Finn <harry@harryfinn.co.uk>
 */

namespace CupraCode\WPActivityLog;

class ActivityLogAdmin
{
    private $post_types = [];
    private static $instance = null;
    private $pagination_per_page = 20;
    private $entry_id_override = [];

    private function __construct()
    {
        $this->setupLogDbTable();

        add_action('admin_menu', [$this, 'setupAdminPages']);
        add_filter('wp_insert_post_data', [$this, 'featuredImageHook'], 10, 2);
        add_action('acf/save_post', [$this, 'acfMetaHook'], 5);
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ActivityLogAdmin();
        }

        return self::$instance;
    }

    public function addPostType(string $post_type)
    {
        if (!in_array($post_type, $this->post_types)) {
            $this->post_types[] = $post_type;
        }
    }

    public function getPostTypes()
    {
        return $this->post_types;
    }

    public function setPaginationPerPage($pagination_per_page)
    {
        $this->pagination_per_page = $pagination_per_page;
    }

    public function setEntryIdOverride($override_array)
    {
        $this->entry_id_override = $override_array;
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
        $activity_log_table = new ActivityLogListTable($this->pagination_per_page, $this->entry_id_override);
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

            .wp-list-table .activity-images {
                align-items: center;
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
            }

            .wp-list-table .activity-images img:first-child {
                opacity: 0.65;
                width: 40%;
            }

            .wp-list-table .activity-images img:last-child {
                width: 50%;
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

    public function featuredImageHook($data, $postarr)
    {
        $post_id = $postarr['ID'];
        $post_type = ucfirst(get_post_type($post_id));
        $user_id = !empty($user = wp_get_current_user()) ? $user->ID : 0;
        $current_thumbnail = get_post_thumbnail_id($post_id);
        $new_thumbnail = (int) $postarr['_thumbnail_id'];

        if ($current_thumbnail !== $new_thumbnail) {
            $activity_json = json_encode([
                'field_name' => 'Featured Image',
                'previous_value' => wp_get_attachment_image_src($current_thumbnail)[0],
                'updated_value' => wp_get_attachment_image_src($new_thumbnail)[0]
            ]);
            ActivityLog::addEntry($post_type, $post_id, $user_id, $activity_json);
        }
    }

    public function acfMetaHook($post_id)
    {
        if (empty($_POST['acf'])) {
            return;
        }

        if (!in_array(get_post_type($post_id), $this->post_types)) {
            return;
        }

        $post_type = ucfirst(get_post_type($post_id));
        $user_id = !empty($user = wp_get_current_user()) ? $user->ID : 0;

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

                ActivityLog::addEntry($post_type, $post_id, $user_id, $activity_json);
            }
        }

        $current_post_title = get_the_title($post_id);

        if ($current_post_title !== $_POST['post_title']) {
            $activity_json = json_encode([
                'field_name' => 'Title',
                'previous_value' => $current_post_title,
                'updated_value' => $_POST['post_title']
            ]);
            ActivityLog::addEntry($post_type, $post_id, $user_id, $activity_json);
        }

        $current_post_content = get_post_field('post_content', $post_id);

        if ($current_post_content !== $_POST['post_content']) {
            $activity_json = json_encode([
                'field_name' => 'Content',
                'previous_value' => $current_post_content,
                'updated_value' => $_POST['post_content']
            ]);
            ActivityLog::addEntry($post_type, $post_id, $user_id, $activity_json);
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
