<?php

$database = \Drupal::database();
$query = $database->select('router', 'r');
$query->fields('r', ['path', 'route']);
$all = $query->execute()->fetchAll();

$routes = [];

$allowed_permissions = [
    'access sitemap',
    'access content',
    'view media',
    'download media',
    'allowed',
];

foreach ($all as $item) {
    if (str_contains($item->path, 'node')) {
        $route = (array)unserialize($item->route);

//        print_r($route);
    }
}

foreach ($all as $item) {
    $route = (array)unserialize($item->route);

    $reqidx = -1;
    $keys = array_keys($route);
    foreach ($keys as $key) {
        if (str_contains($key, 'requirements')) {
            $reqidx = $key;
        }
    }

    if ($reqidx !== -1) {
        if (isset($route[$reqidx]['_access']) && ($route[$reqidx]['_access'] == 'TRUE' || $route[$reqidx]['_access'] == TRUE)) {
            // Permission is allowed.
            $path = preg_replace('/(\{.*\})/', '*' ,$item->path);
            @ $routes[$path] = $routes[$path] . '+allowed';
        }
        elseif (isset($route[$reqidx]['_permission'])) {
            if (in_array($route[$reqidx]['_permission'], $allowed_permissions, FALSE)) {
                // Permission is allowed.
                $path = preg_replace('/(\{.*\})/', '*' ,$item->path);
                @ $routes[$path] = $routes[$path] . '+' . $route[$reqidx]['_permission'];
            }
            else {

                $path = preg_replace('/(\{.*\})/', '*' ,$item->path);
                @ $routes[$path] = $routes[$path] . '+' . $route[$reqidx]['_permission'];
            }
        }
        elseif (isset($route[$reqidx]['_entity_access'])) {
            $path = preg_replace('/(\{.*\})/', '*', $item->path);
            @ $routes[$path] = $routes[$path] . '+' . '_entity_access(' . $route[$reqidx]['_entity_access'] . ')';
        }
        else {
            $path = preg_replace('/(\{.*\})/', '*', $item->path);
            @ $routes[$path] = $routes[$path] . '+' . implode('+', array_keys($route[$reqidx]));
        }
    }
    else {
        $path = preg_replace('/(\{.*\})/', '*', $item->path);
        @ $routes[$path] = $routes[$path] . '+' . 'HAS NO REQUIREMENTS';

        print_r($route);
    }
}
ksort($routes);
foreach ($routes as $path => $permission) {
    $permission = trim($permission, '+');

    $permissions = explode('+', $permission);

    foreach ($permissions as $k) {
        if (in_array($k, $allowed_permissions, FALSE)) {
            // allowed;
            continue 2;
        }
    }

    echo $path . ', ' . $permission . PHP_EOL;
}