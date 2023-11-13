<?php

namespace WPEMS\Shortcodes\Instances;

// Import the shortcode classes.
use WPEMS\Shortcodes\AccountShortcode;
use WPEMS\Shortcodes\CountdownShortcode;
use WPEMS\Shortcodes\ForgotPasswordShortcode;
use WPEMS\Shortcodes\ListEventShortcode;
use WPEMS\Shortcodes\LoginShortcode;
use WPEMS\Shortcodes\RegisterShortcode;
use WPEMS\Shortcodes\ResetPasswordShortcode;

use WPEMS\Shortcodes\ListShortcode;
use WPEMS\Shortcodes\CalendarsShortcode;
use WPEMS\Shortcodes\SyncEventShortcode;

// Instantiate each shortcode class.
ListEventShortcode::instance();
RegisterShortcode::instance();
LoginShortcode::instance();
ForgotPasswordShortcode::instance();
ResetPasswordShortcode::instance();
AccountShortcode::instance();
CountdownShortcode::instance();

ListShortcode::instance();
CalendarsShortcode::instance();
SyncEventShortcode::instance();
