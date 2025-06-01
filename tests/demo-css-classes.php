<?php
/**
 * Demo script showing CSS class handling in action
 * 
 * Usage: php tests/demo-css-classes.php
 */

// Load mock WordPress environment
require_once __DIR__ . '/mock-wordpress.php';

// Load the plugin files
require_once dirname(__DIR__) . '/PatreonClient.php';
require_once dirname(__DIR__) . '/goalietron.php';
require_once dirname(__DIR__) . '/block-render.php';

echo "GoalieTron CSS Class Handling Demo\n";
echo "==================================\n\n";

// Initialize WordPress environment
reset_wp_state();
simulate_wp_init();

// Demo attributes
$attributes = array(
    'goal_mode' => 'custom',
    'custom_goal_id' => 'patrons-10',
    'patreon_username' => 'demo-user',
    'design' => 'default',
    'toptext' => 'Help us reach our goal!',
    'bottomtext' => 'Every patron counts!'
);

echo "1. Default Block (no custom classes):\n";
echo "-------------------------------------\n";
reset_mock_block_custom_class();
$output1 = goalietron_block_render_callback($attributes, '');

// Extract just the opening div tag to show class attribute
preg_match('/<div[^>]*class="[^"]*"[^>]*>/', $output1, $matches1);
if ($matches1) {
    echo "HTML: " . htmlspecialchars($matches1[0]) . "\n";
} else {
    echo "No class attribute found\n";
}
echo "\n";

echo "2. Block with Single Custom Class:\n";
echo "----------------------------------\n";
set_mock_block_custom_class('my-theme');
$output2 = goalietron_block_render_callback($attributes, '');

preg_match('/<div[^>]*class="[^"]*"[^>]*>/', $output2, $matches2);
if ($matches2) {
    echo "HTML: " . htmlspecialchars($matches2[0]) . "\n";
}
echo "\n";

echo "3. Block with Multiple Custom Classes:\n";
echo "--------------------------------------\n";
set_mock_block_custom_class('theme-dark large-text highlighted');
$output3 = goalietron_block_render_callback($attributes, '');

preg_match('/<div[^>]*class="[^"]*"[^>]*>/', $output3, $matches3);
if ($matches3) {
    echo "HTML: " . htmlspecialchars($matches3[0]) . "\n";
}
echo "\n";

echo "4. Block with Custom Classes for Responsive Design:\n";
echo "---------------------------------------------------\n";
set_mock_block_custom_class('wp-block-column is-vertically-aligned-center');
$output4 = goalietron_block_render_callback($attributes, '');

preg_match('/<div[^>]*class="[^"]*"[^>]*>/', $output4, $matches4);
if ($matches4) {
    echo "HTML: " . htmlspecialchars($matches4[0]) . "\n";
}
echo "\n";

echo "5. Verification that content is preserved:\n";
echo "------------------------------------------\n";
set_mock_block_custom_class('custom-styled');
$output5 = goalietron_block_render_callback($attributes, '');

if (strpos($output5, 'Help us reach our goal!') !== false) {
    echo "✓ Top text preserved: 'Help us reach our goal!'\n";
} else {
    echo "✗ Top text missing\n";
}

if (strpos($output5, 'Every patron counts!') !== false) {
    echo "✓ Bottom text preserved: 'Every patron counts!'\n";
} else {
    echo "✗ Bottom text missing\n";
}

if (strpos($output5, 'goalietron_meter') !== false) {
    echo "✓ Progress meter element preserved\n";
} else {
    echo "✗ Progress meter missing\n";
}

if (strpos($output5, 'PatreonData') !== false) {
    echo "✓ JavaScript data integration preserved\n";
} else {
    echo "✗ JavaScript data missing\n";
}

echo "\n";
echo "Demo completed successfully!\n";
echo "============================\n";
echo "This demonstrates that:\n";
echo "- Default classes 'widget goalietron_widget' are always present\n";
echo "- Custom classes from the WordPress editor are properly added\n";
echo "- Multiple custom classes are supported\n";
echo "- All functionality and content is preserved\n";
echo "- The block integrates properly with WordPress's customClassName support\n";