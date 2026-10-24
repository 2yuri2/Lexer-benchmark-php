
<?php

$source = file_get_contents(__DIR__ . '/sample.php');

if ($source === false) {
    die("Error: Cannot read sample.php\n");
}

function lexPHP(string $source): array
{
    $rawTokens = token_get_all($source);
    $tokens = [];

    foreach ($rawTokens as $token) {
        if (is_array($token)) {
            [$id, $text] = $token;

            if (in_array($id, [
                T_OPEN_TAG,
                T_CLOSE_TAG,
                T_WHITESPACE,
                T_COMMENT,
                T_DOC_COMMENT
            ], true)) {
                continue;
            }

            if ($id === T_VARIABLE) {
                $type = 'name';
            } elseif ($id === T_LNUMBER || $id === T_DNUMBER) {
                $type = 'num';
            } elseif ($id === T_STRING) {
                $type = 'name';
            } else {
                $type = token_name($id);
            }
        } else {
            $text = $token;

            if (trim($text) === '') {
                continue;
            }

            if (in_array($text, ['+', '-', '*', '/'], true)) {
                $type = '+/-';
            } else {
                $type = $text;
            }
        }

        $tokens[] = [
            'type' => $type,
            'text' => $text
        ];
    }

    return $tokens;
}

$iterations = 1000;
$times = [];

$baselineMemory = memory_get_usage();

for ($i = 0; $i < $iterations; $i++) {
    $start = hrtime(true);

    $tokens = lexPHP($source);

    $end = hrtime(true);

    $times[] = ($end - $start) / 1_000_000;

    unset($tokens);
}

sort($times);

$count = count($times);

if ($count % 2 === 0) {
    $median = (
        $times[(int) ($count / 2) - 1] +
        $times[(int) ($count / 2)]
    ) / 2;
} else {
    $median = $times[(int) floor($count / 2)];
}

$tokens = lexPHP($source);

foreach ($tokens as $index => $token) {
    printf(
        "%d %s %s\n",
        $index + 1,
        $token['type'],
        json_encode($token['text'])
    );
}

$memoryIncrease = max(
    0,
    memory_get_peak_usage() - $baselineMemory
);

echo "\nIterations: $iterations\n";
printf("Median time (ms): %.4f\n", $median);
printf(
    "Peak memory increase (MB): %.4f\n",
    $memoryIncrease / (1024 * 1024)
);

?>
