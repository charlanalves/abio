<?php

namespace backend\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response; 
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Notification;
use frontend\models\Journal_pages as JournalPages;
use frontend\models\Occurrence;

class ProcessController extends ActiveController
{
    
  
 
    public function actionAlerts()
    {	
          $notifications =  Notification::find();
          $pages = $this->getJournalPagesByDate('2018-04-19');
          $this->findAndSaveOccurrenceAndProcess($pages, $notifications);
    }
    
    private function findAndSaveOccurrenceAndProcess($pages, $notifications)
    {	
        foreach ($notifications as $not) {
            foreach ($pages as $page) {
               if ($this->existOccurrence($page, $not)) {
                    $this->saveOccurrence($page, $not);
                    $this->saveProcess($process);                   
               }
            }
        }
    }
    
 
    private function saveProcess($page, $not)
    {
        // implementar
        
    }
 
    private function existOccurrence($page, $not)
    {
        $pos = stripos($page, $not);
        if ($pos === false) {
            return false;
        }
        return true;
    }
 
    private function saveOccurrence($page, $not)
    {
        $oc = new Occurrence;        
        $oc->id_notification = $not['id_notification'];
        $oc->id_journal_page = $page['id_journal_page'];
        $oc->content = $this->getContent($not, $page['content']);
        $oc->save();
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
