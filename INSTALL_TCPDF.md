# Installing TCPDF for PDF Generation

## Quick Installation

To enable automatic PDF generation for reports, you need to install TCPDF using Composer.

### Step 1: Install Composer (if not already installed)

Download and install Composer from: https://getcomposer.org/download/

### Step 2: Install TCPDF

Open a terminal/command prompt in your project root directory (`C:\xampp\htdocs\Capstone-3`) and run:

```bash
composer require tecnickcom/tcpdf
```

Or if you prefer to install all dependencies:

```bash
composer install
```

### Step 3: Verify Installation

After installation, you should see a `vendor` folder in your project root. The PDF download feature will now work automatically.

## Alternative: Browser Print-to-PDF

If you cannot install TCPDF, the system will automatically generate a print-friendly HTML page that you can save as PDF using your browser:

1. Click the "Download PDF" button
2. A print-friendly page will open
3. Press **Ctrl+P** (Windows) or **Cmd+P** (Mac)
4. Select "Save as PDF" or "Microsoft Print to PDF"
5. Click "Save"

This method works without any additional installation but requires manual steps.

## Troubleshooting

- **"composer: command not found"**: Make sure Composer is installed and added to your system PATH
- **Permission errors**: Make sure you have write permissions in the project directory
- **Vendor folder not created**: Check that Composer ran successfully and there were no errors

