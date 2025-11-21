<?php
// Email Configuration for CHMSU Business Affairs Office
// This file contains email settings for sending OTP and notifications

return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls', // Changed to TLS
        'username' => 'systembao123@gmail.com', // Your Gmail address
        'password' => 'qokm mplq shbf tdbi', // App Password (NOT your regular password)
        'from_email' => 'systembao123@gmail.com',
        'from_name' => 'CHMSU Business Affairs',
        'debug' => 0 // Disable debugging for production
    ],
    
    'otp' => [
        'expiry_minutes' => 3,
        'length' => 6
    ]
];

/*
IMPORTANT: Gmail Setup Instructions

1. Enable 2-Factor Authentication on your Gmail account:
   - Go to Google Account settings
   - Security → 2-Step Verification → Turn on

2. Generate an App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Select "Mail" and "Other (Custom name)"
   - Enter "CHMSU Business Affairs" as the name
   - Copy the generated 16-character password (no spaces)
   - Replace the password above with this new app password

3. If you're still having issues:
   - Make sure "Less secure app access" is turned OFF (it should be OFF when using app passwords)
   - Try using 'tls' instead of 'ssl' for encryption
   - Change port to 587 if using TLS
   - Enable SMTP debugging by setting 'debug' => 2

4. Alternative: Use a different email service:
   - Outlook/Hotmail: smtp-mail.outlook.com, port 587, TLS
   - Yahoo: smtp.mail.yahoo.com, port 587, TLS
   - Or use a service like SendGrid, Mailgun, etc.
*/
