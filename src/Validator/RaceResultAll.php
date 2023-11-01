<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints\All;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RaceResultAll extends All
{
    public int $headerRows = 1;
}
