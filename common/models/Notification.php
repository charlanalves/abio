<?php
namespace common\models;

use yii\base\Model;
use \yii\db\ActiveRecord;


/**
 * Signup form
 */
class Notification extends ActiveRecord
{
  
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
             [['name','id_user'], 'unique','targetAttribute' => ['name','id_user'], 'message' => 'Já existe um alerta cadastrado com esse mesmo nome.', ],
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }
        
        $this->User = new User();
        $this->User->username = $this->username;
        $this->User->email = $this->email;
        $this->User->setPassword($this->password);
        $this->User->generateAuthKey();
        
        return $this->User->save() ? $this->User : null;
    }
    
 public static function tableName()
{
    return 'notification';
}

 public function getOccurencesNot()
{
     if (empty($this->id_notification)){
        throw new \Exception('Erro interno 001xhs. Entre em contato com o suporte técnico');
    }
    $command = \Yii::$app->db->createCommand('
        SELECT * 
        FROM notification
        JOIN occurrence on occurrence.id_notification = notification.id_notification
        WHERE notification.id_notification = :idNotification
    ')->bindValue(':idNotification', $this->id_notification);
    $reader = $command->query();
    return $reader->readAll();
}
  
  
}
