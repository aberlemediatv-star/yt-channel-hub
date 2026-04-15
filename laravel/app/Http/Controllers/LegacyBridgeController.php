<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Führt Legacy-Skripte unter /public aus (gleiche Logik wie früher direkter PHP-FPM-Zugriff).
 * SCRIPT_NAME/DOCUMENT_ROOT werden gesetzt, damit Admin/Staff-Bootstrap korrekt arbeiten.
 */
final class LegacyBridgeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $public = public_path();
        $rel = $this->resolveLegacyScript($request, $public);

        if (str_contains($rel, '..') || str_contains($rel, "\0")) {
            abort(404);
        }

        $full = $public.'/'.$rel;
        if (! is_file($full) || ! str_ends_with($rel, '.php')) {
            abort(404);
        }

        $orig = [
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
            'PHP_SELF' => $_SERVER['PHP_SELF'] ?? null,
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? null,
        ];

        $_SERVER['SCRIPT_NAME'] = '/'.$rel;
        $_SERVER['PHP_SELF'] = '/'.$rel;
        $_SERVER['DOCUMENT_ROOT'] = $public;

        ob_start();
        try {
            include $full;
        } catch (\Throwable $e) {
            ob_end_clean();
            foreach ($orig as $k => $v) {
                if ($v !== null) {
                    $_SERVER[$k] = $v;
                }
            }
            throw $e;
        }

        $content = ob_get_clean();

        foreach ($orig as $k => $v) {
            if ($v !== null) {
                $_SERVER[$k] = $v;
            }
        }

        return response($content);
    }

    private function resolveLegacyScript(Request $request, string $public): string
    {
        $uriPath = parse_url($request->getRequestUri(), PHP_URL_PATH);
        $uriPath = is_string($uriPath) ? trim($uriPath, '/') : '';

        if ($uriPath === '' || $uriPath === 'index.php') {
            return 'index.php';
        }

        $candidates = [
            $uriPath,
            $uriPath.'.php',
            $uriPath.'/index.php',
        ];

        foreach ($candidates as $c) {
            if (str_contains($c, '..')) {
                continue;
            }
            if (str_contains($c, 'partials')) {
                continue;
            }
            $full = $public.'/'.$c;
            if (is_file($full) && str_ends_with($c, '.php')) {
                return $c;
            }
        }

        abort(404);
    }
}
