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
  protected $thumb_mime = 'image/png';
  
  /**
   * create the thumb file for a video in a given format (the default format is
   * png)
   *
   * @param int $video_id
   *        Id of the seed Drupal\file\Entity\File (video) to be converted
   * @param int $frame_second
   *        the second where the frame will be captured
   * @return File|boolean the not saved file that have been generated or false
   */
  public function createThumbFile($video_id, $frame_seconde = 1) {
    $file = File::load($video_id);
    $file_uri = $file->getFileUri();
    [
      'filename' => $filename,
      'dirname' => $dirname
    ] = pathinfo($file_uri);
    
    // create thumb path + name
    $thumb_path = $dirname . '/' . $filename . $this->thumb_extension;
    
    $ffmpeg = FFMpeg::create();
    /**
     *
     * @var FileSystem $file_system
     */
    $file_system = \Drupal::service('file_system');
    $ffm_video = $ffmpeg->open($file_system->realpath($file_uri));
    try {
      $ffm_video->frame(TimeCode::fromSeconds($frame_seconde))->save($file_system->realpath($thumb_path));
      /**
       *
       * @var File $thumb_file
       */
      $thumb_file = File::create();
      $thumb_file->setFileUri($thumb_path);
      $thumb_file->setFilename(pathinfo($thumb_path, PATHINFO_FILENAME));
      $thumb_file->setMimeType($this->thumb_mime);
      return $thumb_file;
    }
    catch (\Throwable $th) {
      return FALSE;
    }
  }
  
  /**
   * define in the extension of the thumb when it will be genereted
   * at the same time it define the thumb mime
   *
   * @param string $extension
   *        extension of the thumb. ex: png, jpeg, webp(not supported yet)
   */
  public function setThumbExtension($extension) {
    $this->thumb_extension = "." . $extension;
    $this->thumb_mime = "image/" . $extension;
  }
  
}
