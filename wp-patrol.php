<?php
/*
 * WP Patrol - WordPress Security Intelligence & Monitoring Tool
 * Author: Yeni Setiawan <sandalian@protonmail.com>
 * Website: https://github.com/sandalian/wp-patrol
 * Version: 1.0.0
 * License: MIT
 * Usage: php wp-patrol.php <target_directory>
 * Example: php wp-patrol.php /var/www/html
 */

// ============================================================================
// COLOR & STYLING FUNCTIONS
// ============================================================================

function wp_color($text, $code) {
    return "\033[" . $code . "m" . $text . "\033[0m";
}

function wp_header($text) {
    return wp_color($text, "1;36"); // Bright Cyan
}

function wp_sep($text) {
    return wp_color($text, "2;37"); // Dim White
}

function wp_label($text) {
    return wp_color($text, "1;34"); // Bright Blue
}

function wp_value($text) {
    return wp_color($text, "0;37"); // White
}

function wp_bullet($text) {
    return wp_color($text, "0;32"); // Green
}

function wp_section($text) {
    return wp_color($text, "1;35"); // Bright Magenta
}

function wp_error($text) {
    return wp_color($text, "1;31"); // Bright Red
}

function wp_success($text) {
    return wp_color($text, "1;32"); // Bright Green
}

function wp_warning($text) {
    return wp_color($text, "1;33"); // Bright Yellow
}

function wp_info($text) {
    return wp_color($text, "0;36"); // Cyan
}

function wp_dim($text) {
    return wp_color($text, "2;37"); // Dim
}

function wp_bold($text) {
    return wp_color($text, "1;37"); // Bold White
}

function wp_strikethrough($text) {
    return wp_color($text, "9;37"); // Strikethrough
}

// ============================================================================
// BOX DRAWING & UI ELEMENTS
// ============================================================================

function draw_box($title, $content, $width = 80) {
    $lines = [];
    
    // Top border with title
    $titleLen = mb_strlen($title);
    $leftPad = floor(($width - $titleLen - 4) / 2);
    $rightPad = $width - $titleLen - $leftPad - 4;
    
    $lines[] = wp_header("╔" . str_repeat("═", $leftPad) . "═ " . $title . " " . str_repeat("═", $rightPad) . "╗");
    
    // Content
    foreach ($content as $line) {
        $lineLen = mb_strlen(preg_replace('/\033\[[0-9;]+m/', '', $line));
        $maxContentWidth = $width - 3; // Account for "║ " and " ║"
        
        // If line is too long, truncate it
        if ($lineLen > $maxContentWidth) {
            // Strip ANSI codes, truncate, then re-apply last color if any
            $plainLine = preg_replace('/\033\[[0-9;]+m/', '', $line);
            $truncated = mb_substr($plainLine, 0, $maxContentWidth - 3) . "...";
            $line = $truncated;
            $lineLen = mb_strlen($line);
        }
        
        $padding = $width - $lineLen - 2;
        $padding = max(0, $padding - 1); // Ensure padding is never negative
        $lines[] = wp_header("║") . " " . $line . str_repeat(" ", $padding) . wp_header("║");
    }
    
    // Bottom border
    $lines[] = wp_header("╚" . str_repeat("═", $width - 2) . "╝");
    
    return implode("\n", $lines);
}


function draw_banner() {
    $banner = [
        "",
        wp_header("  ██╗    ██╗██████╗       ██████╗  █████╗ ████████╗██████╗  ██████╗ ██╗     "),
        wp_header("  ██║    ██║██╔══██╗      ██╔══██╗██╔══██╗╚══██╔══╝██╔══██╗██╔═══██╗██║     "),
        wp_header("  ██║ █╗ ██║██████╔╝█████╗██████╔╝███████║   ██║   ██████╔╝██║   ██║██║     "),
        wp_header("  ██║███╗██║██╔═══╝ ╚════╝██╔═══╝ ██╔══██║   ██║   ██╔══██╗██║   ██║██║     "),
        wp_header("  ╚███╔███╔╝██║           ██║     ██║  ██║   ██║   ██║  ██║╚██████╔╝███████╗"),
        wp_header("   ╚══╝╚══╝ ╚═╝           ╚═╝     ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝ ╚═════╝ ╚══════╝"),
        "",
        wp_dim("                    WordPress Security Intelligence & Monitoring Tool"),
        wp_dim("                                   v1.0.0"),
        ""
    ];
    
    return implode("\n", $banner);
}

// yep, fancy progress bar
function draw_progress_bar($current, $total, $width = 50) {
    $percentage = ($current / $total) * 100;
    $filled = floor(($current / $total) * $width);
    $empty = $width - $filled;
    
    $bar = wp_success("█") . wp_success(str_repeat("█", $filled - 1)) . 
           wp_dim(str_repeat("░", $empty));
    
    return sprintf("[%s] %3d%% (%d/%d)", $bar, $percentage, $current, $total);
}

