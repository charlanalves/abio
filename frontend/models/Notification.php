<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "notification".
 *
 * @property integer $id_notification
 * @property integer $id_user
 * @property string $name
 * @property string $desc
 * @property string $created
 *
 * @property User $idUser
 * @property Occurrence[] $occurrences
 */
class Notification extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_user', 'name', 'desc'], 'required'],
            [['id_user'], 'integer'],
            [['created'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['desc'], 'string', 'max' => 300],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_user' => 'id_user']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_notification' => 'Id Notification',
            'id_user' => 'Id User',
            'name' => 'Name',
            'desc' => 'Desc',
            'created' => 'Created',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser()
    {
        return $this->hasOne(User::className(), ['id_user' => 'id_user']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccurrences()
    {
        return $this->hasMany(Occurrence::className(), ['id_notification' => 'id_notification']);
    }
}
