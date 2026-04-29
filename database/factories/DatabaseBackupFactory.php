<?php

namespace Database\Factories;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
{
    protected $model = DatabaseBackup::class;

    public function definition(): array
    {
        $fileName = 'database-backup-test-' . fake()->unique()->numerify('####') . '.sql.gz';

        return [
            'file_name' => $fileName,
            'original_file_name' => $fileName,
            'file_path' => 'database-backups/' . $fileName,
            'file_size' => fake()->numberBetween(1024, 1024 * 1024),
            'source_type' => DatabaseBackup::SOURCE_GENERATED,
            'created_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