// yep, fancy table
function draw_table($headers, $rows, $columnWidths = null, $totalWidth = null) {
    if ($columnWidths === null) {
        // Auto-calculate column widths
        $columnWidths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $cellLen = strlen(preg_replace('/\033\[[0-9;]+m/', '', $cell));
                if ($cellLen > $columnWidths[$i]) {
                    $columnWidths[$i] = $cellLen;
                }
            }
        }
        // Add padding
        $columnWidths = array_map(function($w) { return $w + 2; }, $columnWidths);
        
        // If totalWidth is specified, adjust column widths to fit
        if ($totalWidth !== null) {
            $numColumns = count($columnWidths);
            $borderWidth = $numColumns + 1; // vertical borders
            $availableWidth = $totalWidth - $borderWidth;
            
            // Calculate current total
            $currentTotal = array_sum($columnWidths);
            
            if ($currentTotal < $availableWidth) {
                // Expand columns proportionally to fill the width
                $extraSpace = $availableWidth - $currentTotal;
                $spacePerColumn = floor($extraSpace / $numColumns);
                $remainder = $extraSpace % $numColumns;
                
                for ($i = 0; $i < $numColumns; $i++) {
                    $columnWidths[$i] += $spacePerColumn;
                    if ($i < $remainder) {
                        $columnWidths[$i]++;
                    }
                }
            } elseif ($currentTotal > $availableWidth) {
                // Shrink columns proportionally to fit the width
                $ratio = $availableWidth / $currentTotal;
                for ($i = 0; $i < $numColumns; $i++) {
                    $columnWidths[$i] = max(10, floor($columnWidths[$i] * $ratio));
                }
                // Adjust to exact width
                $currentTotal = array_sum($columnWidths);
                $diff = $availableWidth - $currentTotal;
                if ($diff > 0) {
                    $columnWidths[0] += $diff;
                }
            }
        }
    }
    
    $lines = [];
    
    // Top border
    $lines[] = wp_sep("┌" . implode("┬", array_map(function($w) {
        return str_repeat("─", $w);
    }, $columnWidths)) . "┐");
    
    // Headers
    $headerLine = wp_sep("│");
    foreach ($headers as $i => $header) {
        $headerLine .= " " . wp_bold(str_pad($header, $columnWidths[$i] - 1)) . wp_sep("│");
    }
    $lines[] = $headerLine;
    
    // Header separator
    $lines[] = wp_sep("├" . implode("┼", array_map(function($w) {
        return str_repeat("─", $w);
    }, $columnWidths)) . "┤");
    
    // Rows
    foreach ($rows as $row) {
        $rowLine = wp_sep("│");
        foreach ($row as $i => $cell) {
            $cellLen = strlen(preg_replace('/\033\[[0-9;]+m/', '', $cell));
            $padding = $columnWidths[$i] - $cellLen - 1;
            $rowLine .= " " . $cell . str_repeat(" ", $padding) . wp_sep("│");
        }
        $lines[] = $rowLine;
    }
    
    // Bottom border
    $lines[] = wp_sep("└" . implode("┴", array_map(function($w) {
        return str_repeat("─", $w);
    }, $columnWidths)) . "┘");
    
    return implode("\n", $lines);
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

// Clear screen and show banner
echo "\033[2J\033[H"; // Clear screen
echo draw_banner();
echo "\n";

$scanDir = null;
$htmlPath = null;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '-o' || $arg === '--output' || $arg === '-h' || $arg === '--html') {
        if (isset($argv[$i + 1])) {
            $htmlPath = $argv[$i + 1];
            $i++;
        } else {
            echo draw_box("ERROR", [
                wp_error("Missing value for output path option!"),
                "",
                wp_label("Usage: ") . wp_value("php wp-patrol.php <directory> [options]"),
                "",
                wp_dim("Options:"),
                wp_dim("  -o, --output, -h, --html <path>  Specify output HTML file path")
            ], 70);
            echo "\n";
            exit(1);
        }
    } elseif (strpos($arg, '--output=') === 0) {
        $htmlPath = substr($arg, 9);
    } elseif (strpos($arg, '--html=') === 0) {
        $htmlPath = substr($arg, 7);
    } elseif (strpos($arg, '-') === 0) {
        echo draw_box("ERROR", [
            wp_error("Unknown option: " . $arg),
            "",
            wp_label("Usage: ") . wp_value("php wp-patrol.php <directory> [options]"),
            "",
            wp_dim("Options:"),
            wp_dim("  -o, --output, -h, --html <path>  Specify output HTML file path")
        ], 70);
        echo "\n";
        exit(1);
    } else {
        $scanDir = $arg;
    }
}

if ($scanDir === null) {
    echo draw_box("ERROR", [
        wp_error("Target directory is required!"),
        "",
        wp_label("Usage: ") . wp_value("php wp-patrol.php <directory> [options]"),
        "",
        wp_dim("Options:"),
        wp_dim("  -o, --output, -h, --html <path>  Specify output HTML file path"),
        "",
        wp_dim("Example: php wp-patrol.php /var/www/html -o report.html")
    ], 70);
    echo "\n";
    exit(1);
}

if (!is_dir($scanDir)) {
    echo draw_box("ERROR", [
        wp_error("Directory not found!"),
        "",
        wp_label("Path: ") . wp_value($scanDir),
        "",
        wp_dim("Please check the path and try again.")
    ], 70);
    echo "\n";
    exit(1);
}

