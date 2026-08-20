<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskIntro;
use App\Services\Print\PrintVariantSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintVariantSelectorTest extends TestCase
{
    use RefreshDatabase;

    /** Готовит два сюжета блока 1–5 и по одной задаче в темах 06 и 24. */
    private function seedBank(): void
    {
        foreach (['A', 'B'] as $letter) {
            $guid = str_pad($letter, 32, '0');
            TaskIntro::create(['bank' => 'oge', 'guid' => $guid, 'html' => "<p>Сюжет {$letter}</p>"]);

            foreach (['01', '02', '03', '04', '05'] as $topic) {
                $group = TaskGroup::create([
                    'bank' => 'oge', 'topic' => $topic, 'block_number' => 1,
                    'zadanie_number' => 1, 'type' => 'fipi', 'status' => 'production',
                    'instruction' => 'Сюжет ' . $letter,
                ]);
                Task::create([
                    'task_group_id' => $group->id,
                    'payload' => ['html' => "<p>{$letter}{$topic}</p>"],
                    'answer' => '1', 'status' => 'production', 'intro_guid' => $guid,
                ]);
            }
        }

        $six = TaskGroup::create([
            'bank' => 'oge', 'topic' => '06', 'block_number' => 1,
            'zadanie_number' => 1, 'type' => 'fipi', 'status' => 'production',
        ]);
        foreach (range(1, 3) as $i) {
            Task::create([
                'task_group_id' => $six->id,
                'payload' => ['html' => "<p>шесть {$i}</p>"],
                'answer' => (string) $i, 'status' => 'production',
            ]);
        }

        // Доказательства темы 24 лежат в draft именно из-за отсутствия ответа.
        $proof = TaskGroup::create([
            'bank' => 'oge', 'topic' => '24', 'block_number' => 1,
            'zadanie_number' => 1, 'type' => 'fipi', 'status' => 'production',
        ]);
        Task::create([
            'task_group_id' => $proof->id,
            'payload' => ['html' => '<p>Докажите</p>'],
            'answer' => null, 'status' => 'draft',
        ]);
    }

    public function test_block_one_to_five_shares_a_single_scenario(): void
    {
        $this->seedBank();

        $items = (new PrintVariantSelector())->select(123, ['01', '02', '03', '04', '05']);

        $this->assertCount(5, $items);

        $guids = array_unique(array_map(static fn (array $i): string => $i['task']->intro_guid, $items));
        $this->assertCount(1, $guids, 'все пять заданий обязаны быть из одного сюжета');
    }

    public function test_intro_matches_the_chosen_scenario(): void
    {
        $this->seedBank();

        $selector = new PrintVariantSelector();
        $items = $selector->select(7, ['01', '02', '03', '04', '05']);

        $this->assertNotNull($selector->intro());
        $this->assertSame($items[0]['task']->intro_guid, $selector->intro()->guid);
    }

    public function test_same_seed_gives_the_same_variant(): void
    {
        $this->seedBank();

        $first = (new PrintVariantSelector())->select(99, ['06']);
        $second = (new PrintVariantSelector())->select(99, ['06']);

        $this->assertSame($first[0]['task']->id, $second[0]['task']->id);
    }

    /** Внутри одной пачки задача не повторяется, пока пул не исчерпан. */
    public function test_tasks_do_not_repeat_while_pool_lasts(): void
    {
        $this->seedBank();

        $selector = new PrintVariantSelector();
        $ids = [];
        foreach ([1, 2, 3] as $seed) {
            $ids[] = $selector->select($seed, ['06'])[0]['task']->id;
        }

        $this->assertCount(3, array_unique($ids));
    }

    /** Доказательство без ответа — нормальное задание печатной работы. */
    public function test_proof_topic_is_included_despite_draft_status(): void
    {
        $this->seedBank();

        $items = (new PrintVariantSelector())->select(5, ['24']);

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['part2']);
        $this->assertNull($items[0]['task']->answer);
    }
}
