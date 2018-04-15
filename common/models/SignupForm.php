<?php
namespace common\models;

use yii\base\Model;
use common\models\User;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $User;
    public $profile;
    public $id_company;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'Já existe um usuário com esse e-mail.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'Já existe um usuário com esse e-mail.'],

            ['password', 'required'],
          //  ['password', 'string', 'min' => 6, 'message' => 'A senha deve ter pelo menos 6 caracteres.'],
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
        $this->User->id_company = $this->id_company;
        $this->User->profile = $this->profile;
        $this->User->email = $this->email;
        $this->User->setPassword($this->password);
        $this->User->generateAuthKey();
        
        return $this->User->save() ? $this->User : null;
    }
  
  
}
