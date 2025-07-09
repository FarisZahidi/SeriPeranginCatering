<?php
// Default expiry periods for different item categories
// This makes stock entry more user-friendly by suggesting expiry dates

$EXPIRY_DEFAULTS = [
    'Vegetables' => [
        'default_days' => 7,
        'description' => 'Fresh vegetables typically last 7 days',
        'icon' => 'fa-carrot'
    ],
    'Fruits' => [
        'default_days' => 7,
        'description' => 'Fresh fruits typically last 7 days',
        'icon' => 'fa-apple-whole'
    ],
    'Meat' => [
        'default_days' => 3,
        'description' => 'Fresh meat typically lasts 3 days',
        'icon' => 'fa-drumstick-bite'
    ],
    'Fish' => [
        'default_days' => 2,
        'description' => 'Fresh fish typically lasts 2 days',
        'icon' => 'fa-fish'
    ],
    'Dairy' => [
        'default_days' => 7,
        'description' => 'Dairy products typically last 7 days',
        'icon' => 'fa-cheese'
    ],
    'Beverages' => [
        'default_days' => 30,
        'description' => 'Beverages typically last 30 days',
        'icon' => 'fa-wine-bottle'
    ],
    'Dry Goods' => [
        'default_days' => 365,
        'description' => 'Dry goods typically last 1 year',
        'icon' => 'fa-wheat-awn'
    ],
    'Canned Goods' => [
        'default_days' => 730,
        'description' => 'Canned goods typically last 2 years',
        'icon' => 'fa-can-food'
    ],
    'Frozen' => [
        'default_days' => 90,
        'description' => 'Frozen items typically last 90 days',
        'icon' => 'fa-snowflake'
    ],
    'Other' => [
        'default_days' => 30,
        'description' => 'Other items typically last 30 days',
        'icon' => 'fa-box'
    ]
];

// Function to get default expiry date for a category
function getDefaultExpiryDate($category)
{
    global $EXPIRY_DEFAULTS;

    if (isset($EXPIRY_DEFAULTS[$category])) {
        $default_days = $EXPIRY_DEFAULTS[$category]['default_days'];
        return date('Y-m-d', strtotime("+{$default_days} days"));
    }

    // Default fallback
    return date('Y-m-d', strtotime('+30 days'));
}

// Function to get expiry info for a category
function getExpiryInfo($category)
{
    global $EXPIRY_DEFAULTS;

    if (isset($EXPIRY_DEFAULTS[$category])) {
        return $EXPIRY_DEFAULTS[$category];
    }

    return $EXPIRY_DEFAULTS['Other'];
}

// Function to format expiry date for display
function formatExpiryDate($date)
{
    if (!$date)
        return 'No expiry';

    $expiry = new DateTime($date);
    $today = new DateTime();
    $diff = $today->diff($expiry);
    $days_until = $diff->invert ? -$diff->days : $diff->days;

    if ($days_until < 0) {
        return date('d M Y', strtotime($date)) . ' <span style="color: #dc3545; font-weight: 600;">(Expired ' . abs($days_until) . ' days ago)</span>';
    } elseif ($days_until == 0) {
        return date('d M Y', strtotime($date)) . ' <span style="color: #ffc107; font-weight: 600;">(Expires today)</span>';
    } elseif ($days_until <= 7) {
        return date('d M Y', strtotime($date)) . ' <span style="color: #fd7e14; font-weight: 600;">(' . $days_until . ' days left)</span>';
    } else {
        return date('d M Y', strtotime($date)) . ' <span style="color: #28a745; font-weight: 600;">(' . $days_until . ' days left)</span>';
    }
}
?>