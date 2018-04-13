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
    
  
 
    public function actionAlerts()
    {	
        try{
          $today = date('Y-m-d');
          echo "TODAY IS ---.>>>> ". $today.'<BR>'; 
          $notifications = Notification::find()->all();
          echo "NOTIFICATIONS ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> "; 
          print'<pre>';
          print_r($notifications);
          echo "--------------------------------- ---.>>>><br><br><br> "; 
          $pages = $this->getJournalPagesByDate($today);
          
          echo "getJournalPagesByDate ---.>>>>>>>>>>>>>>>>>>>>>>>>>>><br><br><br> "; 
          print'<pre>';
          print_r($pages);
          echo "--------------------------------- ---.>>>><br><br><br> "; 
          
          $this->findAndSaveOccurrenceAndProcess($pages, $notifications);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->saveProcess($not); 
        } 
        $a = 1;
    }
    
    private function findAndSaveOccurrenceAndProcess($pages, $notifications)
    {	
        foreach ($notifications as $not) {
            foreach ($pages as $page) {
               if ($this->existOccurrence($page, $not)) {
                    $this->saveOccurrence($page, $not);
               }else{
                   echo "nao existe ocorencia para a page ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> "; 
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
        $pos = stripos($page->content, $not->name);
        if ($pos === false) {
            return false;
        }
        return true;
    }
 
    private function saveOccurrence($page, $not)
    {
        $oc = new Occurrence;        
        $oc->id_notification = $not->id_notification;
        $oc->id_journal_page = $page->id_journal_pages;
        $oc->content = $this->getContent($not->name, $page->content);
        if (!$oc->save()){
            $errors = json_encode($oc->getErrors());
            throw new Exception($errors);
        }else{
             echo "OCORRENCIA SALVA ---.>>>>>>>>>>>>>>>>>>>>>>>>>>> "; 
                    print'<pre>';
                    print_r($oc);
            echo "--------------------------------- ---.>>>><br><br><br> "; 
        }
    }
    
   private function getContent($findMe, $content) {       
        $posicao = strpos($content, $findMe);
        $aux = substr($content, $posicao);
        return str_replace($findMe,"<b>$findMe</b>", substr($aux, 0,50));
   }
   
   private function strafter($string, $substring) {
        $pos = strpos($string, $substring);
        if ($pos === false)
         return $string;
        else  
         return(substr($string, $pos+strlen($substring)));
  }
    
    private function getJournalPagesByDate($date)
    {	
        if (empty($date)){
            return false;
        }
        return JournalPages::findBySql('          
              SELECT journal_pages.* 
              FROM journal 
              JOIN journal_pages ON journal_pages.id_journal = journal.id_journal
              WHERE journal.publish_date = :curDate
        ', [':curDate' => $date])->all();
    }
}
