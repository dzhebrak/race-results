<?php declare(strict_types=1);

namespace App\Controller\Action;

use App\Entity\Race;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

class ImportRaceAction extends AbstractController
{
    public function __invoke(Request $request): Race
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }


        $decoder = new CsvEncoder();
        $contents = file_get_contents($uploadedFile->getPathname());
        dd($decoder->decode($contents, 'csv'));




        dd($uploadedFile->getFilename());


        return new Race();
    }
}
