<?php
// PHPStorm Meta file, to provide autocomplete information for meta box

namespace PHPSTORM_META {
    // Register WordPress core functions and classes
    override(
        \add_action(),
        map([
            // WordPress core actions
            'init' => 'void',
            'admin_init' => 'void',
            'wp_enqueue_scripts' => 'void',
            'admin_enqueue_scripts' => 'void',
            'wp_loaded' => 'void',
            'plugins_loaded' => 'void',
            'after_setup_theme' => 'void',
            // Add more actions as needed
        ])
    );

    override(
        \add_filter(),
        map([
            // WordPress core filters
            'the_content' => 'string',
            'the_title' => 'string',
            'wp_title' => 'string',
            // Add more filters as needed
        ])
    );

    // WordPress core classes
    override(
        \WP_Error::get_error_message(),
        map([
            '' => 'string',
        ])
    );
}
