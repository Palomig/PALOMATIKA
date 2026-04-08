<?php
// php scripts/create-vpr-stubs.php
// Создаёт пустые JSON-заглушки topic_01-18 для классов 5-8

foreach ([5, 6, 7, 8] as $grade) {
    $dir = __DIR__ . "/../storage/app/tasks/vpr/grade_{$grade}";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    for ($n = 1; $n <= 18; $n++) {
        $topicId = str_pad($n, 2, '0', STR_PAD_LEFT);
        $file = "{$dir}/topic_{$topicId}.json";

        if (file_exists($file)) {
            echo "SKIP  grade_{$grade}/topic_{$topicId}.json (already exists)\n";
            continue;
        }

        $stub = [
            'topic_id'  => $topicId,
            'exam_type' => 'vpr',
            'grade'     => $grade,
            'blocks'    => [],
        ];

        file_put_contents($file, json_encode($stub, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "OK    grade_{$grade}/topic_{$topicId}.json\n";
    }
}
echo "Done.\n";
