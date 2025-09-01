<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Timecode;

class MigrateTimecodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timecodes:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing timecodes from JSON format to new timecodes table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting timecode migration...');

        $projects = Project::whereNotNull('timecodes')->get();
        $totalMigrated = 0;

        foreach ($projects as $project) {
            $this->info("Processing project: {$project->name} (ID: {$project->id})");

            // Parse JSON timecodes
            $timecodes = $project->timecodes;
            if (is_string($timecodes)) {
                $timecodes = json_decode($timecodes, true);
            }

            if (!is_array($timecodes)) {
                $this->warn("  No valid timecodes found for project {$project->id}");
                continue;
            }

            $migratedCount = 0;
            foreach ($timecodes as $tc) {
                if (!isset($tc['start'], $tc['end'], $tc['text'])) {
                    $this->warn("  Skipping invalid timecode: " . json_encode($tc));
                    continue;
                }

                // Create new timecode record (line 1 by default)
                Timecode::create([
                    'project_id' => $project->id,
                    'line_number' => 1,
                    'start' => $tc['start'],
                    'end' => $tc['end'],
                    'text' => $tc['text']
                ]);

                $migratedCount++;
            }

            $this->info("  Migrated {$migratedCount} timecodes");
            $totalMigrated += $migratedCount;

            // Set default rythmo_lines_count if not set
            if (!$project->rythmo_lines_count) {
                $project->rythmo_lines_count = 1;
                $project->save();
                $this->info("  Set rythmo_lines_count to 1");
            }
        }

        $this->info("Migration completed! Total timecodes migrated: {$totalMigrated}");
        $this->info("You can now safely remove the 'timecodes' JSON column if desired.");

        return 0;
    }
}
