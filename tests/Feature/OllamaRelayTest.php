<?php

namespace Tests\Feature;

use App\Services\OllamaService;
use App\Support\NdjsonEmitStream;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// الرجوع للسيرفر (2026-10-10): رد Ollama بيعدّي من السيرفر للمتصفح سطر بسطر وهو بيوصل.
class OllamaRelayTest extends TestCase
{
    public function test_pieces_that_arrive_split_in_the_middle_of_a_line_come_out_as_whole_lines(): void
    {
        $lines = [];
        $sink = NdjsonEmitStream::open(function (string $line) use (&$lines) {
            $lines[] = $line;
        });

        fwrite($sink, '{"response":"أ');
        fwrite($sink, 'هلاً"}'."\n\n".'{"done":');
        $this->assertSame(['{"response":"أهلاً"}'], $lines);

        fwrite($sink, 'true}');
        $this->assertSame(1, NdjsonEmitStream::lines($sink));

        NdjsonEmitStream::finish($sink); // آخر سطر من غير \n بيطلع مع القفل
        $this->assertSame(['{"response":"أهلاً"}', '{"done":true}'], $lines);
    }

    public function test_a_missing_model_comes_out_as_one_error_line_with_its_kind(): void
    {
        Http::fake(['*/api/generate' => Http::response(['error' => "model 'qwen3:8b' not found"], 404)]);

        $lines = [];
        (new OllamaService)->stream(['model' => 'qwen3:8b', 'prompt' => 'x'], function (string $line) use (&$lines) {
            $lines[] = json_decode($line, true);
        });

        $errors = array_values(array_filter($lines, fn ($line) => isset($line['kind'])));
        $this->assertCount(1, $errors);
        $this->assertSame('model_missing', $errors[0]['kind']);
    }

    public function test_an_empty_reply_is_reported_instead_of_silence(): void
    {
        Http::fake(['*/api/generate' => Http::response('', 200)]);

        $lines = [];
        (new OllamaService)->stream(['model' => 'qwen3:8b', 'prompt' => 'x'], function (string $line) use (&$lines) {
            $lines[] = json_decode($line, true);
        });

        $this->assertSame('down', end($lines)['kind']);
    }
}
