<?php

namespace deanar\fileProcessor\controllers;


use \Yii;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use dosamigos\transliterator\TransliteratorHelper;
use deanar\fileProcessor\vendor\FileAPI;
use deanar\fileProcessor\FileProcessor;
use deanar\fileProcessor\models\FileStorage;
use deanar\fileProcessor\models\FileSequence;
use deanar\fileProcessor\Module;

// only for tests
use app\models\Project;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Imagine\Exception\Exception;


class BaseController extends \yii\web\Controller
{
    //public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $imagine = new Imagine();
        $image = $imagine->open('testimage.jpg')->thumbnail(new Box(300, 200), ImageInterface::THUMBNAIL_OUTBOUND)->save('testimage2.jpg', array('quality' => 95));
        return Html::img('testimage2.jpg');
        return $this->render('index');
        return '';
    }

    public function actionRemove()
    {
        // TODO check for POST request

        $id         = Yii::$app->request->post('id');
        $type       = Yii::$app->request->post('type');
        $type_id    = Yii::$app->request->post('type_id');

        $success = FileSequence::staticRemoveFile($id, compact('type', 'type_id'));

        if ($success) {
            return 'File with id: ' . $id . ' removed successfully';
        } else {
            return 'Fail to remove file with id: ' . $id;
        }
    }

    public function actionUpload()
    {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            // Enable CORS
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $files = FileAPI::getFiles(); // Retrieve File List
            $images = array();

            //$file = UploadedFile::getInstanceByName('filedata');

            // Fetch all image-info from files list
            $this->fetchFiles($files, $images);

            // JSONP callback name
            $jsonp = isset($_REQUEST['callback']) ? trim($_REQUEST['callback']) : null;

            // JSON-data for server response
            $json = array(
                'images' => $images
            , 'data' => array('_REQUEST' => $_REQUEST, '_FILES' => $files)
            );

            // Server response: "HTTP/1.1 200 OK"
            FileAPI::makeResponse(array(
                'status' => FileAPI::OK
            , 'statusText' => 'OK'
            , 'body' => $json
            ), $jsonp);
            exit;
        }

    } // end of actionUpload

    private function fetchFiles($files, &$images, $name = 'file')
    {

        if (isset($files['tmp_name'])) {
            // system info
            $type = Yii::$app->request->post('type');
            $type_id = Yii::$app->request->post('type_id');
            $hash = Yii::$app->request->post('hash');

            // file info
            $file_temp_name = $files['tmp_name'];
            $file_real_name = basename($files['name']);


            if (is_uploaded_file($file_temp_name)) {

                $mime = FileHelper::getMimeType($file_temp_name);

                if( is_null($mime)){
                    $mime = FileHelper::getMimeTypeByExtension($file_real_name);
                }

                if (strpos($mime, 'image') !== false) {
                    $file_dimensions = getimagesize($file_temp_name);
                } else {
                    $file_dimensions = [null, null];
                }

                // insert into db
                $model = new FileStorage();
                $model->filename = FileStorage::generateBaseFileName($file_real_name);
                $model->original = $file_real_name;
                $model->mime = $mime;
                $model->size = filesize($file_temp_name);
                $model->width = $file_dimensions[0];
                $model->height = $file_dimensions[1];

                // save model, save file and fill response array
                if ($model->save()) {

                    $sequence = new FileSequence(); // maybe static better?
                    $sequence->attachFile($model, $type, $type_id, $hash); // add error handling

                    // load configuration
                    $config = FileProcessor::loadVariationsConfig($sequence->type);

                    // upload and process variations
                    $model->process($file_temp_name, $config);

                    $images[$name] = [
                        // file
                        'width'     => $model->width,
                        'height'    => $model->height,
                        'mime'      => $model->mime,
                        'size'      => $model->size,

                        // sequence
                        'id'        => $sequence->id,
                        'type'      => $sequence->type,
                        'type_id'   => $sequence->type_id,
                        'hash'      => $sequence->hash,
                        'errors'    => null,
                    ];

                } else {
                    VarDumper::dumpAsString($model->getErrors());
                    Yii::$app->end();
                }
            }else{ // is_uploaded_file
                Yii::$app->end('No file was uploaded');
            }

        } else {
            foreach ($files as $name => $file) {
                $this->fetchFiles($file, $images, $name);
            }
        }

    }

    public function actionSort(){
        $sort = Yii::$app->request->post('sort',[]);
        if( !is_array($sort)) return false;

        foreach ($sort as $k => $v) {
            $file = FileStorage::findOne($v);
            if(is_null($file)) continue;
            $file->ord = $k;
            $file->save();
        }
        return '';
    }

}
