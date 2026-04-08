<?php
namespace Tests\Unit;

use App\Models\OgeVariant;
use PHPUnit\Framework\TestCase;

class OgeVariantExamTypeTest extends TestCase
{
    public function test_exam_type_constants_defined(): void
    {
        $this->assertSame('oge',   OgeVariant::EXAM_OGE);
        $this->assertSame('vpr_5', OgeVariant::EXAM_VPR5);
        $this->assertSame('vpr_6', OgeVariant::EXAM_VPR6);
        $this->assertSame('vpr_7', OgeVariant::EXAM_VPR7);
        $this->assertSame('vpr_8', OgeVariant::EXAM_VPR8);
        $this->assertSame('ege',   OgeVariant::EXAM_EGE);
    }

    public function test_exam_type_is_in_fillable(): void
    {
        $model = new OgeVariant();
        $this->assertContains('exam_type', $model->getFillable());
    }
}
