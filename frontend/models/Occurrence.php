<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "occurrence".
 *
 * @property integer $id_occurrence
 * @property integer $id_notification
 * @property integer $id_journal_page
 * @property string $content
 *
 * @property JournalPages $idJournalPage
 * @property Notification $idNotification
 */
class Occurrence extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'occurrence';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_notification', 'id_journal_page', 'content'], 'required'],
            [['id_notification', 'id_journal_page'], 'integer'],
            [['content'], 'string', 'max' => 500],
            [['id_journal_page'], 'exist', 'skipOnError' => true, 'targetClass' => Journal_pages::className(), 'targetAttribute' => ['id_journal_page' => 'id_journal_pages']],
            [['id_notification'], 'exist', 'skipOnError' => true, 'targetClass' => Notification::className(), 'targetAttribute' => ['id_notification' => 'id_notification']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_occurrence' => 'Id Occurrence',
            'id_notification' => 'Id Notification',
            'id_journal_page' => 'Id Journal Page',
            'content' => 'Content',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdJournalPage()
    {
        return $this->hasOne(JournalPages::className(), ['id_journal_pages' => 'id_journal_page']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdNotification()
    {
        return $this->hasOne(Notification::className(), ['id_notification' => 'id_notification']);
    }
}
