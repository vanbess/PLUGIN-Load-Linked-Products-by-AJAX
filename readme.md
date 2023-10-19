# SBWC Load Linked Products via AJAX

Fetches products linked by variatons (set up via associated plugin *Products Linked by Variations for WooCommerce*) and allows them to load on same page instead of redirecting.

*Products Linked by Variations for WooCommerce* needs to be installed for this plugin to work.

WP Cache and transients are used to store linked product HTML and replace the entire document on click instead of redirecting.

Server side caching must be enabled for this plugin to work properly. Similary, any flags disabling wp_cache must be disabled in your wp-config.php file, unless you're using Redis or Memcached.