<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public function record(string $action, string $type, ?int $id, ?array $before = null, ?array $after = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(), 'action' => $action, 'record_type' => $type, 'record_id' => $id,
            'before_values' => $before ? json_encode($before) : null, 'after_values' => $after ? json_encode($after) : null,
            'ip_address' => request()->ip(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
