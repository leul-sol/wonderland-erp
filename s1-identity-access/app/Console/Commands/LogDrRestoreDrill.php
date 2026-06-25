<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;

class LogDrRestoreDrill extends Command
{
    protected $signature = 'audit:dr-restore-drill
                            {archive : Backup archive file name}
                            {tables : Number of tables restored in the drill database}';

    protected $description = 'Record a successful DR restore drill in the S1 audit log';

    public function handle(): int
    {
        $superAdminId = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->value('id');

        AuditService::log(
            'dr.restore_drill',
            $superAdminId,
            '127.0.0.1',
            'restore-drill',
            [
                'archive' => $this->argument('archive'),
                'tables_restored' => (int) $this->argument('tables'),
                'result' => 'pass',
            ],
        );

        $this->info('Logged dr.restore_drill audit entry.');

        return self::SUCCESS;
    }
}
