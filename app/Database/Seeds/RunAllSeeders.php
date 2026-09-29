<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

class RunAllSeeders extends Seeder
{
    private string $seedTable = 'seeders';

    /**
     * These seeders always run first, in this exact order.
     */
    private array $prioritySeeders = [
        'App\Database\Seeds\InitialAuthSeeder',
        'App\Database\Seeds\PermissionSeederOrg',
    ];

    /**
     * Seeders that should never be auto-run by this master seeder.
     */
    private array $excludedSeeders = [
        self::class,
        'App\Database\Seeds\RunAllSeeders',
    ];

    public function run()
    {
        $this->ensureSeederTable();

        foreach ($this->discoverSeeders() as $seederClass) {
            if ($this->hasSeedRun($seederClass)) {
                continue;
            }

            $this->runSeederOnce($seederClass);
        }
    }

    private function ensureSeederTable(): void
    {
        if ($this->db->tableExists($this->seedTable)) {
            return;
        }

        $forge = \Config\Database::forge();

        $forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'seeder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'batch' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
            ],
            'executed_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $forge->addKey('id', true);
        $forge->addUniqueKey('seeder', 'seeders_seeder_unique');

        $forge->createTable($this->seedTable, true);
    }

    private function discoverSeeders(): array
    {
        $seedPath = APPPATH . 'Database/Seeds';

        if (! is_dir($seedPath)) {
            return [];
        }

        $discoveredSeeders = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($seedPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();

            $relativePath = str_replace($seedPath . DIRECTORY_SEPARATOR, '', $filePath);
            $relativeClass = str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            $seederClass = 'App\\Database\\Seeds\\' . $relativeClass;

            if (in_array($seederClass, $this->excludedSeeders, true)) {
                continue;
            }

            require_once $filePath;

            if (! class_exists($seederClass)) {
                continue;
            }

            if (! is_subclass_of($seederClass, Seeder::class)) {
                continue;
            }

            $reflection = new ReflectionClass($seederClass);

            if ($reflection->isAbstract()) {
                continue;
            }

            $discoveredSeeders[] = $seederClass;
        }

        $discoveredSeeders = array_values(array_unique($discoveredSeeders));

        /*
         * Keep only priority seeders that actually exist in the Seeds folder.
         */
        $prioritySeeders = array_values(array_filter($this->prioritySeeders, static function ($seederClass) use ($discoveredSeeders) {
            return in_array($seederClass, $discoveredSeeders, true);
        }));

        /*
         * Everything else can run after the priority seeders.
         */
        $remainingSeeders = array_values(array_filter($discoveredSeeders, function ($seederClass) use ($prioritySeeders) {
            return ! in_array($seederClass, $prioritySeeders, true);
        }));

        sort($remainingSeeders);

        return array_merge($prioritySeeders, $remainingSeeders);
    }

    private function hasSeedRun(string $seederClass): bool
    {
        return (bool) $this->db->table($this->seedTable)
            ->where('seeder', $seederClass)
            ->get()
            ->getRowArray();
    }

    private function runSeederOnce(string $seederClass): void
    {
        $this->db->transBegin();

        try {
            $this->call($seederClass);

            $this->db->table($this->seedTable)->insert([
                'seeder'      => $seederClass,
                'batch'       => $this->nextBatchNumber(),
                'executed_at' => date('Y-m-d H:i:s'),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->transStatus() === false) {
                throw new DatabaseException("Transaction failed while running {$seederClass}.");
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw new DatabaseException(
                "Seeder failed: {$seederClass}. Error: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function nextBatchNumber(): int
    {
        $row = $this->db->table($this->seedTable)
            ->selectMax('batch', 'max_batch')
            ->get()
            ->getRowArray();

        return ((int) ($row['max_batch'] ?? 0)) + 1;
    }
}