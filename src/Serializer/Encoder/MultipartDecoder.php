<?php declare(strict_types=1);

namespace App\Serializer\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(private RequestStack $requestStack)
    {

    }

    public function decode(string $data, string $format, array $context = [])
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return array_map(static function (string $element) {
            $decoded = json_decode($element, true);

            return \is_array($decoded) ? $decoded : $element;
        }, $request->request->all()) + $request->files->all();
    }

    public function supportsDecoding(string $format)
    {
        return self::FORMAT === $format;
    }
}
