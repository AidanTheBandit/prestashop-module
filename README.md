# Printful PrestaShop Module

Official Printful integration module for PrestaShop.

## Compatibility

- **PrestaShop**: 1.7.6 - 9.99.99
- **PHP**: 7.1+

## Installation

### From GitHub Releases

1. Download the latest `printful-vX.X.X.zip` from the [Releases page](https://github.com/AidanTheBandit/prestashop-module/releases)
2. In your PrestaShop admin panel, go to **Modules > Module Manager**
3. Click **"Upload a module"** and select the downloaded zip file
4. Follow the installation instructions

### From Source

1. Clone this repository
2. Run `composer install --no-dev` (if you have composer installed)
3. Create a zip file containing all module files
4. Upload via PrestaShop Module Manager

## Features

- Connect your PrestaShop store to Printful
- Automatic order synchronization
- Product fulfillment management
- Store statistics and reporting

## Configuration

After installation:

1. Navigate to **Modules > Printful** in your PrestaShop admin panel
2. Click **"Connect"** to link your Printful account
3. Configure your API settings

## Development

### Creating a Release

There are two ways to create a new release:

#### Method 1: Using Git Tags (Recommended)

1. Update the version number in `printful.php`
2. Commit your changes
3. Create and push a git tag:
   ```bash
   git tag v2.1
   git push origin v2.1
   ```
4. GitHub Actions will automatically create a release with the module zip file

#### Method 2: Manual Trigger (via GitHub Actions)

1. Update the version number in `printful.php` and commit your changes
2. Go to the [Actions tab](https://github.com/AidanTheBandit/prestashop-module/actions/workflows/release.yml) in GitHub
3. Click on "Create Release" workflow
4. Click "Run workflow" button
5. Enter the version number (e.g., `2.1.0`) without the `v` prefix
6. Click "Run workflow"
7. The workflow will automatically create a tag and release with the module zip file

Both methods will:
- Create a properly formatted zip file containing the module
- Generate a SHA256 checksum for verification
- Create a GitHub release with installation instructions and compatibility information

### File Structure

```
printful/
├── controllers/       # Admin controllers
├── src/              # Service classes and API client
├── translations/     # Language files
├── vendor/           # Composer dependencies
├── views/            # Templates, CSS, and JS
├── printful.php      # Main module file
├── composer.json     # Composer configuration
└── LICENSE.txt       # License file
```

## Support

For support, please visit [Printful Help Center](https://www.printful.com/help) or contact Printful support.

## License

See [LICENSE.txt](LICENSE.txt) for license information.

## Changelog

### Version 2.1
- Added PrestaShop 9.x compatibility
- Implemented service registry for better performance
- Added fallback service instantiation for PrestaShop 9+

### Version 2.0
- Initial public release
- PrestaShop 1.7.6 - 8.1.3 support
