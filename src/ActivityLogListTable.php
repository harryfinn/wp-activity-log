<?php

/**
 * @package CupraCode\WPActivityLog
 * @author Harry Finn <harry@harryfinn.co.uk>
 */

namespace CupraCode\WPActivityLog;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ActivityLogListTable extends \WP_List_Table
{
    private $pagination_per_page;

    public function __construct($pagination_per_page = 20)
    {
        parent::__construct();

        $this->pagination_per_page = $pagination_per_page;
    }

    public function get_columns()
    {
        $columns = [
            'entry_id' => 'Entry #',
            'entry_type' => 'Entry Type',
            'entry_object' => 'Entry Object',
            'activity' => 'Activity',
            'user' => 'User',
            'created_at' => 'Recorded'
        ];

        return $columns;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'entry_id':
            case 'entry_type':
            case 'created_at':
                return $item->$column_name;
            case 'entry_object':
                if ($item->entry_object === 0) {
                    return 'N/A';
                }

                $object_link = get_edit_post_link($item->$column_name);
                $object_link_text = get_the_title($item->$column_name) . " (ID: {$item->$column_name})";

                return "<a href='$object_link'>$object_link_text</a>";
            case 'activity':
                $activity = json_decode($item->$column_name);
                $activity_output = "Data for: <strong>{$activity->field_name}</strong><br>";
                $activity_output .= "Changed from: <strong>{$activity->previous_value}</strong>, to: <strong>{$activity->updated_value}</strong>";

                return $activity_output;
            case 'user':
                $user = get_user_by('ID', $item->user_id);

                if (empty($user)) {
                    return 'System';
                }

                return $user->display_name;
            default:
                return print_r($item, true);
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [
            'created_at'  => ['created_at', false],
            'user' => ['user_id', false],
            'entry_object' => ['entry_object', false]
        ];

        return $sortable_columns;
    }

    public function usort_reorder($a, $b)
    {
        $orderby = (! empty($_GET['orderby'])) ? $_GET['orderby'] : 'entry_id';
        $order = (! empty($_GET['order'])) ? $_GET['order'] : 'desc';
        $result = strcmp($a->$orderby, $b->$orderby);

        return ($order === 'asc') ? $result : -$result;
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = $this->pagination_per_page;
        $current_page = $this->get_pagenum();
        $offset = $current_page > 1 ? $per_page * ($current_page - 1) : 0;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}activity_log
                 ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $count = $wpdb->get_var("SELECT COUNT(entry_id) FROM {$wpdb->prefix}activity_log");

        usort($items, [&$this, 'usort_reorder']);

        $this->items = $items;

        $this->set_pagination_args(array(
            'total_items' => $count,
            'per_page' => $per_page,
            'total_pages' => ceil($count / $per_page)
        ));
    }
}
