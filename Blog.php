<?php

/**
 * This is the model class for table "blog".
 *
 * The followings are the available columns in table 'blog':
 * @property string $idss
 * @property string $title
 * @property string $grp_id
 * @property string $img_url
 * @property string $description
 * @property string $exp_id
 * @property string $modified_flag
 * @property string $status_id
 * @property string $create_time
 * @property string $create_usr_id
 * @property string $update_time
 * @property string $update_usr_id
 * @property string $start_time
 * @property string $end_time
 * @property string $cat_id
 *
 * The followings are the available model relations:
 * @property Usr $createUsr
 * @property Grp $grp
 * @property RefStatus $status
 * @property Oembed $oembed_id
 */
class Blog extends LeActiveRecord
{
	
	private $_newsBeforeSave;
	public $videoList;
	public $oembed_id;
    // available categories 
	public $cat_id = array();
    //selected categories 
	private $_categories = array();

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'blog';
    }
	
	public function behaviors()
	{
		return array(
			'leMessenger'=>array(
				'class'=>'ext.behaviours.Messenger',
			),
			'getOptions'=>array(
				'class'=>'ext.behaviours.ModelGetOptions'
			),	
			
		);
	}

	/* ******************************************************** */
	public function beforeSave()
	{
		$this->_newsBeforeSave = self::findByPk($this->id);
		return parent::beforeSave();			 	 				
	}
	
	/* *************************************************************** */
	public function afterSave()
	{
        BlogHelper::updateBlogCount($this->grp_id);
		if ($this->isNewRecord) 
			AuditHelper::eventNewContent(Types::$modelType['blog']['id'],
			 	 				$this->grp_id,
			 	 				$this->id,
			 	 				$this->status_id,
			 	 				$this->title,
			 	 				$this->getAttributes()); 
		elseif ($this->_newsBeforeSave->status_id == Types::$status['draft']['id'] && $this->status_id == Types::$status['active']['id'])
			AuditHelper::eventPublishContent(Types::$modelType['blog']['id'],
			 	 				$this->grp_id,
			 	 				$this->id,
			 	 				$this->status_id,
			 	 				$this->title,
			 	 				$this->getAttributes()); 
		else 
			AuditHelper::eventUpdateContent(Types::$modelType['blog']['id'],
			 	 				$this->grp_id,
			 	 				$this->id,
			 	 				$this->status_id,
			 	 				$this->title,
			 	 				$this->getAttributes()); 
		return parent::afterSave(); 
	} 
	
	/* *************************************************************** */
	public function init()
	{
		if ($this->isNewRecord)
			$this->videoList = array();
		return parent::init(); 
	} 
	/* *************************************************************** */
	
	public function afterFind()
	{
		$this->cat_id = array_keys($this->getCategories());  
		$this->helperInfo = BlogHelper::blogFromId($this->id); 
		return parent::afterFind(); 
	
	} 
	/* *************************************************************** */	
	
    /**
     * validate image url
     */
	public function validImage($attribute,$params)
	{
		$val = $this->$attribute;
		if (!empty($val) && !@getimagesize($val))
			$this->img_url = 'http://vocaleyes.org/img/ve_logo_faded.png';
	}
/* *************************************************************** */
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('create_time, create_usr_id, title, description, cat_id', 'required'),
            array('title, img_url, start_time', 'length', 'max'=>255),
            array('cat_id' ,  'safe'), 
			array('img_url', 'validImage' ),
            array('grp_id, modified_flag, status_id, exp_id, create_time, create_usr_id, update_time, update_usr_id', 'length', 'max'=>10),
            array('description', 'length', 'max'=>16384),
			array('start_time', 'default', 'value' => null),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('imageList, videoList', 'safe'),
            array('id, title, grp_id, description, status_id, exp_id, statusName', 'safe', 'on'=>'search'),
        );
    }
/* *************************************************************** */
    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'createUsr' => array(self::BELONGS_TO, 'Usr', 'create_usr_id'),
            'grp' => array(self::BELONGS_TO, 'Grp', 'grp_id'),
            'status' => array(self::BELONGS_TO, 'RefStatus', 'status_id'),
            'exp' => array(self::BELONGS_TO, 'RefExp', 'exp_id'),
            'oembed_id' => array(self::BELONGS_TO, 'Oembed', 'id'),
        );
    }
/* *************************************************************** */
    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
                        'id' => Yii::t('Blog','ID'),
                        'cat_id' => Yii::t('Blog','Category'),
			'title' => Yii::t('Blog','Title'),
			'description' => Yii::t('Blog','Description'),
			'exp_id' => Yii::t('Blog','Keep this post public?'),
			'grp_id' => Yii::t('Blog','Grp'),
			'img_url' => Yii::t('Blog','Current News Image'),
			'modified_flag' => Yii::t('Blog','Modified Flag'),
			'status_id' => Yii::t('Blog','Status'),
			'create_time' => Yii::t('Blog','Create Time'),
			'create_usr_id' => Yii::t('Blog','Create Usr'),
			'update_time' => Yii::t('Blog','Update Time'),
			'update_usr_id' => Yii::t('Blog','Update Usr'),
			'start_time' => Yii::t('Blog','Posting time'),
        );
    }
/* *************************************************************** */
    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;
        $criteria->with = array('status');
        $criteria->compare('status.name', $this->statusName, true);
        $criteria->compare('id',$this->id,true);
        $criteria->compare('t.title',$this->title,true);
        $criteria->compare('grp_id',$this->grp_id,true);
        $criteria->compare('description',$this->description,true);
        $criteria->compare('modified_flag',$this->modified_flag,true);
        $criteria->compare('t.status_id',$this->status_id,true);
        $criteria->compare('create_time',$this->create_time,true);
        $criteria->compare('t.create_usr_id',$this->create_usr_id,true);
        $criteria->compare('update_time',$this->update_time,true);
        $criteria->compare('update_usr_id',$this->update_usr_id,true);
        $criteria->compare('start_time',$this->start_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
				'defaultOrder'=>'t.create_time DESC',
			),
		));
    }
/* *************************************************************** */
    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Blog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
/* *************************************************************** */
	protected function beforeValidate()
	{		
		$this->create_time = time();
		
		if (isset(Yii::app()->user->id))
			$this->create_usr_id = Yii::app()->user->id;
		else 
			$this->create_usr_id = Utility::systemUser();
                
		$this->start_time = time();
                
		return true;
	}
/* *************************************************************** */	
    public function withGroup()
    {
        $this->getDbCriteria()->mergeWith(array(
            'with'=>array('grp'), 
        ));  
        return $this;
    }
    /* ******************************************************** */
    public function getCategories()
    { 
        if (count($this->_categories) === 0)
        {
            $rec = BlogHelper::categoryList($this->id);
            foreach($rec as $r){
                $this->_categories[$r['id']] = $r['name'];	
            }
        }
    	return $this->_categories; 
    }
    /* ******************************************************** */
    
}

