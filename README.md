# GitHub Update Checker

Serve your WordPress plugin's updates straight from **GitHub Releases** — no wordpress.org submission, no custom update server. WordPress shows the normal "Update now" flow in Dashboard → Updates and on the Plugins screen.

## How it works

`src/UpdateChecker.php` hooks `pre_set_site_transient_update_plugins`, asks the GitHub API for the latest release of your repo, and compares the release tag against your plugin's `Version` header. When the release is newer, it registers the update with the release zipball as the download package. The API response is cached for 12 hours so admin pages stay fast.

## Use it in your own plugin

```php
require_once __DIR__ . '/src/UpdateChecker.php';

new Azanumer\UpdateChecker(
    __FILE__,
    'YourOrg/your-plugin-repo'   // GitHub "owner/repo"
);
```

Then publish a release on GitHub (tag `v1.2.0` or `1.2.0` — both work) and your users get the update prompt.

- **Private repos:** pass a fine-grained personal access token with "Contents: read" as the third argument.
- **Force a fresh check:** `$checker->clear_cache()`.

## This plugin

This plugin checks **itself** for updates from `Azanumer/wp-plugin-update-checker` — install it and it demonstrates the flow live. No settings page; nothing to configure.

## License

MIT
