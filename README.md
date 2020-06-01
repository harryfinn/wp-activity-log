# WP Activity Log

This composer library contains a series of base classes which can be used in a WordPress theme or plugin to generate a WordPress activity log. It is designed to allow the recording of meta data through custom field adapters such as ACF, the standard WP custom meta field hooks or expanded to your own implementation.

The activity log can be further expanded to house other data, sat outside of the WP meta hooks, but there are other plugins and also built in logging for some data types so this package does not try to fulfil all of these out of the box and instead focuses on postmeta hooks and data.

## Installation

```
composer require cupracodes\wp-activity-log
```

## Usage

Include the package and add any post types (built-in or custom) that you wish to log ACF field changes for.

```php
use CupraCode\WPActivityLog\ActivityLogAdmin;

$activity_log_admin = ActivityLogAdmin::getInstance();

// Log ACF field changes for posts, pages and a custom post type named 'photo'
$activity_log_admin->addPostType('post');
$activity_log_admin->addPostType('page');
$activity_log_admin->addPostType('photo');
```

Click on the 'Activity Log' menu item in the CMS admin menu to view your log.

## Customising

The `ActivityLogAdmin` class allows you to change the number of entries per page and also the first column, which defaults to the `entry_id` value should you wish to use a unique identifier of your own, perhaps a custom field from your CPT.

To change the default entries per page, use the following example as a guide, here we change the value to 25 entries per page:

```php
$activity_log_admin->setPaginationPerPage(25)
```

To change the first column to a custom field, use the following example as a guide, here we change the column name to Ref. Num and specify the custom meta field key we want to fetch and return the value for:

```php
$activity_log_admin->setEntryIdOverride([
    'column_name' => 'Ref. Num',
    'column_meta_key' => 'reference_no'
]);
```
