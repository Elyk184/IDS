<?php
// includes/PHPMailer.php
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/PHPMailer/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // not PHPMailer classes
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . $relative_class . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
