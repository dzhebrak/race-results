<?php declare(strict_types=1);

namespace App\Model;

class FinishTime
{
    public const TIME_REGEX_PATTERN = '/^\d+:\d{2}:\d{2}$/';

    public function __construct(private int $seconds)
    {

    }

    public static function fromTime(string $time): static
    {
        if (preg_match(self::TIME_REGEX_PATTERN, $time) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid finish time value. Finish time must be specified in the format "h:mm:ss", e.g. "4:07:45"', $time));
        }

        [$hh, $mm, $ss] = array_map('intval', explode(':', $time));

        return new static($hh * 60 * 60 + $mm * 60 + $ss);
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toString(): string
    {
        $seconds = $this->seconds;

        $hh = floor($seconds / 3600);
        $seconds = $hh > 0 ? $seconds - $hh * 3600 : $seconds;
        $mm = floor($seconds / 60);
        $seconds = $mm > 0 ? $seconds - $mm * 60 : $seconds;
        $ss = $seconds;

        return sprintf('%d:%02d:%02d', $hh, $mm, $ss);
    }
}
