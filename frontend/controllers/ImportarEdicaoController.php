<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use frontend\models\Journal;
use frontend\models\Journal_session;
use frontend\models\Journal_pages;
use frontend\models\Log;
use frontend\libs\PDF2Text;

class ImportarEdicaoController extends Controller
{
    private $emailOrigem = "charlan.job@gmail.com";
    private $emailDestino = "charlan.job@gmail.com";
    private $emailDestinatario = "Charlan";
    private $emailCorpo = "";
    private $emailTitulo = "";
    
    private $typeLog = 1; // importacao de edicao
    
    /**
     * @inheritdoc
     */
    public function actionTeste()
    {
         $this->logErro(['message'=>'teste']);
    }
    /**
     * @inheritdoc
     */
    public function actionProcessaPdf()
    {
        error_reporting(~0);
        ini_set('display_errors', 1);
             $totalPages = [];
             
             ob_clean();
            $commandGetTotalPages = "sudo ls -la";
            var_dump('iniciando processo');
            die('aaa');
            exec($commandGetTotalPages, $totalPages);
            var_dump($totalPages.'\n');
//            $path = ' /var/www/html/abio/frontend/web/uploads/processed/1/2016/12/empresarial.pdf';
//            $path2 = ' /var/www/html/abio/frontend/web/uploads/processed/1/2016/12/teste.txt';
//            die;
//           for($i = 1; $i <= $totalPages; $i++) {
//               
//               exec('sudo pdftotext -f '.$i.' -l '.$i.' '.$path.' '.$i.$path2, $totalPages);
//               var_dump('arquivo'.$i.$path2.' criado com sucesso \n');
//           }
           die('asdasd');
        
        
        $pdfPendente = $this->listaPdfPendente();
        // loop nos registro do banco se existir
        if($pdfPendente['pdfDb']){
            foreach ($pdfPendente['pdfDb'] as $journal) {

                // verifica se pdf não existe
                $pathCompleto = 'uploads/unprocessed/' . $journal->file_name;
                if(!is_file($pathCompleto)){
                    $this->logErro(['message'=>'O PDF (' . $pathCompleto . ') não foi encontrado.']);
                    continue;
                }

                try {
                    
                    // le pdf
                    $textPDF = $this->lerPdf($pathCompleto);
                    
                    if(is_array($textPDF)){

                        // salva pdf no banco e move o arquivo
                        $this->salvaMovePdf([
                            'id_journal_session'=>$journal->id_journal_session,
                            'id_journal'=>$journal->id_journal,
                            'content'=>$textPDF,
                            'path'=>$journal->path,
                            'file_name'=>$journal->file_name,
                            ]);
                        
                    }
                    
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
    
    /**
     * @inheritdoc
     */
    private function listaPdfPendente()
    {
        // busca pdf pendente no banco (sem data de processamento)
        $subQuery = Journal::find()
                ->select('id_journal')
                ->where(['deleted_date' => null]);
        
        $pdfDb = Journal_session::find()
                ->where(['IN', 'id_journal', $subQuery])
                ->andWhere(['processing_date' => null])
                ->all();
        
        // busca pdf pendente na pasta
        // $pdfPasta = CFileHelper::findFiles("/uploads/unprocessed/");
        $pdfPasta = [];
        
        return ['pdfDb'=>$pdfDb, 'pdfPasta'=>$pdfPasta];
    }
    
    /**
     * @inheritdoc
     */
    private function lerPdf($path)
    {
        
        try {
            $totalPages = [];
            
            $commandGetTotalPages = "pdftotext file.pdf - | grep -c $'\f'";
            exec($commandGetTotalPages, $totalPages);
            var_dump($totalPages);
            $path2 = ' /var/www/html/abio/frontend/web/uploads/processed/1/2016/12/teste.txt';
            $path = ' /var/www/html/abio/frontend/web/uploads/processed/1/2016/12/empresarial.pdf';
            
           for($i = 1; $i <= $totalPages; $i++) {
               
               exec("pdftotext -f $i -l $i $path $path2", $totalPages);
           }
           die;
            
//            $a = new PDF2Text();
//            $a->setFilename($path);
//            $a->decodePDF();
//            $textFull = $a->output(false);
//            
//            $textFullTratado = $this->trataPdf($textFull);
//
//            $re = '/(?=(Diário Oficial do Município Instituído pela Lei)|(Página\s\d\sDiário Oficial do Município)).*(?<=Página\s\d)/si';
// 
//            preg_match_all($re, $textFullTratado, $matches);
//               
//            if (!empty($matches[0][0])) {
//                $text = preg_split('/(Página)/si', $matches[0][0]);
//            }
            
        } catch (\Exception $e) {
            $this->logErro(['message'=>'Erro ao tentar ler o PDF (' . $path . ')','error'=>$e]);
            throw $e;
            
        }
        
        return $text;
    }
    
    /**
     * @inheritdoc
     */
    private function trataPdf($text)
    {
        // Retira quebra de linhas
        $search = array ("\r\n", "\r", "\n");
        $replace = array(' ', ' ', ' ');
        $text1 = str_replace($search, $replace, $text);

        // Corrige separações de sílabas.
        $text2 = preg_replace('/([a-zA-Z])\- ([a-zA-Z])/', '\1\2', $text1);
        
        return $text2;
    }
    
    /**
     * @inheritdoc
     */
    private function salvaMovePdf($data)
    {
        $connecton = \Yii::$app->db;
        $transaction = $connecton->beginTransaction();
        
        try {

            // atualiza data do processamento do PDF
            $journal = Journal_session::findOne($data['id_journal_session']);
            $journal->processing_date = Date('Y-m-d H:i:s');
            $journal->save();
            
            
            foreach ($data['content'] as $pg => $textPg) {

                // cadastra as paginas do jornal
                $journal_pages = new Journal_pages();
                $journal_pages->id_journal = $data['id_journal'];
                $journal_pages->content = $textPg;
                $journal_pages->page_number = $pg+1;
                $journal_pages->save();

            }
            
            // move pdf
            $this->movePdf('uploads/unprocessed/' . $data['file_name'], 'uploads/processed/' . $data['path'] . $data['file_name']);
            
            $transaction->commit();

        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->logErro(['message'=>'Journal Session ('.$data['id_journal_session'].') - ' . $e]);
            throw $e;
            
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    private function movePdf($origem, $destino)
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
     * se não existir cria
     */
    private function verificaPath($path)
    {
        $pastas = explode('/', $path);
        $arqPdf = $pastas[count($pastas)-1];
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
     * @param $log[message, error]
     */
    private function logErro($log, $enviaEmail = false)
    {
        if(isset($log['message'])){
            
            $Log = new Log();
            $Log->message = $log['message'];
            $Log->error = (isset($log['error'])) ? $log['error'] : "";
            $Log->type = $this->typeLog;
            $Log->save();

            // envia email
            if($enviaEmail){
                $this->emailErro($log);
            }
            
        }
    }
    
    /**
     * @inheritdoc
     */
    private function emailErro($log)
    {
        $this->emailCorpo = "ABIO \n\n" . 
                            $log['message'] . "\n\n" . 
                            (isset($log['error'])) ? "ERRO: " . $log['error']:"";
        
        $this->enviaEmail();
    }
    
    /**
     * @inheritdoc
     */
    private function enviaEmail()
    {        
        Yii::$app->mailer->compose()
        ->setTo($this->emailOrigem)
        ->setFrom([$this->emailDestino => $this->emailDestinatario])
        ->setSubject($this->emailTitulo)
        ->setTextBody($this->emailCorpo)
        ->send();
    }
    
}
