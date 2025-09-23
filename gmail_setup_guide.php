<?php
// Gmail Setup Guide and Test
// This will help you set up Gmail properly

echo "<h2>Gmail Setup Guide for damasingjoemarie@gmail.com</h2>";

echo "<h3>Step 1: Check 2-Factor Authentication</h3>";
echo "<p>1. Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></p>";
echo "<p>2. Look for '2-Step Verification' - it should be ON</p>";
echo "<p>3. If it's OFF, click on it and follow the setup process</p>";

echo "<h3>Step 2: Generate App Password</h3>";
echo "<p>1. Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></p>";
echo "<p>2. Click on '2-Step Verification'</p>";
echo "<p>3. Scroll down to 'App passwords'</p>";
echo "<p>4. Click 'App passwords'</p>";
echo "<p>5. Select 'Mail' from the dropdown</p>";
echo "<p>6. Select 'Other (Custom name)' from the device dropdown</p>";
echo "<p>7. Type 'CHMSU Business Affairs' as the name</p>";
echo "<p>8. Click 'Generate'</p>";
echo "<p>9. Copy the 16-character password (it looks like: abcd efgh ijkl mnop)</p>";
echo "<p>10. <strong>IMPORTANT:</strong> Remove all spaces when copying to the config file</p>";

echo "<h3>Step 3: Test Current Configuration</h3>";
echo "<p>Current password in config: <code>uwfs tkrb hqep pehx</code></p>";
echo "<p>This should be exactly 16 characters with no spaces.</p>";

echo "<h3>Step 4: Common Issues</h3>";
echo "<ul>";
echo "<li><strong>Spaces in password:</strong> Make sure there are NO spaces in the app password</li>";
echo "<li><strong>Wrong account:</strong> Make sure you're generating the app password for damasingjoemarie@gmail.com</li>";
echo "<li><strong>2FA not enabled:</strong> App passwords only work if 2-Factor Authentication is ON</li>";
echo "<li><strong>Account locked:</strong> Gmail may have temporarily locked the account</li>";
echo "<li><strong>Less secure apps:</strong> Make sure 'Less secure app access' is OFF (not ON)</li>";
echo "</ul>";

echo "<h3>Step 5: Test Different Configurations</h3>";
echo "<p>Try these configurations one by one:</p>";

$testConfigs = [
    'Configuration 1: TLS Port 587' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls'
    ],
    'Configuration 2: SSL Port 465' => [
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'encryption' => 'ssl'
    ],
    'Configuration 3: TLS Port 25' => [
        'host' => 'smtp.gmail.com',
        'port' => 25,
        'encryption' => 'tls'
    ]
];

foreach ($testConfigs as $name => $config) {
    echo "<h4>$name</h4>";
    echo "<pre>";
    echo "Host: {$config['host']}\n";
    echo "Port: {$config['port']}\n";
    echo "Encryption: {$config['encryption']}\n";
    echo "Username: damasingjoemarie@gmail.com\n";
    echo "Password: [your-16-character-app-password]\n";
    echo "</pre>";
}

echo "<h3>Step 6: Quick Test</h3>";
echo "<p>After updating your app password, run <a href='test_email.php'>test_email.php</a> to test the configuration.</p>";

echo "<h3>Step 7: If Still Failing</h3>";
echo "<p>If Gmail continues to fail, consider using Outlook/Hotmail instead:</p>";
echo "<ul>";
echo "<li>No app password needed</li>";
echo "<li>More reliable for automated emails</li>";
echo "<li>Easier to set up</li>";
echo "</ul>";
?>

