<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Редирект на /api/doc, если запрошен корневой путь
if ($_SERVER['REQUEST_URI'] === '/') {
    header('Location: /swagger');
    exit();
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
