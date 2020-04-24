<?php

/**
 * @package CupraCode\WPActivityLog
 * @author Harry Finn <harry@harryfinn.co.uk>
 */

namespace CupraCode\WPActivityLog;

class ActivityLog
{
    public static function addEntry(string $type = 'post', $related_object = 0, $user_id = 0, $activity = '')
    {
        global $wpdb;

        $wpdb->insert(
            "{$wpdb->prefix}activity_log",
            [
                'entry_type' => $type,
                'entry_object' => $related_object,
                'user_id' => $user_id,
                'activity' => $activity
            ]
        );
    }
}
