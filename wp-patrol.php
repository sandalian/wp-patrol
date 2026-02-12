<?php
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
    $sql = "SELECT option_name, option_value FROM {$tablePrefix}options WHERE option_name IN ('siteurl', 'home', 'blogname') ORDER BY option_name ASC";
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
        'home_url' => $siteInfo[1]['option_value'],
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

foreach ($allSiteData as $siteData) {
    echo "--------------------------------------------------\n";
    echo $siteData['blogname'] . "\n";
    echo "--------------------------------------------------\n";
    echo "Config Path: " . $siteData['config_path'] . "\n";
    echo "Site URL: " . $siteData['site_url'] . "\n";
    echo "Home URL: " . $siteData['home_url'] . "\n";
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
}
