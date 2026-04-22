<?php

namespace Tests\Feature;

use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\User;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantPoolExamIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    private function makeVariant(string $hash, string $examType, string $mode = OgeVariant::MODE_FULL): OgeVariant
    {
        return OgeVariant::create([
            'hash' => $hash,
            'exam_type' => $examType,
            'title' => "Variant {$examType}",
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => $mode,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 1, 'topic_id' => '01', 'text' => 'Task 1'],
                ],
            ],
        ]);
    }

    private function addPoolEntry(OgeVariant $variant, string $type): void
    {
        OgeVariantPoolEntry::create([
            'variant_id' => $variant->id,
            'exam_type' => $variant->exam_type,
            'type' => $type,
            'status' => 'active',
            'task_fingerprint' => md5($variant->hash . '|' . $type),
            'created_at' => now(),
        ]);
    }

    public function test_oge_mixed_pool_does_not_reuse_vpr_mixed_variant(): void
    {
        $student = $this->makeStudent(9);
        $vprVariant = $this->makeVariant('vprmixed1', OgeVariant::EXAM_VPR5, OgeVariant::MODE_MINI_MIXED);
        $this->addPoolEntry($vprVariant, 'mixed');

        $service = new OgeVariantPoolService(
            app(TaskDataService::class),
            app(MiniAppTaskCanonicalizer::class),
        );

        $picked = $service->getOrCreateVariant($student, 'mixed');

        $this->assertSame(OgeVariant::EXAM_OGE, $picked->exam_type);
        $this->assertNotSame($vprVariant->id, $picked->id);
    }

    public function test_oge_full_pool_does_not_reuse_vpr_full_variant(): void
    {
        $student = $this->makeStudent(9);
        $vprVariant = $this->makeVariant('vprfull11', OgeVariant::EXAM_VPR5, OgeVariant::MODE_FULL);
        $this->addPoolEntry($vprVariant, 'full');

        $service = new OgeVariantPoolService(
            app(TaskDataService::class),
            app(MiniAppTaskCanonicalizer::class),
        );

        $picked = $service->getOrCreateVariant($student, 'full');

        $this->assertSame(OgeVariant::EXAM_OGE, $picked->exam_type);
        $this->assertNotSame($vprVariant->id, $picked->id);
    }

    public function test_vpr_pool_reuses_only_matching_grade_variants(): void
    {
        $student = $this->makeStudent(6);
        $gradeFive = $this->makeVariant('vpr5full1', OgeVariant::EXAM_VPR5, OgeVariant::MODE_FULL);
        $gradeSix = $this->makeVariant('vpr6full1', OgeVariant::EXAM_VPR6, OgeVariant::MODE_FULL);
        $this->addPoolEntry($gradeFive, 'full');
        $this->addPoolEntry($gradeSix, 'full');

        $service = new VprVariantPoolService(
            new VprTaskDataService(6),
            new VprVariantBuilderService(new VprTaskDataService(6)),
        );

        $picked = $service->getOrCreateVariant($student, 'full');

        $this->assertSame(OgeVariant::EXAM_VPR6, $picked->exam_type);
        $this->assertSame($gradeSix->id, $picked->id);
    }
}
