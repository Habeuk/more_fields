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

    protected $thumb_extension = '.png';

    /**
     * create the thumb file for a video in a given format (the default format is png)
     * @param int $video_id
     * Id of the seed Drupal\file\Entity\File (video) to be converted 
     * @param int $frame_second
     * the second where the frame will be captured
     * @return string $path
     */
    public function createThumbFile($video_id, $frame_seconde = 1) {
        $file = File::load($video_id);
        $file_uri = $file->getFileUri();
        ['filename' => $filename, 'dirname' => $dirname] = pathinfo($file_uri);

        //create thumb path + name 
        $thumb_path = $dirname . '/' . $filename . $this->thumb_extension;

        $ffmpeg = FFMpeg::create();
        /**
         * @var FileSystem $file_system
         */
        $file_system = \Drupal::service('file_system');
        $ffm_video = $ffmpeg->open($file_system->realpath($file_uri));
        try {
            $temp = $ffm_video->frame(TimeCode::fromSeconds($frame_seconde))
                ->save($file_system->realpath($thumb_path));
            return $thumb_path;
        } catch (\Throwable $th) {
            return FALSE;
        }
    }

    /**
     * define in the extension of the thumb when it will be genereted
     */
    public function setThumbExtension($extension) {
        $this->thumb_extension = $extension;
    }
}
