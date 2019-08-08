EP4 Must-Use Plugins Autoloader
===============================

__Contributors:__      DaveLavoie, EP4
__Donate link:__
__Tags:__              must-use, must-use plugins, mu-plugins, MU, plugins, loader, autoloader, directory, subdirectory, WP Captain, EP4
__Requires at least:__ 4.7
__Tested up to:__      5.2.2
__Stable tag:__        1.0.2
__License:__           GPLv3 or later
__License URI:__       http://www.gnu.org/licenses/gpl-3.0.html

This must-use plugin loads all plugins inside subdirectories of the /mu-plugins directory.

Description
-----------

By default, WordPress only looks for PHP files right inside the mu-plugins directory (WPMU_PLUGIN_DIR), and not for files in subdirectories. This must-use plugin loads all plugins inside subdirectories of the /mu-plugins directory. Simply
drop this file at the root of the mu-plugins folder, and it'll take care of the rest! If you need to exclude specific plugins from being loaded automatically, see __Installation__.

Installation
------------

### From WordPress.org or GitHub ###

1. Download 'EP4 Must-Use Plugins Autoloader' from the WordPress plugin repository or from GitHub.
2. Upload the 'ep4-mu-loader' file to the '/wp-content/mu-plugins/' directory (or to the custom directory represented by the WMPU_PLUGIN_DIR constant, if it applies), using your favorite method (FTP, SFTP, SCP, etc...).
3. That's it! It should automatically work!

To exclude specific folders or plugins from being loaded by the autoloader, see the FAQ.

### Directly From WordPress Admin Dashboard ###

This is not recommended since the autoloader should really be moved to the ``mu-plugins`` directory and installing this plugin from the WordPress Admin Dashboard doesn't allow that. However this plugin will also work if used as a normal plugin, as long as it's enabled! If you install the plugin from the admin dashboard, you should move it to the mu-plugins directory afterwards.

Frequently Asked Questions
--------------------------

### Can I exclude specific plugins and directories from being loaded by the autoloader? ###

Yes! To exclude specific folders or plugins from being loaded by the autoloader, you can do one of the following:

1. **If using PHP7 or earlier**, define the `WPMU_PLUGIN_AUTOLOAD_EXCLUDE` constant in wp-config.php. For example, for websites hosted on WPEngine servers,  since they add their own set of plugins to the mu-plugins directory and use their own autoloader to load them, one must add the following in the wp-config.php file:

```php
define( 'WPMU_PLUGIN_AUTOLOAD_EXCLUDE', array( 'wpengine-common', 'force-strong-passwords' ) );
```

2. **If you're running a version of PHP older than PHP7**, arrays can't be used in constant, so you must define the constant with a string. The following will work, regardless of the PHP version used:

```php
 define( 'WPMU_PLUGIN_AUTOLOAD_EXCLUDE', 'wpengine-common, force-strong-passwords' );
```

3. Even though **using the constant is the preferred way**, you can also exclude folders by changing a line in the plugin itself. Look for the following line of code just before the while loop found at the end of the file, near line 461:

```php
EP4_MU_Loader::this()->exclude_mu_plugins();
```

And replace it with the following (change the values in the array so it fits your needs):

```php
EP4_MU_Loader::this()->exclude_mu_plugins( array( 'wpengine-common', 'force-strong-passwords' ) );
```

So at the end of the PHP file, it should look like this:

```php
if ( EP4_MU_Loader::this()->have_mu_plugins() ) {
	// Replace the values in the array with yours.
	EP4_MU_Loader::this()->exclude_mu_plugins( array( 'wpengine-common', 'force-strong-passwords' ) );
	while ( EP4_MU_Loader::this()->have_mu_plugins() ) {
		include_once EP4_MU_Loader::this()->the_mu_plugin();
	}
}
```

Remember that you can replace the values in the array by those you want to exclude. If the array contains PHP files, they will be excluded. Otherwise, it'll loop through all MU plugins available for autoloading, and look for the directory name in their path, or the presence of a keyword in the path. For example, ``array( 'wp', 'seo' )`` would exclude any plugin that includes the words 'wp' or 'seo' in their directory name.

### How I can know if the autoloader is working? ###

You can tell it's working by logging in to your WP Admin and taking a look at the list of Must-Use plugins that are shown (usually displayed at ``/wp-admin/plugins.php?plugin_status=mustuse``). All plugins loaded by the autoloader will be displayed with a folder icon just before their name.


Screenshots
-----------

None.

Changelog
---------
### 1.0.2 - 2019-08-08 ###

* Fixing an error regarding file permissions when cloning the repo from Github.

### 1.0.1 - 2019-07-19 ###

* Added support for using the ``WPMU_PLUGIN_AUTOLOAD_EXCLUDE`` constant.
* Fixed an issue that was preventing plugins from being excluded in specific cases. Previously, using ``array( 'wp', 'seo' )`` would have only worked if the plugin path started with those keywords, but not if the string was found in the plugin path.
* Added the composer.json file so the plugin can be included using composer.

### 1.0 - 2019-01-22 ###

* First Release.

