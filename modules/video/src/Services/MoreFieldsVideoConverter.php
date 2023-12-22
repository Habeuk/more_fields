<?php

namespace Drupal\more_fields_video\Services;

use FFMpeg\FFMpeg;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use FFMpeg\Coordinate\TimeCode;

/**
 * Prepares the salutation to the world.
 */
class MoreFieldsVideoConverter {

    use StringTranslationTrait;

    private $thumb_extension = '.webp';

    /**
     * create the thumb file for a video .webp format
     * @param int $video_id
     * @return string $path
     */
    public function createThumbFile($video_id) {
        $video_file = File::load($video_id);
        $ffmpeg = FFMpeg::create();
        $video_uri = $video_file->getFileUri();
        //contruct thumb file uri
        ['filename' => $filename, 'dirname' => $dirname] = pathinfo('/www/htdocs/inc/lib.inc.php');
        $thumb_path = $dirname . '/' . $filename . $this->thumb_extension;

        //open and convert stream
        $ffm_video = $ffmpeg->open($video_uri);

        $ffm_video->frame(TimeCode::fromSeconds(5))
            ->save($thumb_path);
    }
}
