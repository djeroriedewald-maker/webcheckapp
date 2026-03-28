<?php

namespace App\Services\Scanners;

class ApiSecurityScanner
{
    private const TIMEOUT = 5;

    /** Endpoints to probe and the signatures that indicate they are API/docs */
    private const ENDPOINTS = [
        '/api/docs'        => 'API documentation',
        '/swagger-ui.html' => 'Swagger UI',
        '/swagger-ui/'     => 'Swagger UI',
        '/api/swagger'     => 'Swagger UI',
        '/swagger.json'    => 'Swagger spec',
        '/swagger.yaml'    => 'Swagger spec',
        '/openapi.json'    => 'OpenAPI spec',
        '/openapi.yaml'    => 'OpenAPI spec',
        '/graphiql'        => 'GraphiQL IDE',
        '/actuator'        => 'Spring Actuator',
        '/actuator/health' => 'Spring Actuator health',
        '/_cat/indices'    => 'Elasticsearch API',
    ];

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        // 1. Exposed API/docs endpoints
        $maxScore += 50;
        $exposed = [];
        foreach (self::ENDPOINTS as $path => $service) {
            $result = $this->safe(fn() => $this->probeEndpoint($host, $path), null);
            if ($result !== null && $result['exposed']) {
                $exposed[] = ['path' => $path, 'service' => $service, 'auth' => $result['auth_required']];
            }
        }

        if (empty($exposed)) {
            $score += 50;
            $checks[] = [
                'id'          => 'api_docs_exposed',
                'label'       => 'API/docs endpoints',
                'status'      => 'pass',
                'description' => 'No publicly accessible API documentation or admin endpoints were found.',
            ];
        } else {
            foreach ($exposed as $item) {
                $checks[] = [
                    'id'             => 'api_' . md5($item['path']),
                    'label'          => $item['service'] . ' exposed',
                    'status'         => 'warn',
                    'description'    => "Endpoint accessible at {$item['path']}"
                        . ($item['auth'] ? ' (authentication required).' : ' (no authentication detected).'),
                    'recommendation' => $item['auth']
                        ? "The {$item['path']} endpoint is accessible but requires authentication. Review whether it should be publicly reachable."
                        : "Restrict access to {$item['path']} via IP allowlist, authentication, or disable it in production.",
                ];
            }
        }

        // 2. GraphQL endpoint + introspection
        $maxScore += 25;
        $gqlResult = $this->safe(fn() => $this->checkGraphql($host), null);
        if ($gqlResult === 'introspection_enabled') {
            $checks[] = [
                'id'             => 'api_graphql_introspection',
                'label'          => 'GraphQL introspection enabled',
                'status'         => 'fail',
                'description'    => 'GraphQL introspection is enabled — attackers can query your full schema.',
                'recommendation' => 'Disable GraphQL introspection in production. In Apollo Server: introspection: false in the server options.',
            ];
        } elseif ($gqlResult === 'introspection_disabled') {
            $score += 25;
            $checks[] = [
                'id'          => 'api_graphql_introspection',
                'label'       => 'GraphQL introspection disabled',
                'status'      => 'pass',
                'description' => 'A GraphQL endpoint was found but introspection is disabled — correct for production.',
            ];
        } else {
            $score += 25;
            $checks[] = [
                'id'          => 'api_graphql_introspection',
                'label'       => 'GraphQL introspection',
                'status'      => 'pass',
                'description' => 'No GraphQL endpoint detected.',
            ];
        }

        // 3. WordPress REST API user enumeration
        $maxScore += 25;
        $wpUsers = $this->safe(fn() => $this->checkWpUserEnum($host), false);
        if ($wpUsers) {
            $checks[] = [
                'id'             => 'api_wp_users',
                'label'          => 'WordPress user enumeration',
                'status'         => 'warn',
                'description'    => 'WordPress REST API exposes usernames at /wp-json/wp/v2/users.',
                'recommendation' => "Restrict the endpoint in functions.php:\nadd_filter('rest_endpoints', function(\$e) {\n    unset(\$e['/wp/v2/users']);\n    return \$e;\n});",
            ];
        } else {
            $score += 25;
            $checks[] = [
                'id'          => 'api_wp_users',
                'label'       => 'WordPress user enumeration',
                'status'      => 'pass',
                'description' => 'WordPress user enumeration via REST API is not possible (or WordPress is not used).',
            ];
        }

        return [
            'category' => 'API Security',
            'icon'     => 'code-bracket',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function probeEndpoint(string $host, string $path): ?array
    {
        $ch = curl_init("https://{$host}{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_RANGE          => '0-4095',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 0 || $code === 404 || $code === 410 || $code >= 500) {
            return null;
        }

        $authRequired = in_array($code, [401, 403]);
        $isApiContent = $body && preg_match('/swagger|openapi|graphql|"paths"\s*:|"version"\s*:|__schema/i', $body);
        $isJson       = $body && str_starts_with(ltrim($body), '{') || str_starts_with(ltrim((string) $body), '[');

        if (! $authRequired && ! $isApiContent && ! $isJson) {
            return null;
        }

        return [
            'exposed'      => true,
            'auth_required' => $authRequired,
            'code'         => $code,
        ];
    }

    private function checkGraphql(string $host): string
    {
        $payload = json_encode(['query' => '{__schema{types{name}}}']);
        $ch      = curl_init("https://{$host}/graphql");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 0 || $code === 404) {
            return 'not_found';
        }

        $data = $body ? @json_decode($body, true) : null;
        if (isset($data['data']['__schema']['types'])) {
            return 'introspection_enabled';
        }

        if ($code >= 200 && $code < 400) {
            return 'introspection_disabled';
        }

        return 'not_found';
    }

    private function checkWpUserEnum(string $host): bool
    {
        $ch = curl_init("https://{$host}/wp-json/wp/v2/users");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RANGE          => '0-4095',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $body) {
            return false;
        }

        $data = @json_decode($body, true);
        return is_array($data) && ! empty($data) && isset($data[0]['name']);
    }

    private function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }
}
