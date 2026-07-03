<?php

declare(strict_types=1);

namespace PHPAdmin\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testEscapesHtml(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        $this->assertSame($expected, e($input));
    }

    public function testEscapesAmpersandAndQuotes(): void
    {
        $this->assertSame('&amp;', e('&'));
        $this->assertSame('&quot;', e('"'));
        $this->assertSame('&#039;', e("'"));
    }

    public function testNullReturnsEmptyString(): void
    {
        $this->assertSame('', e(null));
    }

    public function testEmptyStringReturnsEmptyString(): void
    {
        $this->assertSame('', e(''));
    }

    public function testPlainStringPassesThrough(): void
    {
        $this->assertSame('hello world', e('hello world'));
    }

    public function testUuidMatchesRfc4122V4Pattern(): void
    {
        $uuid = uuid();
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $uuid);
    }

    public function testUuidUniqueness(): void
    {
        $uuids = [];
        for ($i = 0; $i < 100; $i++) {
            $uuids[] = uuid();
        }
        $unique = array_unique($uuids);
        $this->assertCount(100, $unique, 'uuid() should produce unique values');
    }
}
