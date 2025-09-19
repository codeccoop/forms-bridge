<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;

if (!defined('ABSPATH')) {
    exit();
}

class OpenAPI
{
    public const methods = ['get', 'post', 'put', 'patch', 'delete'];

    public $data;
    public $version;

    public static function from($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $content = file_get_contents($path);

        try {
            $data = json_decode($content, true, JSON_THROW_ON_ERROR);
        } catch (Error) {
            return;
        }

        return new OpenAPI($data);
    }

    public function __construct($data)
    {
        if (!$data['openapi']) {
            throw new Exception('Invalid OpenAPI data');
        }

        $this->data = $data;
        $this->version = $data['openapi'];
    }

    public function path($path)
    {
        $path = self::parse_path($path);

        foreach ($this->data['paths'] as $name => $spec) {
            if (preg_match_all('/{([^}]+)}/', $name, $matches)) {
                $regexp = $name;

                foreach ($matches[0] as $match) {
                    $regexp = str_replace($match, '[^\/]+', $regexp);
                }

                if (preg_match('$' . $regexp . '$', $path)) {
                    return $spec;
                }
            } elseif ($path === $name) {
                return $spec;
            }
        }
    }

    public function encoding($path, $method)
    {
        $path = $this->path($path);
        $content = $path[$method]['requestBody']['content'] ?? null;

        if (!$content) {
            return;
        }

        return array_keys($content);
    }

    public function params($path, $method = null)
    {
        $path = self::parse_path($path);

        $path_spec = $this->path($path);
        if (!$path_spec) {
            return;
        }

        $parameters = $path_spec['parameters'] ?? null;
        if (!$parameters) {
            if (!$method) {
                return;
            }

            $parameters = [];
        }

        $method_spec = $path_spec[$method] ?? null;
        if ($method && !$method_spec) {
            return;
        }

        $parameters = array_merge(
            $parameters,
            $method_spec['parameters'] ?? []
        );

        if ($body = $method_spec['requestBody'] ?? null) {
            $parameters = array_merge($parameters, self::body_to_params($body));
        }

        return $parameters;
    }

    private static function body_to_params($spec)
    {
        $parameters = [];
        foreach ($spec['content'] as $encoding => $spec) {
            foreach ($spec['shema']['properties'] as $name => $defn) {
                $parameters[] = array_merge(
                    [
                        'name' => $name,
                        'encoding' => $encoding,
                    ],
                    $defn
                );
            }
        }

        return $parameters;
    }

    private static function parse_path($path)
    {
        $url = parse_url($path);
        if (empty($url['path'])) {
            return '/';
        }

        $path = strpos($path, '/') !== 0 ? '/' . $path : $path;
        return preg_replace('/\/+$/', '', $path);
    }
}
