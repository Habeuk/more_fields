<?php

namespace Drupal\more_fields_video\Services;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use FFMpeg\Coordinate\TimeCode;
use Drupal\more_fields_video\Entity\MultiformatVideo;
use Exception;

/**
 * Prepares the salutation to the world.
 */
class MoreFieldsVideoConverter {

  use StringTranslationTrait;
  protected $thumb_extension = '.png';
  protected $thumb_mime = 'image/png';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MyCustomService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *        The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   *
   * @param File $file
   * @param EntityStorageInterface $multiformatHandler
   * @return MultiformatVideo|NULL
   */
  public function getMultiFormat(&$file, &$multiformatHandler = null) {
    $multiformat = null;
    $fileType = explode("/", $file->getMimeType())[0];

    if ($fileType === "video") {
      if (!isset($multiformatHandler)) {
        $multiformatHandler = $this->entityTypeManager->getStorage("multiformat_video");
      }
      /**
       *
       * @param MultiformatVideo|null $multiformat
       */
      $multiformat = $multiformatHandler->load($file->id());

      if (!$multiformat) {
        $result = $this->createThumbFile((int) $file->id());
        if ($result !== FALSE) {
          $multiformat = $this->sync_multiformat($file->id(), $result, $multiformatHandler);
        }
      }
    }
    return $multiformat;
  }

  /**
   *
   * @param int $fid
   * @param EntityStorageInterface $multiformatHandler
   * @return MultiformatVideo|NULL
   */
  public function manageUploadedFile($fid, $multiformatHandler = null, $generateThumb = True, $convertVideo = True, $vFormat = "webm", $toConvert = [
    "mov",
    "quicktime"
  ]) {
    $file = File::load($fid);
    $multiformat = null;
    if ($convertVideo) {
      $multiformat = null;
      $fileMime = explode("/", $file->getMimeType());

      if ($fileMime[0] === "video") {
        if ($fileMime[1] != $vFormat && in_array($fileMime[1], $toConvert)) {
          $convertedFilePath = $this->convertVideo($file, $vFormat);
          $file->setFileUri($convertedFilePath);
          $file->setFilename(pathinfo($convertedFilePath, PATHINFO_FILENAME) . '.' . $vFormat);
          $file->save();
        }
      }
    }

    if ($generateThumb) {
      $multiformat = $this->getMultiFormat($file, $multiformatHandler);
    }
    return [
      "multiformat" => $multiformat,
      "furi" => $file->getFileUri()
    ];
  }

  /**
   * Cette methode est statique car elle est utilisé par à l'exterieur de la
   * classe.
   * la transférer dans un service est une option
   *
   * @param File $thumb_file
   * @return MultiformatVideo
   */
  public function sync_multiformat($video_id, File $thumb_file, EntityStorageInterface &$multiformatHandler) {
    // creating and handling the multiformat
    /**
     *
     * @var MultiformatVideo $multiformat
     */
    $multiformat = $multiformatHandler->load($video_id) ?? $multiformatHandler->create();
    $thumb_file->setPermanent();
    $thumb_file->save();
    $multiformat->setThumbId($thumb_file->id());
    $multiformat->setVideoId($video_id);
    $multiformat->save();
    return $multiformat;
  }

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
    } catch (\Throwable $th) {
      return FALSE;
    }
  }

  /**
   *
   * @param File $file
   * @return string path of the new file
   */
  public function convertVideo(&$file, $finalType = "webm") {
    $file_uri = $file->getFileUri();
    [
      'filename' => $filename,
      'dirname' => $dirname
    ] = pathinfo($file_uri);

    // create thumb path + name
    $convertedVidPath = $dirname . '/' . $filename . "." . $finalType;

    $ffmpeg = FFMpeg::create();
    /**
     *
     * @var FileSystem $file_system
     */
    $file_system = \Drupal::service('file_system');
    $ffm_video = $ffmpeg->open($file_system->realpath($file_uri));
    try {
      $ffm_video->save(new WebM(), $file_system->realpath($convertedVidPath));
      return $convertedVidPath;
    } catch (\Throwable $th) {
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
