<?php
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

function draw_progress_bar($current, $total, $width = 50) {
    $percentage = ($current / $total) * 100;
    $filled = floor(($current / $total) * $width);
    $empty = $width - $filled;
    
    $bar = wp_success("█") . wp_success(str_repeat("█", $filled - 1)) . 
           wp_dim(str_repeat("░", $empty));
    
    return sprintf("[%s] %3d%% (%d/%d)", $bar, $percentage, $current, $total);
}

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

if ($argc !== 2) {
    echo "Usage: php wp-patrol.php <dirname>\n";
    exit(1);
}

$scanDir = $argv[1];

if (!is_dir($scanDir)) {
    echo "Error: Directory not found.\n";
    exit(1);
}

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
    echo "No wp-config.php files found in the specified directory.\n";
    exit(0);
}

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

foreach ($wpConfigs as $wpConfig) {
    $credentials = getDbCredentials($wpConfig);

    if (empty($credentials)) {
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
        continue;
    }

    if ($conn->connect_error) {
        continue;
    }

    $tablePrefix = $credentials['DB_TABLE_PREFIX'];
    $sql = "SELECT option_name, option_value FROM {$tablePrefix}options WHERE option_name IN ('siteurl', 'blogname') ORDER BY option_name ASC";
    $result = $conn->query($sql);
    $siteInfo = $result->fetch_all(MYSQLI_ASSOC);

    $sql = "SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'template'";
    $result = $conn->query($sql);
    $theme = $result->fetch_assoc();

    $sql = "SELECT option_value FROM {$tablePrefix}options WHERE option_name = 'active_plugins'";
    $result = $conn->query($sql);
    $plugins = $result->fetch_assoc();

    $sql = "SELECT user_login, user_email, user_registered FROM {$tablePrefix}users u JOIN {$tablePrefix}usermeta um ON u.ID = um.user_id WHERE um.meta_key = '{$tablePrefix}capabilities' AND um.meta_value LIKE '%administrator%'";
    $result = $conn->query($sql);
    $admins = $result->fetch_all(MYSQLI_ASSOC);

    $allSiteData[] = [
        'config_path' => $wpConfig,
        'blogname' => $siteInfo[0]['option_value'],
        'site_url' => $siteInfo[2]['option_value'],
        'theme' => $theme['option_value'],
        'plugins' => unserialize($plugins['option_value']),
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

$count = 1;
foreach ($allSiteData as $siteData) {
    echo "--------------------------------------------------\n";
    echo 'SITE '.$count. ': ' .$siteData['blogname'] . "\n";
    echo "--------------------------------------------------\n";
    echo "Config Path: " . $siteData['config_path'] . "\n";
    echo "Site URL: " . $siteData['site_url'] . "\n";
    echo "Theme: " . $siteData['theme'] . "\n";

    echo "Plugins:\n";
    foreach ($siteData['plugins'] as $plugin) {
        echo "  - " . $plugin . "\n";
    }

    echo "Admins:\n";
    $usernameWidth = 0;
    $emailWidth = 0;
    $registeredWidth = 0;

    foreach ($siteData['admins'] as $admin) {
        if (strlen($admin['user_login']) > $usernameWidth) {
            $usernameWidth = strlen($admin['user_login']);
        }
        if (strlen($admin['user_email']) > $emailWidth) {
            $emailWidth = strlen($admin['user_email']);
        }
        if (strlen($admin['user_registered']) > $registeredWidth) {
            $registeredWidth = strlen($admin['user_registered']);
        }
    }
    $usernameWidth += 2;
    $emailWidth += 2;
    $registeredWidth += 2;

    echo str_pad("Username", $usernameWidth) . str_pad("Email", $emailWidth) . str_pad("Registered", $registeredWidth) . "\n";
    echo str_repeat("-", $usernameWidth + $emailWidth + $registeredWidth) . "\n";

    foreach ($siteData['admins'] as $admin) {
        echo str_pad($admin['user_login'], $usernameWidth) . str_pad($admin['user_email'], $emailWidth) . str_pad($admin['user_registered'], $registeredWidth) . "\n";
    }

    echo str_repeat("-", $usernameWidth + $emailWidth + $registeredWidth) . "\n";
    echo "\n\n";
    $count++;
}
