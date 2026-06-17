<?php

namespace App\Services;

class AuditService
{
    public function log(string $action, ?string $entity = null, $entityId = null, array $meta = []): void
    {
        $db = db_connect();
        $req = service('request');

        $actorId = session('uid') ? (int) session('uid') : null;
        $actorUsername = session('username') ?: null;

        $db->table('audit_logs')->insert([
            'actor_user_id'   => $actorId,
            'actor_username'  => $actorUsername,
            'ip_address'      => $req->getIPAddress(),
            'user_agent'      => substr((string) $req->getUserAgent(), 0, 255),

            'action'          => $action,
            'entity'          => $entity,
            'entity_id'       => $entityId !== null ? (string) $entityId : null,
            'meta'            => $meta ? json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}