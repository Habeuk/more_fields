<?php

namespace Drupal\more_fields_video\Services;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\Video\Ogg;
use FFMpeg\Exception\ExecutableNotFoundException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use FFMpeg\Coordinate\TimeCode;
use Drupal\more_fields_video\Entity\MultiformatVideo;
use Drupal\Core\File\FileSystem;

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
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $FileSystem;
  
  /**
   * Constructs a new MyCustomService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *        The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystem $FileSystem) {
    $this->entityTypeManager = $entity_type_manager;
    $this->FileSystem = $FileSystem;
  }
  
  /**
   * Permet de generer la miniature à partir de la video.
   *
   * @param File $file
   * @param EntityStorageInterface $multiformatHandler
   * @return MultiformatVideo|NULL
   */
  public function getMultiFormat($file, $multiformatHandler = null) {
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
        $result = $this->createThumbFile($file);
        if ($result !== FALSE) {
          $multiformat = $this->sync_multiformat($file->id(), $result, $multiformatHandler);
        }
      }
    }
    return $multiformat;
  }
  
  /**
   * Cette fonction vise à convertir les videos mov et quictime en webm.
   *
   * @param int $fid
   * @param EntityStorageInterface $multiformatHandler
   * @return MultiformatVideo|NULL
   */
  public function manageUploadedFile($fid, $multiformatHandler = null, $generateThumb = True, $convertVideo = True, $vFormat = "webm", $toConvert = [
    "mov",
    "quicktime"
  ]) {
    $multiformat = null;
    $file = File::load($fid);
    try {
      if ($convertVideo) {
        $multiformat = null;
        $fileMime = explode("/", $file->getMimeType());
        if ($fileMime[0] === "video") {
          if ($fileMime[1] != $vFormat && in_array($fileMime[1], $toConvert)) {
            $convertedFilePath = $this->convertVideo($file, [
              $vFormat
            ]);
            $file->setFileUri($convertedFilePath[$vFormat]['uri']);
            $file->setFilename(pathinfo($convertedFilePath[$vFormat]['uri'], PATHINFO_FILENAME) . '.' . $vFormat);
            $file->save();
          }
        }
      }
      if ($generateThumb) {
        $multiformat = $this->getMultiFormat($file, $multiformatHandler);
      }
    }
    catch (ExecutableNotFoundException $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
    }
    catch (\Error $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
    }
    return [
      "multiformat" => $multiformat,
      "furi" => $file->getFileUri()
    ];
  }
  
  /**
   * Permet de convertir les videos en un formet bien definit.
   */
  public function ConvertVideoToRightFormat(File $file, array $outputFormat, $updateFile = false) {
    try {
      $files = [];
      $fileMime = explode("/", $file->getMimeType());
      $files[$file->id()] = $file;
      if ($fileMime[0] === "video" && $outputFormat) {
        $extention = $fileMime[1];
        if ($extention == 'mp4' && $this->isMp4H264($file)) {
          $index = array_search("mp4", $outputFormat);
          if ($index !== false) {
            unset($outputFormat[$index]);
          }
        }
        elseif ($extention == 'ogg') {
          $index = array_search("ogg", $outputFormat);
          if ($index !== false) {
            unset($outputFormat[$index]);
          }
        }
        elseif ($extention == 'webm') {
          $index = array_search("webm", $outputFormat);
          
          if ($index !== false) {
            unset($outputFormat[$index]);
          }
        }
        if ($outputFormat) {
          $convertedFilePath = $this->convertVideo($file, $outputFormat);
          $uid = \Drupal::currentUser()->id();
          // Pour l'instant, nous avons un soucis pour ajouter les nouveaux
          // elements aux contenus encours.
          foreach ($convertedFilePath as $format => $data) {
            if ($updateFile)
              $newFile = $file;
            else
              $newFile = $file->createDuplicate();
            $newFile->setFileUri($data['uri']);
            $newFile->setFilename(pathinfo($data['filename'], PATHINFO_FILENAME) . '.' . $format);
            $newFile->setMimeType("video/$format");
            $newFile->setOwnerId($uid);
            $newFile->save();
            $files[$newFile->id()] = $newFile;
          }
        }
        return $files;
      }
      return $files;
    }
    catch (ExecutableNotFoundException $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
    }
    catch (\Error $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
    }
  }
  
  /**
   *
   * @param File $file
   * @return boolean
   */
  function isMp4H264(File $file) {
    $filePath = $this->FileSystem->realpath($file->getFileUri());
    // Commande pour obtenir les informations du fichier vidéo
    $command = "ffmpeg -i " . escapeshellarg($filePath) . " 2>&1";
    $output = shell_exec($command);
    
    // Vérification du format et du codec
    $isMp4 = strpos($output, 'Video: h264') !== false;
    $isH264 = strpos($output, 'mp4') !== false;
    if ($isH264) {
      \Drupal::messenger()->addStatus("Le format video mp4 est H264");
    }
    else
      \Drupal::messenger()->addWarning("Le format video mp4 n'est pas H264");
    return $isMp4 && $isH264;
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
  protected function createThumbFile(File $file, $frame_seconde = 1) {
    $file_uri = $file->getFileUri();
    [
      'filename' => $filename,
      'dirname' => $dirname
    ] = pathinfo($file_uri);
    try {
      // create thumb path + name
      $thumb_path = $dirname . '/' . $filename . $this->thumb_extension;
      $ffmpeg = FFMpeg::create();
      $ffm_video = $ffmpeg->open($this->FileSystem->realpath($file_uri));
      $ffm_video->frame(TimeCode::fromSeconds($frame_seconde))->save($this->FileSystem->realpath($thumb_path));
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
    catch (\Error $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
      return FALSE;
    }
  }
  
  /**
   *
   * @param File $file
   * @return string path of the new file
   */
  protected function convertVideo(File $file, array $finalTypes) {
    $file_uri = $file->getFileUri();
    [
      'filename' => $filename,
      'dirname' => $dirname
    ] = pathinfo($file_uri);
    $convertedVidPath = [];
    $ffmpeg = FFMpeg::create();
    
    $ffm_video = $ffmpeg->open($this->FileSystem->realpath($file_uri));
    try {
      \Drupal::messenger()->addStatus("Video converti");
      foreach ($finalTypes as $extention) {
        $PathUri = $dirname . '/' . $filename . "." . $extention;
        $newpath = $this->FileSystem->realpath($PathUri);
        switch ($extention) {
          case 'webm':
            $ffm_video->save(new WebM(), $newpath);
            $convertedVidPath[$extention] = [];
            $convertedVidPath[$extention]['uri'] = $PathUri;
            $convertedVidPath[$extention]['filename'] = $filename . "." . $extention;
            // $convertedVidPath[$extention]['size'] = filesize($realpath);
            break;
          case 'mp4':
            $ffm_video->save(new X264(), $newpath);
            $convertedVidPath[$extention] = [];
            $convertedVidPath[$extention]['uri'] = $PathUri;
            $convertedVidPath[$extention]['filename'] = $filename . "." . $extention;
            // $convertedVidPath[$extention]['size'] = filesize($realpath);
            break;
          case 'ogg':
            $ffm_video->save(new Ogg(), $newpath);
            $convertedVidPath[$extention] = [];
            $convertedVidPath[$extention]['uri'] = $PathUri;
            $convertedVidPath[$extention]['filename'] = $filename . "." . $extention;
            // $convertedVidPath[$extention]['size'] = filesize($realpath);
            break;
        }
      }
      return $convertedVidPath;
    }
    catch (ExecutableNotFoundException $e) {
      \Drupal::logger('more_fields_video')->error($e->getMessage());
    }
    catch (\Throwable $th) {
      \Drupal::logger('more_fields_video')->error($th->getMessage());
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
