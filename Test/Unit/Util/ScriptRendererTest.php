<?php declare(strict_types=1);

namespace Yireo\MspDevToolsFixes\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Yireo\MspDevToolsFixes\Util\ScriptRenderer;

class ScriptRendererTest extends TestCase
{
    public function testEscapesHtmlSensitiveCharacters(): void
    {
        $html = (new ScriptRenderer())->render(
            ['general' => ['q' => '</script><!--<script>alert(1)&\'"']],
            'YWJj'
        );

        $this->assertSame(1, substr_count($html, '<script'));
        $this->assertSame(1, substr_count($html, '</script>'));
        $this->assertStringNotContainsString('<!--', $html);
        $this->assertStringContainsString('\u003C\/script\u003E\u003C!--', $html);
        $this->assertStringContainsString('\u0026\u0027\u0022', $html);
        $this->assertStringStartsWith('<script type="text/javascript" nonce="YWJj">', $html);
        $this->assertStringEndsWith('window.mspDevTools["_protocol"] = 3;</script>', $html);
    }

    public function testEncodesKeys(): void
    {
        $html = (new ScriptRenderer())->render(['a"]</script>' => 1], '');

        $this->assertStringContainsString('window.mspDevTools["a\u0022]\u003C\/script\u003E"] = 1;', $html);
    }

    public function testSubstitutesInvalidUtf8(): void
    {
        $html = (new ScriptRenderer())->render(['general' => ['q' => "\xff"]], '');

        $this->assertStringContainsString('{"q":"\ufffd"}', $html);
    }

    public function testOmitsInvalidNonce(): void
    {
        $html = (new ScriptRenderer())->render([], 'abc" onload="alert(1)');

        $this->assertStringStartsWith('<script type="text/javascript">', $html);
        $this->assertStringNotContainsString('onload', $html);
    }
}
