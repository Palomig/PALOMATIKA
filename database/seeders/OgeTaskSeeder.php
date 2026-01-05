<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OgeTaskSeeder extends Seeder
{
    /**
     * Import all 5660 OGE tasks from parsed_full.json
     */
    public function run(): void
    {
        $jsonPath = database_path('data/parsed_full.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found at: $jsonPath");
            $this->command->info("Please copy parsed_full.json to database/data/");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (!$data || !isset($data['tasks'])) {
            $this->command->error("Invalid JSON structure");
            return;
        }

        $this->command->info("Starting OGE tasks import...");
        $this->command->info("Topics in JSON: " . count($data['topics']));
        $this->command->info("Subtopics in JSON: " . count($data['subtopics']));
        $this->command->info("Tasks in JSON: " . count($data['tasks']));

        // Build subtopic lookup: subtopic_id => subtopic_name
        $subtopicLookup = [];
        foreach ($data['subtopics'] as $subtopic) {
            $subtopicLookup[$subtopic['id']] = $subtopic['name'];
        }

        // Build topic oge_number lookup from JSON: json_topic_id => oge_number
        $jsonTopicOgeNumbers = [];
        foreach ($data['topics'] as $topic) {
            $jsonTopicOgeNumbers[$topic['id']] = $topic['oge_number'];
        }

        // Get database topics by oge_number
        $dbTopics = Topic::all()->keyBy('oge_number');

        if ($dbTopics->isEmpty()) {
            $this->command->error("No topics found in database. Run TopicSeeder first.");
            return;
        }

        $this->command->info("Database topics: " . $dbTopics->count());

        // Map JSON topic_id to database topic_id via oge_number
        $topicIdMapping = [];
        foreach ($data['topics'] as $jsonTopic) {
            $ogeNumber = $jsonTopic['oge_number'];
            if ($dbTopics->has($ogeNumber)) {
                $topicIdMapping[$jsonTopic['id']] = $dbTopics->get($ogeNumber)->id;
            }
        }

        $this->command->info("Topic mappings created: " . count($topicIdMapping));

        // Clear existing tasks (optional - comment out if you want to keep old tasks)
        // Task::truncate();

        $tasksToInsert = [];
        $imported = 0;
        $skipped = 0;
        $batchSize = 500;

        $bar = $this->command->getOutput()->createProgressBar(count($data['tasks']));
        $bar->start();

        foreach ($data['tasks'] as $taskData) {
            $bar->advance();

            // Get database topic_id from JSON topic_id
            $jsonTopicId = $taskData['topic_id'] ?? null;
            if (!$jsonTopicId || !isset($topicIdMapping[$jsonTopicId])) {
                $skipped++;
                continue;
            }

            $dbTopicId = $topicIdMapping[$jsonTopicId];

            // Get subtopic name
            $subtopicName = null;
            if (isset($taskData['subtopic_id']) && isset($subtopicLookup[$taskData['subtopic_id']])) {
                $subtopicName = $subtopicLookup[$taskData['subtopic_id']];
            }

            // Get first image path if available
            $imagePath = null;
            if (!empty($taskData['images']) && is_array($taskData['images'])) {
                $firstImage = $taskData['images'][0];
                $imagePath = 'images/tasks/' . $firstImage['filename'];
            }

            // Build text - combine block, context, and text if available
            $textParts = [];
            if (!empty($taskData['block'])) {
                $textParts[] = $taskData['block'];
            }
            if (!empty($taskData['context'])) {
                $textParts[] = $taskData['context'];
            }
            if (!empty($taskData['text'])) {
                $textParts[] = $taskData['text'];
            }
            $fullText = implode("\n\n", $textParts);

            // Skip if text is too short (likely parsing artifact)
            if (strlen($fullText) < 3) {
                $skipped++;
                continue;
            }

            $tasksToInsert[] = [
                'topic_id' => $dbTopicId,
                'external_id' => 'oge_' . $taskData['id'],
                'text' => $fullText,
                'text_html' => null,
                'image_path' => $imagePath,
                'subtopic' => $subtopicName,
                'difficulty' => $taskData['difficulty'] ?? 1,
                'correct_answer' => $taskData['correct_answer'] ?? null,
                'answer_type' => $taskData['answer_type'] ?? 'text',
                'puzzle_template_id' => null,
                'times_shown' => 0,
                'times_correct' => 0,
                'avg_time_seconds' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $imported++;

            // Insert in batches
            if (count($tasksToInsert) >= $batchSize) {
                DB::table('tasks')->insert($tasksToInsert);
                $tasksToInsert = [];
            }
        }

        // Insert remaining tasks
        if (!empty($tasksToInsert)) {
            DB::table('tasks')->insert($tasksToInsert);
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->command->info("Import completed!");
        $this->command->info("Imported: $imported tasks");
        $this->command->info("Skipped: $skipped tasks (missing topic or short text)");
        $this->command->info("Total tasks in DB: " . Task::count());
    }
}