echo draw_box("SCAN INITIATED", [
    wp_label("Target Directory: ") . wp_value($scanDir),
    wp_label("Timestamp: ") . wp_value(date('Y-m-d H:i:s')),
], 80);
echo "\n\n";

echo wp_info("Scanning for WordPress installations...\n");

// find any wp-config.php files, should be filtered later
function findWpConfigs($dir) {
    $wpConfigs = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->getFilename() === 'wp-config.php') {
            $wpConfigs[] = $file->getPathname();
        }
    }

    return $wpConfigs;
}

$wpConfigs = findWpConfigs($scanDir);

if (empty($wpConfigs)) {
    echo "\n";
    echo draw_box("SCAN COMPLETE", [
        wp_warning("No WordPress installations found"),
        "",
        wp_dim("No wp-config.php files were detected in the specified directory."),
        wp_dim("This could mean:"),
        wp_dim("  • No WordPress sites are installed"),
        wp_dim("  • Insufficient read permissions"),
        wp_dim("  • Sites are located in a different directory")
    ], 80);
    echo "\n";

    if ($htmlPath) {
        $htmlContent = generateHtmlReport($scanDir, [], []);
        $dir = dirname($htmlPath);
        if (!empty($dir) && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (@file_put_contents($htmlPath, $htmlContent) === false) {
            echo wp_error("Failed to write HTML report to: ") . wp_value($htmlPath) . "\n";
        } else {
            echo wp_success("HTML report successfully generated at: ") . wp_bold($htmlPath) . "\n\n";
        }
    }

    exit(0);
}

echo wp_success("Found " . count($wpConfigs) . " WordPress installation(s)\n\n");
echo wp_info("Gathering site information...\n\n");

// get db credentials from wp-config.php
function getDbCredentials($file) {
    $content = file_get_contents($file);
    $credentials = [];

    if (preg_match("/define\(\s*'DB_NAME',\s*'([^']+)'\s*\)/", $content, $matches)) {
        $credentials['DB_NAME'] = $matches[1];
    }

    if (preg_match("/define\(\s*'DB_USER',\s*'([^']+)'\s*\)/", $content, $matches)) {
        $credentials['DB_USER'] = $matches[1];
    }

    if (preg_match("/define\(\s*'DB_PASSWORD',\s*'([^']+)'\s*\)/", $content, $matches)) {
        $credentials['DB_PASSWORD'] = $matches[1];
    }

    if (preg_match("/define\(\s*'DB_HOST',\s*'([^']+)'\s*\)/", $content, $matches)) {
        $credentials['DB_HOST'] = $matches[1];
    }

    if (preg_match('/\$table_prefix\s*=\s*\'([^\']+)\';/', $content, $matches)) {
        $credentials['DB_TABLE_PREFIX'] = $matches[1];
    }

    return $credentials;
}


$allSiteData = [];
$totalSites = count($wpConfigs);
$processedSites = 0;
$failedConnections = [];

foreach ($wpConfigs as $wpConfig) {
    $processedSites++;
    
    // Show progress
    echo "\r" . wp_dim("Processing: ") . draw_progress_bar($processedSites, $totalSites, 40);
    
    $credentials = getDbCredentials($wpConfig);

    if (empty($credentials)) {
        $failedConnections[] = [
            'config' => $wpConfig,
            'reason' => 'Unable to parse wp-config.php'
        ];
        continue;
    }

    try {
        $conn = new mysqli(
            $credentials['DB_HOST'],
            $credentials['DB_USER'],
            $credentials['DB_PASSWORD'],
            $credentials['DB_NAME']
        );
    } catch (Exception $e) {
        $failedConnections[] = [
            'config' => $wpConfig,
            'reason' => 'Database connection failed: ' . $e->getMessage()
        ];
        continue;
    }

    if ($conn->connect_error) {
        $failedConnections[] = [
            'config' => $wpConfig,
            'reason' => 'Database connection error: ' . $conn->connect_error
        ];
        continue;
    }

    // get site info from database
    $tablePrefix = $credentials['DB_TABLE_PREFIX'];
    $sql = "SELECT option_name, option_value FROM {$tablePrefix}options WHERE option_name IN ('siteurl', 'blogname') ORDER BY option_name ASC";
    $result = $conn->query($sql);
    $siteInfo = $result->fetch_all(MYSQLI_ASSOC);

    // get active theme
    $sql = "SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'template'";
    $result = $conn->query($sql);
    $theme = $result->fetch_assoc();

    // get active plugins
    $sql = "SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'active_plugins'";
    $result = $conn->query($sql);
    $plugins = $result->fetch_assoc();

    // get administrator users
    $sql = "SELECT user_login, user_email, user_registered FROM {$tablePrefix}users u JOIN {$tablePrefix}usermeta um ON u.ID = um.user_id WHERE um.meta_key = '{$tablePrefix}capabilities' AND um.meta_value LIKE '%administrator%'";
    $result = $conn->query($sql);
    $admins = $result->fetch_all(MYSQLI_ASSOC);

    // Get WordPress directory (parent of wp-config.php)
    $wpDir = dirname($wpConfig);
    $pluginsDir = $wpDir . '/wp-content/plugins';
    
    // Process plugins and check if files exist
    $pluginsList = unserialize($plugins['option_value']);
    $pluginsWithStatus = [];
    
    if (is_array($pluginsList)) {
        foreach ($pluginsList as $plugin) {
            $pluginPath = $pluginsDir . '/' . $plugin;
            $pluginsWithStatus[] = [
                'name' => $plugin,
                'exists' => file_exists($pluginPath)
            ];
        }
    }
    
    $allSiteData[] = [
        'config_path' => $wpConfig,
        'blogname' => $siteInfo[0]['option_value'],
        'site_url' => $siteInfo[1]['option_value'],
        'theme' => $theme['option_value'],
        'plugins' => $pluginsWithStatus,
        'admins' => array_map(function ($admin) {
            return [
                'user_login' => $admin['user_login'],
                'user_email' => $admin['user_email'],
                'user_registered' => $admin['user_registered'],
            ];
        }, $admins),
    ];

    $conn->close();
}

echo "\n\n";
echo wp_success("Data collection complete!\n\n");

// ============================================================================
// SUMMARY DASHBOARD
// ============================================================================

$totalPlugins = 0;
$totalAdmins = 0;
$uniqueThemes = [];

foreach ($allSiteData as $site) {
    $totalPlugins += count($site['plugins']);
    $totalAdmins += count($site['admins']);
    $uniqueThemes[$site['theme']] = true;
}

echo draw_box("SUMMARY DASHBOARD", [
    "",
    wp_label("Total Sites Found:        ") . wp_bold(count($allSiteData)),
    wp_label("Failed Connections:       ") . (count($failedConnections) > 0 ? wp_warning(count($failedConnections)) : wp_success("0")),
    wp_label("Total Active Plugins:     ") . wp_bold($totalPlugins),
    wp_label("Total Admin Users:        ") . wp_bold($totalAdmins),
    wp_label("Unique Themes:            ") . wp_bold(count($uniqueThemes)),
    "",
], 80);

echo "\n\n";
echo wp_header("═══════════════════════════════════════════════════════════════════════════════\n");
echo wp_bold("                              DETAILED SITE REPORTS                              \n");
echo wp_header("═══════════════════════════════════════════════════════════════════════════════\n");
echo "\n";



$count = 1;
foreach ($allSiteData as $siteData) {
    // Site header
    echo wp_header("┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓\n");
    $siteTitle = "SITE #" . $count . ": " . $siteData['blogname'];
    $padding = 78 - strlen($siteTitle);
    echo wp_header("┃") . " " . wp_bold($siteTitle) . str_repeat(" ", $padding) . wp_header("┃\n");
    echo wp_header("┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛\n");
    echo "\n";
    
    // Basic Information
    echo wp_section("BASIC INFORMATION\n");
    echo wp_sep("   ├─ ") . wp_label("Config Path:  ") . wp_dim($siteData['config_path']) . "\n";
    echo wp_sep("   ├─ ") . wp_label("Site URL:     ") . wp_info($siteData['site_url']) . "\n";
    echo wp_sep("   └─ ") . wp_label("Active Theme: ") . wp_value($siteData['theme']) . "\n";
    echo "\n";

    // Plugins Section
    echo wp_section("ACTIVE PLUGINS (" . count($siteData['plugins']) . ")\n");
    if (empty($siteData['plugins'])) {
        echo wp_dim("   └─ No active plugins\n");
    } else {
        $pluginCount = count($siteData['plugins']);
        $pluginIndex = 0;
        foreach ($siteData['plugins'] as $pluginData) {
            $pluginIndex++;
            $isLast = ($pluginIndex === $pluginCount);
            $connector = $isLast ? "└─" : "├─";
            
            // Display plugin name with strikethrough if file doesn't exist
            if ($pluginData['exists']) {
                echo wp_sep("   " . $connector . " ") . wp_value($pluginData['name']) . "\n";
            } else {
                echo wp_sep("   " . $connector . " ") . wp_strikethrough($pluginData['name']) . wp_dim(" (missing)") . "\n";
            }
        }
    }
    echo "\n";

    // Administrators Section
    echo wp_section("ADMINISTRATOR ACCOUNTS (" . count($siteData['admins']) . ")\n");
    
    if (empty($siteData['admins'])) {
        echo wp_warning("   └─ No administrators found!\n");
    } else {
        // Build table data
        $headers = ['Username', 'Email', 'Registered'];
        $rows = [];
        
        foreach ($siteData['admins'] as $admin) {
            $rows[] = [
                wp_value($admin['user_login']),
                wp_info($admin['user_email']),
                wp_dim($admin['user_registered'])
            ];
        }
        
        // Draw table with indent - match header width (80 chars total, minus 3 for indent = 77)
        $table = draw_table($headers, $rows, null, 77);
        $tableLines = explode("\n", $table);
        foreach ($tableLines as $line) {
            echo "   " . $line . "\n";
        }
    }
    
    echo "\n";
    echo wp_dim("───────────────────────────────────────────────────────────────────────────────\n");
    echo "\n";
    
    $count++;
}

// Final summary
echo "\n";

// Build summary content
$summaryContent = [
    wp_success("All sites have been analyzed"),
    "",
    wp_label("Total Sites Scanned: ") . wp_bold(count($allSiteData)),
    wp_label("Failed Connections: ") . (count($failedConnections) > 0 ? wp_warning(count($failedConnections)) : wp_success("0")),
];

// Add failed connection details if any
if (count($failedConnections) > 0) {
    $summaryContent[] = "";
    $summaryContent[] = wp_warning("Failed Connection Details:");
    foreach ($failedConnections as $failure) {
        $summaryContent[] = wp_dim("  " . basename(dirname($failure['config'])));
        $summaryContent[] = wp_dim("    Path: ") . wp_value($failure['config']);
        $summaryContent[] = wp_dim("    Reason: ") . wp_error($failure['reason']);
        $summaryContent[] = "";
    }
}

$summaryContent[] = wp_label("Timestamp: ") . wp_value(date('Y-m-d H:i:s'));

echo draw_box("SCAN COMPLETE", $summaryContent, 80);
echo "\n";

if ($htmlPath) {
    $htmlContent = generateHtmlReport($scanDir, $allSiteData, $failedConnections);
    $dir = dirname($htmlPath);
    if (!empty($dir) && !is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (@file_put_contents($htmlPath, $htmlContent) === false) {
        echo wp_error("Failed to write HTML report to: ") . wp_value($htmlPath) . "\n";
    } else {
        echo wp_success("HTML report successfully generated at: ") . wp_bold($htmlPath) . "\n\n";
    }
}

// ============================================================================
// HTML REPORT GENERATOR
// ============================================================================

function generateHtmlReport($scanDir, $allSiteData, $failedConnections) {
    $scanData = [
        'scanDir' => $scanDir,
        'allSiteData' => $allSiteData,
        'failedConnections' => $failedConnections,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $jsonData = json_encode($scanData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Patrol Security Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #060913;
            color: #f3f4f6;
            line-height: 1.5;
            padding: 2rem;
            min-height: 100vh;
            background-image: 
                radial-gradient(at 0% 0%, rgba(6, 182, 212, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(239, 68, 68, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            background: linear-gradient(135deg, rgba(16, 24, 48, 0.8) 0%, rgba(10, 15, 30, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(12px);
        }
        header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00f0ff, #6366f1, #ef4444);
        }
        .header-title-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logo-section svg {
            width: 48px;
            height: 48px;
            color: #00f0ff;
            filter: drop-shadow(0 0 8px rgba(0, 240, 255, 0.4));
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            background: linear-gradient(to right, #ffffff, #9ca3af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 0.9rem;
            color: #00f0ff;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .meta-info {
            font-size: 0.9rem;
            color: #8a99ad;
            margin-top: 1.5rem;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.5rem;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .meta-item span.label {
            color: #52657a;
            font-weight: 500;
        }
        .meta-item span.value {
            color: #f3f4f6;
            font-family: 'JetBrains Mono', monospace;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: rgba(16, 24, 48, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 12px 30px 0 rgba(0, 0, 0, 0.4);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg {
            width: 24px;
            height: 24px;
        }
        .stat-content {
            display: flex;
            flex-direction: column;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            font-family: 'JetBrains Mono', monospace;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #8a99ad;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.25rem;
        }
        
        .color-cyan .stat-icon { background: rgba(0, 240, 255, 0.1); color: #00f0ff; }
        .color-cyan:hover { border-color: rgba(0, 240, 255, 0.3); }
        
        .color-emerald .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .color-emerald:hover { border-color: rgba(16, 185, 129, 0.3); }
        
        .color-amber .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .color-amber:hover { border-color: rgba(245, 158, 11, 0.3); }
        
        .color-rose .stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .color-rose:hover { border-color: rgba(239, 68, 68, 0.3); }
        
        .color-violet .stat-icon { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .color-violet:hover { border-color: rgba(99, 102, 241, 0.3); }

        .search-filter-section {
            background: rgba(16, 24, 48, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            backdrop-filter: blur(12px);
        }
        .search-box {
            position: relative;
            flex-grow: 1;
            max-width: 500px;
        }
        .search-box svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #52657a;
            pointer-events: none;
            transition: color 0.3s;
        }
        .search-input {
            width: 100%;
            background: rgba(8, 12, 24, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #00f0ff;
            box-shadow: 0 0 0 3px rgba(0, 240, 255, 0.15);
            background: rgba(8, 12, 24, 0.8);
        }
        .search-input:focus + svg {
            color: #00f0ff;
        }
        .filter-group {
            display: flex;
            gap: 0.5rem;
        }
        .filter-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #8a99ad;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .filter-btn.active {
            background: #00f0ff;
            border-color: #00f0ff;
            color: #090d16;
            box-shadow: 0 0 12px rgba(0, 240, 255, 0.3);
        }
        .filter-badge {
            background: rgba(0, 0, 0, 0.15);
            padding: 0.1rem 0.4rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .filter-btn.active .filter-badge {
            background: rgba(9, 13, 22, 0.15);
            color: #00f0ff;
        }

        .failures-section {
            margin-bottom: 2rem;
            display: none;
        }
        .failures-title {
            color: #ef4444;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .failures-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .failure-card {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            animation: pulse-border 2s infinite ease-in-out;
        }
        @keyframes pulse-border {
            0%, 100% { border-color: rgba(239, 68, 68, 0.2); }
            50% { border-color: rgba(239, 68, 68, 0.4); }
        }
        .failure-icon {
            color: #ef4444;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        .failure-info h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #ef4444;
            margin-bottom: 0.25rem;
        }
        .failure-details {
            font-size: 0.9rem;
            color: #e5e7eb;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 0.5rem;
        }
        .failure-path {
            font-size: 0.85rem;
            color: #8a99ad;
            margin-top: 0.25rem;
        }

        .sites-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        .site-card {
            background: rgba(16, 24, 48, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(12px);
        }
        .site-card:hover {
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        .site-card.has-issues {
            border-left: 4px solid #ef4444;
        }
        .site-card.healthy {
            border-left: 4px solid #10b981;
        }
        .site-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 1.25rem;
        }
        .site-title-area {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .site-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .site-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .site-badge.badge-healthy {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .site-badge.badge-issues {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .site-url-link {
            color: #00f0ff;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 500;
            width: fit-content;
        }
        .site-url-link:hover {
            text-decoration: underline;
        }
        
        .site-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-block {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .info-label {
            font-size: 0.8rem;
            color: #52657a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .info-value {
            font-size: 0.95rem;
            color: #e5e7eb;
            background: rgba(8, 12, 24, 0.4);
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            font-family: inherit;
        }
        .info-value.code-font {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }

        .site-tabs-section {
            background: rgba(8, 12, 24, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            overflow: hidden;
        }
        .tabs-header {
            display: flex;
            background: rgba(8, 12, 24, 0.5);
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .tab-button {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: #8a99ad;
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tab-button:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.02);
        }
        .tab-button.active {
            color: #00f0ff;
            border-bottom-color: #00f0ff;
            background: rgba(0, 240, 255, 0.02);
        }
        .tabs-content {
            padding: 1.5rem;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .plugins-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.75rem;
        }
        .plugin-item {
            background: rgba(8, 12, 24, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        .plugin-item:hover {
            background: rgba(8, 12, 24, 0.6);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .plugin-name-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .plugin-status-icon {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .plugin-status-icon.status-ok { color: #10b981; }
        .plugin-status-icon.status-missing { color: #ef4444; }
        .plugin-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #e5e7eb;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .plugin-item.missing .plugin-name {
            color: #8a99ad;
            text-decoration: line-through;
        }
        .plugin-badge-missing {
            font-size: 0.7rem;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }
        th {
            background: rgba(8, 12, 24, 0.5);
            color: #8a99ad;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: #e5e7eb;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }
        .admin-username {
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-email {
            font-family: 'JetBrains Mono', monospace;
            color: #00f0ff;
            font-size: 0.85rem;
        }
        .admin-date {
            color: #8a99ad;
            font-size: 0.85rem;
        }

        .no-records {
            text-align: center;
            color: #8a99ad;
            padding: 2rem;
            font-size: 0.95rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .no-records svg {
            width: 32px;
            height: 32px;
            color: #52657a;
        }

        .no-results {
            background: rgba(16, 24, 48, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            color: #8a99ad;
            margin-top: 2rem;
            display: none;
        }
        .no-results-title {
            font-size: 1.25rem;
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        footer {
            margin-top: 4rem;
            text-align: center;
            font-size: 0.85rem;
            color: #52657a;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1.5rem;
        }
        footer a {
            color: #00f0ff;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            header {
                padding: 1.5rem;
            }
            h1 {
                font-size: 1.8rem;
            }
            .meta-info {
                gap: 1rem;
                flex-direction: column;
            }
            .search-filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                max-width: 100%;
            }
            .filter-group {
                justify-content: space-between;
            }
            .filter-btn {
                flex-grow: 1;
                justify-content: center;
            }
            .site-card {
                padding: 1.25rem;
            }
            .site-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title-container">
                <div class="logo-section">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <div>
                        <h1>WP Patrol</h1>
                        <div class="subtitle">Security Intelligence Report</div>
                    </div>
                </div>
            </div>
            <div class="meta-info">
                <div class="meta-item">
                    <span class="label">Target Directory:</span>
                    <span class="value" id="meta-target-dir"></span>
                </div>
                <div class="meta-item">
                    <span class="label">Scan Time:</span>
                    <span class="value" id="meta-timestamp"></span>
                </div>
            </div>
        </header>

        <section class="dashboard-grid">
            <div class="stat-card color-cyan">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="stat-total-sites">0</span>
                    <span class="stat-label">Sites Found</span>
                </div>
            </div>
            
            <div class="stat-card color-rose">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="stat-failures">0</span>
                    <span class="stat-label">DB Failures</span>
                </div>
            </div>
            
            <div class="stat-card color-emerald">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a2 2 0 11-4 0V4zM4 14a2 2 0 114 0v1a2 2 0 11-4 0v-1zM11 14a2 2 0 114 0v1a2 2 0 11-4 0v-1zM4 4a2 2 0 114 0v1a2 2 0 11-4 0V4z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="stat-plugins">0</span>
                    <span class="stat-label">Active Plugins</span>
                </div>
            </div>
            
            <div class="stat-card color-amber">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="stat-admins">0</span>
                    <span class="stat-label">Admin Accounts</span>
                </div>
            </div>
            
            <div class="stat-card color-violet">
                <div class="stat-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value" id="stat-themes">0</span>
                    <span class="stat-label">Unique Themes</span>
                </div>
            </div>
        </section>

        <section class="failures-section" id="failures-section">
            <h2 class="failures-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Connection / Parsing Failures
            </h2>
            <div class="failures-grid" id="failures-container"></div>
        </section>

        <section class="search-filter-section">
            <div class="search-box">
                <input type="text" class="search-input" id="search-input" placeholder="Search site name, url, configuration, theme, or plugin...">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">
                    All Sites
                    <span class="filter-badge" id="count-all">0</span>
                </button>
                <button class="filter-btn" data-filter="issues">
                    Issues Detected
                    <span class="filter-badge" id="count-issues">0</span>
                </button>
                <button class="filter-btn" data-filter="healthy">
                    Healthy
                    <span class="filter-badge" id="count-healthy">0</span>
                </button>
            </div>
        </section>

        <div class="sites-grid" id="sites-container"></div>
        
        <div class="no-results" id="no-results">
            <h3 class="no-results-title">No WordPress sites found matching filters</h3>
            <p>Try resetting the search filter or active statuses.</p>
        </div>

        <footer>
            Generated by <a href="https://github.com/sandalian/wp-patrol" target="_blank" rel="noopener noreferrer">WP Patrol</a>.
        </footer>
    </div>

    <script id="scan-data" type="application/json"><?php echo $jsonData; ?></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = JSON.parse(document.getElementById('scan-data').textContent);
            const sites = data.allSiteData || [];
            const failures = data.failedConnections || [];

            document.getElementById('meta-target-dir').textContent = data.scanDir;
            document.getElementById('meta-timestamp').textContent = data.timestamp;

            let totalPlugins = 0;
            let totalAdmins = 0;
            const uniqueThemes = new Set();
            let sitesWithIssuesCount = 0;

            sites.forEach(site => {
                totalPlugins += site.plugins ? site.plugins.length : 0;
                totalAdmins += site.admins ? site.admins.length : 0;
                if (site.theme) uniqueThemes.add(site.theme);
                
                let hasMissingPlugin = site.plugins && site.plugins.some(p => !p.exists);
                let hasNoAdmin = !site.admins || site.admins.length === 0;
                if (hasMissingPlugin || hasNoAdmin) {
                    sitesWithIssuesCount++;
                    site.hasIssues = true;
                } else {
                    site.hasIssues = false;
                }
            });

            document.getElementById('stat-total-sites').textContent = sites.length;
            document.getElementById('stat-failures').textContent = failures.length;
            document.getElementById('stat-plugins').textContent = totalPlugins;
            document.getElementById('stat-admins').textContent = totalAdmins;
            document.getElementById('stat-themes').textContent = uniqueThemes.size;

            document.getElementById('count-all').textContent = sites.length;
            document.getElementById('count-issues').textContent = sitesWithIssuesCount + failures.length;
            document.getElementById('count-healthy').textContent = sites.length - sitesWithIssuesCount;

            const failuresContainer = document.getElementById('failures-container');
            const failuresSection = document.getElementById('failures-section');
            if (failures.length > 0) {
                failuresSection.style.display = 'block';
                failuresContainer.innerHTML = failures.map(f => `
                    <div class="failure-card">
                        <div class="failure-icon">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="failure-info">
                            <h3>Database Connection Failed</h3>
                            <div class="failure-path">Configuration: ${escapeHtml(f.config)}</div>
                            <div class="failure-details">${escapeHtml(f.reason)}</div>
                        </div>
                    </div>
                `).join('');
            }

            const sitesContainer = document.getElementById('sites-container');
            
            function renderSitesList(filteredSites) {
                if (filteredSites.length === 0) {
                    sitesContainer.innerHTML = '';
                    document.getElementById('no-results').style.display = 'block';
                    return;
                }
                document.getElementById('no-results').style.display = 'none';

                sitesContainer.innerHTML = filteredSites.map((site, index) => {
                    const pluginCount = site.plugins ? site.plugins.length : 0;
                    const adminCount = site.admins ? site.admins.length : 0;
                    
                    let pluginsHtml = '';
                    if (pluginCount === 0) {
                        pluginsHtml = `
                            <div class="no-records">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                No active plugins detected
                            </div>
                        `;
                    } else {
                        pluginsHtml = `
                            <div class="plugins-list">
                                ${site.plugins.map(p => `
                                    <div class="plugin-item ${p.exists ? '' : 'missing'}">
                                        <div class="plugin-name-wrapper">
                                            <span class="plugin-status-icon ${p.exists ? 'status-ok' : 'status-missing'}">
                                                ${p.exists ? `
                                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                ` : `
                                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                `}
                                            </span>
                                            <span class="plugin-name" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</span>
                                        </div>
                                        ${p.exists ? '' : '<span class="plugin-badge-missing">Missing file</span>'}
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }

                    let adminsHtml = '';
                    if (adminCount === 0) {
                        adminsHtml = `
                            <div class="no-records">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span style="color: #ef4444; font-weight: 600;">No administrator accounts found!</span>
                            </div>
                        `;
                    } else {
                        adminsHtml = `
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${site.admins.map(a => `
                                            <tr>
                                                <td>
                                                    <div class="admin-username">
                                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        ${escapeHtml(a.user_login)}
                                                    </div>
                                                </td>
                                                <td><span class="admin-email">${escapeHtml(a.user_email)}</span></td>
                                                <td><span class="admin-date">${escapeHtml(a.user_registered)}</span></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    return `
                        <div class="site-card ${site.hasIssues ? 'has-issues' : 'healthy'}" data-index="${index}">
                            <div class="site-card-header">
                                <div class="site-title-area">
                                    <h2 class="site-title">
                                        ${escapeHtml(site.blogname || 'WordPress Site')}
                                        <span class="site-badge ${site.hasIssues ? 'badge-issues' : 'badge-healthy'}">
                                            ${site.hasIssues ? 'Issues Detected' : 'Healthy'}
                                        </span>
                                    </h2>
                                    <a href="${escapeHtml(site.site_url)}" target="_blank" rel="noopener noreferrer" class="site-url-link">
                                        ${escapeHtml(site.site_url)}
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="site-info-grid">
                                <div class="info-block">
                                    <span class="info-label">Configuration Path</span>
                                    <span class="info-value code-font" title="${escapeHtml(site.config_path)}">${escapeHtml(site.config_path)}</span>
                                </div>
                                <div class="info-block">
                                    <span class="info-label">Active Theme</span>
                                    <span class="info-value">${escapeHtml(site.theme || 'None')}</span>
                                </div>
                            </div>

                            <div class="site-tabs-section">
                                <div class="tabs-header">
                                    <button class="tab-button active" onclick="switchTab(this, 'plugins', ${index})">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a2 2 0 11-4 0V4zM4 14a2 2 0 114 0v1a2 2 0 11-4 0v-1zM11 14a2 2 0 114 0v1a2 2 0 11-4 0v-1zM4 4a2 2 0 114 0v1a2 2 0 11-4 0V4z"/>
                                        </svg>
                                        Active Plugins (${pluginCount})
                                    </button>
                                    <button class="tab-button" onclick="switchTab(this, 'admins', ${index})">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        Administrators (${adminCount})
                                    </button>
                                </div>
                                <div class="tabs-content">
                                    <div class="tab-panel active" data-panel="plugins-${index}">
                                        ${pluginsHtml}
                                    </div>
                                    <div class="tab-panel" data-panel="admins-${index}">
                                        ${adminsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            window.switchTab = function(button, tabName, siteIndex) {
                const header = button.parentElement;
                Array.from(header.children).forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const card = header.closest('.site-card');
                const panels = card.querySelectorAll('.tab-panel');
                panels.forEach(panel => {
                    const panelName = panel.dataset.panel;
                    if (panelName === `${tabName}-${siteIndex}`) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });
            };

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;')
                          .replace(/</g, '&lt;')
                          .replace(/>/g, '&gt;')
                          .replace(/"/g, '&quot;')
                          .replace(/'/g, '&#039;');
            }

            const searchInput = document.getElementById('search-input');
            const filterButtons = document.querySelectorAll('.filter-btn');

            function applySearchAndFilters() {
                const query = searchInput.value.toLowerCase().trim();
                let activeFilter = 'all';
                
                filterButtons.forEach(btn => {
                    if (btn.classList.contains('active')) {
                        activeFilter = btn.dataset.filter;
                    }
                });

                const filtered = sites.filter(site => {
                    if (activeFilter === 'healthy' && site.hasIssues) return false;
                    if (activeFilter === 'issues' && !site.hasIssues) return false;

                    if (query !== '') {
                        const nameMatch = (site.blogname || '').toLowerCase().includes(query);
                        const urlMatch = (site.site_url || '').toLowerCase().includes(query);
                        const themeMatch = (site.theme || '').toLowerCase().includes(query);
                        const configMatch = (site.config_path || '').toLowerCase().includes(query);
                        const pluginMatch = site.plugins && site.plugins.some(p => p.name.toLowerCase().includes(query));
                        
                        return nameMatch || urlMatch || themeMatch || configMatch || pluginMatch;
                    }

                    return true;
                });

                renderSitesList(filtered);
            }

            searchInput.addEventListener('input', applySearchAndFilters);
            
            filterButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    applySearchAndFilters();
                });
            });

            renderSitesList(sites);
        });
    </script>
</body>
</html>
    <?php
    return ob_get_clean();
}
