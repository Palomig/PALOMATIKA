<?php

namespace App\Support;

use App\Models\OgeAttempt;

class OgeResultLinkBuilder
{
    public function buildVariantResultsUrl(OgeAttempt $attempt): string
    {
        return route('teacher.oge.results', ['variantId' => $attempt->variant_id]);
    }

    public function buildAttemptResultsUrl(OgeAttempt $attempt): string
    {
        $base = $this->buildVariantResultsUrl($attempt);
        $attemptId = (int) $attempt->id;

        return "{$base}?attempt={$attemptId}#attempt-{$attemptId}";
    }
}
