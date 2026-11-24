<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\Dns\DnsPacket;
use PHPUnit\Framework\TestCase;

final class DnsPacketTest extends TestCase
{
    public function test_encodes_a_caa_query(): void
    {
        $packet = DnsPacket::encodeQuery(0x1234, 'example.com');

        $this->assertSame("\x12\x34", substr($packet, 0, 2));
        $this->assertStringContainsString("\x07example\x03com\x00", $packet);
        $this->assertSame(DnsPacket::TYPE_CAA, unpack('n', substr($packet, -4, 2))[1]);
    }

    public function test_parses_a_caa_answer_with_compression(): void
    {
        $value = 'letsencrypt.org';
        $rdata = chr(0).chr(5).'issue'.$value;
        $answer = "\xc0\x0c".pack('nnNn', 257, 1, 300, strlen($rdata)).$rdata;

        $packet = pack('n6', 0x2222, 0x8180, 1, 1, 0, 0)
            ."\x07example\x03com\x00".pack('nn', 257, 1)
            .$answer;

        $parsed = DnsPacket::parseResponse($packet);

        $this->assertNotNull($parsed);
        $this->assertSame(0x2222, $parsed['id']);
        $this->assertSame(0, $parsed['rcode']);
        $this->assertFalse($parsed['truncated']);
        $this->assertCount(1, $parsed['records']);
        $this->assertSame(0, $parsed['records'][0]['flags']);
        $this->assertSame('issue', $parsed['records'][0]['tag']);
        $this->assertSame($value, $parsed['records'][0]['value']);
    }

    public function test_ignores_non_caa_records(): void
    {
        $rdata = pack('N', 0x5DB8D822);
        $answer = "\xc0\x0c".pack('nnNn', 1, 1, 300, 4).$rdata;

        $packet = pack('n6', 7, 0x8180, 1, 1, 0, 0)
            ."\x07example\x03com\x00".pack('nn', 1, 1)
            .$answer;

        $parsed = DnsPacket::parseResponse($packet);

        $this->assertNotNull($parsed);
        $this->assertSame([], $parsed['records']);
    }

    public function test_reports_truncation(): void
    {
        $packet = pack('n6', 1, 0x8380, 0, 0, 0, 0);

        $parsed = DnsPacket::parseResponse($packet);

        $this->assertNotNull($parsed);
        $this->assertTrue($parsed['truncated']);
    }

    public function test_rejects_malformed_packets(): void
    {
        $this->assertNull(DnsPacket::parseResponse(''));
        $this->assertNull(DnsPacket::parseResponse('short'));
        $this->assertNull(DnsPacket::parseResponse(pack('n6', 1, 1, 1, 0, 0, 0)));
    }

    public function test_rejects_pointers_that_loop(): void
    {
        $packet = pack('n6', 1, 1, 1, 0, 0, 0)."\xc0\x0c".pack('nn', 257, 1);

        $this->assertNull(DnsPacket::parseResponse($packet));
    }

    public function test_rejects_invalid_labels_when_encoding(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DnsPacket::encodeQuery(1, str_repeat('a', 64).'.com');
    }
}
