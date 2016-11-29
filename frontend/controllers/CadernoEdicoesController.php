<?php
namespace frontend\controllers;
use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\controllers\SiteController;
use frontend\models\Journal;
use frontend\models\Journal_pages;
use frontend\models\Journal_session;
use app\models\Log;
use frontend\lib\PDF2Text\PDF2Text;

class CadernoEdicoesController extends SiteController
{
    
    public $enableCsrfValidation;
    private $id_journal = null;
    private $empresa = 1;
    private $id_usuario = 1;
    private $tp_caderno = null;
    private $dt_publicacao = null;
    private $hash = 'sASda2e2sa';
    private $file_name = '';
    
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
		'except' => [],
                'rules' => [
                    // Coloque aqui as actions que deseja liberar para usuarios visitantes 
                    [
                        'actions' => ['login', 'error','request'],
                        'allow' => true,
                    ],
                    // Coloque aqui as actions que deseja liberar para usuarios logados acessarem diretamente
                    [                
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }
	
    /**
     * @inheritdoc
     */
    public function actionIndex()
    {   
        return $this->render('index');
    }
    
    /**
     * @inheritdoc
     */
    public function actionGridJournal()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $headers = Yii::$app->response->headers;
        $headers->add('Content-type', 'text/xml');
        
        $xml = new \SimpleXMLElement('<rows></rows>');
        
        $data = Journal::find()
                ->select(['id_journal','journal_number','DATE_FORMAT(publish_date, \'%d/%m/%Y\') as publish_date'])
                ->where('deleted_date IS NULL')
                ->all();
        
        foreach ($data as $value) {
            $i = 0;
            $row = $xml->addChild('row');
            foreach ($value as $k => $v) {
                if(in_array($k,['publish_date','journal_number'])){
                    $id_journal = $value['id_journal'];
                    $row->addChild('cell', $v);
                }
            }
            $row->addChild('cell', '../../vendor/dhtmlx/imgs/default/close.png^Excluir Jornal^javascript:deleteJournal('.$id_journal.')^_self');
        }
        
        echo $xml->asXML(); 
        
        //return $this->renderPartial('@app/views/default/xmlMask', array("xml" => $xml));
    }
    
    
    /**
     * @inheritdoc
     */
    public function actionDeleteJournal()
    {
        if(($id_journal = Yii::$app->request->post('id_journal'))){
            $journal = Journal::findOne(['id_journal' => $id_journal]);
            $journal->deleted_date = Date('Y-m-d H:i:s');
            $journal->save();
        }
    }
    
    /**
     * @inheritdoc
     */
    public function actionDataJournal()
    {         	
        $dt = Yii::$app->request->post('dt');         
        $dt = str_replace('/', '-', $dt);
        $dt = date('Y-m-d', strtotime($dt));
        
        $empresa = $this->empresa;
	$journal = Journal::findBySql("SELECT * FROM journal WHERE publish_date='$dt' AND deleted_date IS NULL AND id_user IN(SELECT id FROM user WHERE id_company=$empresa)")->all();
        echo (!empty($journal)) ? 'existe' : 'não existe';
    }
    
    /**
     * @inheritdoc
     */
    public function actionWinUploadCaderno()
    {   
	$this->layout = 'main-login';
	    
        $this->enableCsrfValidation = false;
        Yii::$app->session->set('id_journal', null);
        return $this->render('win-upload-caderno');
    }
    
    /**
     * @inheritdoc
     */
    public function actionProcessaCaderno()
    {
        
        $post = Yii::$app->request;
        $this->tp_caderno = $post->post('tp');
        $this->dt_publicacao = $post->post('dt');
        $this->file_name = $file = $post->post('file');
        
        if(!$this->tp_caderno || !$this->dt_publicacao || !$file){
            echo $this->tp_caderno . $this->dt_publicacao . $file;
            return false;
        }
        
        $connecton = \Yii::$app->db;
        $transaction = $connecton->beginTransaction();
        $origem = '../../vendor/FileUpload/server/php/files/'.$file;
        try {
            
            // registra no banco
            $this->salvaRegistro();
            
            // move pdf
            $destino = 'uploads/unprocessed/'.$this->file_name;
            //$this->movePDF($origem,$destino);
            // salva id do jornal na sessao
            Yii::$app->session->set('id_journal', $this->id_journal);
            
            $transaction->commit();
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->excluiPDF($origem);
            return ['message'=>'Journal Session ('.$this->id_journal.') - ' . $e];
        }
        
    }
    
    /**
     * @inheritdoc
     */
    private function movePDF($origem, $destino)
    {   
     
        if(!($this->verificaPath($destino))){
            throw new Exception('Erro ao tentar criar o diretórios "'.$destino.'"');
        }
        
        if(!rename($origem, $destino)){
            throw new Exception('Erro ao tentar mover o PDF (' . $origem . ' para ' . $destino . ').');
        }
        
        return true;
    
    }
    
    /**
     * @inheritdoc
     */
    private function excluiPDF($pdf)
    {   
        return @unlink($pdf);
    }
    
    /**
     * @inheritdoc
     * se não existir cria
     */
    private function verificaPath($path)
    {
        $pastas = explode('/', $path);
        unset($pastas[count($pastas)-1]); // remove o arquivo
        
        $dir = '';
        foreach ($pastas as $pasta) {
            $dir .= $pasta.'/';
            if(!is_dir($dir)){
                if(!mkdir($dir, 0755)){
                    return false;
                } else {
                    chmod($dir, 0755);
                }
            }
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    private function salvaRegistro()
    {
        $session = Yii::$app->session;
        
        if(!$session->has('id_journal')){
		
            // insert journal
            $journal = new Journal();
            $journal->id_user = $this->id_usuario;
            //$journal->journal_number = null;
            $journal->publish_date = $this->dt_publicacao;
            $journal->upload_date = Date('Y-m-d H:i:s');
            $journal->save();
            $this->id_journal = $journal->id_journal;
            
        } else {
            $this->id_journal = $session['id_journal'];
			
        }
        
        // insert session
        $journal_session = new Journal_session();
        $journal_session->id_journal = $this->id_journal;
        $journal_session->path = $this->empresa.'/'.Date('Y/m/');
        $journal_session->id_session = $this->tp_caderno;
        $journal_session->file_name = $this->file_name;
        $journal_session->save();
        
        /*
        $this->file_name = $journal_session->id_journal_session . '-' . $this->id_usuario . '-' . Date('w-d-m-Y') . $this->hash . '.pdf';
        
        $journal_session = Journal_session::findOne($journal_session->id_journal_session);
        $journal_session->file_name = $this->file_name;
        $journal_session->save();
        */
    }
    
    
}
