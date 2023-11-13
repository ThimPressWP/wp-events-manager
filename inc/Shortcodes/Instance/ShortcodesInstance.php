<?php

namespace WPEMS\Shortcodes\Instance;

// Import the shortcode classes.
use WPEMS\Shortcodes\AccountShortcode;
use WPEMS\Shortcodes\CountdownShortcode;
use WPEMS\Shortcodes\ForgotPasswordShortcode;
use WPEMS\Shortcodes\ListEventShortcode;
use WPEMS\Shortcodes\LoginShortcode;
use WPEMS\Shortcodes\RegisterShortcode;
use WPEMS\Shortcodes\ResetPasswordShortcode;

// Instantiate each shortcode class.
ListEventShortcode::instance();
RegisterShortcode::instance();
LoginShortcode::instance();
ForgotPasswordShortcode::instance();
ResetPasswordShortcode::instance();
AccountShortcode::instance();
CountdownShortcode::instance();