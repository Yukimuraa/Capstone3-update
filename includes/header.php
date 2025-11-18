<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'CHMSU Business Affairs Office'; ?></title>
    <!-- Tailwind CSS - Local first, CDN fallback -->
    <script src="<?php echo $base_url ?? ''; ?>/assets/js/tailwindcss.js" onerror="this.onerror=null; this.src='https://cdn.tailwindcss.com'"></script>
    <!-- Font Awesome - Local first, CDN fallback -->
    <link rel="stylesheet" href="<?php echo $base_url ?? ''; ?>/assets/js/fontawesome.css" onerror="this.onerror=null; this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url ?? ''; ?>/assets/css/styles.css">
</head>
<body class="bg-gray-100">

