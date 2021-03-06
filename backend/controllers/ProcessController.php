<?php

namespace backend\controllers;

use Yii;
use yii\web\Response; 
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Notification;
use common\models\Process;
use frontend\models\Journal_pages as JournalPages;
use frontend\models\Occurrence;

class ProcessController extends \yii\web\Controller
{
    
  public $today;
 
    public function actionAlerts()
    {	
        try{
          $this->today = date('Y-m-d');
          echo "TODAY IS ---.>>>> ". $this->today.'<BR>'; 
          $notifications = Notification::find()->all();         
          echo "NOTIFICATIONS ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> "; 
          print'<pre>';
          print_r($notifications);
          echo "--------------------------------- ---.>>>><br><br><br> "; 
        
          
          $this->findAndSaveOccurrenceAndProcess($notifications);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->saveProcess($not); 
        } 
        $a = 1;
    }
    
    private function findAndSaveOccurrenceAndProcess($notifications)
    {	
        foreach ($notifications as $not) {
          $pages = $this->getJournalPagesByDate($this->today, $not['id_user']);
          echo "getJournalPagesByDate ---.>>>>>>>>>>>>>>>>>>>>>>>>>>><br><br><br> "; 
          print'<pre>';
          print_r($pages);
          echo "--------------------------------- ---.>>>><br><br><br> "; 
            foreach ($pages as $page) {
               if ($this->existOccurrence($page, $not)) {
                    $this->saveOccurrence($page, $not);
               }else{
                   echo "nao existe ocorencia para a page na not $not->name ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> "; 
                    print'<pre>';
                    print_r($page);
                    echo "--------------------------------- ---.>>>><br><br><br> "; 
               }
            }
        }
                                      
    }
    
 
    private function saveProcess($process)
    {
        $p = new \common\models\Process;
        $p->id_notification = $process['id_notification'];
        $p->id_journal_page = $process['id_journal_page'];
        $p->last_error = $process['last_error'];
        $p->status = 0;       
        
        if (!$p->save()){
            $errors = json_encode($p->getErrors());
            throw new Exception($errors);
        }
        
    }
 
    private function existOccurrence($page, $not)
    {
        $pos = stripos($this->tirarAcentos($page->content), $this->tirarAcentos($not->name));
        if ($pos === false) {
            return false;
        }
        return true;
    }
    public function tirarAcentos($string){
       return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
   }

    private function saveOccurrence($page, $not)
    {
        $oc = new Occurrence;        
        $oc->id_notification = $not->id_notification;
        $oc->id_journal_page = $page->id_journal_pages;
        $oc->content = $this->getContent($not->name, $page->content);
       $ocorrenciaDuplicada =  Occurrence::find()->where(['id_notification'=>$not->id_notification,'id_journal_page'=>$page->id_journal_pages])->exists();
        if(!$ocorrenciaDuplicada){
            if (!$oc->save()){
                $errors = json_encode($oc->getErrors());
                throw new Exception($errors);
            }else{
                 echo "OCORRENCIA SALVA ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> $not->name"; 
                        print'<pre>';
                        print_r($oc);
                echo "--------------------------------- ---.>>>><br><br><br> "; 
            }
        }else{
            echo 'OCORENCIA DUPLICADA ABORTANDO SALVAMENTO.......';
        }
    }
    
   private function getContent($findMe, $content) {        
        $posicao = strpos($this->tirarAcentos(strtolower($content)), $this->tirarAcentos(strtolower($findMe)));
        $aux = substr($content, $posicao);
//        if($findMe == 'diario' || $findMe == 'diário'){;;
//            echo 'POSICAO LALALALLALAL';
//            var_dump($this->tirarAcentos($content));
//            var_dump($this->tirarAcentos($findMe));
//            var_dump($this->tirarAcentos($aux));
//            var_dump($posicao);
//            var_dump(str_ireplace($this->tirarAcentos($findMe),"<b>$findMe</b>", substr($this->tirarAcentos($aux), 0,50)));
//            die;
//       }
        return str_ireplace($this->tirarAcentos($findMe),"<b>$findMe</b>", substr($this->tirarAcentos($aux), 0,50));
   }
   
   private function strafter($string, $substring) {
        $pos = stripos($string, $substring);
        if ($pos === false)
         return $string;
        else  
         return(substr($string, $pos+strlen($substring)));
  }
    //Obtêm todos as paginas de todos os jornais não deletados da imprensa que usuário esta cadastrado
    private function getJournalPagesByDate($date, $userId)
    {	
        if (empty($date)){
            return false;
        }
        
        return JournalPages::findBySql('          
              SELECT journal_pages.* , company_sessions.id_company
              FROM journal 
              JOIN journal_pages ON journal_pages.id_journal = journal.id_journal
              JOIN journal_session ON journal_session.id_journal = journal_pages.id_journal              
              JOIN company_sessions ON company_sessions.id_session = journal_session.id_session              
              WHERE journal.publish_date = :curDate
              AND company_sessions.id_company = (select user.id_company from user where user.id = :userId)
              AND journal.deleted_date is null
        ', [':curDate' => $date, ':userId' => $userId])->all();
    }
}
