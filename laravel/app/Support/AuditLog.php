<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;
use YtHub\AdminAuth;
use YtHub\StaffAuth;

/**
 * Persistent audit trail for privileged actions (admin/staff/system).
 * Never throws — logging must not break the original flow.
 */
final class AuditLog
{
    public static function adminAction(string $action, ?string $targetType = null, int|string|null $targetId = null, array $context = []): void
    {
        self::write('admin', self::adminActorId(), self::adminActorLabel(), $action, $targetType, $targetId, $context);
    }

    public static function staffAction(string $action, ?string $targetType = null, int|string|null $targetId = null, array $context = []): void
    {
        self::write('staff', self::staffActorId(), self::staffActorLabel(), $action, $targetType, $targetId, $context);
    }

    public static function systemAction(string $action, ?string $targetType = null, int|string|null $targetId = null, array $context = []): void
    {
        self::write('system', null, null, $action, $targetType, $targetId, $context);
    }

    private static function write(
        string $actorType,
        ?int $actorId,
        ?string $actorLabel,
        string $action,
        ?string $targetType,
        int|string|null $targetId,
        array $context,
    ): void {
        try {
            DB::table('admin_audit_log')->insert([
                'actor_type' => mb_substr($actorType, 0, 16),
                'actor_id' => $actorId,
                'actor_label' => $actorLabel !== null ? mb_substr($actorLabel, 0, 128) : null,
                'action' => mb_substr($action, 0, 64),
                'target_type' => $targetType !== null ? mb_substr($targetType, 0, 32) : null,
                'target_id' => $targetId !== null ? (int) $targetId : null,
                'ip' => self::clientIp(),
                'context' => $context === [] ? null : json_encode(self::redactContext($context), JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('audit_log_write_failed', ['error' => $e->getMessage(), 'action' => $action]);
        }
    }

    private static function clientIp(): ?string
    {
        try {
            return Request::ip();
        } catch (Throwable) {
            return $_SERVER['REMOTE_ADDR'] ?? null;
        }
    }

    private static function adminActorId(): ?int
    {
        // Admin auth in this app is a single-password scheme; no numeric user ID.
        return null;
    }

    private static function adminActorLabel(): ?string
    {
        return AdminAuth::isLoggedIn() ? 'admin' : null;
    }

    private static function staffActorId(): ?int
    {
        $id = StaffAuth::staffId();

        return $id > 0 ? $id : null;
    }

    private static function staffActorLabel(): ?string
    {
        return self::staffActorId() !== null ? ('staff#' . self::staffActorId()) : null;
    }

    /**
     * Strip obvious secrets out of a context array before persisting.
     *
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    private static function redactContext(array $ctx): array
    {
        $blocked = ['password', 'password_hash', 'token', 'access_token', 'refresh_token', 'secret', 'api_key'];
        foreach ($ctx as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), $blocked, true)) {
                $ctx[$k] = '***';
            } elseif (is_array($v)) {
                $ctx[$k] = self::redactContext($v);
            }
        }

        return $ctx;
    }
}
