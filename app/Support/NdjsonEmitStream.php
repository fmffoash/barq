<?php

namespace App\Support;

// "ملف" وهمي بيستقبل رد Ollama وهو بيوصل ويسلّمه سطر بسطر لدالة (2026-10-10، الرجوع للسيرفر).
// بيتستخدم كـ sink لطلب HTTP: Guzzle بيكتب فيه كل جزء أول ما يوصل (CURLOPT_WRITEFUNCTION)،
// فالسيرفر يوصّل كل كلمة للمتصفح فوراً — ومع خيار progress بتاع curl (بيتنادى كل ثانية تقريباً
// حتى لو مفيش بيانات) السيرفر يقدر يبعت "لسه شغال" وهو مستني أول كلمة. Http::fake في التستات
// بيكتب الرد كله مرة واحدة بـfwrite على نفس الـresource، فالمسارين بيعدّوا من نفس الكود.
//
// لازم يبقى resource حقيقي (stream wrapper) مش كلاس PSR-7: Http::fake بيعمل fwrite مباشرة.
final class NdjsonEmitStream
{
    private const SCHEME = 'barq-ndjson';

    /** @var resource|null */
    public $context;

    /** @var array<string, array{emit: callable(string): void, lines: int}> */
    private static array $registry = [];

    private string $id = '';

    private string $buffer = '';

    /**
     * @param  callable(string): void  $emit  بتتنادى لكل سطر مش فاضي
     * @return resource
     */
    public static function open(callable $emit)
    {
        if (! in_array(self::SCHEME, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::SCHEME, self::class);
        }

        $id = bin2hex(random_bytes(8));
        self::$registry[$id] = ['emit' => $emit, 'lines' => 0];

        return fopen(self::SCHEME.'://'.$id, 'w+');
    }

    /**
     * عدد السطور اللي اتسلّمت لحد دلوقتي — عشان نعرف لو Ollama رد بخطأ من غير جسم.
     *
     * @param  resource  $resource
     */
    public static function lines($resource): int
    {
        $id = (string) parse_url((string) (stream_get_meta_data($resource)['uri'] ?? ''), PHP_URL_HOST);

        return self::$registry[$id]['lines'] ?? 0;
    }

    /**
     * @param  resource  $resource
     */
    public static function finish($resource): void
    {
        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->id = (string) parse_url($path, PHP_URL_HOST);

        return isset(self::$registry[$this->id]);
    }

    public function stream_write(string $data): int
    {
        $this->buffer .= $data;

        while (($newline = strpos($this->buffer, "\n")) !== false) {
            $this->deliver(substr($this->buffer, 0, $newline));
            $this->buffer = substr($this->buffer, $newline + 1);
        }

        return strlen($data);
    }

    public function stream_close(): void
    {
        $this->deliver($this->buffer);
        $this->buffer = '';
        unset(self::$registry[$this->id]);
    }

    // الباقي عشان Guzzle/fwrite يتعاملوا معاه كملف عادي — مفيش حاجة بتتخزن للقراية.
    public function stream_read(int $count): string
    {
        return '';
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_tell(): int
    {
        return 0;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return true;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return ['size' => 0];
    }

    private function deliver(string $line): void
    {
        $line = trim($line);

        if ($line === '' || ! isset(self::$registry[$this->id])) {
            return;
        }

        self::$registry[$this->id]['lines']++;
        (self::$registry[$this->id]['emit'])($line);
    }
}
