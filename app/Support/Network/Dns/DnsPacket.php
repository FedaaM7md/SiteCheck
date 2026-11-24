<?php

declare(strict_types=1);

namespace App\Support\Network\Dns;

/**
 * Small, dependency-free DNS wire-format helper. Only what is needed for a
 * CAA query is implemented, but the parser skips arbitrary answer records
 * (including compressed owner names) safely.
 */
final class DnsPacket
{
    public const TYPE_CAA = 257;

    public static function encodeQuery(int $id, string $host, int $type = self::TYPE_CAA): string
    {
        $host = rtrim(strtolower($host), '.');
        $labels = explode('.', $host);
        $qname = '';

        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                throw new \InvalidArgumentException('Invalid DNS label.');
            }
            $qname .= chr(strlen($label)).$label;
        }
        $qname .= "\0";

        return pack('n6', $id, 0x0100, 1, 0, 0, 0).$qname.pack('n2', $type, 1);
    }

    /**
     * @return array{id: int, rcode: int, truncated: bool, records: list<array{flags: int, tag: string, value: string}>}|null
     */
    public static function parseResponse(string $packet): ?array
    {
        $length = strlen($packet);
        if ($length < 12) {
            return null;
        }

        $header = unpack('nid/nflags/nqd/nan/nns/nar', substr($packet, 0, 12));
        if ($header === false) {
            return null;
        }

        $offset = 12;
        $questions = (int) $header['qd'];

        for ($i = 0; $i < $questions; $i++) {
            if (self::readName($packet, $offset) === null) {
                return null;
            }
            if ($offset + 4 > $length) {
                return null;
            }
            $offset += 4;
        }

        $total = (int) $header['an'] + (int) $header['ns'] + (int) $header['ar'];
        $records = [];

        for ($i = 0; $i < $total; $i++) {
            if (self::readName($packet, $offset) === null) {
                return null;
            }
            if ($offset + 10 > $length) {
                return null;
            }

            $rr = unpack('ntype/nclass/Nttl/nrdlength', substr($packet, $offset, 10));
            if ($rr === false) {
                return null;
            }
            $offset += 10;

            $rdlength = (int) $rr['rdlength'];
            if ($offset + $rdlength > $length) {
                return null;
            }

            $rdata = substr($packet, $offset, $rdlength);
            $offset += $rdlength;

            if ((int) $rr['type'] !== self::TYPE_CAA || (int) $rr['class'] !== 1) {
                continue;
            }

            if (strlen($rdata) < 2) {
                continue;
            }

            $flags = ord($rdata[0]);
            $tagLength = ord($rdata[1]);
            if (strlen($rdata) < 2 + $tagLength) {
                continue;
            }

            $tag = strtolower(substr($rdata, 2, $tagLength));
            $value = substr($rdata, 2 + $tagLength);

            $records[] = ['flags' => $flags, 'tag' => $tag, 'value' => $value];
        }

        return [
            'id' => (int) $header['id'],
            'rcode' => (int) ($header['flags'] & 0x0F),
            'truncated' => ((int) $header['flags'] & 0x0200) !== 0,
            'records' => $records,
        ];
    }

    private static function readName(string $packet, int &$offset): ?string
    {
        $length = strlen($packet);
        $labels = [];
        $position = $offset;
        $jumps = 0;

        while (true) {
            if ($position >= $length) {
                return null;
            }

            $lengthByte = ord($packet[$position]);

            if ($lengthByte === 0) {
                $position++;
                break;
            }

            if (($lengthByte & 0xC0) === 0xC0) {
                if ($position + 1 >= $length) {
                    return null;
                }
                $pointer = (($lengthByte & 0x3F) << 8) | ord($packet[$position + 1]);
                if ($jumps === 0) {
                    $offset = $position + 2;
                }
                if ($pointer >= $length || ++$jumps > 20) {
                    return null;
                }
                $position = $pointer;

                continue;
            }

            if ($lengthByte > 63 || $position + 1 + $lengthByte > $length) {
                return null;
            }

            $labels[] = substr($packet, $position + 1, $lengthByte);
            $position += 1 + $lengthByte;
        }

        if ($jumps === 0) {
            $offset = $position;
        }

        return implode('.', $labels);
    }
}
